<?php

namespace App\Models;

use App\ModuleStyle;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A reusable block of Markdown, such as the site footer, placed in a page
 * with [module:slug].
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
    'style',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'style' => ModuleStyle::class,
        ];
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
     * The body as HTML in a container whose classes name the module and its style,
     * e.g. "module cta-module theme-dark".
     *
     * @param  Collection<int, Entry>|null  $menu  The menu, if it's already been loaded.
     */
    public function html(?Collection $menu = null): string
    {
        $classes = collect(['module', $this->slug.'-module', $this->style ? 'theme-'.$this->style->value : null])
            ->filter()
            ->implode(' ');

        return '<div class="'.e($classes).'">'."\n".$this->bodyHtml($menu)."\n".'</div>';
    }

    /**
     * The body as HTML, with its shortcodes filled in.
     *
     * Navigation is swapped in after the Markdown is converted, so the
     * converter never sees (or wraps in paragraphs) the menu's HTML.
     *
     * @param  Collection<int, Entry>|null  $menu  The menu, if it's already been loaded.
     */
    public function bodyHtml(?Collection $menu = null): string
    {
        $markdown = str_ireplace('[curr-year]', (string) now()->year, (string) $this->body);

        if (stripos($markdown, '[site-name]') !== false) {
            $markdown = str_ireplace('[site-name]', e(Theme::current()->site_name), $markdown);
        }

        $markdown = preg_replace('/\[navigation:(flat|multi)\]/i', '<!--navigation:$1-->', $markdown);

        $html = Str::markdown($markdown, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return preg_replace_callback(
            '/<!--navigation:(flat|multi)-->/i',
            function (array $matches) use (&$menu): string {
                return view('components.site.footer-nav', [
                    'items' => $menu ??= Entry::menu(),
                    'style' => strtolower($matches[1]),
                ])->render();
            },
            $html,
        );
    }
}
