<?php

namespace Adapterap\NestedSet\Tests;

use Adapterap\NestedSet\Tests\Models\Menu;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Handlers\NestedSetSyncTree;
use Adapterap\NestedSet\Tests\Models\Attribute;
use Adapterap\NestedSet\Tests\Models\Category;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use JsonException;
use Symfony\Component\VarDumper\VarDumper;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Позволяет не настраивать несколько раз Manager.
     *
     * @var bool
     */
    protected static bool $isConfiguredManager = false;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$isConfiguredManager === true) {
            return;
        }

        self::$isConfiguredManager = true;

        $manager = new Manager();
        $manager->addConnection([
            'driver' => 'mysql',
            'host' => 'mysqldb',
            'port' => 3317,
            'database' => 'nested',
            'username' => 'root',
            'password' => 'password',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);

        $manager->setEventDispatcher(new Dispatcher(new Container));

        // Позволяет использовать статичные вызовы при работе с Capsule.
        $manager->setAsGlobal();
        $manager->bootEloquent();

        Category::setConnectionResolver(
            new ConnectionResolver(['default' => $manager->getConnection('default')])
        );
        Attribute::setConnectionResolver(
            new ConnectionResolver(['default' => $manager->getConnection('default')])
        );

        $schema = Manager::schema('default');

        $schema->dropIfExists('categories');
        $schema->create('categories', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('parent_id')
                ->nullable();
            $table->unsignedBigInteger('lft');
            $table->unsignedBigInteger('rgt');
            $table->unsignedBigInteger('depth');
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
        });

        $schema->dropIfExists('attributes');
        $schema->create('attributes', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('place');
            $table->unsignedBigInteger('parent_id')
                ->nullable();
            $table->unsignedBigInteger('lft');
            $table->unsignedBigInteger('rgt');
            $table->unsignedBigInteger('depth');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('attributes')
                ->cascadeOnDelete();
        });

        $schema->disableForeignKeyConstraints();

        if ($schema->hasTable('menus')) {
            $schema->disableForeignKeyConstraints();
            Manager::table('menus')->truncate();

            $schema->drop('menus');
            $schema->enableForeignKeyConstraints();
        }

        $schema->create('menus', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        if ($schema->hasTable('menu_items')) {
            $schema->disableForeignKeyConstraints();
            Manager::table('menu_items')->truncate();

            $schema->drop('menu_items');
            $schema->enableForeignKeyConstraints();
        }

        $schema->create('menu_items', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('parent_id')
                  ->nullable();
            $table->unsignedBigInteger('lft');
            $table->unsignedBigInteger('rgt');
            $table->unsignedBigInteger('depth');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('menu_items')
                  ->cascadeOnDelete();

            $table->foreign('menu_id')
                  ->references('id')
                  ->on('menus')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Проверяет наличие данных в БД.
     *
     * @param string $table
     * @param array $filters
     */
    public static function assertDatabaseHas(string $table, array $filters): void
    {
        self::assertTrue(
            Manager::table($table)
                ->where($filters)
                ->exists()
        );
    }

    /**
     * Проверяет отсутствие данных в БД.
     *
     * @param string $table
     * @param array $filters
     */
    public static function assertDatabaseDoesNotHave(string $table, array $filters): void
    {
        self::assertFalse(
            Manager::table($table)
                ->where($filters)
                ->exists()
        );
    }

    /**
     * This method is called before each test.
     */
    public function setUp(): void
    {
        parent::setUp();

        $schema = Manager::schema('default');
        $schema->disableForeignKeyConstraints();

        Manager::table('categories')->truncate();
        Manager::table('menu_items')->delete();
        Manager::table('menus')->truncate();

        $schema->enableForeignKeyConstraints();
    }

    /**
     * Создает дерево категорий для тестирования.
     *
     * @return array
     */
    protected function createCategoryTree(): array
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

    /**
     * Создает дерево атрибутов для тестирования.
     *
     * @return void
     * @throws JsonException
     */
    protected function createAttributeTree(): void
    {
        $handler = new NestedSetSyncTree(new Attribute(), null, ['name'], []);
        $handler->sync(
            json_decode(file_get_contents(__DIR__ . '/data/attributes.json'), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Создает дерево пунктов меню для тестирования
     *
     * @return array
     */
    protected function createMenuItemsTree(): array
    {
        $menu1 = Menu::factory()->create(['name' => '1']);
        $menu2 = Menu::factory()->create(['name' => '2']);

        // menu 1
        $menu1Root1 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'name' => '11']);
        $menu1Root2 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'name' => '12']);
        $menu1Root3 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'name' => '13']);

        $menu1Child11 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'parent_id' => $menu1Root1->id, 'name' => '11.1']);
        $menu1Child12 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'parent_id' => $menu1Root1->id, 'name' => '11.2']);
        $menu1Child21 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'parent_id' => $menu1Root2->id, 'name' => '12.1']);
        $menu1Child31 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'parent_id' => $menu1Root3->id, 'name' => '13.1']);

        $menu1Child111 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'parent_id' => $menu1Child11->id, 'name' => '11.1.1']);

        $menu1Child1111 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'parent_id' => $menu1Child111->id, 'name' => '11.1.1.1']);
        $menu1Child1112 = MenuItem::factory()->create(['menu_id' => $menu1->id, 'parent_id' => $menu1Child111->id, 'name' => '11.1.1.2']);

        // menu 2
        $menu2Root1 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'name' => '21']);
        $menu2Root2 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'name' => '22']);
        $menu2Root3 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'name' => '23']);

        $menu2Child11 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'parent_id' => $menu2Root1->id, 'name' => '21.1']);
        $menu2Child12 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'parent_id' => $menu2Root1->id, 'name' => '21.2']);
        $menu2Child21 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'parent_id' => $menu2Root2->id, 'name' => '22.1']);
        $menu2Child31 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'parent_id' => $menu2Root3->id, 'name' => '23.1']);

        $menu2Child121 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'parent_id' => $menu2Child12->id, 'name' => '21.2.1']);

        $menu2Child1211 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'parent_id' => $menu2Child121->id, 'name' => '21.2.1.1']);
        $menu2Child1212 = MenuItem::factory()->create(['menu_id' => $menu2->id, 'parent_id' => $menu2Child121->id, 'name' => '21.2.1.2']);

        return compact(
            'menu1',
            'menu2',
            'menu1Root1',
            'menu1Root2',
            'menu1Root3',
            'menu1Child11',
            'menu1Child12',
            'menu1Child21',
            'menu1Child31',
            'menu1Child111',
            'menu1Child1111',
            'menu1Child1112',
            'menu2Root1',
            'menu2Root2',
            'menu2Root3',
            'menu2Child11',
            'menu2Child12',
            'menu2Child21',
            'menu2Child31',
            'menu2Child121',
            'menu2Child1211',
            'menu2Child1212',
        );
    }
}

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            VarDumper::dump($v);
        }

        exit(1);
    }
}
