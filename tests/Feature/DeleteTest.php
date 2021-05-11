<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Capsule\Manager;

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
}
