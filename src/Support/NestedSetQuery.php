<?php

namespace Adapterap\NestedSet\Support;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Adapterap\NestedSet\NestedSetModelTrait;

class NestedSetQuery
{
    /**
     * Подготавливает запрос к выполнению.
     *
     * @param string $query
     * @param Model $model
     *
     * @return array|string|string[]
     */
    public static function prepare(string $query, Model $model)
    {
        return str_replace(
            ['`lft`', '`rgt`', '`parent_id`', '`depth`', '`id`', '`table`', '`deleted_at`', '`scopes`', '`whereScopes`'],
            [
                "`{$model->getLftName()}`",
                "`{$model->getRgtName()}`",
                "`{$model->getParentIdName()}`",
                "`{$model->getDepthName()}`",
                "`{$model->getKeyName()}`",
                "`{$model->getTable()}`",
                "`{$model->getDeletedAtColumn()}`",
                self::addScopeToSql($model),
                self::addScopeToSql($model, true),
            ],
            $query
        );
    }

    /**
     * Добавляет условия для полей в scope у модели, если есть
     *
     * @param NestedSetModelTrait|Model $model
     * @param bool                      $addWhere Добавлять ли WHERE
     *
     * @return string
     */
    protected static function addScopeToSql(Model $model, bool $addWhere = false): string
    {
        $scopes = $model->getScopeAttributes();

        $sql = '';

        if (empty($scopes)) {
            return $sql;
        }

        foreach ($scopes as $scope) {
            $value = $model->getAttribute($scope);

            if ($value === null) {
                continue;
            }

            $sql .= " AND {$scope} = {$value}";
        }

        if ($addWhere) {
            $sql = Str::replaceFirst(' AND', ' WHERE', $sql);
        }

        return $sql;
    }
}
