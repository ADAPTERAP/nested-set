<?php

namespace Adapterap\NestedSet\Relations;

use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Support\NestedSetQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AncestorsRelation.
 *
 * @property-read Model|NestedSetModelTrait $parent
 */
class AncestorsRelation extends BaseRelation
{
    /**
     * Направление сортировки по умолчанию.
     *
     * @var string
     */
    protected string $defaultOrderDirection = 'desc';

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param Model[]|NestedSetModelTrait[] $models
     * @param Collection                    $results
     * @param string                        $relation
     *
     * @return array
     */
    public function match(array $models, Collection $results, $relation): array
    {
        // Соотносим найденные модели с их детьми
        foreach ($models as $index => $model) {
            $ancestorIds = static::getAncestorIds($model, $results);

            $models[$index] = $model->setRelation(
                $relation,
                $results
                    ->filter(function ($item) use ($ancestorIds) {
                        /** @var NestedSetModelTrait $item */
                        $primary = $item->getAttribute($item->getKeyName());

                        return in_array($primary, $ancestorIds, true);
                    })
                    ->values()
            );
        }

        return $models;
    }

    /**
     * Добавляет фильтры к указанному билдеру для указанной модели.
     *
     * @param Builder                   $builder
     * @param Model|NestedSetModelTrait $model
     *
     * @return Builder
     */
    protected static function addFiltersForModel(Builder $builder, Model $model): Builder
    {
        $lftName = $model->getLftName();
        $primary = $model->getKey();

        $builder
            ->whereRaw(
                NestedSetQuery::prepare('$lftName < (SELECT $lftName FROM $tableName WHERE $idName = ?)', $model),
                [$primary]
            )
            ->whereRaw(
                NestedSetQuery::prepare('$rgtName > (SELECT $rgtName FROM $tableName WHERE $idName = ?)', $model),
                [$primary]
            )
            ->orderByDesc($lftName);

        foreach ($model->getScopeAttributes() as $scope) {
            $builder->whereRaw(
                NestedSetQuery::prepare($scope . ' = (SELECT ' . $scope . ' FROM $tableName WHERE $idName = ?)', $model),
                [$primary]
            );
        }

        return self::addScopeFilter($builder, $model);
    }

    /**
     * Возвращает массив идентификаторов предков для указанной модели.
     *
     * @param Model|NestedSetModelTrait $model
     * @param Collection                $relations
     *
     * @return int[]
     */
    protected static function getAncestorIds(Model $model, Collection $relations): array
    {
        /** @var Collection|Model[]|NestedSetModelTrait[] $ancestors */
        $ancestors = clone $relations;
        $result = [];

        foreach ($ancestors as $index => $ancestor) {
            $ancestorId = $ancestor->getKey();
            $modelParentId = $model->getParentId();

            if ($modelParentId === $ancestorId) {
                $result[] = $ancestorId;
                $subAncestors = self::getAncestorIds($ancestor, $ancestors->forget($index));

                foreach ($subAncestors as $id) {
                    $result[] = $id;
                }
            }
        }

        return $result;
    }
}
