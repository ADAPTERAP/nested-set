<?php

namespace Adapterap\NestedSet\Traits;

trait Attributes
{
    /**
     * Глобальные названия колонок.
     *
     * @var array|string[]
     */
    protected static array $nestedSetColumns = [
        'lft' => 'lft',
        'rgt' => 'rgt',
        'depth' => 'depth',
        'parent_id' => 'parent_id',
    ];

    /**
     * Возвращает название колонки с индексом вложенности слева.
     *
     * @return string
     */
    public function getLftName(): string
    {
        return static::$nestedSetColumns['lft'] ?? 'lft';
    }

    /**
     * Возвращает название колонки с индексом вложенности справа.
     *
     * @return string
     */
    public function getRgtName(): string
    {
        return static::$nestedSetColumns['rgt'] ?? 'rgt';
    }

    /**
     * Возвращает название колонки с глубиной вложенности.
     *
     * @return string
     */
    public function getDepthName(): string
    {
        return static::$nestedSetColumns['depth'] ?? 'depth';
    }

    /**
     * Возвращает название колонки с идентификатором родителя.
     *
     * @return string
     */
    public function getParentIdName(): string
    {
        return static::$nestedSetColumns['parent_id'] ?? 'parent_id';
    }

    /**
     * Возвращает индекс вложенности слева.
     *
     * @return mixed
     */
    public function getLft()
    {
        return $this->getAttribute($this->getLftName());
    }

    /**
     * Возвращает индекс вложенности справа.
     *
     * @return mixed
     */
    public function getRgt()
    {
        return $this->getAttribute($this->getRgtName());
    }

    /**
     * Возвращает глубину вложенности.
     *
     * @return mixed
     */
    public function getDepth()
    {
        return $this->getAttribute($this->getDepthName());
    }

    /**
     * Возвращает идентификатор родителя.
     *
     * @return mixed
     */
    public function getParentId()
    {
        return $this->getAttribute($this->getParentIdName());
    }

    /**
     * Возвращает массив полей объединяющих узлы.
     *
     * @return array
     */
    public function getScopeAttributes(): array
    {
        return [];
    }

    /**
     * Сеттер для глобальных названий колонок.
     *
     * @param array $attributes
     */
    public static function setNestedSetGlobalAttributes(array $attributes): void
    {
        static::$nestedSetColumns['lft'] = $attributes['lft'] ?? static::$nestedSetColumns['lft'];
        static::$nestedSetColumns['rgt'] = $attributes['rgt'] ?? static::$nestedSetColumns['rgt'];
        static::$nestedSetColumns['depth'] = $attributes['depth'] ?? static::$nestedSetColumns['depth'];
        static::$nestedSetColumns['parent_id'] = $attributes['parent_id'] ?? static::$nestedSetColumns['parent_id'];
    }
}
