<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Relations\AncestorsRelation;
use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AncestorsTest extends TestCase
{
    /**
     * This method is called before each test.
     */
    public function setUp(): void
    {
        parent::setUp();

        Capsule::table('categories')->truncate();
    }

    /**
     * Попытка получить список предков.
     */
    public function testGetViaBuilder(): void
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
            'child1112' => $child1112
        ] = $this->createTree();

        $ancestors = $child1112->ancestors()
            ->orderByDesc('lft')
            ->get();

        self::assertCount(3, $ancestors);
        self::assertEquals($child111->id, $ancestors->get(0)->id);
        self::assertEquals($child11->id, $ancestors->get(1)->id);
        self::assertEquals($root1->id, $ancestors->get(2)->id);
    }

    /**
     * Проверка корректности работы load() в коллекции
     */
    public function testLoadFromCollection(): void
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
        ] = $this->createTree();

        $children = (new Category())->newCollection([$child1112, $child21, $child31]);
        $children->load([
            'ancestors' => function (AncestorsRelation $builder) {
                $builder->orderByDesc('lft');
            }
        ]);

        // child1112
        self::assertCount(3, $children->get(0)->ancestors);
        self::assertEquals($child111->id, $children->get(0)->ancestors->get(0)->id);
        self::assertEquals($child11->id, $children->get(0)->ancestors->get(1)->id);
        self::assertEquals($root1->id, $children->get(0)->ancestors->get(2)->id);

        // child21
        self::assertCount(1, $children->get(1)->ancestors);
        self::assertEquals($root2->id, $children->get(1)->ancestors->get(0)->id);

        // child31
        self::assertCount(1, $children->get(2)->ancestors);
        self::assertEquals($root3->id, $children->get(2)->ancestors->get(0)->id);
    }

    /**
     * Проверка корректности работы with() в билдере
     */
    public function testWithInBuilder(): void
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
        ] = $this->createTree();

        $children = Category::query()
            ->whereRaw('rgt = lft + 1')
            ->with([
                'ancestors' => function (AncestorsRelation $builder) {
                    $builder->orderByDesc('lft');
                },
            ])
            ->orderBy('lft')
            ->get();

        self::assertCount(4, $children);

        // child1111
        self::assertEquals($child1111->id, $children->get(0)->id);
        self::assertCount(3, $children->get(0)->ancestors);
        self::assertEquals($child111->id, $children->get(0)->ancestors->get(0)->id);
        self::assertEquals($child11->id, $children->get(0)->ancestors->get(1)->id);
        self::assertEquals($root1->id, $children->get(0)->ancestors->get(2)->id);

        // child1112
        self::assertEquals($child1112->id, $children->get(1)->id);
        self::assertCount(3, $children->get(1)->ancestors);
        self::assertEquals($child111->id, $children->get(1)->ancestors->get(0)->id);
        self::assertEquals($child11->id, $children->get(1)->ancestors->get(1)->id);
        self::assertEquals($root1->id, $children->get(1)->ancestors->get(2)->id);

        // child21
        self::assertEquals($child21->id, $children->get(2)->id);
        self::assertCount(1, $children->get(2)->ancestors);
        self::assertEquals($root2->id, $children->get(2)->ancestors->get(0)->id);

        // child31
        self::assertEquals($child31->id, $children->get(3)->id);
        self::assertCount(1, $children->get(3)->ancestors);
        self::assertEquals($root3->id, $children->get(3)->ancestors->get(0)->id);
    }

    /**
     * Проверка корректности работы lazy load
     */
    public function testLazyLoad(): void
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

        $ancestors = $child1111->ancestors;

        self::assertCount(3, $ancestors);
        self::assertEquals($child111->id, $ancestors->get(0)->id);
        self::assertEquals($child11->id, $ancestors->get(1)->id);
        self::assertEquals($root1->id, $ancestors->get(2)->id);
    }
}
