<?php

namespace Adapterap\NestedSet\Handlers;

use Adapterap\NestedSet\Contracts\NestedSetModel;
use Illuminate\Database\Eloquent\Collection;

class NestedSetConvertDescendantsToChildren
{
    /**
     * Конвертирует плоскую коллекцию из потомков в дерево.
     * Раскладывает дочерние элементы по связям "children".
     *
     * @param Collection $items
     */
    public function handle(Collection $items): void
    {
        foreach ($items as $item) {
            if ($item instanceof Collection) {
                $this->handle($item);

                continue;
            }

            if ($item instanceof NestedSetModel) {
                $item->setRelation(
                    'children',
                    $item->descendants->where($item->getParentIdName(), $item->getKey())
                );

                $this->recursively($item->children, $item->descendants);
            }
        }
    }

    /**
     * Рекурсивно определяет дочерние элементы.
     *
     * @param Collection $children
     * @param Collection $descendants Весь список потомков
     */
    private function recursively(Collection $children, Collection $descendants): void
    {
        /** @var NestedSetModel $child */
        foreach ($children as $child) {
            $child->setRelation(
                'children',
                $descendants->where($child->getParentIdName(), $child->getKey())
            );

            $this->recursively($child->children, $descendants);
        }
    }
}
