<?php

namespace Adapterap\NestedSet;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait NestedSetBuilder
 *
 * @package Adapterap\NestedSet
 * @method NestedSetModel|Model getModel()
 */
trait NestedSetBuilder
{
    /**
     * Фильтр по корневым элементам.
     *
     * @return Builder
     */
    public function whereDoesNotHaveParent(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModel $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereDoesNotHaveParent($this);
    }

    /**
     * Фильтр по корневым элементам.
     *
     * @return Builder
     */
    public function whereIsRoot(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModel $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereIsRoot($this);
    }

    /**
     * Сортировка дерева по индексу вложенности.
     *
     * @param string $direction
     *
     * @return Builder
     */
    public function orderByLft(string $direction = 'asc'): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModel $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeOrderByLft($this, $direction);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param Model $model
     *
     * @return Builder
     */
    public function whereParent(Model $model): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModel $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereParent($this, $model);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param mixed $primary
     *
     * @return Builder
     */
    public function whereParentId($primary): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModel $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereParentId($this, $primary);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param Model $model
     *
     * @return Builder
     */
    public function whereAncestor(Model $model): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModel $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereAncestor($this, $model);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param mixed $primary
     *
     * @return Builder
     */
    public function whereAncestorId($primary): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModel $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereAncestorId($this, $primary);
    }
}
