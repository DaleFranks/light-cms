<?php

namespace App\Models;

use App\EntryStatus;
use App\EntryType;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A page or blog post, written in Markdown.
 */
#[Fillable([
    'type',
    'title',
    'slug',
    'body',
    'status',
    'published_at',
    'nav_order',
    'parent_id',
    'meta_description',
    'meta_keywords',
    'author_id',
])]
class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EntryType::class,
            'status' => EntryStatus::class,
            'published_at' => 'datetime',
            'nav_order' => 'integer',
        ];
    }

    /**
     * The user who wrote the entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The entry this one sits under in the navigation menu.
     *
     * @return BelongsTo<Entry, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'parent_id');
    }

    /**
     * The entries that sit under this one in the navigation menu.
     *
     * @return HasMany<Entry, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Entry::class, 'parent_id');
    }

    /**
     * Only entries that are published and whose publish date has arrived.
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', EntryStatus::Published)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
