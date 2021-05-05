<?php

namespace Adapterap\NestedSet\Tests;

use Adapterap\NestedSet\Tests\Models\Category;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Symfony\Component\VarDumper\VarDumper;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static $isConfiguredManager = false;

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

        $schema = Manager::schema('default');
        $schema->dropIfExists('categories');
        $schema->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
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
    }

    public static function assertDatabaseHas(string $table, array $filters): void
    {
        self::assertTrue(
            Capsule::table($table)
                ->where($filters)
                ->exists()
        );
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

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            VarDumper::dump($v);
        }

        exit(1);
    }
}
