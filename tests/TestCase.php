<?php

namespace Adapterap\NestedSet\Tests;

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
            'host' => 'localhost',
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
        $schema->create('categories', function (Blueprint $table) {
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
        $schema->create('attributes', function (Blueprint $table) {
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

        Manager::table('categories')->truncate();
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
