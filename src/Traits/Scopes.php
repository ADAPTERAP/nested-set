<?php

namespace Adapterap\NestedSet\Traits;

use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Support\NestedSetQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Scopes.
 *
 * @mixin NestedSetModelTrait
 *
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
    public function scopeWhereIsRoot(Builder $builder): Builder
    {
        return $this->scopeWhereDoesNotHaveParent($builder);
    }

    /**
     * Сортировка дерева по индексу вложенности.
     *
     * @param Builder $builder
     * @param string  $direction
     *
     * @return Builder
     */
    public function scopeOrderByLft(Builder $builder, string $direction = 'asc'): Builder
    {
        return $builder->orderBy($this->getLftName(), $direction);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param Builder $builder
     * @param Model   $model
     *
     * @return Builder
     */
    public function scopeWhereParent(Builder $builder, Model $model): Builder
    {
        return $this->scopeWhereParentId($builder, $model->getKey());
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param Builder $builder
     * @param mixed   $id
     *
     * @return Builder
     */
    public function scopeWhereParentId(Builder $builder, $id): Builder
    {
        return $builder->where($this->getParentIdName(), $id);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param Builder $builder
     * @param Model   $model
     *
     * @return Builder
     */
    public function scopeWhereAncestor(Builder $builder, Model $model): Builder
    {
        return $this->scopeWhereAncestorId($builder, $model->getKey());
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param Builder $builder
     * @param mixed   $primary
     *
     * @return Builder
     */
    public function scopeWhereAncestorId(Builder $builder, $primary): Builder
    {
        /** @var Model $this */
        return $builder
            ->whereRaw(
                NestedSetQuery::prepare('$lftName > (SELECT $lftName FROM $tableName WHERE $idName = ?)', $this),
                [$primary]
            )
            ->whereRaw(
                NestedSetQuery::prepare('$rgtName < (SELECT $rgtName FROM $tableName WHERE $idName = ?)', $this),
                [$primary]
            );
    }

    /**
     * Фильтр по конечным узлам
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeWhereIsLeafNodes(Builder $builder): Builder
    {
        return $builder->whereRaw("{$this->getRgtName()} = {$this->getLftName()} + 1");
    }
}
