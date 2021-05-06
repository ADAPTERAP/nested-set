<?php

namespace Adapterap\NestedSet\Support;

use Illuminate\Database\Eloquent\Model;

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
            ['`lft`', '`rgt`', '`parent_id`', '`depth`', '`id`', '`table`', '`deleted_at`'],
            [
                "`{$model->getLftName()}`",
                "`{$model->getRgtName()}`",
                "`{$model->getParentIdName()}`",
                "`{$model->getDepthName()}`",
                "`{$model->getKeyName()}`",
                "`{$model->getTable()}`",
                "`{$model->getDeletedAtColumn()}`",
            ],
            $query
        );
    }
}
