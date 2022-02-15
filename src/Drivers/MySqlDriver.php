<?php

namespace Adapterap\NestedSet\Drivers;

use Adapterap\NestedSet\Support\NestedSetQuery;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;

class MySqlDriver extends NestedSetDriver
{
    /**
     * Пересчитывает индексы вложенности для:
     * - всех предков $lft
     * - всех элементов ниже $lft.
     *
     * @param mixed $primary Идентификатор созданного элемента
     * @param int   $lft     Индекс вложенности слева созданного элемента
     */
    public function freshIndexesAfterInsert($primary, int $lft): void
    {
        $sql = '
            UPDATE `table`
                SET `lft` = IF(`lft` > ?, `lft` + 2, `lft`),
                    `rgt` = IF(`rgt` >= ? AND `id` != ?, `rgt` + 2, `rgt`)
                WHERE ((`rgt` >= ? AND `id` != ?) OR `lft` > ?)`scopes`
        ';

        $this->model
            ->getConnection()
            ->statement(
                NestedSetQuery::prepare($sql, $this->model),
                [$lft, $lft, $primary, $lft, $primary, $lft]
            );
    }

    /**
     * Определяет значения для заполнения колонок lft/rgt/depth перед записью в БД.
     *
     * @param array $attributes
     *
     * @return array
     */
    public function getAttributesForInsert(array $attributes): array
    {
        $parentId = $attributes[$this->model->getParentIdName()] ?? null;

        if ($parentId !== null && !is_numeric($parentId)) {
            return $attributes;
        }

        if ($parentId === null) {
            $attributes[$this->model->getLftName()] = new Expression(
                NestedSetQuery::prepare(
                    '(SELECT `max` + 1 FROM (SELECT COALESCE(MAX(`rgt`), -1) AS `max` FROM `table` WHERE `parent_id` IS NULL`scopes`) t)',
                    $this->model
                )
            );

            $attributes[$this->model->getRgtName()] = new Expression(
                NestedSetQuery::prepare(
                    '(SELECT `max` + 2 FROM (SELECT COALESCE(MAX(`rgt`), -1) AS `max` FROM `table` WHERE `parent_id` IS NULL`scopes`) t)',
                    $this->model
                )
            );

            $attributes[$this->model->getDepthName()] = 0;

            return $attributes;
        }

        $attributes[$this->model->getLftName()] = new Expression(
            NestedSetQuery::prepare(
                "(SELECT `rgt` FROM (SELECT `rgt` FROM `table` WHERE `id` = {$parentId}`scopes`) t)",
                $this->model
            )
        );

        $attributes[$this->model->getRgtName()] = new Expression(
            NestedSetQuery::prepare(
                "(SELECT `rgt` + 1 FROM (SELECT `rgt` FROM `table` WHERE `id` = {$parentId}`scopes`) t)",
                $this->model
            )
        );

        $attributes[$this->model->getDepthName()] = new Expression(
            NestedSetQuery::prepare(
                "(SELECT `depth` + 1 FROM (SELECT `depth` FROM `table` WHERE `id` = {$parentId}`scopes`) t)",
                $this->model
            )
        );

        return $attributes;
    }

    /**
     * Перемещение поддерева.
     *
     * @param int   $id
     * @param int   $parentId
     * @param array $values
     *
     * @return int
     */
    public function rebaseSubTree(int $id, int $parentId, array $values): int
    {
        $bindings = [$id, $parentId];
        $sql = $this->getWithClauseForRebaseSubTree()
            . 'UPDATE `table` t '
            . $this->getSetClauseForRebaseSubTree();

        // Добавляем в запрос обновление пользовательских значений
        $nestedSetColumns = [
            $this->model->getLftName(),
            $this->model->getRgtName(),
            $this->model->getParentIdName(),
            $this->model->getDepthName(),
        ];

        foreach ($values as $field => $value) {
            if (in_array($field, $nestedSetColumns, true)) {
                continue;
            }

            $sql .= ", `{$field}` = IF(`id` = (select `id` from `item`), ?, `{$field}`)";
            $bindings[] = $value;
        }

        $sql .= $this->getWhereClauseForRebaseSubTree();

        return (int) $this->model->getConnection()->statement(
            NestedSetQuery::prepare($sql, $this->model),
            $bindings
        );
    }

    /**
     * Мягко удаляет элемент с указанным идентификатором.
     *
     * @param Builder $builder
     *
     * @return bool
     */
    public function softDelete(Builder $builder): bool
    {
        $sqlPath = dirname(__DIR__, 2) . '/resources/sql/mysql/softDelete.sql';

        // Добавляем фильтр по элементам, которые необходимо удалить.
        $sql = str_replace(
            '/* filter */',
            $builder->clone()
                ->select([$this->model->getKeyName(), $this->model->getLftName(), $this->model->getRgtName()])
                ->toSql(),
            file_get_contents($sqlPath)
        );

        return $this->model->getConnection()->statement(
            NestedSetQuery::prepare($sql, $this->model),
            $builder->getBindings()
        );
    }

    /**
     * Жестко удаляет элемент с указанным идентификатором.
     *
     * @param int|string $primary
     *
     * @return bool
     */
    public function forceDelete($primary): bool
    {
        $sql = '
            WITH `item` AS (SELECT `lft`, `rgt` FROM `table` WHERE `id` = ?)
            DELETE
            FROM `table`
            WHERE `lft` >= (SELECT `lft` FROM `item`) AND `rgt` <= (SELECT `rgt` FROM `item`)`scopes`;
        ';

        return $this->model->getConnection()->statement(
            NestedSetQuery::prepare($sql, $this->model),
            [$primary]
        );
    }

    /**
     * Обновляет индексы после жесткого удаления поддерева.
     *
     * @param int $lft
     * @param int $rgt
     */
    public function freshIndexesAfterForceDelete(int $lft, int $rgt): void
    {
        $diff = $rgt - $lft + 1;

        $sql = '
            UPDATE `table`
            SET `lft` = IF(`lft` > ?, `lft` - ?, `lft`),
                `rgt` = IF(`rgt` > ?, `rgt` - ?, `rgt`)
            WHERE (`lft` > ? OR `rgt` > ?)`scopes`
        ';

        $this->model->getConnection()->statement(
            NestedSetQuery::prepare($sql, $this->model),
            [$rgt, $diff, $rgt, $diff, $rgt, $rgt]
        );
    }

    /**
     * Insert new records or update the existing ones.
     *
     * @param array      $preparedValues
     * @param array      $uniqueBy
     * @param null|array $update
     *
     * @return SupportCollection
     */
    public function upsert(array $preparedValues, array $uniqueBy, array $update = null): SupportCollection
    {
        if (empty($preparedValues)) {
            return new SupportCollection();
        }

        $countColumns = count(Arr::first($preparedValues) ?? []);

        $chunks = array_chunk($preparedValues, ceil(48_000 / $countColumns));

        foreach ($chunks as $chunk) {
            $this->model
                ->newScopedQuery()
                ->upsert($chunk, $uniqueBy, $update);
        }

        $builder = $this->model->newScopedQuery();

        foreach ($preparedValues as $item) {
            $builder->orWhere(function ($builder) use ($item) {
                /** @var Builder $builder */
                if (!empty($this->uniqueBy)) {
                    foreach ($this->uniqueBy as $fieldName) {
                        $builder->where($fieldName, $item[$fieldName] ?? null);
                    }
                } else {
                    $builder
                        ->where($this->model->getLftName(), $item[$this->model->getLftName()])
                        ->where($this->model->getRgtName(), $item[$this->model->getRgtName()])
                        ->where($this->model->getDepthName(), $item[$this->model->getDepthName()])
                        ->where($this->model->getParentIdName(), $item[$this->model->getParentIdName()]);
                }
            });
        }

        // Запрашиваем найденные строки
        return $builder->get();
    }

    /**
     * Удаляет неиспользуемые элементы дерева.
     *
     * @param array $usedPrimaries
     */
    public function deleteUnusedItems(array $usedPrimaries): void
    {
        if ($this->model->nestedSetHasSoftDeletes()) {
            $this->model->getConnection()
                ->table($this->model->getTable())
                ->whereNotIn($this->model->getKeyName(), $usedPrimaries)
                ->whereNull($this->model->getDeletedAtColumn())
                ->update([
                    $this->model->getDeletedAtColumn() => Carbon::now(),
                ]);
        } else {
            $this->model->getConnection()
                ->table($this->model->getTable())
                ->whereNotIn($this->model->getKeyName(), $usedPrimaries)
                ->delete();
        }
    }

    /**
     * Возвращает секцию with для метода rebaseSubTree().
     *
     * @return string
     */
    protected function getWithClauseForRebaseSubTree(): string
    {
        return '
            WITH 
                # Информация о рутовом элементе перемещаемого поддерева
                `item` AS (SELECT `id`, `lft`, `rgt`, `depth` FROM `table` WHERE `id` = ?`scopes`),
                # Информация о родительском элементе, внутрь которого перемещается поддерево
                `newParent` AS (SELECT `id`, `lft`, `rgt`, `depth` FROM `table` WHERE `id` = ?`scopes`),
                # Список элементов, которые входят в перемещаемое поддерево
                `tree` AS (SELECT `id` FROM `table` WHERE `lft` >= (SELECT `lft` FROM `item`) AND `rgt` <= (SELECT `rgt` FROM `item`)`scopes`),
                # Разница между rgt и lft. Необходима для других запросов
                `diffBetweenRgtAndLft` AS (
                    SELECT (SELECT `rgt` FROM `item`) - (SELECT `lft` FROM `item`) AS `diff`
                ),
                # Коэффициенты, для корректного подсчета lft/rgt
                `coefficients` AS (
                    SELECT
                        (SELECT `diff` FROM `diffBetweenRgtAndLft`) + 1 AS `ancestorsLft`,
                        CASE
                            WHEN (SELECT `lft` FROM `item`) < (SELECT `lft` FROM `newParent`)
                                THEN (SELECT `lft` FROM `newParent`) - (SELECT `diff` FROM `diffBetweenRgtAndLft`) - (SELECT `lft` FROM `item`)
                            WHEN (SELECT `lft` FROM `item`) > (SELECT `lft` FROM `newParent`)
                                then (SELECT `lft` FROM `item`) - (SELECT `lft` FROM `newParent`) - 1
                            ELSE 1
                        END AS `subTreeLft`
                )
        ';
    }

    /**
     * Возвращает секцию SET для метода rebaseSubTree().
     *
     * @return string
     */
    protected function getSetClauseForRebaseSubTree(): string
    {
        return '
            SET
                `parent_id` = IF(id = (SELECT id FROM `item`), (SELECT id FROM `newParent`), `parent_id`),
                `depth` = 
                    CASE
                        WHEN exists (SELECT 1 FROM `tree` WHERE `tree`.id = t.id)
                            THEN `depth` - (SELECT `depth` FROM `item`) + (SELECT `depth` FROM `newParent`) + 1
                        ELSE `depth`
                    END,
                `lft` = 
                    CASE
                        # предки при перемещении вниз
                        WHEN (SELECT `lft` FROM `item`) < (SELECT `lft` FROM `newParent`) AND `lft` > (SELECT `lft` FROM `item`) AND `lft` < (SELECT `rgt` FROM `newParent`) AND `rgt` > (SELECT `rgt` FROM `item`)
                            THEN `lft` - (SELECT `ancestorsLft` FROM `coefficients`)
    
                        # предки при перемещении вверх
                        WHEN (SELECT `lft` FROM `item`) > (SELECT `lft` FROM `newParent`) AND `lft` < (SELECT `lft` FROM `item`) AND `lft` > (SELECT `rgt` FROM `newParent`)
                            THEN `lft` + (SELECT `ancestorsLft` FROM `coefficients`)
    
                        # перемещаемое дерево при перемещении вниз
                        WHEN (SELECT `lft` FROM `item`) < (SELECT `lft` FROM `newParent`) AND EXISTS (SELECT 1 FROM `tree` WHERE `tree`.id = `t`.`id`)
                            THEN `lft` + (SELECT `subTreeLft` FROM `coefficients`)
    
                        # элементы перемещаемого дерева при меремещении вверх
                        WHEN (SELECT `lft` FROM `item`) > (SELECT `lft` FROM `newParent`) AND EXISTS (SELECT 1 FROM `tree` WHERE `tree`.`id` = `t`.`id`)
                            THEN `lft` - (SELECT `subTreeLft` FROM `coefficients`)
    
                        ELSE `lft`
                    END,
                `rgt` = 
                    CASE
                        # предки при перемещении вниз
                        WHEN (SELECT `lft` FROM `item`) < (SELECT `lft` FROM `newParent`) AND `rgt` > (SELECT `rgt` FROM `item`) AND `rgt` < (SELECT `rgt` FROM `newParent`)
                            THEN `rgt` - (SELECT `ancestorsLft` FROM `coefficients`)
                        
                        # предки при перемещении вверх
                        WHEN (SELECT `lft` FROM `item`) > (SELECT `lft` FROM `newParent`) AND `rgt` < (SELECT `lft` FROM `item`) AND `rgt` >= (SELECT `rgt` FROM `newParent`)
                            THEN `rgt` + (SELECT `ancestorsLft` FROM `coefficients`)
                        
                        # дерево при перемещении вниз
                        WHEN (SELECT `lft` FROM `item`) < (SELECT `lft` FROM `newParent`) AND EXISTS (SELECT 1 FROM `tree` WHERE `tree`.id = t.id)
                            THEN `rgt` + (SELECT `subTreeLft` FROM `coefficients`)
                        
                        # дерево при перемещении вверх
                        WHEN (SELECT `lft` FROM `item`) > (SELECT `lft` FROM `newParent`) AND EXISTS (SELECT 1 FROM `tree` WHERE `tree`.id = t.id)
                            THEN `rgt` - (SELECT `subTreeLft` FROM `coefficients`)
                        
                        ELSE `rgt`
                    END
        ';
    }

    /**
     * Возвращает секцию WHERE для метода rebaseSubTree().
     *
     * @return string
     */
    protected function getWhereClauseForRebaseSubTree(): string
    {
        return '
            WHERE 
                (SELECT `lft` FROM `item`) < (SELECT `lft` FROM `newParent`) 
                    AND (`lft` >= (SELECT `lft` FROM `item`) OR `rgt` <= (SELECT `rgt` FROM `newParent`))
                OR (
                    (SELECT `lft` FROM `item`) > (SELECT `lft` FROM `newParent`) 
                        AND (`lft` <= (SELECT `lft` FROM `item`) OR `rgt` >= (SELECT `rgt` FROM `newParent`))
                )
        ';
    }
}
