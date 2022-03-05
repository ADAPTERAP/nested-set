<?php

namespace Adapterap\NestedSet;

use Adapterap\NestedSet\Handlers\NestedSetConvertDescendantsToChildren;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait NestedSetCollection.
 *
 * @mixin Collection
 */
trait NestedSetCollectionTrait
{
    /**
     * Конвертирует плоскую коллекцию из потомков в дерево в виде множества связей "children".
     *
     * @return $this
     */
    public function convertDescendantsToChildren()
    {
        (new NestedSetConvertDescendantsToChildren())->handle($this);

        return $this;
    }
}
