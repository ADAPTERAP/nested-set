<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DescendantsTest extends TestCase
{
    /**
     * Попытка получить список потомков.
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

        $descendants = $root1->descendants()
            ->orderBy('lft')
            ->get();

        self::assertCount(4, $descendants);
        self::assertEquals($child11->id, $descendants->get(0)->id);
        self::assertEquals($child111->id, $descendants->get(1)->id);
        self::assertEquals($child1111->id, $descendants->get(2)->id);
        self::assertEquals($child1112->id, $descendants->get(3)->id);
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

        $roots = new Collection([$root1, $root2, $root3]);
        $roots->load([
            'descendants' => function ($builder) {
                /** @var Builder $builder */
                $builder->orderBy('lft');
            }
        ]);

        // root1
        self::assertCount(4, $roots->get(0)->descendants);
        self::assertEquals($child11->id, $roots->get(0)->descendants->get(0)->id);
        self::assertEquals($child111->id, $roots->get(0)->descendants->get(1)->id);
        self::assertEquals($child1111->id, $roots->get(0)->descendants->get(2)->id);
        self::assertEquals($child1112->id, $roots->get(0)->descendants->get(3)->id);

        // root2
        self::assertCount(1, $roots->get(1)->descendants);
        self::assertEquals($child21->id, $roots->get(1)->descendants->get(0)->id);

        // root3
        self::assertCount(1, $roots->get(2)->descendants);
        self::assertEquals($child31->id, $roots->get(2)->descendants->get(0)->id);
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

        $roots = Category::query()
            ->whereNull('parent_id')
            ->with('descendants')
            ->get();

        // root1
        self::assertCount(4, $roots->get(0)->descendants);
        self::assertEquals($child11->id, $roots->get(0)->descendants->get(0)->id);
        self::assertEquals($child111->id, $roots->get(0)->descendants->get(1)->id);
        self::assertEquals($child1111->id, $roots->get(0)->descendants->get(2)->id);
        self::assertEquals($child1112->id, $roots->get(0)->descendants->get(3)->id);

        // root2
        self::assertCount(1, $roots->get(1)->descendants);
        self::assertEquals($child21->id, $roots->get(1)->descendants->get(0)->id);

        // root3
        self::assertCount(1, $roots->get(2)->descendants);
        self::assertEquals($child31->id, $roots->get(2)->descendants->get(0)->id);
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

        $descendants = $root1->descendants;

        self::assertCount(4, $descendants);
        self::assertEquals($child11->id, $descendants->get(0)->id);
        self::assertEquals($child111->id, $descendants->get(1)->id);
        self::assertEquals($child1111->id, $descendants->get(2)->id);
        self::assertEquals($child1112->id, $descendants->get(3)->id);
    }
}
