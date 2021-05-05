<?php

namespace Adapterap\NestedSet\Relations;

use Adapterap\NestedSet\NestedSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;

/**
 * Class AncestorsRelation
 *
 * @package Adapterap\NestedSet\Relations
 * @property-read Model|NestedSet $parent
 */
class AncestorsRelation extends BaseRelation
{
    /**
     * Направление сортировки по умолчанию
     *
     * @var string
     */
    protected string $defaultOrderDirection = 'desc';

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
        // Соотносим найденные модели с их детьми
        foreach ($models as $index => $model) {
            $ancestorIds = static::getAncestorIds($model, $results);

            $models[$index] = $model->setRelation(
                $relation,
                $results
                    ->filter(function ($item) use ($ancestorIds) {
                        /** @var NestedSet $item */
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
     * @param Builder $builder
     * @param Model|NestedSet $model
     *
     * @return Builder
     */
    protected static function addFiltersForModel(Builder $builder, Model $model): Builder
    {
        $lftName = $model->getLftName();
        $rgtName = $model->getRgtName();
        $tableName = $model->getTable();
        $primaryName = $model->getKeyName();
        $primary = $model->getAttribute($primaryName);

        return $builder
            ->where($lftName, '<', new Expression("(SELECT `{$lftName}` FROM `{$tableName}` WHERE `{$primaryName}` = {$primary})"))
            ->where($rgtName, '>', new Expression("(SELECT `{$rgtName}` FROM `{$tableName}` WHERE `{$primaryName}` = {$primary})"));
    }

    /**
     * Возвращает массив идентификаторов предков для указанной модели.
     *
     * @param Model|NestedSet $model
     * @param Collection $relations
     *
     * @return int[]
     */
    protected static function getAncestorIds(Model $model, Collection $relations): array
    {
        /** @var Collection|Model[]|NestedSet[] $ancestors */
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
