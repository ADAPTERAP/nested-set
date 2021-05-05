<?php

namespace Adapterap\NestedSet\Drivers;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Query\Expression;

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

        Manager::table($this->table)
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
        $parentId = $attributes[$this->parentIdName] ?? null;

        if ($parentId !== null && !is_numeric($parentId)) {
            return $attributes;
        }

        if ($parentId === null) {
            $attributes[$this->lftName] = new Expression(
                $this->prepareNestedSetSql(
                    "(SELECT `max` + 1 FROM (SELECT COALESCE(MAX(`rgt`), -1) AS `max` FROM `table` WHERE `parent_id` IS NULL) t)"
                )
            );

            $attributes[$this->rgtName] = new Expression(
                $this->prepareNestedSetSql(
                    "(SELECT `max` + 2 FROM (SELECT COALESCE(MAX(`rgt`), -1) AS `max` FROM `table` WHERE `parent_id` IS NULL) t)"
                )
            );

            $attributes[$this->depthName] = 0;

            return $attributes;
        }

        $attributes[$this->lftName] = new Expression(
            $this->prepareNestedSetSql(
                "(SELECT `rgt` FROM (SELECT `rgt` FROM `table` WHERE `id` = {$parentId}) t)"
            )
        );

        $attributes[$this->rgtName] = new Expression(
            $this->prepareNestedSetSql(
                "(SELECT `rgt` + 1 FROM (SELECT `rgt` FROM `table` WHERE `id` = {$parentId}) t)"
            )
        );

        $attributes[$this->depthName] = new Expression(
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
        foreach ($values as $field => $value) {
            if (in_array($field, [$this->lftName, $this->rgtName, $this->parentIdName, $this->depthName], true)) {
                continue;
            }

            $sql .= ", `{$field}` = IF(`id` = (select `id` from `item`), ?, `{$field}`)";
            $bindings[] = $value;
        }

        $sql .= $this->getWhereClauseForRebaseSubTree();

        return (int)Manager::connection()->statement(
            $this->prepareNestedSetSql($sql),
            $bindings
        );
    }

    /**
     * Удаляет элемент с указанным идентификатором.
     *
     * @param int|string $primary
     *
     * @return bool
     */
    public function delete($primary): bool
    {
        $sql = "
            WITH `item` AS (SELECT `lft`, `rgt` FROM `table` WHERE `id` = ?)
            DELETE
            FROM `table`
            WHERE `lft` >= (SELECT `lft` FROM `item`) AND `rgt` <= (SELECT `rgt` FROM `item`);
        ";

        return Manager::connection()->statement(
            $this->prepareNestedSetSql($sql),
            [$primary]
        );
    }

    /**
     * Обновляет индексы после удаления поддерева.
     *
     * @param int $lft
     * @param int $rgt
     */
    public function freshIndexesAfterDelete(int $lft, int $rgt): void
    {
        $diff = $rgt - $lft + 1;

        $sql = "
            UPDATE `table`
            SET `lft` = IF(`lft` > ?, `lft` - ?, `lft`),
                `rgt` = IF(`rgt` > ?, `rgt` - ?, `rgt`)
            WHERE `lft` > ? OR `rgt` > ?
        ";

        Manager::connection()->statement(
            $this->prepareNestedSetSql($sql),
            [$rgt, $diff, $rgt, $diff, $rgt, $rgt]
        );
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
            ['`lft`', '`rgt`', '`parent_id`', '`depth`', '`id`', '`table`'],
            ["`{$this->lftName}`", "`{$this->rgtName}`", "`{$this->parentIdName}`", "`{$this->depthName}`", "`{$this->primaryName}`", "`{$this->table}`"],
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
}
