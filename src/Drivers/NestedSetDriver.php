<?php

namespace Adapterap\NestedSet\Drivers;

abstract class NestedSetDriver
{
    /**
     * Название таблицы с которой работает драйвер.
     *
     * @var string
     */
    protected string $table;

    /**
     * Название колонки с идексом вложенности слева.
     *
     * @var string
     */
    protected string $lftName;

    /**
     * Название колонки с идексом вложенности справа.
     *
     * @var string
     */
    protected string $rgtName;

    /**
     * Название колонки с идентификатором родительского элемента.
     *
     * @var string
     */
    protected string $parentIdName;

    /**
     * Название колонки со значением глубины вложенности.
     *
     * @var string
     */
    protected string $depthName;

    /**
     * Название колонки с primary ключом.
     *
     * @var string
     */
    protected string $primaryName;

    /**
     * MySqlDriver constructor.
     *
     * @param string $table
     * @param string $primaryName
     * @param string $lftName
     * @param string $rgtName
     * @param string $parentIdName
     * @param string $depthName
     */
    public function __construct(string $table, string $primaryName, string $lftName, string $rgtName, string $parentIdName, string $depthName)
    {
        $this->table = $table;
        $this->lftName = $lftName;
        $this->rgtName = $rgtName;
        $this->parentIdName = $parentIdName;
        $this->depthName = $depthName;
        $this->primaryName = $primaryName;
    }

    /**
     * Определяет значения для заполнения колонок lft/rgt/depth перед записью в БД.
     *
     * @param array $attributes
     *
     * @return array
     */
    abstract public function getAttributesForInsert(array $attributes): array;

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
    abstract public function freshIndexesAfterInsert($primary, int $lft): void;

    /**
     * Перемещение поддерева.
     *
     * @param int $id
     * @param int $parentId
     * @param array $values
     *
     * @return int
     */
    abstract public function rebaseSubTree(int $id, int $parentId, array $values): int;
}
