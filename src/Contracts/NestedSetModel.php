<?php

namespace Adapterap\NestedSet\Contracts;

use Adapterap\NestedSet\Builders\NestedSetBuilder;
use Adapterap\NestedSet\Collections\NestedSetCollection;
use Adapterap\NestedSet\Relations\AncestorsRelation;
use Adapterap\NestedSet\Relations\DescendantsRelation;
use Adapterap\NestedSet\Relations\SiblingsRelation;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property-read Collection $parents
 * @property-read Collection $children
 * @property-read Collection $descendants
 * @property-read Collection $ancestors
 * @property-read Collection $siblings
 * @property-read bool       $is_root
 * @property-read bool       $is_child
 * @property-read bool       $has_children
 */
interface NestedSetModel
{
    /**
     * Возвращает название колонки с индексом вложенности слева.
     *
     * @return string
     */
    public function getLftName(): string;

    /**
     * Возвращает название колонки с индексом вложенности справа.
     *
     * @return string
     */
    public function getRgtName(): string;

    /**
     * Возвращает название колонки с глубиной вложенности.
     *
     * @return string
     */
    public function getDepthName(): string;

    /**
     * Возвращает название колонки с идентификатором родителя.
     *
     * @return string
     */
    public function getParentIdName(): string;

    /**
     * Возвращает массив полей объединяющих узлы.
     *
     * @return array
     */
    public function getScopeAttributes(): array;

    /**
     * Возвращает индекс вложенности слева.
     *
     * @return mixed
     */
    public function getLft();

    /**
     * Возвращает индекс вложенности справа.
     *
     * @return mixed
     */
    public function getRgt();

    /**
     * Возвращает глубину вложенности.
     *
     * @return mixed
     */
    public function getDepth();

    /**
     * Возвращает идентификатор родителя.
     *
     * @return mixed
     */
    public function getParentId();

    /**
     * Связь с родительской категорией.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo;

    /**
     * Связь с дочерними категориями.
     *
     * @return HasMany
     */
    public function children(): HasMany;

    /**
     * Потомки.
     *
     * @return DescendantsRelation
     */
    public function descendants(): DescendantsRelation;

    /**
     * Предки.
     *
     * @return AncestorsRelation
     */
    public function ancestors(): AncestorsRelation;

    /**
     * Элементы, находящиеся на одном уровне с текущим элементом.
     *
     * @return SiblingsRelation
     */
    public function siblings(): SiblingsRelation;

    /**
     * Виртуальное свойство. Определяет, является ли модель корневым элементом.
     *
     * @return bool
     */
    public function getIsRootAttribute(): bool;

    /**
     * Виртуальное свойство. Определяет, является ли модель корневым элементом.
     *
     * @return bool
     */
    public function getIsChildAttribute(): bool;

    /**
     * Виртуальное свойство. Определяет, есть ли у элемента дочерние элементы.
     *
     * @return bool
     */
    public function getHasChildrenAttribute(): bool;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param QueryBuilder $query
     *
     * @return NestedSetBuilder()
     */
    public function newEloquentBuilder($query): EloquentBuilder;

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param array $models
     *
     * @return NestedSetCollection
     */
    public function newCollection(array $models = []): EloquentCollection;

    /**
     * Set the given relationship on the model.
     *
     * @param string $relation
     * @param mixed  $value
     *
     * @return $this
     */
    public function setRelation($relation, $value);

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName();

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey();

    /**
     * Синхронизирует дерево.
     *
     * @param              $values
     * @param array        $uniqueBy
     * @param null|array   $update
     * @param null|Closure $map
     *
     * @return Collection
     */
    public static function syncTree($values, array $uniqueBy = [], ?array $update = null, ?Closure $map = null): Collection;

    /**
     * Сеттер для глобальных названий колонок.
     *
     * @param array $attributes
     */
    public static function setNestedSetGlobalAttributes(array $attributes): void;
}
