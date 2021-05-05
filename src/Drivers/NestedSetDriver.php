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
     * Мягко удаляет элемент с указанным идентификатором.
     *
     * @param int|string $primary
     *
     * @return bool
     */
    abstract public function softDelete($primary): bool;

    /**
     * Жестко удаляет элемент с указанным идентификатором.
     *
     * @param int|string $primary
     *
     * @return bool
     */
    abstract public function forceDelete($primary): bool;

    /**
     * Обновляет индексы после удаления поддерева.
     *
     * @param int $lft
     * @param int $rgt
     *
     * @return void
     */
    abstract public function freshIndexesAfterForceDelete(int $lft, int $rgt): void;

    /**
     * Определяет, используется ли модель мягкое удаление.
     *
     * @return bool
     */
    protected function hasSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses($this->model), true);
    }
}
