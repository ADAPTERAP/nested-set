<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

class UpdateTest extends TestCase
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
     * Попытка переместить элемент вниз по дереву.
     */
    public function testRebaseDownChild(): void
    {
        $root1 = Category::factory()->create(['name' => '1']);
        $root2 = Category::factory()->create(['name' => '2']);
        $root3 = Category::factory()->create(['name' => '3']);

        $child11 = Category::factory()->create(['parent_id' => $root1->id, 'name' => '1.1']);
        $child21 = Category::factory()->create(['parent_id' => $root2->id, 'name' => '2.1']);
        $child31 = Category::factory()->create(['parent_id' => $root3->id, 'name' => '3.1']);

        $target = Category::factory()->create(['parent_id' => $child11->id, 'name' => '1.1.1']);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $target->update(['parent_id' => $child21->id]);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 4, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 5, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 6, 'rgt' => 7, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
    }

    /**
     * Попытка переместить элемент вверх по дереву.
     */
    public function testRebaseUpChild(): void
    {
        $root1 = Category::factory()->create(['name' => '1']);
        $root2 = Category::factory()->create(['name' => '2']);
        $root3 = Category::factory()->create(['name' => '3']);

        $child11 = Category::factory()->create(['parent_id' => $root1->id, 'name' => '1.1']);
        $child21 = Category::factory()->create(['parent_id' => $root2->id, 'name' => '2.1']);
        $child31 = Category::factory()->create(['parent_id' => $root3->id, 'name' => '3.1']);

        $target = Category::factory()->create(['parent_id' => $child21->id, 'name' => '2.1.1']);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 4, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 5, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 6, 'rgt' => 7, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $target->update(['parent_id' => $child11->id]);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
    }

    /**
     * Попытка переместить поддерево вниз.
     */
    public function testRebaseDownSubTree(): void
    {
        $root1 = Category::factory()->create(['name' => '1']);
        $root2 = Category::factory()->create(['name' => '2']);
        $root3 = Category::factory()->create(['name' => '3']);

        $child11 = Category::factory()->create(['parent_id' => $root1->id, 'name' => '1.1']);
        $child21 = Category::factory()->create(['parent_id' => $root2->id, 'name' => '2.1']);
        $child31 = Category::factory()->create(['parent_id' => $root3->id, 'name' => '3.1']);

        $child111 = Category::factory()->create(['parent_id' => $child11->id, 'name' => '1.1.1']);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $child111->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $child11->update(['parent_id' => $child21->id]);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 1, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 2, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 3, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 4, 'rgt' => 7, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $child111->id, 'lft' => 5, 'rgt' => 6, 'depth' => 3]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
    }

    /**
     * Попытка переместить поддерево вверх.
     */
    public function testRebaseUpSubTree(): void
    {
        $root1 = Category::factory()->create(['name' => '1']);
        $root2 = Category::factory()->create(['name' => '2']);
        $root3 = Category::factory()->create(['name' => '3']);

        $child11 = Category::factory()->create(['parent_id' => $root1->id, 'name' => '1.1']);
        $child21 = Category::factory()->create(['parent_id' => $root2->id, 'name' => '2.1']);
        $child31 = Category::factory()->create(['parent_id' => $root3->id, 'name' => '3.1']);

        $child211 = Category::factory()->create(['parent_id' => $child21->id, 'name' => '2.1.1']);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 4, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 5, 'rgt' => 8, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $child211->id, 'lft' => 6, 'rgt' => 7, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $child21->update(['parent_id' => $child11->id]);

        self::assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 7, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 6, 'depth' => 1]);
        self::assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 2, 'rgt' => 5, 'depth' => 2]);
        self::assertDatabaseHas('categories', ['id' => $child211->id, 'lft' => 3, 'rgt' => 4, 'depth' => 3]);
        self::assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 8, 'rgt' => 9, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        self::assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
    }
}
