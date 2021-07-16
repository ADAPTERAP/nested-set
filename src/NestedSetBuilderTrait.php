<?php

namespace Adapterap\NestedSet;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait NestedSetBuilder
 *
 * @package Adapterap\NestedSet
 * @method NestedSetModelTrait|Model getModel()
 */
trait NestedSetBuilderTrait
{
    /**
     * Фильтр по корневым элементам.
     *
     * @return Builder|$this
     */
    public function whereDoesNotHaveParent(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereDoesNotHaveParent($this);
    }

    /**
     * Фильтр по корневым элементам.
     *
     * @return Builder|$this
     */
    public function whereIsRoot(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereIsRoot($this);
    }

    /**
     * Сортировка дерева по индексу вложенности.
     *
     * @param string $direction
     *
     * @return Builder|$this
     */
    public function orderByLft(string $direction = 'asc'): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeOrderByLft($this, $direction);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param Model $model
     *
     * @return Builder|$this
     */
    public function whereParent(Model $model): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereParent($this, $model);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param mixed $primary
     *
     * @return Builder|$this
     */
    public function whereParentId($primary): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereParentId($this, $primary);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param Model $model
     *
     * @return Builder|$this
     */
    public function whereAncestor(Model $model): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereAncestor($this, $model);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param mixed $primary
     *
     * @return Builder|$this
     */
    public function whereAncestorId($primary): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereAncestorId($this, $primary);
    }

    /**
     * Фильтр по конечным узлам
     *
     * @return Builder
     */
    public function whereIsLeafNodes(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereIsLeafNodes($this);
    }
}
