<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Capsule\Manager;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Exceptions\NestedSetCreateChildHasOtherScope;

class CreateTest extends TestCase
{
    /**
     * Создание единственного элемента в дереве.
     */
    public function testCreateSingleRoot(): void
    {
        $root = Category::factory()->create();

        self::assertDatabaseHas('categories', [
            'id' => $root->id,
            'lft' => 0,
            'rgt' => 1,
            'depth' => 0,
            'parent_id' => null,
        ]);
    }

    /**
     * Создание очернего элемента в дереве.
     */
    public function testCreateSingleChild(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->create([
            'parent_id' => $root->id,
        ]);

        self::assertDatabaseHas('categories', [
            'id' => $root->id,
            'lft' => 0,
            'rgt' => 3,
            'depth' => 0,
            'parent_id' => null,
        ]);

        self::assertDatabaseHas('categories', [
            'id' => $child->id,
            'lft' => 1,
            'rgt' => 2,
            'depth' => 1,
            'parent_id' => $root->id,
        ]);
    }

    /**
     * Создание дочернего элемента в дереве.
     */
    public function testCreateSingleChildWithScope(): void
    {
        $root = MenuItem::factory()->create();
        $child = MenuItem::factory()->create([
            'parent_id' => $root->id,
            'menu_id' => $root->menu_id,
        ]);

        self::assertDatabaseHas('menu_items', [
            'id' => $root->id,
            'lft' => 0,
            'rgt' => 3,
            'depth' => 0,
            'parent_id' => null,
            'menu_id' => $root->menu_id,
        ]);

        self::assertDatabaseHas('menu_items', [
            'id' => $child->id,
            'lft' => 1,
            'rgt' => 2,
            'depth' => 1,
            'parent_id' => $root->id,
            'menu_id' => $child->menu_id,
        ]);
    }

    /**
     * Создание дочеренего элемента в дереве, с разным scope
     */
    public function testCreateSingleChildWithDifferentScope(): void
    {
        $root = MenuItem::factory()->create();

        self::assertDatabaseHas('menu_items', [
            'id' => $root->id,
            'lft' => 0,
            'rgt' => 1,
            'depth' => 0,
            'parent_id' => null,
            'menu_id' => $root->menu_id,
        ]);

        $this->expectException(NestedSetCreateChildHasOtherScope::class);

        $child = MenuItem::factory()->create([
            'parent_id' => $root->id,
        ]);

        self::assertDatabaseDoesNotHave('menu_items', [
            'id' => $child->id,
            'lft' => 1,
            'rgt' => 2,
            'depth' => 1,
            'parent_id' => $root->id,
        ]);
    }

    /**
     * Попытка добавить новый элемент существующее дерево.
     */
    public function testInsertChildToExistsTree(): void
    {
        Manager::connection()->enableQueryLog();

        $root1 = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $root1->id]);
        $root2 = Category::factory()->create();
        $child2 = Category::factory()->create(['parent_id' => $root2->id]);

        $result = Category::factory()->create([
            'parent_id' => $child1->id,
        ]);

        self::assertDatabaseHas('categories', [
            'id' => $root1->id,
            'lft' => 0,
            'rgt' => 5,
            'depth' => 0,
            'parent_id' => null,
        ]);

        self::assertDatabaseHas('categories', [
            'id' => $child1->id,
            'lft' => 1,
            'rgt' => 4,
            'depth' => 1,
            'parent_id' => $root1->id,
        ]);

        self::assertDatabaseHas('categories', [
            'id' => $result->id,
            'lft' => 2,
            'rgt' => 3,
            'depth' => 2,
            'parent_id' => $child1->id,
        ]);

        self::assertDatabaseHas('categories', [
            'id' => $root2->id,
            'lft' => 6,
            'rgt' => 9,
            'depth' => 0,
            'parent_id' => null,
        ]);

        self::assertDatabaseHas('categories', [
            'id' => $child2->id,
            'lft' => 7,
            'rgt' => 8,
            'depth' => 1,
            'parent_id' => $root2->id,
        ]);
    }
}
