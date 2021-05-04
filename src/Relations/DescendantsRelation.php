<?php

namespace Adapterap\NestedSet\Relations;

use Adapterap\NestedSet\NestedSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;

/**
 * Class DescendantsRelation
 *
 * @package Adapterap\NestedSet\Relations
 * @property-read Model|NestedSet $parent
 */
class DescendantsRelation extends BaseRelation
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
        // Соотносим найденные модели с их родителями
        foreach ($models as $index => $model) {
            $descendantIds = self::getDescendantIds($model, $results);

            $models[$index] = $model->setRelation(
                $relation,
                $results
                    ->filter(function ($item) use ($descendantIds) {
                        /** @var NestedSet $item */
                        $itemPrimary = $item->getAttribute($item->getKeyName());

                        return in_array($itemPrimary, $descendantIds, true);
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
        $lftName = $model->lftName;
        $rgtName = $model->rgtName;
        $tableName = $model->getTable();
        $primaryName = $model->getKeyName();
        $primary = $model->getAttribute($primaryName);

        return $builder
            ->where($lftName, '>', new Expression("(SELECT `{$lftName}` FROM `{$tableName}` WHERE `{$primaryName}` = {$primary})"))
            ->where($rgtName, '<', new Expression("(SELECT `{$rgtName}` FROM `{$tableName}` WHERE `{$primaryName}` = {$primary})"));
    }

    /**
     * Возвращает массив идентификаторов потомков для указанной модели.
     *
     * @param Model|NestedSet $model
     * @param Collection|Model[]|NestedSet[] $relations
     *
     * @return int[]
     */
    protected static function getDescendantIds(Model $model, Collection $relations): array
    {
        /** @var Collection|Model[]|NestedSet[] $descendants */
        $descendants = clone $relations;
        $modelId = $model->getAttribute($model->getKeyName());
        $result = [];

        foreach ($descendants as $index => $descendant) {
            $descendantParentId = $descendant->getAttribute($descendant->parentIdName);
            $descendantPrimary = $descendant->getAttribute($descendant->getKeyName());

            if ($modelId === $descendantParentId) {
                $result[] = $descendantPrimary;
                $subDescendants = self::getDescendantIds($descendant, $descendants->forget($index));

                foreach ($subDescendants as $id) {
                    $result[] = $id;
                }
            }
        }

        return $result;
    }
}
