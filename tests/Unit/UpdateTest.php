<?php

namespace Adapterap\NestedSet\Tests\Unit;

use Adapterap\NestedSet\Exceptions\NestedSetCreateChildHasOtherScope;
use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\Models\Menu;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 * @coversNothing
 */
class UpdateTest extends TestCase
{
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

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $target->update(['parent_id' => $child21->id, 'name' => '2.1.1']);
        dump('sqlite version', DB::statement('select sqlite_version();'));

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 4, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 5, 'rgt' => 8, 'depth' => 1]);

        $this->assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 6, 'rgt' => 7, 'depth' => 2, 'name' => '2.1.1']);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
    }

    /**
     * Попытка переместить элемент по дереву, в родителя с другим scope.
     */
    public function testRebaseChildWithDifferentScope(): void
    {
        $menu1 = Menu::factory()->create();
        $menu2 = Menu::factory()->create();

        $root1 = MenuItem::factory()->create(['name' => '1', 'menu_id' => $menu1->id]);
        $root2 = MenuItem::factory()->create(['name' => '2', 'menu_id' => $menu1->id]);
        $root3 = MenuItem::factory()->create(['name' => '3', 'menu_id' => $menu1->id]);
        $root4 = MenuItem::factory()->create(['name' => '4', 'menu_id' => $menu2->id]);

        $child11 = MenuItem::factory()->create(['parent_id' => $root1->id, 'name' => '1.1', 'menu_id' => $menu1->id]);
        $child21 = MenuItem::factory()->create(['parent_id' => $root2->id, 'name' => '2.1', 'menu_id' => $menu1->id]);
        $child31 = MenuItem::factory()->create(['parent_id' => $root3->id, 'name' => '3.1', 'menu_id' => $menu1->id]);

        $target = MenuItem::factory()->create(['parent_id' => $child11->id, 'name' => '1.1.1', 'menu_id' => $menu1->id]);

        $this->assertDatabaseHas('menu_items', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $target->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        $this->assertDatabaseHas('menu_items', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $root4->id, 'lft' => 0, 'rgt' => 1, 'depth' => 0]);

        $this->expectException(NestedSetCreateChildHasOtherScope::class);

        $target->update(['parent_id' => $root4->id, 'name' => '4.1.1']);

        $this->assertDatabaseHas('menu_items', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $target->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        $this->assertDatabaseHas('menu_items', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $root4->id, 'lft' => 14, 'rgt' => 15, 'depth' => 0]);
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

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 4, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 5, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 6, 'rgt' => 7, 'depth' => 2]);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $target->update(['parent_id' => $child11->id, 'name' => '1.1.1']);

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $target->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2, 'name' => '1.1.1']);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
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

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $child111->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $child11->update(['parent_id' => $child21->id, 'name' => '2.1.1']);

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 1, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 2, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 3, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 4, 'rgt' => 7, 'depth' => 2, 'name' => '2.1.1']);
        $this->assertDatabaseHas('categories', ['id' => $child111->id, 'lft' => 5, 'rgt' => 6, 'depth' => 3]);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
    }

    /**
     * Попытка переместить поддерево, в родителя с другим scope.
     */
    public function testRebaseSubTreeWithDifferentScope(): void
    {
        $menu1 = Menu::factory()->create();
        $menu2 = Menu::factory()->create();

        $root1 = MenuItem::factory()->create(['name' => '1', 'menu_id' => $menu1->id]);
        $root2 = MenuItem::factory()->create(['name' => '2', 'menu_id' => $menu1->id]);
        $root3 = MenuItem::factory()->create(['name' => '3', 'menu_id' => $menu1->id]);
        $root4 = MenuItem::factory()->create(['name' => '4', 'menu_id' => $menu2->id]);

        $child11 = MenuItem::factory()->create(['parent_id' => $root1->id, 'name' => '1.1', 'menu_id' => $menu1->id]);
        $child21 = MenuItem::factory()->create(['parent_id' => $root2->id, 'name' => '2.1', 'menu_id' => $menu1->id]);
        $child31 = MenuItem::factory()->create(['parent_id' => $root3->id, 'name' => '3.1', 'menu_id' => $menu1->id]);

        $child111 = MenuItem::factory()->create(['parent_id' => $child11->id, 'name' => '1.1.1', 'menu_id' => $menu1->id]);

        $this->assertDatabaseHas('menu_items', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $child111->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        $this->assertDatabaseHas('menu_items', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $this->expectException(NestedSetCreateChildHasOtherScope::class);

        $child11->update(['parent_id' => $root4->id, 'name' => '4.1.1']);

        $this->assertDatabaseHas('menu_items', ['id' => $root1->id, 'lft' => 0, 'rgt' => 5, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child11->id, 'lft' => 1, 'rgt' => 4, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $child111->id, 'lft' => 2, 'rgt' => 3, 'depth' => 2]);
        $this->assertDatabaseHas('menu_items', ['id' => $root2->id, 'lft' => 6, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child21->id, 'lft' => 7, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('menu_items', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
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

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 3, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 2, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 4, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 5, 'rgt' => 8, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $child211->id, 'lft' => 6, 'rgt' => 7, 'depth' => 2]);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);

        $child21->update(['parent_id' => $child11->id, 'name' => '1.1.1']);

        $this->assertDatabaseHas('categories', ['id' => $root1->id, 'lft' => 0, 'rgt' => 7, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child11->id, 'lft' => 1, 'rgt' => 6, 'depth' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $child21->id, 'lft' => 2, 'rgt' => 5, 'depth' => 2, 'name' => '1.1.1']);
        $this->assertDatabaseHas('categories', ['id' => $child211->id, 'lft' => 3, 'rgt' => 4, 'depth' => 3]);
        $this->assertDatabaseHas('categories', ['id' => $root2->id, 'lft' => 8, 'rgt' => 9, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $root3->id, 'lft' => 10, 'rgt' => 13, 'depth' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $child31->id, 'lft' => 11, 'rgt' => 12, 'depth' => 1]);
    }
}
