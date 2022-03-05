<?php

namespace Adapterap\NestedSet\Traits;

/**
 * Trait Mutators.
 *
 * @property-read bool $is_root
 * @property-read bool $is_child
 * @property-read bool $has_children
 */
trait Mutators
{
    /**
     * Виртуальное свойство. Определяет, является ли модель корневым элементом.
     *
     * @return bool
     */
    public function getIsRootAttribute(): bool
    {
        return $this->getParentId() === null;
    }

    /**
     * Виртуальное свойство. Определяет, является ли модель корневым элементом.
     *
     * @return bool
     */
    public function getIsChildAttribute(): bool
    {
        return $this->getParentId() !== null;
    }

    /**
     * Виртуальное свойство. Определяет, есть ли у элемента дочерние элементы.
     *
     * @return bool
     */
    public function getHasChildrenAttribute(): bool
    {
        return $this->getLft() + 1 < $this->getRgt();
    }
}
