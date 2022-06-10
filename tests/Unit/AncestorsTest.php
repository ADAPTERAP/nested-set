<?php

namespace Adapterap\NestedSet\Tests\Unit;

use Adapterap\NestedSet\Relations\AncestorsRelation;
use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\Models\Menu;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 * @coversNothing
 */
class AncestorsTest extends TestCase
{
    /**
     * Попытка получить список предков.
     */
    public function testGetViaBuilder(): void
    {
        /** @var Category $root1 */
        /** @var Category $child11 */
        /** @var Category $child111 */
        /** @var Category $child1112 */
        [
            'root1' => $root1,
            'child11' => $child11,
            'child111' => $child111,
            'child1112' => $child1112
        ] = $this->createCategoryTree();

        $ancestors = $child1112->ancestors()
            ->orderByDesc('lft')
            ->get();

        self::assertCount(3, $ancestors);
        self::assertEquals($child111->id, $ancestors->get(0)->id);
        self::assertEquals($child11->id, $ancestors->get(1)->id);
        self::assertEquals($root1->id, $ancestors->get(2)->id);
    }

    /**
     * Попытка получить список предков.
     */
    public function testGetViaBuilderWithScope(): void
    {
        /** @var MenuItem $root1 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child111 */
        /** @var MenuItem $child1112 */
        [
            'menu1Root1' => $root1,
            'menu1Child11' => $child11,
            'menu1Child111' => $child111,
            'menu1Child1112' => $child1112
        ] = $this->createMenuItemsTree();

        $ancestors = $child1112->ancestors()
            ->orderByDesc('lft')
            ->get();

        self::assertCount(3, $ancestors);
        self::assertEquals($child111->id, $ancestors->get(0)->id);
        self::assertEquals($child11->id, $ancestors->get(1)->id);
        self::assertEquals($root1->id, $ancestors->get(2)->id);
    }

    /**
     * Проверка корректности работы load() в коллекции.
     *
     * @group 123
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
        /** @var Category $child1112 */
        [
            'root1' => $root1,
            'root2' => $root2,
            'root3' => $root3,
            'child11' => $child11,
            'child21' => $child21,
            'child31' => $child31,
            'child111' => $child111,
            'child1112' => $child1112,
        ] = $this->createCategoryTree();

        $children = (new Category())->newCollection([$child1112, $child21, $child31]);
        DB::enableQueryLog();
        $children->load('ancestors');

        // child1112
        if ($children->get(0)->ancestors->isEmpty()) {
            $children->get(0)->ancestors()->get();
            dd([
                'queries' => DB::getQueryLog(),
                'id' => $children->get(0)->id,
                'categories' => DB::table('categories')->get(),
                'ancestors' => DB::table('categories')
                    ->where('lft', '<', 5)
                    ->where('rgt', '>', 6)
                    ->get(),
                '$children->ancestors' => $children->toArray(),
            ]);
        }
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
     * Проверка корректности работы load() в коллекции.
     */
    public function testLoadFromCollectionWithScope(): void
    {
        /** @var Menu $menu1 */
        /** @var MenuItem $root1 */
        /** @var MenuItem $root2 */
        /** @var MenuItem $root3 */
        /** @var MenuItem $child11 */
        /** @var MenuItem $child21 */
        /** @var MenuItem $child31 */
        /** @var MenuItem $child111 */
        /** @var MenuItem $child1112 */
        [
            'menu1' => $menu1,
            'menu1Root1' => $root1,
            'menu1Root2' => $root2,
            'menu1Root3' => $root3,
            'menu1Child11' => $child11,
            'menu1Child21' => $child21,
            'menu1Child31' => $child31,
            'menu1Child111' => $child111,
            'menu1Child1112' => $child1112,
            'menu2Child1211' => $child1211,
        ] = $this->createMenuItemsTree();

        $children = (new Category())->newCollection([$child1112, $child21, $child31, $child1211]);
        $children->load([
            'ancestors' => function (AncestorsRelation $builder) use ($menu1) {
                $builder->where('menu_id', $menu1->id)->orderByDesc('lft');
            },
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

        // child1211
        self::assertCount(0, $children->get(3)->ancestors);
    }

    /**
     * Проверка корректности работы with() в билдере.
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
        ] = $this->createCategoryTree();

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
     * Проверка корректности работы with() в билдере.
     */
    public function testWithInBuilderWithScope(): void
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

        $children = MenuItem::scoped(['menu_id' => $menu1->id])
            ->whereRaw('rgt = lft + 1')
            ->with([
                'ancestors' => function (AncestorsRelation $builder) {
                    $builder->orderByDesc('lft');
                },
            ])
            ->orderBy('lft')
            ->get();

        self::assertCount(5, $children);

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

        // child12
        self::assertEquals($child12->id, $children->get(2)->id);
        self::assertCount(1, $children->get(2)->ancestors);
        self::assertEquals($root1->id, $children->get(2)->ancestors->get(0)->id);

        // child21
        self::assertEquals($child21->id, $children->get(3)->id);
        self::assertCount(1, $children->get(3)->ancestors);
        self::assertEquals($root2->id, $children->get(3)->ancestors->get(0)->id);

        // child31
        self::assertEquals($child31->id, $children->get(4)->id);
        self::assertCount(1, $children->get(4)->ancestors);
        self::assertEquals($root3->id, $children->get(4)->ancestors->get(0)->id);
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
        [
            'root1' => $root1,
            'child11' => $child11,
            'child111' => $child111,
            'child1111' => $child1111,
        ] = $this->createCategoryTree();

        $ancestors = $child1111->ancestors;

        self::assertCount(3, $ancestors);
        self::assertEquals($child111->id, $ancestors->get(0)->id);
        self::assertEquals($child11->id, $ancestors->get(1)->id);
        self::assertEquals($root1->id, $ancestors->get(2)->id);
    }

    /**
     * Проверка корректности работы lazy load.
     */
    public function testLazyLoadWithScope(): void
    {
        /** @var Category $root1 */
        /** @var Category $child11 */
        /** @var Category $child111 */
        /** @var Category $child1111 */
        [
            'menu1Root1' => $root1,
            'menu1Child11' => $child11,
            'menu1Child111' => $child111,
            'menu1Child1111' => $child1111,
        ] = $this->createMenuItemsTree();

        $ancestors = $child1111->ancestors;

        self::assertCount(3, $ancestors);
        self::assertEquals($child111->id, $ancestors->get(0)->id);
        self::assertEquals($child11->id, $ancestors->get(1)->id);
        self::assertEquals($root1->id, $ancestors->get(2)->id);
    }
}
