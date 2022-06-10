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
     * @param int   $lft Индекс вложенности слева созданного элемента
     */
    public function freshIndexesAfterInsert($primary, int $lft): void
    {
        $sql = '
            UPDATE $tableName
                SET $lftName = CASE WHEN $lftName > ? THEN $lftName + 2 ELSE $lftName END,
                    $rgtName = CASE WHEN $rgtName >= ? AND $idName != ? THEN $rgtName + 2 ELSE $rgtName END
                WHERE (($rgtName >= ? AND $idName != ?) OR $lftName > ?)$scopes
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
                    '(SELECT max + 1 FROM (SELECT COALESCE(MAX($rgtName), -1) AS max FROM $tableName WHERE $parentIdName IS NULL$scopes) t)',
                    $this->model
                )
            );

            $attributes[$this->model->getRgtName()] = new Expression(
                NestedSetQuery::prepare(
                    '(SELECT max + 2 FROM (SELECT COALESCE(MAX($rgtName), -1) AS max FROM $tableName WHERE $parentIdName IS NULL$scopes) t)',
                    $this->model
                )
            );

            if (!$this->model->canSetDepthColumn()) {
                $attributes[$this->model->getDepthName()] = 0;
            } else {
                $attributes[$this->model->getDepthName()] ??= 0;
            }

            return $attributes;
        }

        $attributes[$this->model->getLftName()] = new Expression(
            NestedSetQuery::prepare(
                sprintf(
                    '(SELECT $rgtName FROM (SELECT $rgtName FROM $tableName WHERE $idName = %d$scopes) t)',
                    $parentId
                ),
                $this->model
            )
        );

        $attributes[$this->model->getRgtName()] = new Expression(
            NestedSetQuery::prepare(
                sprintf(
                    '(SELECT $rgtName + 1 FROM (SELECT $rgtName FROM $tableName WHERE $idName = %d$scopes) t)',
                    $parentId
                ),
                $this->model
            )
        );

        $attributes[$this->model->getDepthName()] = new Expression(
            NestedSetQuery::prepare(
                sprintf(
                    '(SELECT $depthName + 1 FROM (SELECT $depthName FROM $tableName WHERE $idName = %d$scopes) t)',
                    $parentId
                ),
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
            . 'UPDATE $tableName AS t '
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

            $sql .= ", {$field} = CASE WHEN \$idName = (select \$idName from item) THEN ? ELSE {$field} END";
            $bindings[] = $value;
        }

        $sql .= $this->getWhereClauseForRebaseSubTree();

        return (int)$this->model->getConnection()->statement(
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
            WITH item AS (SELECT $lftName, $rgtName FROM $tableName WHERE $idName = ?)
            DELETE
            FROM $tableName
            WHERE $lftName >= (SELECT $lftName FROM item) AND $rgtName <= (SELECT $rgtName FROM item)$scopes;
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
            UPDATE $tableName
            SET $lftName = CASE WHEN $lftName > ? THEN $lftName - ? ELSE $lftName END,
                $rgtName = CASE WHEN $rgtName > ? THEN $rgtName - ? ELSE $rgtName END
            WHERE ($lftName > ? OR $rgtName > ?)$scopes
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
                -- Информация о рутовом элементе перемещаемого поддерева
                item AS (SELECT $idName, $lftName, $rgtName, $depthName FROM $tableName WHERE $idName = ?$scopes),
                -- Информация о родительском элементе, внутрь которого перемещается поддерево
                newParent AS (SELECT $idName, $lftName, $rgtName, $depthName FROM $tableName WHERE $idName = ?$scopes),
                -- Список элементов, которые входят в перемещаемое поддерево
                tree AS (SELECT $idName FROM $tableName WHERE $lftName >= (SELECT $lftName FROM item) AND $rgtName <= (SELECT $rgtName FROM item)$scopes),
                -- Разница между rgt и lft. Необходима для других запросов
                diffBetweenRgtAndLft AS (
                    SELECT (SELECT $rgtName FROM item) - (SELECT $lftName FROM item) AS diff
                ),
                -- Коэффициенты, для корректного подсчета lft/rgt
                coefficients AS (
                    SELECT
                        (SELECT diff FROM diffBetweenRgtAndLft) + 1 AS ancestorsLft,
                        CASE
                            WHEN (SELECT $lftName FROM item) < (SELECT $lftName FROM newParent)
                                THEN (SELECT $lftName FROM newParent) - (SELECT diff FROM diffBetweenRgtAndLft) - (SELECT $lftName FROM item)
                            WHEN (SELECT $lftName FROM item) > (SELECT $lftName FROM newParent)
                                then (SELECT $lftName FROM item) - (SELECT $lftName FROM newParent) - 1
                            ELSE 1
                        END AS subTreeLft
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
                $parentIdName = CASE WHEN id = (SELECT id FROM item) THEN (SELECT id FROM newParent) ELSE $parentIdName END,
                $depthName =
                    CASE
                        WHEN exists (SELECT 1 FROM tree WHERE tree.id = t.id)
                            THEN $depthName - (SELECT $depthName FROM item) + (SELECT $depthName FROM newParent) + 1
                        ELSE $depthName
                    END,
                $lftName =
                    CASE
                        -- предки при перемещении вниз
                        WHEN (SELECT $lftName FROM item) < (SELECT $lftName FROM newParent) AND $lftName > (SELECT $lftName FROM item) AND $lftName < (SELECT $rgtName FROM newParent) AND $rgtName > (SELECT $rgtName FROM item)
                            THEN $lftName - (SELECT ancestorsLft FROM coefficients)

                        -- предки при перемещении вверх
                        WHEN (SELECT $lftName FROM item) > (SELECT $lftName FROM newParent) AND $lftName < (SELECT $lftName FROM item) AND $lftName > (SELECT $rgtName FROM newParent)
                            THEN $lftName + (SELECT ancestorsLft FROM coefficients)

                        -- перемещаемое дерево при перемещении вниз
                        WHEN (SELECT $lftName FROM item) < (SELECT $lftName FROM newParent) AND EXISTS (SELECT 1 FROM tree WHERE tree.id = t.$idName)
                            THEN $lftName + (SELECT subTreeLft FROM coefficients)

                        -- элементы перемещаемого дерева при меремещении вверх
                        WHEN (SELECT $lftName FROM item) > (SELECT $lftName FROM newParent) AND EXISTS (SELECT 1 FROM tree WHERE tree.$idName = t.$idName)
                            THEN $lftName - (SELECT subTreeLft FROM coefficients)

                        ELSE $lftName
                    END,
                $rgtName =
                    CASE
                        -- предки при перемещении вниз
                        WHEN (SELECT $lftName FROM item) < (SELECT $lftName FROM newParent) AND $rgtName > (SELECT $rgtName FROM item) AND $rgtName < (SELECT $rgtName FROM newParent)
                            THEN $rgtName - (SELECT ancestorsLft FROM coefficients)

                        -- предки при перемещении вверх
                        WHEN (SELECT $lftName FROM item) > (SELECT $lftName FROM newParent) AND $rgtName < (SELECT $lftName FROM item) AND $rgtName >= (SELECT $rgtName FROM newParent)
                            THEN $rgtName + (SELECT ancestorsLft FROM coefficients)

                        -- дерево при перемещении вниз
                        WHEN (SELECT $lftName FROM item) < (SELECT $lftName FROM newParent) AND EXISTS (SELECT 1 FROM tree WHERE tree.id = t.id)
                            THEN $rgtName + (SELECT subTreeLft FROM coefficients)

                        -- дерево при перемещении вверх
                        WHEN (SELECT $lftName FROM item) > (SELECT $lftName FROM newParent) AND EXISTS (SELECT 1 FROM tree WHERE tree.id = t.id)
                            THEN $rgtName - (SELECT subTreeLft FROM coefficients)

                        ELSE $rgtName
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
                (SELECT $lftName FROM item) < (SELECT $lftName FROM newParent)
                    AND ($lftName >= (SELECT $lftName FROM item) OR $rgtName <= (SELECT $rgtName FROM newParent))
                OR (
                    (SELECT $lftName FROM item) > (SELECT $lftName FROM newParent)
                        AND ($lftName <= (SELECT $lftName FROM item) OR $rgtName >= (SELECT $rgtName FROM newParent))
                )
        ';
    }
}
