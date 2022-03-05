<?php

namespace Adapterap\NestedSet\Tests\Unit\Handlers;

use Adapterap\NestedSet\Handlers\NestedSetConvertDescendantsToChildren;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Collection;

/**
 * @internal
 * @coversNothing
 */
class NestedSetConvertDescendantsToChildrenTest extends TestCase
{
    /**
     * Проверяет корректность разбиения потомков на дерево из детей.
     *
     * @throws Exception
     */
    public function test(): void
    {
        $menuItems = $this->arrange();
        $menuItems->load('descendants');

        $handler = new NestedSetConvertDescendantsToChildren();
        $handler->handle($menuItems);

        $this->asserts($menuItems);
    }

    /**
     * Подготавливает данные для теста.
     *
     * @throws Exception
     *
     * @return Collection
     */
    private function arrange(): Collection
    {
        return MenuItem::factory()
            ->count(2)
            ->create()
            ->each(function (MenuItem $menuItem) {
                $this->createDescendantsRecursively($menuItem);
            });
    }

    /**
     * Рекурсивно создает дочерние элементы для указанной модели.
     *
     * @param MenuItem $parent
     *
     * @throws Exception
     */
    private function createDescendantsRecursively(MenuItem $parent): void
    {
        if ($parent->getDepth() > random_int(1, 3)) {
            return;
        }

        MenuItem::factory()
            ->count(random_int(1, 3))
            ->create([
                'menu_id' => $parent->menu_id,
                'parent_id' => $parent->id,
            ])
            ->each(function (MenuItem $menuItem) {
                $this->createDescendantsRecursively($menuItem);
            });
    }

    /**
     * Проверяет корректность данных в коллекции.
     *
     * @param Collection $menuItems
     */
    private function asserts(Collection $menuItems): void
    {
        /** @var MenuItem $menuItem */
        foreach ($menuItems as $menuItem) {
            $this->assertMenuItem($menuItem);
        }
    }

    /**
     * Проверяет корректность данных в указанном пункте меню.
     *
     * @param MenuItem $menuItem
     */
    private function assertMenuItem(MenuItem $menuItem): void
    {
        self::assertTrue($menuItem->relationLoaded('children'));

        foreach ($menuItem->children as $child) {
            self::assertEquals($menuItem->id, $child->parent_id);

            $this->assertMenuItem($child);
        }
    }
}
