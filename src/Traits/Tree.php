<?php

namespace Adapterap\NestedSet\Traits;

use Adapterap\NestedSet\Handlers\NestedSetSyncTree;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait MakeTree.
 */
trait Tree
{
    /**
     * Синхронизирует дерево.
     *
     * @param $values
     * @param array        $uniqueBy
     * @param null|array   $update
     * @param null|Closure $map
     *
     * @return Collection
     */
    public static function syncTree($values, array $uniqueBy = [], ?array $update = null, ?Closure $map = null): Collection
    {
        /** @var Model $model */
        $model = new static();

        $job = new NestedSetSyncTree($model, $map, $uniqueBy, $update);
        $job->sync($values);

        return new Collection();
    }
}
