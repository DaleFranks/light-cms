<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A reusable block of Markdown, such as the site footer.
 *
 * The body may use these shortcodes:
 * - [navigation:flat] for a row of links to the top-level menu items
 * - [navigation:multi] for a column per top-level menu item, with its children listed under it
 * - [curr-year] for the current year
 * - [site-name] for the site name set on the Theme screen
 */
#[Fillable([
    'name',
    'slug',
    'body',
])]
class Module extends Model
{
    /**
     * The slug of the module shown at the bottom of every page.
     */
    public const FOOTER_SLUG = 'footer';

    /**
     * What the footer says until it's edited.
     */
    public const FOOTER_DEFAULT_BODY = "[navigation:flat]\n\n&copy; [curr-year] [site-name]. All rights reserved.";

    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Module $module): bool {
            return ! $module->isFooter();
        });
    }

    /**
     * The site footer, created with the default text the first time it's needed.
     */
    public static function footer(): self
    {
        return self::query()->where('slug', self::FOOTER_SLUG)->firstOr(
            fn (): self => self::query()->create([
                'name' => 'Footer',
                'slug' => self::FOOTER_SLUG,
                'body' => self::FOOTER_DEFAULT_BODY,
            ]),
        );
    }

    public function isFooter(): bool
    {
        return $this->slug === self::FOOTER_SLUG;
    }

    /**
     * The body as HTML, with its shortcodes filled in.
     *
     * Navigation is swapped in after the Markdown is converted, so the
     * converter never sees (or wraps in paragraphs) the menu's HTML.
     *
     * @param  Collection<int, Entry>  $menu
     */
    public function bodyHtml(Collection $menu, string $siteName): string
    {
        $markdown = str_ireplace(
            ['[curr-year]', '[site-name]'],
            [(string) now()->year, e($siteName)],
            (string) $this->body,
        );

        $markdown = preg_replace('/\[navigation:(flat|multi)\]/i', '<!--navigation:$1-->', $markdown);

        $html = Str::markdown($markdown, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return preg_replace_callback(
            '/<!--navigation:(flat|multi)-->/i',
            fn (array $matches): string => view('components.site.footer-nav', [
                'items' => $menu,
                'style' => strtolower($matches[1]),
            ])->render(),
            $html,
        );
    }
}
