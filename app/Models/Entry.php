<?php

namespace App\Models;

use App\EntryLayout;
use App\EntryStatus;
use App\EntryType;
use App\Markdown;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A page, blog or blog post, written in Markdown.
 *
 * A blog is a page that lists its posts. Each post belongs to one blog, and its
 * path is the blog's path followed by the post's own slug, e.g. "news/v1-released".
 */
#[Fillable([
    'type',
    'title',
    'slug',
    'blog_id',
    'body',
    'layout',
    'status',
    'published_at',
    'nav_order',
    'menu_label',
    'parent_id',
    'meta_description',
    'meta_keywords',
    'author_id',
])]
class Entry extends Model
{
    /**
     * The path of the entry shown at the site's root address.
     */
    public const HOME_PATH = 'home';

    /**
     * Paths used by the app itself, which no entry may take.
     *
     * @var list<string>
     */
    public const RESERVED_PATHS = ['admin', 'filament', 'forms', 'livewire', 'setup', 'storage', 'up', 'uploads'];

    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'layout' => 'standard',
    ];

    protected static function booted(): void
    {
        static::saving(function (Entry $entry): void {
            if ($entry->type !== EntryType::Post) {
                $entry->blog_id = null;
            }

            $entry->path = $entry->buildPath();
        });

        static::saved(function (Entry $entry): void {
            if ($entry->type === EntryType::Blog && $entry->wasChanged('path')) {
                $entry->posts()->each(fn (Entry $post): bool => $post->save());
            }
        });

        // A published page that moves leaves a redirect behind, so old links and
        // search results still find it.
        static::updated(function (Entry $entry): void {
            if ($entry->wasChanged('path') && $entry->getRawOriginal('status') === EntryStatus::Published->value) {
                Redirect::forMovedPage((string) $entry->getRawOriginal('path'), $entry->path);
            }
        });

        static::deleting(function (Entry $entry): bool {
            return ! $entry->posts()->exists();
        });
    }

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
            'layout' => EntryLayout::class,
            'published_at' => 'datetime',
            'nav_order' => 'integer',
        ];
    }

    /**
     * Build the URL path for the given slug, as it would be for this entry.
     */
    public function buildPath(?string $slug = null): string
    {
        $slug ??= $this->slug;

        if ($this->type !== EntryType::Post || $this->blog_id === null) {
            return $slug;
        }

        $blogPath = Entry::query()->whereKey($this->blog_id)->value('path');

        return $blogPath.'/'.$slug;
    }

    /**
     * The address of the entry on the public site.
     */
    public function url(): string
    {
        return $this->path === self::HOME_PATH ? url('/') : url($this->path);
    }

    /**
     * The body as HTML, with its shortcodes filled in. A leading "# Heading" is
     * dropped, because the page title is already shown above the content.
     */
    public function bodyHtml(): string
    {
        $markdown = preg_replace('/^\s*#\s+.+?(\r?\n)+/', '', (string) $this->body, 1);

        return Markdown::toHtml($markdown, withBlocks: true);
    }

    /**
     * The first three sentences of the body as plain text, for blog feeds.
     */
    public function excerpt(): string
    {
        $markdown = preg_replace([
            '/!\[.*?\]\(.*?\)/',
            '/^#+\s+.*$/m',
            '/\[(module|card|cards|youtube|image|slider):[^\]]*\]/i',
        ], '', (string) $this->body);

        $text = trim(html_entity_decode(strip_tags(Str::markdown($markdown)), ENT_QUOTES, 'UTF-8'));
        $sentences = preg_split('/(?<=[.?!])\s+/u', $text) ?: [];

        return implode(' ', array_slice($sentences, 0, 3));
    }

    /**
     * The file or address of the first image in the body, placed with
     * [image:...] or ![description](...), whichever comes first.
     */
    public function firstImageReference(): ?string
    {
        if (! preg_match('/\[image:\s*([^|\]]+)|!\[[^\]]*\]\(\s*([^)\s]+)/i', (string) $this->body, $match)) {
            return null;
        }

        return trim($match[2] ?? '') ?: trim($match[1]);
    }

    /**
     * The address of each post's first image, for the pictures in a blog feed,
     * keyed by post id. Library images are found in one query; a post whose
     * first image isn't in the library (or has none) is left out.
     *
     * @param  iterable<Entry>  $posts
     * @return array<int, string>
     */
    public static function previewImageUrls(iterable $posts): array
    {
        $references = collect($posts)
            ->mapWithKeys(fn (Entry $post): array => [$post->id => $post->firstImageReference()])
            ->filter();

        $images = Image::query()
            ->whereIn('filename', $references->map(fn (string $reference): ?string => Image::nameFromReference($reference))->filter()->unique()->values())
            ->get()
            ->keyBy('filename');

        return $references
            ->map(fn (string $reference): ?string => preg_match('#^https?://#i', $reference)
                ? $reference
                : $images->get((string) Image::nameFromReference($reference))?->url())
            ->filter()
            ->all();
    }

    /**
     * Published pages and blogs that are in the menu, with their children
     * nested under them in menu order.
     *
     * @return Collection<int, Entry>
     */
    public static function menu(): Collection
    {
        $items = self::query()
            ->published()
            ->whereNot('type', EntryType::Post)
            ->whereNotNull('nav_order')
            ->orderBy('nav_order')
            ->orderBy('title')
            ->get(['id', 'type', 'title', 'menu_label', 'path', 'parent_id', 'nav_order']);

        $itemsById = $items->keyBy('id');
        $childrenByParent = $items->groupBy('parent_id');

        foreach ($items as $item) {
            $item->setRelation('children', $childrenByParent->get($item->id, new Collection));
        }

        return $items
            ->filter(fn (Entry $item): bool => $item->parent_id === null || ! $itemsById->has($item->parent_id))
            ->values();
    }

    /**
     * The entry's name in menus: its short menu label if it has one, otherwise its title.
     */
    public function menuLabel(): string
    {
        return filled($this->menu_label) ? $this->menu_label : $this->title;
    }

    /**
     * Whether this menu item, or anything under it, is the given path.
     */
    public function isActiveFor(string $path): bool
    {
        return $this->path === $path
            || $this->children->contains(fn (Entry $child): bool => $child->isActiveFor($path));
    }

    /**
     * The blog a post belongs to.
     *
     * @return BelongsTo<Entry, $this>
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'blog_id');
    }

    /**
     * The posts in a blog.
     *
     * @return HasMany<Entry, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Entry::class, 'blog_id');
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
