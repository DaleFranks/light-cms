<?php

namespace App\Models;

use App\FooterStyle;
use App\LogoPosition;
use App\ThemeOptions;
use Closure;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * The site's look: branding, colors, fonts and layout options, edited on the
 * Theme screen and output as CSS variables that override the stylesheet defaults.
 */
#[Fillable([
    'site_name',
    'show_site_name',
    'logo_path',
    'favicon_path',
    'logo_position',
    'sticky_header',
    'color_primary',
    'color_secondary',
    'color_background',
    'color_text',
    'color_card_background',
    'color_header_background',
    'font_body',
    'font_headings',
    'border_radius',
    'content_width',
    'footer_style',
    'custom_css',
    'options',
])]
class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    /**
     * The original Light CMS look.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'show_site_name' => true,
        'logo_path' => null,
        'favicon_path' => null,
        'logo_position' => 'left',
        'sticky_header' => true,
        'color_primary' => '#2563eb',
        'color_secondary' => '#0f172a',
        'color_background' => '#f8fafc',
        'color_text' => '#1e293b',
        'color_card_background' => '#ffffff',
        'color_header_background' => '#ffffff',
        'font_body' => 'Noto Sans',
        'font_headings' => 'Noto Serif',
        'border_radius' => 12,
        'content_width' => 840,
        'footer_style' => 'dark',
        'custom_css' => null,
        'options' => null,
    ];

    /**
     * Google Fonts offered on the Theme screen, with their fallback font.
     *
     * @var array<string, string>
     */
    public const FONTS = [
        'Inter' => 'sans-serif',
        'Lato' => 'sans-serif',
        'Montserrat' => 'sans-serif',
        'Noto Sans' => 'sans-serif',
        'Open Sans' => 'sans-serif',
        'Outfit' => 'sans-serif',
        'Poppins' => 'sans-serif',
        'Roboto' => 'sans-serif',
        'Source Sans 3' => 'sans-serif',
        'Work Sans' => 'sans-serif',
        'Lora' => 'serif',
        'Merriweather' => 'serif',
        'Noto Serif' => 'serif',
        'Playfair Display' => 'serif',
        'Roboto Slab' => 'serif',
    ];

    /**
     * Google Fonts offered for code, all monospaced.
     *
     * @var list<string>
     */
    public const CODE_FONTS = ['Fira Code', 'IBM Plex Mono', 'JetBrains Mono', 'Roboto Mono', 'Source Code Pro'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_site_name' => 'boolean',
            'sticky_header' => 'boolean',
            'logo_position' => LogoPosition::class,
            'footer_style' => FooterStyle::class,
            'options' => 'array',
            'border_radius' => 'integer',
            'content_width' => 'integer',
        ];
    }

    /**
     * The site's theme, created with the default look the first time it's needed.
     */
    public static function current(): self
    {
        return self::query()->oldest('id')->firstOr(
            fn (): self => self::query()->create([
                ...self::DEFAULTS,
                'site_name' => config('app.name'),
            ]),
        );
    }

    /**
     * Put every setting except the site name back to the original look.
     */
    public function resetToDefaults(): void
    {
        $this->update(self::DEFAULTS);
    }

    /**
     * The value of one of the detailed settings in ThemeOptions: the one chosen
     * on the Theme screen, or the automatic one if it's empty.
     */
    public function option(string $key): mixed
    {
        $value = $this->options[$key] ?? null;

        return filled($value) ? $value : $this->automaticOption($key);
    }

    /**
     * What a detailed setting is when left empty, worked out from the main settings.
     */
    public function automaticOption(string $key): mixed
    {
        $default = ThemeOptions::all()[$key]['default'] ?? null;

        return $default instanceof Closure ? $default($this) : $default;
    }

    /**
     * The CSS variables that override the stylesheet's defaults: the main
     * settings, then every detailed setting (chosen or automatic).
     *
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        $variables = [
            '--color-primary' => $this->color_primary,
            '--color-primary-rgb' => implode(', ', self::rgb($this->color_primary)),
            '--color-secondary' => $this->color_secondary,
            '--color-bg-body' => $this->color_background,
            '--color-text-main' => $this->color_text,
            '--color-bg-card' => $this->color_card_background,
            '--header-bg-color' => self::rgba($this->color_header_background, 0.85),
            '--font-family-body' => self::fontStack($this->font_body),
            '--font-family-headers' => self::fontStack($this->font_headings),
            '--border-radius' => $this->border_radius.'px',
            '--border-radius-sm' => (int) round($this->border_radius / 2).'px',
            '--max-width-content' => $this->content_width.'px',
        ];

        foreach (ThemeOptions::all() as $key => $option) {
            $value = $this->option($key);

            $variables = [...$variables, ...(isset($option['css'])
                ? $option['css']($value, $this)
                : [$option['var'] => $this->optionCss($option, $value)])];
        }

        return $variables;
    }

    /**
     * A detailed setting's value as CSS.
     *
     * @param  array<string, mixed>  $option
     */
    private function optionCss(array $option, mixed $value): string
    {
        return match ($option['type']) {
            'color' => isset($option['alpha']) ? self::rgba((string) $value, $option['alpha']) : (string) $value,
            'px' => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'px',
            'font' => self::fontStack((string) $value),
            'code_font' => "'{$value}', monospace",
            'image' => filled($value) ? "url('".Storage::disk('uploads')->url((string) $value)."')" : 'none',
            'toggle' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Classes for the <body> element that switch on the header options.
     */
    public function bodyClasses(): string
    {
        return collect([
            'logo-'.$this->logo_position->value,
            $this->sticky_header ? null : 'header-not-sticky',
        ])->filter()->implode(' ');
    }

    /**
     * The custom CSS, made safe to place inside a <style> element.
     */
    public function safeCustomCss(): string
    {
        return str_replace('<', '', (string) $this->custom_css);
    }

    /**
     * The Google Fonts stylesheet URL for every font the site uses.
     */
    public function googleFontsUrl(): string
    {
        $families = collect([$this->font_body, $this->font_headings, ...array_map($this->option(...), ['font_quotes', 'font_display', 'font_code'])])
            ->unique()
            ->map(fn (string $font): string => 'family='.str_replace(' ', '+', $font).':wght@400;500;600;700')
            ->implode('&');

        return 'https://fonts.googleapis.com/css2?'.$families.'&display=swap';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('uploads')->url($this->logo_path) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon_path ? Storage::disk('uploads')->url($this->favicon_path) : null;
    }

    private static function fontStack(string $font): string
    {
        return "'{$font}', ".(self::FONTS[$font] ?? 'sans-serif');
    }

    /**
     * @return array{int, int, int}
     */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function rgba(string $hex, float $alpha): string
    {
        return 'rgba('.implode(', ', self::rgb($hex)).', '.$alpha.')';
    }

    public static function darken(string $hex, float $amount): string
    {
        return self::mix($hex, 0, $amount);
    }

    public static function lighten(string $hex, float $amount): string
    {
        return self::mix($hex, 255, $amount);
    }

    /**
     * Move each color channel the given fraction of the way towards the target.
     */
    private static function mix(string $hex, int $target, float $amount): string
    {
        return '#'.collect(self::rgb($hex))
            ->map(fn (int $channel): string => str_pad(dechex((int) round($channel + ($target - $channel) * $amount)), 2, '0', STR_PAD_LEFT))
            ->implode('');
    }
}
