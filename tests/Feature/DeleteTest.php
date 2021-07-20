<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Menu;
use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Capsule\Manager;
use Adapterap\NestedSet\Tests\Models\MenuItem;

class DeleteTest extends TestCase
{
    /**
     * Жесткое удаление одного элемента.
     */
    public function testForceDeleteSingleItem(): void
    {
        /** @var Category $root1 */
        /** @var Category $root2 */
        /** @var Category $root3 */
        /** @var Category $child11 */
        /** @var Category $child21 */
        /** @var Category $child31 */
        /** @var Category $child111 */
        /** @var Category $child1111 */
        /** @var Category $child1112 */
        [
            'root1' => $root1,
            'root2' => $root2,
            'root3' => $root3,
            'child11' => $child11,
            'child21' => $child21,
            'child31' => $child31,
            'child111' => $child111,
            'child1111' => $child1111,
            'child1112' => $child1112,
        ] = $this->createCategoryTree();

        $child1112->forceDelete();

        self::assertEquals(8, Manager::table('categories')->count());

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 7, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 6, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $child111->id, 'lft' => 2, 'rgt' => 5, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $child1111->id, 'lft' => 3, 'rgt' => 4, 'depth' => 3]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 8, 'rgt' => 11, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 9, 'rgt' => 10, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 12, 'rgt' => 15, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 13, 'rgt' => 14, 'depth' => 1]);
    }

    /**
     * Жесткое удаление одного элемента.
     */
    public function testForceDeleteSingleItemWithScope(): void
    {
        /** @var MenuItem $menu1Root1 */
        /** @var MenuItem $menu1Root2 */
        /** @var MenuItem $menu1Root3 */
        /** @var MenuItem $menu1Child11 */
        /** @var MenuItem $menu1Child12 */
        /** @var MenuItem $menu1Child21 */
        /** @var MenuItem $menu1Child31 */
        /** @var MenuItem $menu1Child111 */
        /** @var MenuItem $menu1Child1111 */
        /** @var MenuItem $menu1Child1112 */
        /** @var MenuItem $menu2Root1 */
        /** @var MenuItem $menu2Root2 */
        /** @var MenuItem $menu2Root3 */
        /** @var MenuItem $menu2Child11 */
        /** @var MenuItem $menu2Child12 */
        /** @var MenuItem $menu2Child21 */
        /** @var MenuItem $menu2Child31 */
        /** @var MenuItem $menu2Child121 */
        /** @var MenuItem $menu2Child1211 */
        /** @var MenuItem $menu2Child1212 */
        [
            'menu1Root1' => $menu1Root1,
            'menu1Root2' => $menu1Root2,
            'menu1Root3' => $menu1Root3,
            'menu1Child11' => $menu1Child11,
            'menu1Child12' => $menu1Child12,
            'menu1Child21' => $menu1Child21,
            'menu1Child31' => $menu1Child31,
            'menu1Child111' => $menu1Child111,
            'menu1Child1111' => $menu1Child1111,
            'menu1Child1112' => $menu1Child1112,
            'menu2Root1' => $menu2Root1,
            'menu2Root2' => $menu2Root2,
            'menu2Root3' => $menu2Root3,
            'menu2Child11' => $menu2Child11,
            'menu2Child12' => $menu2Child12,
            'menu2Child21' => $menu2Child21,
            'menu2Child31' => $menu2Child31,
            'menu2Child121' => $menu2Child121,
            'menu2Child1211' => $menu2Child1211,
            'menu2Child1212' => $menu2Child1212,
        ] = $this->createMenuItemsTree();

        $menu1Child1112->forceDelete();

        self::assertEquals(19, Manager::table('menu_items')->count());

        self::assertDatabaseDoesNotHave('menu_items', ['id' => $menu1Child1112->id]);

        self::assertDatabaseHas('menu_items', ['id' => $menu1Root1->id, 'lft' => 0, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Child11->id, 'lft' => 1, 'rgt' => 6, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Child12->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Child111->id, 'lft' => 2, 'rgt' => 5, 'depth' => 2]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Child1111->id, 'lft' => 3, 'rgt' => 4, 'depth' => 3]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Root2->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Child21->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Root3->id, 'lft' => 14, 'rgt' => 17, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $menu1Child31->id, 'lft' => 15, 'rgt' => 16, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Root1->id, 'lft' => 0, 'rgt' => 11, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Child11->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Child12->id, 'lft' => 3, 'rgt' => 10, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Child121->id, 'lft' => 4, 'rgt' => 9, 'depth' => 2]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Child1211->id, 'lft' => 5, 'rgt' => 6, 'depth' => 3]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Child1212->id, 'lft' => 7, 'rgt' => 8, 'depth' => 3]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Root2->id, 'lft' => 12, 'rgt' => 15, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Child21->id, 'lft' => 13, 'rgt' => 14, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Root3->id, 'lft' => 16, 'rgt' => 19, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $menu2Child31->id, 'lft' => 17, 'rgt' => 18, 'depth' => 1]);
    }

    /**
     * Жесткое удаление поддерева.
     */
    public function testForceDeleteSubTree(): void
    {
        /** @var Category $root1 */
        /** @var Category $root2 */
        /** @var Category $root3 */
        /** @var Category $child11 */
        /** @var Category $child21 */
        /** @var Category $child31 */
        [
            'root1' => $root1,
            'root2' => $root2,
            'root3' => $root3,
            'child11' => $child11,
            'child21' => $child21,
            'child31' => $child31,
        ] = $this->createCategoryTree();

        $child11->forceDelete();

        self::assertEquals(5, Manager::table('categories')->count());

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 1, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 2, 'rgt' => 5, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 3, 'rgt' => 4, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
    }

    /**
     * Жесткое удаление поддерева.
     */
    public function testForceDeleteSubTreeWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $root2 */
        /** @var MenuItem $root3 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child12 */
        /** @var MenuItem $child21 */
        /** @var MenuItem $child31 */
        [
            'menu1' => $menu1,
            'menu1Root1' => $root1,
            'menu1Root2' => $root2,
            'menu1Root3' => $root3,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
            'menu1Child21' => $child21,
            'menu1Child31' => $child31,
        ] = $this->createMenuItemsTree();

        $child11->forceDelete();

        self::assertEquals(6, Manager::table('menu_items')->where(['menu_id' => $menu1->id])->count());

        self::assertDatabaseHas('menu_items', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child12->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $root2->id, 'lft' => 4, 'rgt' => 7, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child21->id, 'lft' => 5, 'rgt' => 6, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $root3->id, 'lft' => 8, 'rgt' => 11, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child31->id, 'lft' => 9, 'rgt' => 10, 'depth' => 1]);
    }

    /**
     * Мягкое удаление одного элемента.
     */
    public function testSoftDeleteSingleItem(): void
    {
        /** @var Category $root1 */
        /** @var Category $root2 */
        /** @var Category $root3 */
        /** @var Category $child11 */
        /** @var Category $child21 */
        /** @var Category $child31 */
        /** @var Category $child111 */
        /** @var Category $child1111 */
        /** @var Category $child1112 */
        [
            'root1' => $root1,
            'root2' => $root2,
            'root3' => $root3,
            'child11' => $child11,
            'child21' => $child21,
            'child31' => $child31,
            'child111' => $child111,
            'child1111' => $child1111,
            'child1112' => $child1112,
        ] = $this->createCategoryTree();

        $child1112->delete();

        self::assertEquals(
            8,
            Manager::table('categories')
                ->whereNull('deleted_at')
                ->count()
        );

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 7, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 6, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $child111->id, 'lft' => 2, 'rgt' => 5, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $child1111->id, 'lft' => 3, 'rgt' => 4, 'depth' => 3]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 8, 'rgt' => 11, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 9, 'rgt' => 10, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 12, 'rgt' => 15, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 13, 'rgt' => 14, 'depth' => 1]);
    }

    /**
     * Мягкое удаление одного элемента.
     */
    public function testSoftDeleteSingleItemWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $root2 */
        /** @var MenuItem $root3 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child12 */
        /** @var MenuItem $child21 */
        /** @var MenuItem $child31 */
        /** @var MenuItem $child111 */
        /** @var MenuItem $child1111 */
        /** @var MenuItem $child1112 */
        [
            'menu1' => $menu1,
            'menu1Root1' => $root1,
            'menu1Root2' => $root2,
            'menu1Root3' => $root3,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
            'menu1Child21' => $child21,
            'menu1Child31' => $child31,
            'menu1Child111' => $child111,
            'menu1Child1111' => $child1111,
            'menu1Child1112' => $child1112,
        ] = $this->createMenuItemsTree();

        $child1112->delete();

        self::assertEquals(
            9,
            Manager::table('menu_items')
                ->where('menu_id', $menu1->id)
                ->whereNull('deleted_at')
                ->count()
        );

        self::assertDatabaseHas('menu_items', ['id' => $root1->id, 'lft' => 0, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child11->id, 'lft' => 1, 'rgt' => 6, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $child12->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $child111->id, 'lft' => 2, 'rgt' => 5, 'depth' => 2]);
        self::assertDatabaseHas('menu_items', ['id' => $child1111->id, 'lft' => 3, 'rgt' => 4, 'depth' => 3]);
        self::assertDatabaseHas('menu_items', ['id' => $root2->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child21->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $root3->id, 'lft' => 14, 'rgt' => 17, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child31->id, 'lft' => 15, 'rgt' => 16, 'depth' => 1]);
    }

    /**
     * Мягкое удаление поддерева.
     */
    public function testSoftDeleteSubTree(): void
    {
        /** @var Category $root1 */
        /** @var Category $root2 */
        /** @var Category $root3 */
        /** @var Category $child11 */
        /** @var Category $child21 */
        /** @var Category $child31 */
        [
            'root1' => $root1,
            'root2' => $root2,
            'root3' => $root3,
            'child11' => $child11,
            'child21' => $child21,
            'child31' => $child31,
        ] = $this->createCategoryTree();

        $child11->delete();

        self::assertEquals(
            5,
            Manager::table('categories')
                ->whereNull('deleted_at')
                ->count()
        );

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 1, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 2, 'rgt' => 5, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 3, 'rgt' => 4, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
    }

    /**
     * Мягкое удаление поддерева.
     */
    public function testSoftDeleteSubTreeWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $root2 */
        /** @var MenuItem $root3 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child12 */
        /** @var MenuItem $child21 */
        /** @var MenuItem $child31 */
        [
            'menu1' => $menu1,
            'menu1Root1' => $root1,
            'menu1Root2' => $root2,
            'menu1Root3' => $root3,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
            'menu1Child21' => $child21,
            'menu1Child31' => $child31,
        ] = $this->createMenuItemsTree();

        $child11->delete();

        self::assertEquals(
            6,
            Manager::table('menu_items')
                ->where('menu_id', $menu1->id)
                   ->whereNull('deleted_at')
                   ->count()
        );

        self::assertDatabaseHas('menu_items', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child12->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $root2->id, 'lft' => 4, 'rgt' => 7, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child21->id, 'lft' => 5, 'rgt' => 6, 'depth' => 1]);
        self::assertDatabaseHas('menu_items', ['id' => $root3->id, 'lft' => 8, 'rgt' => 11, 'depth' => 0]);
        self::assertDatabaseHas('menu_items', ['id' => $child31->id, 'lft' => 9, 'rgt' => 10, 'depth' => 1]);
    }
}
