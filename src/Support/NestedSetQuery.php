<?php

namespace Adapterap\NestedSet\Support;

use Adapterap\NestedSet\NestedSetModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NestedSetQuery
{
    /**
     * Подготавливает запрос к выполнению.
     *
     * @param string                                $query
     * @param Model|NestedSetModelTrait|SoftDeletes $model
     *
     * @return array|string|string[]
     */
    public static function prepare(string $query, Model $model)
    {
        $templateColumnNames = [
            '$lftName',
            '$rgtName',
            '$parentIdName',
            '$depthName',
            '$idName',
            '$tableName',
            '$scopes',
            '$whereScopes',
        ];
        $actualColumnNames = [
            $model->getLftName(),
            $model->getRgtName(),
            $model->getParentIdName(),
            $model->getDepthName(),
            $model->getKeyName(),
            $model->getTable(),
            self::addScopeToSql($model),
            self::addScopeToSql($model, true),
        ];

        if ($model->nestedSetHasSoftDeletes()) {
            $templateColumnNames[] = '$deletedAtName';
            $actualColumnNames[] = $model->getDeletedAtColumn();
        }

        $result = str_replace($templateColumnNames, $actualColumnNames, $query);
        $result = preg_replace(['/(--|#)[^\n]+/', '/\n/'], ['', ' '], $result);

        return preg_replace(['/\s{2,}/', '/\s+,\s*/', '/\s+\)/', '/^\s+/', '/\s+$/'], [' ', ', ', ')', '', ''], $result);
    }

    /**
     * Добавляет условия для полей в scope у модели, если есть.
     *
     * @param Model|NestedSetModelTrait $model
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
                $sql .= " AND {$scope} IS NULL";

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
