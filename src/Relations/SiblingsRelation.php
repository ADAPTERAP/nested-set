<?php

namespace Adapterap\NestedSet\Relations;

use Adapterap\NestedSet\NestedSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SiblingsRelation
 *
 * @package Adapterap\NestedSet\Relations
 * @property-read Model|NestedSet $parent
 */
class SiblingsRelation extends BaseRelation
{
    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param Model[]|NestedSet[] $models
     * @param Collection $results
     * @param string $relation
     *
     * @return array
     */
    public function match(array $models, Collection $results, $relation): array
    {
        // Соотносим найденные модели
        foreach ($models as $index => $model) {
            $modelParentId = $model->getParentId();

            $models[$index] = $model->setRelation(
                $relation,
                $results
                    ->filter(function ($potentialSibling) use ($modelParentId) {
                        /** @var NestedSet $potentialSibling */
                        $potentialSiblingParentId = $potentialSibling->getParentId();

                        return $potentialSiblingParentId === $modelParentId;
                    })
                    ->values()
            );
        }

        return $models;
    }

    /**
     * Добавляет фильтры к указанному билдеру для указанной модели.
     *
     * @param Builder $builder
     * @param Model|NestedSet $model
     *
     * @return Builder
     */
    protected static function addFiltersForModel(Builder $builder, Model $model): Builder
    {
        return $builder
            ->where($model->getParentIdName(), $model->getParentId())
            ->where($model->getKeyName(), '!=', $model->getKey());
    }
}
