<?php

namespace Adapterap\NestedSet\Handlers;

use Adapterap\NestedSet\NestedSetModel;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use stdClass;

class NestedSetSyncTree
{
    /**
     * Аналог кеша. Запоминает количество потомков для каждого элемента
     *
     * @var array<int, int>
     */
    protected array $countDescendants = [];

    /**
     * Пустая модель. Необходима для получения названий колонок.
     *
     * @var Model|NestedSetModel|SoftDeletes
     */
    protected Model $stub;

    /**
     * Callback для преобразования элементов к нужному виду.
     *
     * @var Closure
     */
    protected Closure $map;

    /**
     * Идентификаторы синхронизированных элементов.
     *
     * @var array
     */
    protected array $usedPrimaries = [];

    /**
     * Список полей по которым определяется уникальность записи в БД.
     *
     * @var array
     */
    protected array $uniqueBy;

    /**
     * Список полей которое необходимо обновить в случае, если элемент уже существует в БД.
     *
     * @var array
     */
    protected array $update;

    /**
     * Сгруппированные дочерние элементы, где:
     * - ключ - идентификатор родителя
     * - значение - массив из дочерних элементов.
     *
     * @var array<int, array>
     */
    protected array $groupedChildren = [];

    /**
     * NestedSetSyncTree constructor.
     *
     * @param Model|NestedSetModel $model
     * @param Closure|null $map
     * @param array $uniqueBy
     * @param array|null $update
     */
    public function __construct(Model $model, ?Closure $map, array $uniqueBy, ?array $update = null)
    {
        $this->stub = $model;
        $this->map = $map ?? static fn($item) => (array)$item;
        $this->uniqueBy = $uniqueBy;
        $this->update = array_unique(
            array_merge($update ?? [], [
                $model->getLftName(),
                $model->getRgtName(),
                $model->getParentIdName(),
                $model->getDepthName(),
            ])
        );
    }

    /**
     * Синхронизирует дерево.
     *
     * @param $values
     */
    public function sync($values): void
    {
        $values = $this->mappingValues($values);

        // Создаем/обновляем дерево
        $rawParents = $this->getValuesForUpsert($values);
        $this->syncChildren($this->upsert($rawParents));

        // Удаляем элементы, которых не было в дереве
        $this->deleteUnusedItems();

        // Удаляем информацию из памяти
        $this->countDescendants = [];
        $this->groupedChildren = [];
    }

    /**
     * Преобразует каждый элемент к нужному виду и сохраняет сгруппированные дочерние элементы для каждого родителя
     * для дальнейшего использования.
     *
     * @param $values
     *
     * @return array
     */
    protected function mappingValues($values): array
    {
        $map = $this->map;
        $result = [];

        foreach ($values as $item) {
            $item = $map($item);
            $item['children'] = $this->mappingValues($item['children'] ?? []);

            $uniqueKey = $this->getUniqueKeyForItem($item);
            $this->groupedChildren[$uniqueKey] ??= [];

            foreach ($item['children'] as $child) {
                $this->groupedChildren[$uniqueKey][] = Arr::except($child, 'children');
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Синхронизирует дочерние элементы для указанных родителей.
     *
     * @param $parents
     */
    protected function syncChildren($parents): void
    {
        $valuesForUpsert = [];

        foreach ($parents as $parent) {
            $primary = $parent->{$this->stub->getKeyName()};
            $this->usedPrimaries[$primary] = $primary;

            $children = $this->getValuesForUpsert(
                $this->groupedChildren[$this->getUniqueKeyForItem($parent)] ?? [],
                $parent->{$this->stub->getLftName()} + 1,
                $parent->{$this->stub->getDepthName()} + 1,
                $primary
            );

            foreach ($children as $key => $child) {
                $valuesForUpsert[$key] = $child;
            }
        }

        if (empty($valuesForUpsert)) {
            return;
        }

        $this->syncChildren($this->upsert($valuesForUpsert));
    }

    /**
     * Возвращает массив элементов для upsert.
     *
     * @param array $values
     * @param int $lft
     * @param int $depth
     * @param int|null $parentId
     *
     * @return array
     */
    protected function getValuesForUpsert(array $values, int $lft = 0, int $depth = 0, ?int $parentId = null): array
    {
        $upsert = [];

        foreach ($values as $item) {
            $item[$this->stub->getLftName()] = $lft;
            $item[$this->stub->getRgtName()] = $lft + $this->getCountDescendants($item) * 2 + 1;
            $item[$this->stub->getDepthName()] = $depth;
            $item[$this->stub->getParentIdName()] = $parentId;

            // Восстанавливаем раннее удаленные модели.
            if ($this->stub->nestedSetHasSoftDeletes()) {
                $item[$this->stub->getDeletedAtColumn()] = null;
            }

            $uniqueKey = $this->getUniqueKeyForItem($item);

            if (array_key_exists('children', $item)) {
                unset($item['children']);
            }

            $lft = (int)$item[$this->stub->getRgtName()] + 1;
            $upsert[$uniqueKey] = $item;
        }

        return $upsert;
    }

    /**
     * Возвращает количество потомков указанного элемента.
     *
     * @param array $item
     *
     * @return int
     */
    protected function getCountDescendants(array $item): int
    {
        $uniqueKey = $this->getUniqueKeyForItem($item);

        if (!array_key_exists($uniqueKey, $this->countDescendants)) {
            $this->countDescendants[$uniqueKey] = 0;
            $children = $item['children'] ?? [];

            foreach ($children as $child) {
                $this->countDescendants[$uniqueKey] += $this->getCountDescendants($child) + 1;
            }
        }

        return $this->countDescendants[$uniqueKey];
    }

    /**
     * Определяет и возвращает уникальный идентификатор элемента
     * для дальнейшей связки
     *
     * @param array|stdClass|Model $item
     *
     * @return string
     */
    protected function getUniqueKeyForItem($item): string
    {
        $parts = [];
        $attributes = $item instanceof Model ? $item->getAttributes() : (array)$item;

        if (!empty($this->uniqueBy)) {
            foreach ($this->uniqueBy as $fieldName) {
                $parts[] = $attributes[$fieldName] ?? null;
            }
        } else {
            $parts[] = $attributes[$this->stub->getLftName()];
            $parts[] = $attributes[$this->stub->getRgtName()];
            $parts[] = $attributes[$this->stub->getDepthName()];
            $parts[] = $attributes[$this->stub->getParentIdName()];
        }

        return implode('_', $parts);
    }

    /**
     * Создает или обновляет записи в БД.
     *
     * @param array $valuesForUpsert
     *
     * @return Collection
     */
    protected function upsert(array $valuesForUpsert): Collection
    {
        return $this->stub->nestedSetDriver->upsert($valuesForUpsert, $this->uniqueBy, $this->update);
    }

    /**
     * Удаляет элементы, которые не были найдены.
     */
    protected function deleteUnusedItems(): void
    {
        $this->stub->nestedSetDriver->deleteUnusedItems($this->usedPrimaries);
    }
}
