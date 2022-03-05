<?php

namespace Adapterap\NestedSet\Tests\Unit;

use Adapterap\NestedSet\Tests\Models\Menu;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Adapterap\NestedSet\Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class SyncTreeScopeTest extends TestCase
{
    /**
     * Проверка корректности синхронизации дерева при пустой таблице.
     */
    public function testInsert(): void
    {
        $tree = $this->getRawTree();
        MenuItem::syncTree($tree, ['name'], ['name']);
        $this->asserts($tree);
    }

    /**
     * Проверяет корректную синхронизацию дерева с удалением элементов.
     */
    public function testSync(): void
    {
        $menuItemWillBeDelete = MenuItem::factory()->create();

        $tree = $this->getRawTree();
        MenuItem::syncTree($tree, ['name'], ['name']);
        $this->asserts($tree);

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItemWillBeDelete->id,
        ]);
        $this->assertDatabaseMissing('menu_items', [
            'id' => $menuItemWillBeDelete->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Рекурсивно проверяет корректность сохраненных данных в БД.
     *
     * @param array $tree
     * @param int   $lft
     * @param int   $depth
     */
    protected function asserts(array $tree, int $lft = 0, int $depth = 0): void
    {
        $previousMenuId = null;

        foreach ($tree as $item) {
            if ($depth === 0 && $previousMenuId !== $item['menu_id']) {
                $lft = 0;
            }

            $previousMenuId = $item['menu_id'];
            $rgt = $lft + $this->getCountDescendants($item) * 2 + 1;

            $this->assertDatabaseHas('menu_items', [
                'name' => $item['name'],
                'lft' => $lft,
                'rgt' => $rgt,
                'depth' => $depth,
                'menu_id' => $item['menu_id'],
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
        $menu1 = Menu::factory()->create();
        $menu2 = Menu::factory()->create();

        return [
            [
                'name' => '1',
                'menu_id' => $menu1->id,
                'children' => [
                    [
                        'name' => '1.1',
                        'menu_id' => $menu1->id,
                        'children' => [
                            [
                                'name' => '1.1.1',
                                'menu_id' => $menu1->id,
                                'children' => [
                                    ['name' => '1.1.1.1', 'menu_id' => $menu1->id],
                                    ['name' => '1.1.1.2', 'menu_id' => $menu1->id],
                                    ['name' => '1.1.1.3', 'menu_id' => $menu1->id],
                                ],
                            ],
                            [
                                'name' => '1.1.2',
                                'menu_id' => $menu1->id,
                                'children' => [
                                    ['name' => '1.1.2.1', 'menu_id' => $menu1->id],
                                    ['name' => '1.1.2.2', 'menu_id' => $menu1->id],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '1.2',
                        'menu_id' => $menu1->id,
                        'children' => [
                            [
                                'name' => '1.2.1',
                                'menu_id' => $menu1->id,
                                'children' => [
                                    ['name' => '1.2.1.1', 'menu_id' => $menu1->id],
                                ],
                            ],
                            [
                                'name' => '1.2.2',
                                'menu_id' => $menu1->id,
                                'children' => [
                                    ['name' => '1.2.2.1', 'menu_id' => $menu1->id],
                                    ['name' => '1.2.2.2', 'menu_id' => $menu1->id],
                                    ['name' => '1.2.2.3', 'menu_id' => $menu1->id],
                                    ['name' => '1.2.2.4', 'menu_id' => $menu1->id],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => '2',
                'menu_id' => $menu1->id,
                'children' => [
                    [
                        'name' => '2.1',
                        'menu_id' => $menu1->id,
                        'children' => [
                            [
                                'name' => '2.1.1',
                                'menu_id' => $menu1->id,
                                'children' => [
                                    ['name' => '2.1.1.1', 'menu_id' => $menu1->id],
                                    ['name' => '2.1.1.2', 'menu_id' => $menu1->id],
                                ],
                            ],
                            [
                                'name' => '2.1.2',
                                'menu_id' => $menu1->id,
                                'children' => [
                                    ['name' => '2.1.2.1', 'menu_id' => $menu1->id],
                                    ['name' => '2.1.2.2', 'menu_id' => $menu1->id],
                                    ['name' => '2.1.2.3', 'menu_id' => $menu1->id],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '2.2',
                        'menu_id' => $menu1->id,
                        'children' => [
                            ['name' => '2.2.1', 'menu_id' => $menu1->id],
                            [
                                'name' => '2.2.2',
                                'menu_id' => $menu1->id,
                                'children' => [
                                    ['name' => '2.2.2.1', 'menu_id' => $menu1->id],
                                    ['name' => '2.2.2.2', 'menu_id' => $menu1->id],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => '21',
                'menu_id' => $menu2->id,
                'children' => [
                    [
                        'name' => '21.1',
                        'menu_id' => $menu2->id,
                        'children' => [
                            [
                                'name' => '21.1.1',
                                'menu_id' => $menu2->id,
                                'children' => [
                                    ['name' => '21.1.1.1', 'menu_id' => $menu2->id],
                                    ['name' => '21.1.1.2', 'menu_id' => $menu2->id],
                                ],
                            ],
                            [
                                'name' => '21.1.2',
                                'menu_id' => $menu2->id,
                                'children' => [
                                    ['name' => '21.1.2.1', 'menu_id' => $menu2->id],
                                    ['name' => '21.1.2.2', 'menu_id' => $menu2->id],
                                    ['name' => '21.1.2.3', 'menu_id' => $menu2->id],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '21.2',
                        'menu_id' => $menu2->id,
                        'children' => [
                            ['name' => '21.2.1', 'menu_id' => $menu2->id],
                            [
                                'name' => '21.2.2',
                                'menu_id' => $menu2->id,
                                'children' => [
                                    ['name' => '21.2.2.1', 'menu_id' => $menu2->id],
                                    ['name' => '21.2.2.2', 'menu_id' => $menu2->id],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
