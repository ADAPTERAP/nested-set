<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;

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
        ['root1' => $root1, 'root2' => $root2, 'root3' => $root3] = $this->createTree();

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
     * Проверка корректности работы фильтра whereParent().
     */
    public function testWhereParent(): void
    {
        /** @var Category $root1 */
        /** @var Category $child11 */
        ['root1' => $root1, 'child11' => $child11] = $this->createTree();

        $children = Category::query()
            ->whereParent($root1)
            ->get();

        self::assertCount(1, $children);
        self::assertEquals($child11->id, $children->get(0)->id);
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
        ] = $this->createTree();

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
}
