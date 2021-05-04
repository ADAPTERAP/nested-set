<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DescendantsTest extends TestCase
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

    /**
     * Создает дерево элементов для тестирования.
     *
     * @return array
     */
    protected function createTree(): array
    {
        $root1 = Category::factory()->create(['name' => '1']);
        $root2 = Category::factory()->create(['name' => '2']);
        $root3 = Category::factory()->create(['name' => '3']);

        $child11 = Category::factory()->create(['parent_id' => $root1->id, 'name' => '1.1']);
        $child21 = Category::factory()->create(['parent_id' => $root2->id, 'name' => '2.1']);
        $child31 = Category::factory()->create(['parent_id' => $root3->id, 'name' => '3.1']);

        $child111 = Category::factory()->create(['parent_id' => $child11->id, 'name' => '1.1.1']);

        $child1111 = Category::factory()->create(['parent_id' => $child111->id, 'name' => '1.1.1.1']);
        $child1112 = Category::factory()->create(['parent_id' => $child111->id, 'name' => '1.1.1.2']);

        return compact(
            'root1',
            'root2',
            'root3',
            'child11',
            'child21',
            'child31',
            'child111',
            'child1111',
            'child1112',
        );
    }
}
