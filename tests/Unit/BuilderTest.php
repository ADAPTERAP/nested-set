<?php

namespace Adapterap\NestedSet\Tests\Unit;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\Models\Menu;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class BuilderTest extends TestCase
{
    /**
     * Проверка корректности работы фильтра whereDoesNotHaveParent().
     */
    public function testWhereDoesNotHaveParent(): void
    {
        /** @var Category $root1 */
        /** @var Category $root2 */
        /** @var Category $root3 */
        ['root1' => $root1, 'root2' => $root2, 'root3' => $root3] = $this->createCategoryTree();

        $roots = Category::query()
            ->whereDoesNotHaveParent()
            ->orderByLft()
            ->get();

        self::assertCount(3, $roots);
        self::assertEquals($root1->id, $roots->get(0)->id);
        self::assertEquals($root2->id, $roots->get(1)->id);
        self::assertEquals($root3->id, $roots->get(2)->id);
    }

    /**
     * Проверка корректности работы фильтра whereDoesNotHaveParent().
     */
    public function testWhereDoesNotHaveParentWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $root2 */
        /** @var MenuItem $root3 */
        [
            'menu1' => $menu1,
            'menu1Root1' => $root1,
            'menu1Root2' => $root2,
            'menu1Root3' => $root3
        ] = $this->createMenuItemsTree();

        $roots = MenuItem::scoped(['menu_id' => $menu1->id])
            ->whereDoesNotHaveParent()
            ->orderByLft()
            ->get();

        self::assertCount(3, $roots);
        self::assertEquals($root1->id, $roots->get(0)->id);
        self::assertEquals($root2->id, $roots->get(1)->id);
        self::assertEquals($root3->id, $roots->get(2)->id);
    }

    /**
     * Проверка корректности работы фильтра whereParent().
     */
    public function testWhereParent(): void
    {
        /** @var Category $root1 */
        /** @var Category $child11 */
        ['root1' => $root1, 'child11' => $child11] = $this->createCategoryTree();

        $children = Category::query()
            ->whereParent($root1)
            ->get();

        self::assertCount(1, $children);
        self::assertEquals($child11->id, $children->get(0)->id);
    }

    /**
     * Проверка корректности работы фильтра whereParent(), для элементов с общим ключом.
     */
    public function testWhereParentWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child12 */
        [
            'menu1' => $menu1,
            'menu1Root1' => $root1,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
        ] = $this->createMenuItemsTree();

        $children = MenuItem::scoped(['menu_id' => $menu1->id])
            ->whereParent($root1)
            ->get();

        self::assertCount(2, $children);
        self::assertEquals($child11->id, $children->get(0)->id);
        self::assertEquals($child12->id, $children->get(1)->id);
    }

    /**
     * Проверка корректности работы фильтра whereAncestor().
     */
    public function testWhereAncestor(): void
    {
        /** @var Category $root1 */
        /** @var Category $child11 */
        /** @var Category $child111 */
        /** @var Category $child1111 */
        /** @var Category $child1112 */
        [
            'root1' => $root1,
            'child11' => $child11,
            'child111' => $child111,
            'child1111' => $child1111,
            'child1112' => $child1112,
        ] = $this->createCategoryTree();

        $descendants = Category::query()
            ->whereAncestor($root1)
            ->orderByLft()
            ->get();

        self::assertCount(4, $descendants);
        self::assertEquals($child11->id, $descendants->get(0)->id);
        self::assertEquals($child111->id, $descendants->get(1)->id);
        self::assertEquals($child1111->id, $descendants->get(2)->id);
        self::assertEquals($child1112->id, $descendants->get(3)->id);
    }

    /**
     * Проверка корректности работы фильтра whereAncestor(), для элементов с общим ключом.
     */
    public function testWhereAncestorWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child12 */
        /** @var MenuItem $child111 */
        /** @var MenuItem $child1111 */
        /** @var MenuItem $child1112 */
        [
            'menu1' => $menu1,
            'menu1Root1' => $root1,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
            'menu1Child111' => $child111,
            'menu1Child1111' => $child1111,
            'menu1Child1112' => $child1112,
        ] = $this->createMenuItemsTree();

        $descendants = MenuItem::scoped(['menu_id' => $menu1->id])
            ->whereAncestor($root1)
            ->orderByLft()
            ->get();

        self::assertCount(5, $descendants);
        self::assertEquals($child11->id, $descendants->get(0)->id);
        self::assertEquals($child111->id, $descendants->get(1)->id);
        self::assertEquals($child1111->id, $descendants->get(2)->id);
        self::assertEquals($child1112->id, $descendants->get(3)->id);
        self::assertEquals($child12->id, $descendants->get(4)->id);
    }
}
