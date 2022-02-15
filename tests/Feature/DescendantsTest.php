<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\Models\Menu;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @internal
 * @coversNothing
 */
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
        ] = $this->createCategoryTree();

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
     * Попытка получить список потомков.
     */
    public function testGetViaBuilderWithScope(): void
    {
        /** @var MenuItem $root1 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child12 */
        /** @var MenuItem $child111 */
        /** @var MenuItem $child1111 */
        /** @var MenuItem $child1112 */
        [
            'menu1Root1' => $root1,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
            'menu1Child111' => $child111,
            'menu1Child1111' => $child1111,
            'menu1Child1112' => $child1112
        ] = $this->createMenuItemsTree();

        $descendants = $root1->descendants()
            ->orderBy('lft')
            ->get();

        self::assertCount(5, $descendants);
        self::assertEquals($child11->id, $descendants->get(0)->id);
        self::assertEquals($child111->id, $descendants->get(1)->id);
        self::assertEquals($child1111->id, $descendants->get(2)->id);
        self::assertEquals($child1112->id, $descendants->get(3)->id);
        self::assertEquals($child12->id, $descendants->get(4)->id);
    }

    /**
     * Проверка корректности работы load() в коллекции.
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
        ] = $this->createCategoryTree();

        $roots = new Collection([$root1, $root2, $root3]);
        $roots->load([
            'descendants' => function ($builder) {
                /** @var Builder $builder */
                $builder->orderBy('lft');
            },
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
     * Проверка корректности работы load() в коллекции.
     */
    public function testLoadFromCollectionWithScope(): void
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

        $roots = new Collection([$root1, $root2, $root3]);
        $roots->load([
            'descendants' => function ($builder) use ($menu1) {
                /** @var Builder $builder */
                $builder->where('menu_id', $menu1->id)->orderBy('lft');
            },
        ]);

        // root1
        self::assertCount(5, $roots->get(0)->descendants);
        self::assertEquals($child11->id, $roots->get(0)->descendants->get(0)->id);
        self::assertEquals($child111->id, $roots->get(0)->descendants->get(1)->id);
        self::assertEquals($child1111->id, $roots->get(0)->descendants->get(2)->id);
        self::assertEquals($child1112->id, $roots->get(0)->descendants->get(3)->id);
        self::assertEquals($child12->id, $roots->get(0)->descendants->get(4)->id);

        // root2
        self::assertCount(1, $roots->get(1)->descendants);
        self::assertEquals($child21->id, $roots->get(1)->descendants->get(0)->id);

        // root3
        self::assertCount(1, $roots->get(2)->descendants);
        self::assertEquals($child31->id, $roots->get(2)->descendants->get(0)->id);
    }

    /**
     * Проверка корректности работы with() в билдере.
     */
    public function testWithInBuilder(): void
    {
        /** @var Category $child11 */
        /** @var Category $child21 */
        /** @var Category $child31 */
        /** @var Category $child111 */
        /** @var Category $child1111 */
        /** @var Category $child1112 */
        [
            'child11' => $child11,
            'child21' => $child21,
            'child31' => $child31,
            'child111' => $child111,
            'child1111' => $child1111,
            'child1112' => $child1112,
        ] = $this->createCategoryTree();

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
     * Проверка корректности работы with() в билдере.
     */
    public function testWithInBuilderWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $root2 */
        /** @var MenuItem $root3 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child21 */
        /** @var MenuItem $child31 */
        /** @var MenuItem $child111 */
        /** @var MenuItem $child1111 */
        /** @var MenuItem $child1112 */
        [
            'menu1' => $menu1,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
            'menu1Child21' => $child21,
            'menu1Child31' => $child31,
            'menu1Child111' => $child111,
            'menu1Child1111' => $child1111,
            'menu1Child1112' => $child1112,
        ] = $this->createMenuItemsTree();

        $roots = MenuItem::scoped(['menu_id' => $menu1->id])
            ->whereNull('parent_id')
            ->with('descendants')
            ->get();

        // root1
        self::assertCount(5, $roots->get(0)->descendants);
        self::assertEquals($child11->id, $roots->get(0)->descendants->get(0)->id);
        self::assertEquals($child111->id, $roots->get(0)->descendants->get(1)->id);
        self::assertEquals($child1111->id, $roots->get(0)->descendants->get(2)->id);
        self::assertEquals($child1112->id, $roots->get(0)->descendants->get(3)->id);
        self::assertEquals($child12->id, $roots->get(0)->descendants->get(4)->id);

        // root2
        self::assertCount(1, $roots->get(1)->descendants);
        self::assertEquals($child21->id, $roots->get(1)->descendants->get(0)->id);

        // root3
        self::assertCount(1, $roots->get(2)->descendants);
        self::assertEquals($child31->id, $roots->get(2)->descendants->get(0)->id);
    }

    /**
     * Проверка корректности работы lazy load.
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
        ] = $this->createCategoryTree();

        $descendants = $root1->descendants;

        self::assertCount(4, $descendants);
        self::assertEquals($child11->id, $descendants->get(0)->id);
        self::assertEquals($child111->id, $descendants->get(1)->id);
        self::assertEquals($child1111->id, $descendants->get(2)->id);
        self::assertEquals($child1112->id, $descendants->get(3)->id);
    }

    /**
     * Проверка корректности работы lazy load.
     */
    public function testLazyLoadWithScope(): void
    {
        /** @var MenuItem $root1 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child111 */
        /** @var MenuItem $child1111 */
        /** @var MenuItem $child1112 */
        [
            'menu1Root1' => $root1,
            'menu1Child11' => $child11,
            'menu1Child12' => $child12,
            'menu1Child111' => $child111,
            'menu1Child1111' => $child1111,
            'menu1Child1112' => $child1112,
        ] = $this->createMenuItemsTree();

        $descendants = $root1->descendants;

        self::assertCount(5, $descendants);
        self::assertEquals($child11->id, $descendants->get(0)->id);
        self::assertEquals($child111->id, $descendants->get(1)->id);
        self::assertEquals($child1111->id, $descendants->get(2)->id);
        self::assertEquals($child1112->id, $descendants->get(3)->id);
        self::assertEquals($child12->id, $descendants->get(4)->id);
    }
}
