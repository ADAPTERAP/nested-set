<?php

namespace Adapterap\NestedSet\Relations;

use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Support\NestedSetQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DescendantsRelation.
 *
 * @property-read Model|NestedSetModelTrait $parent
 */
class DescendantsRelation extends BaseRelation
{
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
        // Соотносим найденные модели с их родителями
        foreach ($models as $index => $model) {
            $descendantIds = self::getDescendantIds($model, $results);

            $models[$index] = $model->setRelation(
                $relation,
                $results
                    ->filter(function ($item) use ($descendantIds) {
                        /** @var NestedSetModelTrait $item */
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
                NestedSetQuery::prepare('$lftName > (SELECT $lftName FROM $tableName WHERE $idName = ?)', $model),
                [$primary]
            )
            ->whereRaw(
                NestedSetQuery::prepare('$rgtName < (SELECT $rgtName FROM $tableName WHERE $idName = ?)', $model),
                [$primary]
            )
            ->orderBy($lftName);

        return self::addScopeFilter($builder, $model);
    }

    /**
     * Возвращает массив идентификаторов потомков для указанной модели.
     *
     * @param Model|NestedSetModelTrait $model
     * @param Collection                $relations
     *
     * @return int[]
     */
    protected static function getDescendantIds(Model $model, Collection $relations): array
    {
        return self::getDescendantsRecursively($relations, $model->getKey())
            ->pluck($model->getKeyName())
            ->unique()
            ->toArray();
    }

    /**
     * Рекурсивно ищет потомков по parent_id.
     *
     * @param Collection $models
     * @param mixed      $parentId
     *
     * @return Collection
     */
    private static function getDescendantsRecursively(Collection $models, $parentId): Collection
    {
        $result = new Collection();

        /** @var Model|NestedSetModelTrait $model */
        foreach ($models as $model) {
            if ((string) $model->getParentId() === (string) $parentId) {
                $result->push($model);

                foreach (self::getDescendantsRecursively($models, $model->getKey()) as $descendant) {
                    $result->push($descendant);
                }
            }
        }

        return $result;
    }
}
