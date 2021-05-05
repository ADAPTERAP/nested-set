<?php

namespace Adapterap\NestedSet\Traits;

use Adapterap\NestedSet\Relations\AncestorsRelation;
use Adapterap\NestedSet\Relations\DescendantsRelation;
use Adapterap\NestedSet\Relations\SiblingsRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Trait Relations
 *
 * @package Adapterap\NestedSet\Traits
 * @property-read $this|null $parent
 * @property-read Collection|$this[] $children
 * @property-read Collection|$this[] $descendants
 * @property-read Collection|$this[] $ancestors
 * @property-read Collection|$this[] $siblings
 */
trait Relations
{
    /**
     * Связь с родительской категорией.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, $this->getParentIdName(), $this->getKeyName());
    }

    /**
     * Связь с дочерними категориями.
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, $this->getParentIdName(), $this->getKeyName());
    }

    /**
     * Потомки.
     *
     * @return DescendantsRelation
     */
    public function descendants(): DescendantsRelation
    {
        /** @var Model $this */
        return new DescendantsRelation($this->newQuery(), $this);
    }

    /**
     * Предки.
     *
     * @return AncestorsRelation
     */
    public function ancestors(): AncestorsRelation
    {
        /** @var Model $this */
        return new AncestorsRelation($this->newQuery(), $this);
    }

    /**
     * Элементы, находящиеся на одном уровне с текущим элементом.
     *
     * @return SiblingsRelation
     */
    public function siblings(): SiblingsRelation
    {
        /** @var Model $this */
        return new SiblingsRelation($this->newQuery(), $this);
    }
}
