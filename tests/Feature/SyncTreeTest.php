<?php

namespace Adapterap\NestedSet\Tests\Feature;

use Adapterap\NestedSet\Tests\Models\Category;
use Adapterap\NestedSet\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

class SyncTreeTest extends TestCase
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
     * Проверка корректности синхронизации дерева при пустой таблице.
     */
    public function testInsert(): void
    {
        $tree = $this->getRawTree();
        Category::syncTree($tree, ['name'], ['name']);
        $this->asserts($tree);
    }

    /**
     * Проверяет корректную синхронизацию дерева с удалением элементов.
     */
    public function testSync(): void
    {
        $categoryWillBeDelete = Category::factory()->create();

        $tree = $this->getRawTree();
        Category::syncTree($tree, ['name'], ['name']);
        $this->asserts($tree);

        self::assertDatabaseHas('categories', [
            'id' => $categoryWillBeDelete->id,
        ]);
        self::assertDatabaseDoesNotHave('categories', [
            'id' => $categoryWillBeDelete->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Рекурсивно проверяет корректность сохраненных данных в БД.
     *
     * @param array $tree
     * @param int $lft
     * @param int $depth
     */
    protected function asserts(array $tree, int $lft = 0, int $depth = 0): void
    {
        foreach ($tree as $item) {
            $rgt = $lft + $this->getCountDescendants($item) * 2 + 1;

            self::assertDatabaseHas('categories', [
                'name' => $item['name'],
                'lft' => $lft,
                'rgt' => $rgt,
                'depth' => $depth,
            ]);

            $children = $item['children'] ?? [];
            $this->asserts($children, $lft + 1, $depth + 1);

            $lft = $rgt + 1;
        }
    }

    /**
     * Определяет количество потомков указанного элемента.
     *
     * @param array $item
     *
     * @return int
     */
    protected function getCountDescendants(array $item): int
    {
        $count = 0;
        foreach ($item['children'] ?? [] as $child) {
            $count += $this->getCountDescendants($child) + 1;
        }

        return $count;
    }

    /**
     * Возвращает дерево в виде массива.
     *
     * @return array[]
     */
    protected function getRawTree(): array
    {
        return [
            [
                'name' => '1',
                'children' => [
                    [
                        'name' => '1.1',
                        'children' => [
                            [
                                'name' => '1.1.1',
                                'children' => [
                                    ['name' => '1.1.1.1'],
                                    ['name' => '1.1.1.2'],
                                    ['name' => '1.1.1.3'],
                                ],
                            ],
                            [
                                'name' => '1.1.2',
                                'children' => [
                                    ['name' => '1.1.2.1'],
                                    ['name' => '1.1.2.2'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '1.2',
                        'children' => [
                            [
                                'name' => '1.2.1',
                                'children' => [
                                    ['name' => '1.2.1.1'],
                                ],
                            ],
                            [
                                'name' => '1.2.2',
                                'children' => [
                                    ['name' => '1.2.2.1'],
                                    ['name' => '1.2.2.2'],
                                    ['name' => '1.2.2.3'],
                                    ['name' => '1.2.2.4'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => '2',
                'children' => [
                    [
                        'name' => '2.1',
                        'children' => [
                            [
                                'name' => '2.1.1',
                                'children' => [
                                    ['name' => '2.1.1.1'],
                                    ['name' => '2.1.1.2'],
                                ],
                            ],
                            [
                                'name' => '2.1.2',
                                'children' => [
                                    ['name' => '2.1.2.1'],
                                    ['name' => '2.1.2.2'],
                                    ['name' => '2.1.2.3'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '2.2',
                        'children' => [
                            ['name' => '2.2.1'],
                            [
                                'name' => '2.2.2',
                                'children' => [
                                    ['name' => '2.2.2.1'],
                                    ['name' => '2.2.2.2'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }
}
