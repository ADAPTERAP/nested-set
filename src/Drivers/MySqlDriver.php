<?php

namespace Adapterap\NestedSet\Drivers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection as SupportCollection;

class MySqlDriver extends NestedSetDriver
{
    /**
     * Пересчитывает индексы вложенности для:
     * - всех предков $lft
     * - всех элементов ниже $lft
     *
     * @param mixed $primary Идентификатор созданного элемента
     * @param int $lft Индекс вложенности слева созданного элемента
     *
     * @return void
     */
    public function freshIndexesAfterInsert($primary, int $lft): void
    {
        $sql = "
            UPDATE `table`
                SET `lft` = IF(`lft` > ?, `lft` + 2, `lft`),
                    `rgt` = IF(`rgt` >= ? AND `id` != ?, `rgt` + 2, `rgt`)
                WHERE (`rgt` >= ? AND `id` != ?) OR `lft` > ?
        ";

        $this->model
            ->getConnection()
            ->statement($this->prepareNestedSetSql($sql), [$lft, $lft, $primary, $lft, $primary, $lft]);
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
                $this->prepareNestedSetSql(
                    "(SELECT `max` + 1 FROM (SELECT COALESCE(MAX(`rgt`), -1) AS `max` FROM `table` WHERE `parent_id` IS NULL) t)"
                )
            );

            $attributes[$this->model->getRgtName()] = new Expression(
                $this->prepareNestedSetSql(
                    "(SELECT `max` + 2 FROM (SELECT COALESCE(MAX(`rgt`), -1) AS `max` FROM `table` WHERE `parent_id` IS NULL) t)"
                )
            );

            $attributes[$this->model->getDepthName()] = 0;

            return $attributes;
        }

        $attributes[$this->model->getLftName()] = new Expression(
            $this->prepareNestedSetSql(
                "(SELECT `rgt` FROM (SELECT `rgt` FROM `table` WHERE `id` = {$parentId}) t)"
            )
        );

        $attributes[$this->model->getRgtName()] = new Expression(
            $this->prepareNestedSetSql(
                "(SELECT `rgt` + 1 FROM (SELECT `rgt` FROM `table` WHERE `id` = {$parentId}) t)"
            )
        );

        $attributes[$this->model->getDepthName()] = new Expression(
            $this->prepareNestedSetSql(
                "(SELECT `depth` + 1 FROM (SELECT `depth` FROM `table` WHERE `id` = {$parentId}) t)"
            )
        );

        return $attributes;
    }

    /**
     * Перемещение поддерева.
     *
     * @param int $id
     * @param int $parentId
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
            $this->model->getDepthName()
        ];

        foreach ($values as $field => $value) {
            if (in_array($field, $nestedSetColumns, true)) {
                continue;
            }

            $sql .= ", `{$field}` = IF(`id` = (select `id` from `item`), ?, `{$field}`)";
            $bindings[] = $value;
        }

        $sql .= $this->getWhereClauseForRebaseSubTree();

        return (int)$this->model->getConnection()->statement(
            $this->prepareNestedSetSql($sql),
            $bindings
        );
    }

    /**
     * Мягко удаляет элемент с указанным идентификатором.
     *
     * @param int|string $primary
     *
     * @return bool
     */
    public function softDelete($primary): bool
    {
        $sql = $this->getWithClauseForSoftDelete()
            . 'UPDATE `table` t'
            . $this->getSetClauseForSoftDelete()
            . $this->getWhereClauseForSoftDelete();

        return $this->model->getConnection()->statement(
            $this->prepareNestedSetSql($sql),
            [$primary]
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
        $sql = "
            WITH `item` AS (SELECT `lft`, `rgt` FROM `table` WHERE `id` = ?)
            DELETE
            FROM `table`
            WHERE `lft` >= (SELECT `lft` FROM `item`) AND `rgt` <= (SELECT `rgt` FROM `item`);
        ";

        return $this->model->getConnection()->statement(
            $this->prepareNestedSetSql($sql),
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

        $sql = "
            UPDATE `table`
            SET `lft` = IF(`lft` > ?, `lft` - ?, `lft`),
                `rgt` = IF(`rgt` > ?, `rgt` - ?, `rgt`)
            WHERE `lft` > ? OR `rgt` > ?
        ";

        $this->model->getConnection()->statement(
            $this->prepareNestedSetSql($sql),
            [$rgt, $diff, $rgt, $diff, $rgt, $rgt]
        );
    }

    /**
     * Insert new records or update the existing ones.
     *
     * @param array $preparedValues
     * @param array $uniqueBy
     * @param array|null $update
     *
     * @return SupportCollection
     */
    public function upsert(array $preparedValues, array $uniqueBy, array $update = null): SupportCollection
    {
        $chunks = array_chunk($preparedValues, 7000);
        foreach ($chunks as $chunk) {
            $this->model
                ->newQuery()
                ->upsert($chunk, $uniqueBy, $update);
        }

        $builder = $this->model->newQuery();

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
     *
     * @return void
     */
    public function deleteUnusedItems(array $usedPrimaries): void
    {
        if ($this->hasSoftDeletes()) {
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
     * Подготавливает SQL запрос.
     *
     * @param string $sql
     *
     * @return string
     */
    protected function prepareNestedSetSql(string $sql): string
    {
        return str_replace(
            ['`lft`', '`rgt`', '`parent_id`', '`depth`', '`id`', '`table`', '`deleted_at`'],
            [
                "`{$this->model->getLftName()}`",
                "`{$this->model->getRgtName()}`",
                "`{$this->model->getParentIdName()}`",
                "`{$this->model->getDepthName()}`",
                "`{$this->model->getKeyName()}`",
                "`{$this->model->getTable()}`",
                "`{$this->model->getDeletedAtColumn()}`",
            ],
            $sql
        );
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
                `item` AS (SELECT `id`, `lft`, `rgt`, `depth` FROM `table` WHERE `id` = ?),
                # Информация о родительском элементе, внутрь которого перемещается поддерево
                `newParent` AS (SELECT `id`, `lft`, `rgt`, `depth` FROM `table` WHERE `id` = ?),
                # Список элементов, которые входят в перемещаемое поддерево
                `tree` AS (SELECT `id` FROM `table` WHERE `lft` >= (SELECT `lft` FROM `item`) AND `rgt` <= (SELECT `rgt` FROM `item`)),
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

    /**
     * Возвращает секцию WITH для мягкого удаления.
     *
     * @return string
     */
    protected function getWithClauseForSoftDelete(): string
    {
        return '
            WITH
                # Информация о рутовом элементе перемещаемого поддерева
                `item` AS (SELECT `id`, `lft`, `rgt` FROM `table` WHERE `id` = ?),
                # Список элементов, которые входят в перемещаемое поддерево
                `tree` AS (SELECT `id` FROM `table` WHERE `lft` >= (SELECT `lft` FROM `item`) AND `rgt` <= (SELECT `rgt` FROM `item`)),
                # Разница между rgt и lft. Необходима для других запросов
                `diffBetweenRgtAndLft` AS (
                    SELECT (SELECT `rgt` FROM `item`) - (SELECT `lft` FROM `item`) + 1 AS `diff`
                )
        ';
    }

    /**
     * Возвращает секцию SET для мягкого удаления.
     *
     * @return string
     */
    protected function getSetClauseForSoftDelete(): string
    {
        return '
            SET `lft` = 
                CASE
                    WHEN `lft` > (SELECT `rgt` FROM `item`) THEN `lft` - (SELECT `diff` FROM `diffBetweenRgtAndLft`)
                    WHEN EXISTS(SELECT 1 FROM `tree` WHERE `tree`.`id` = `t`.`id`) THEN 0
                    ELSE `lft`
                END,
                `rgt` = CASE
                    WHEN `rgt` > (SELECT `rgt` FROM `item`) THEN `rgt` - (SELECT `diff` FROM `diffBetweenRgtAndLft`)
                    WHEN EXISTS(SELECT 1 FROM `tree` WHERE `tree`.`id` = `t`.`id`) THEN 0
                    ELSE `rgt`
                END,
                `deleted_at` = IF(EXISTS(SELECT 1 FROM tree WHERE `tree`.`id` = `t`.`id`), COALESCE(`t`.`deleted_at`, NOW()), `deleted_at`)
        ';
    }

    /**
     * Возвращает секцию WHERE для мягкого удаления.
     *
     * @return string
     */
    protected function getWhereClauseForSoftDelete(): string
    {
        return 'WHERE `lft` >= (SELECT `lft` FROM `item`) OR `rgt` >= (SELECT `rgt` FROM `item`)';
    }
}
