<?php

namespace Adapterap\NestedSet\Traits;

use Adapterap\NestedSet\NestedSetModel;
use Adapterap\NestedSet\Support\NestedSetQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Scopes
 *
 * @package Adapterap\NestedSet\Traits
 * @mixin NestedSetModel
 * @method self whereDoesNotHaveParent()
 * @method self whereIsRoot()
 * @method self orderByLft(string $direction = 'asc')
 * @method self whereParent(Model $model)
 * @method self whereParentId(mixed $primary)
 * @method self whereAncestor(Model $model)
 * @method self whereAncestorId(mixed $primary)
 */
trait Scopes
{
    /**
     * Фильтр по корневым элементам.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeWhereDoesNotHaveParent(Builder $builder): Builder
    {
        return $builder->whereNull($this->getParentIdName());
    }

    /**
     * Фильтр по корневым элементам.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeWhereIsRoot(Builder $builder, ): Builder
    {
        return $builder->whereDoesNotHaveParent();
    }

    /**
     * Сортировка дерева по индексу вложенности.
     *
     * @param Builder $builder
     * @param string $direction
     *
     * @return Builder
     */
    public function scopeOrderByLft(Builder $builder, string $direction = 'asc'): Builder
    {
        return $builder->orderBy($this->getModel()->getLftName(), $direction);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param Builder $builder
     * @param Model $model
     *
     * @return Builder
     */
    public function scopeWhereParent(Builder $builder, Model $model): Builder
    {
        return $builder->whereParentId($model->getKeyName());
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeWhereParentId(Builder $builder, $id): Builder
    {
        return $builder->where($this->getModel()->getParentIdName(), $id);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param Builder $builder
     * @param Model $model
     *
     * @return Builder
     */
    public function scopeWhereAncestor(Builder $builder, Model $model): Builder
    {
        return $builder->whereAncestorId($model->getKey());
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param Builder $builder
     * @param mixed $primary
     *
     * @return Builder
     */
    public function scopeWhereAncestorId(Builder $builder, $primary): Builder
    {
        /** @var Model $this */
        return $builder
            ->whereRaw(
                NestedSetQuery::prepare('`lft` > ((SELECT `lft` FROM `table` WHERE `id` = ?))', $this),
                [$primary]
            )
            ->whereRaw(
                NestedSetQuery::prepare('`rgt` > ((SELECT `rgt` FROM `table` WHERE `id` = ?))', $this),
                [$primary]
            );
    }
}
