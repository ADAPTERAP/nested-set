## NestedSet

Библиотека для работы со связью NestedSet для Laravel.

При реализации данной библиотеки главными критериями были:
- Чем меньше запросов в БД - тем лучше (сейчас - три при создании и один при обновлении)
- Невозможность переопределения Builder'ов (библиотека использует трейты)

### Ограничения

* PHP 7.4+
* MySQL 8+
* Laravel 8+

### Установка и настройка

```shell
composer require adapterap/nested-set
```

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Adapterap\NestedSet\NestedSetModel;

class Category extends Model {
    use NestedSetModel;
    
    /**
     * Название колонки с индексом вложенности слева.
     *
     * @var string
     */
    public string $lftName = 'lft';

    /**
     * Название колонки с индексом вложенности справа.
     *
     * @var string
     */
    public string $rgtName = 'rgt';

    /**
     * Название колонки с идентификатором родительской категории.
     *
     * @var string
     */
    public string $parentIdName = 'parent_id';

    /**
     * Название колонки со значением глубины вложенности.
     *
     * @var string
     */
    public string $depthName = 'depth';
}
```

### Пример использования

```php
use App\Models\Category;

$category = Category::query()->create([
    // ...
]);

// Родительская категория
$parentLazyLoad = $category->parent;
$parentFromBuilder = $category->parent()->first();

// Дочерние категории
$childrenLazyLoad = $category->children;
$childrenFromBuilder = $category->children()->get();

// Потомки
$descendantsLazyLoad = $category->descendants;
$descendantsFromBuilder = $category->descendants()->get();

// Предки
$ancestorsLazyLoad = $category->ancestors;
$ancestorsFromBuilder = $category->ancestors()->get();

// Категории, находящиеся на том же уровне (братья и сестры)
$siblingsLazyLoad = $category->siblings;
$siblingsFromBuilder = $category->siblings()->get();
```


### ToDo

- Добавить возможность создавать все дерево сразу (пример: `Category::createTree()`)
- Проверить корректность создания поддерева (сейчас работает только одиночное создание)
- Реализовать возможность по исправлению существующих деревьев
- Добавить поддержку хранения нескольких деревьев в одной таблице
- Добавить поддержку множественного primary key
- Добавить возможность вставки элемента до/после указанного элемента (без участия parent_id)

### Тесты

```shell
./vendor/bin/phpunit
```
