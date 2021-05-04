<?php

namespace Adapterap\NestedSet\Traits;

trait Attributes
{
    /**
     * Название колонки с индексом вложенности слева.
     *
     * @var string
     */
    public string $lftName = 'lft';

    /**
     * Название колонки с индексом вложенности справа.
     *
     * @var string
     */
    public string $rgtName = 'rgt';

    /**
     * Название колонки с идентификатором родительской категории.
     *
     * @var string
     */
    public string $parentIdName = 'parent_id';

    /**
     * Название колонки со значением глубины вложенности.
     *
     * @var string
     */
    public string $depthName = 'depth';
}
