<?php

namespace Adapterap\NestedSet\Drivers;

use Adapterap\NestedSet\NestedSet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;

abstract class NestedSetDriver
{
    /**
     * Экземпляр модели, которая содержит имена полей.
     *
     * @var Model|NestedSet|SoftDeletes
     */
    protected Model $model;

    /**
     * MySqlDriver constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Определяет значения для заполнения колонок lft/rgt/depth перед записью в БД.
     *
     * @param array $attributes
     *
     * @return array
     */
    abstract public function getAttributesForInsert(array $attributes): array;

    /**
     * Пересчитывает индексы вложенности для:
     * - всех предков $lft
     * - всех элементов ниже $lft
     *
     * @param mixed $primary Идентификатор созданного элемента
     * @param int $lft Индекс вложенности слева созданного элемента
     *
     * @return void
     */
    abstract public function freshIndexesAfterInsert($primary, int $lft): void;

    /**
     * Перемещение поддерева.
     *
     * @param int $id
     * @param int $parentId
     * @param array $values
     *
     * @return int
     */
    abstract public function rebaseSubTree(int $id, int $parentId, array $values): int;

    /**
     * Удаляет элемент с указанным идентификатором.
     *
     * @param int|string $primary
     *
     * @return bool
     */
    abstract public function delete($primary): bool;

    /**
     * Обновляет индексы после удаления поддерева.
     *
     * @param int $lft
     * @param int $rgt
     */
    abstract public function freshIndexesAfterDelete(int $lft, int $rgt): void;
}
