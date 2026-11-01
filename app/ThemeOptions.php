<?php

namespace App;

use App\Models\Theme;
use Closure;

/**
 * The detailed look settings on the Theme screen, beyond the main ones.
 *
 * Every one starts empty, which means "automatic": its value is worked out
 * from the main settings (most often the primary and secondary colors), so a
 * site looks right with only those set. Entering a value overrides just that
 * one setting; clearing it goes back to automatic.
 *
 * Each setting has:
 * - tab and section: where it appears on the Theme screen
 * - label, and optionally help
 * - type: color (hex), rgba (a color that may be see-through), px, seconds,
 *   font, code_font, image, toggle or choice (with choices)
 * - default: its automatic value, or a closure working it out from the theme
 * - var: the CSS variable it sets, or css: a closure returning the variables
 *   for settings that set more than one, or combine with another (gradients)
 * - alpha: for colors shown see-through on the site, such as the header
 * - footer_style: for footer colors, the footer style they belong to
 */
class ThemeOptions
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        static $options;

        return $options ??= [
            ...self::branding(),
            ...self::colors(),
            ...self::header(),
            ...self::fonts(),
            ...self::cards(),
            ...self::boxStyles(),
            ...self::footer(),
            ...self::slider(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function inSection(string $tab, string $section): array
    {
        return array_filter(self::all(), fn (array $option): bool => $option['tab'] === $tab && $option['section'] === $section);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function branding(): array
    {
        return [
            'logo_height' => self::px('Branding', 'Logo', 'Logo height', 38, '--logo-height', 16, 200),
            'logo_max_width' => self::px('Branding', 'Logo', 'Logo maximum width', 250, '--logo-max-width', 40, 600,
                'Stops a wide logo from crowding the menu.'),
            'logo_hover_animation' => [
                'tab' => 'Branding', 'section' => 'Logo', 'label' => 'Tilt the logo slightly when pointed at',
                'type' => 'toggle', 'default' => true, 'var' => '--logo-hover-animation-enabled',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function colors(): array
    {
        return [
            'color_primary_hover' => self::color('Colors', 'Main colors', 'Primary, when pointed at',
                fn (Theme $theme): string => Theme::darken($theme->color_primary, 0.12), '--color-primary-hover',
                help: 'Buttons and links when the pointer is over them.'),
            'color_secondary_hover' => self::color('Colors', 'Main colors', 'Secondary, when pointed at',
                fn (Theme $theme): string => Theme::lighten($theme->color_secondary, 0.1), '--color-secondary-hover'),
            'color_text_muted' => self::color('Colors', 'Text', 'Muted text', '#64748b', '--color-text-muted',
                help: 'Captions, dates and other secondary text.'),
            'color_text_light' => self::color('Colors', 'Text', 'Faint text', '#94a3b8', '--color-text-light'),
            'color_link' => self::color('Colors', 'Text', 'Links',
                fn (Theme $theme): string => $theme->color_primary, '--color-link'),
            'color_link_hover' => self::color('Colors', 'Text', 'Links, when pointed at',
                fn (Theme $theme): string => $theme->option('color_primary_hover'), '--color-link-hover'),
            'color_border' => self::color('Colors', 'Text', 'Lines and borders', '#e2e8f0', '--color-border'),
            'color_code_background' => self::color('Colors', 'Code', 'Code background', '#f1f5f9', '--color-code-bg'),
            'color_code_text' => self::color('Colors', 'Code', 'Code text', '#0f172a', '--color-code-text'),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function header(): array
    {
        return [
            'header_background_scrolled' => self::color('Header', 'Header bar', 'Background, once scrolled',
                fn (Theme $theme): string => $theme->color_header_background, '--header-bg-color-scrolled', alpha: 0.95),
            'header_link' => self::color('Header', 'Header bar', 'Menu links',
                fn (Theme $theme): string => $theme->option('color_text_muted'), '--header-nav-link-color'),
            'header_link_hover' => self::color('Header', 'Header bar', 'Menu links, when pointed at',
                fn (Theme $theme): string => $theme->color_primary, '--header-nav-link-hover-color'),
            'header_link_hover_background' => self::rgba('Header', 'Header bar', 'Behind menu links, when pointed at',
                fn (Theme $theme): string => Theme::rgba($theme->color_primary, 0.06), '--header-nav-link-hover-bg'),
            'header_logo_text' => self::color('Header', 'Header bar', 'Site name',
                fn (Theme $theme): string => $theme->color_secondary, '--header-logo-text-color'),
            'header_menu_button' => self::color('Header', 'Header bar', 'Menu button (on phones)',
                fn (Theme $theme): string => $theme->color_secondary, '--header-nav-toggle-color'),
            'header_height' => self::px('Header', 'Header bar', 'Height', 70, '--header-height', 40, 200),
            'header_height_scrolled' => self::px('Header', 'Header bar', 'Height, once scrolled',
                fn (Theme $theme): float => (float) $theme->option('header_height'), '--header-height-scrolled', 40, 200),
            'header_background_image' => [
                'tab' => 'Header', 'section' => 'Header bar', 'label' => 'Background image', 'type' => 'image', 'default' => null,
                'help' => 'Optional. Covers the header behind the logo and menu.', 'var' => '--header-bg-image',
            ],
            'mobile_menu_background' => self::color('Header', 'Menu on phones', 'Background',
                fn (Theme $theme): string => $theme->color_card_background, '--mobile-nav-bg-color'),
            'mobile_menu_link' => self::color('Header', 'Menu on phones', 'Links',
                fn (Theme $theme): string => $theme->option('color_text_muted'), '--mobile-nav-link-color'),
            'mobile_menu_link_hover' => self::color('Header', 'Menu on phones', 'Links, when tapped or current',
                fn (Theme $theme): string => $theme->color_primary, '--mobile-nav-link-hover-color'),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function fonts(): array
    {
        return [
            'font_quotes' => self::font('Quotes', 'Roboto Slab', '--font-family-quotes'),
            'font_display' => self::font('Large display headings', 'Outfit', '--font-family-display'),
            'font_code' => [
                'tab' => 'Fonts', 'section' => 'Fonts', 'label' => 'Code', 'type' => 'code_font',
                'default' => 'Fira Code', 'var' => '--font-family-code',
            ],
            'font_size_base' => self::px('Fonts', 'Text sizes', 'Body text', 17, '--font-size-base', 12, 28),
            'font_size_h1' => self::px('Fonts', 'Text sizes', 'Heading 1', 36, '--font-size-h1', 12, 96),
            'font_size_h2' => self::px('Fonts', 'Text sizes', 'Heading 2', 26, '--font-size-h2', 12, 96),
            'font_size_h3' => self::px('Fonts', 'Text sizes', 'Heading 3', 20, '--font-size-h3', 12, 96),
            'font_size_h4' => self::px('Fonts', 'Text sizes', 'Heading 4', 17.6, '--font-size-h4', 12, 96),
            'font_size_h5' => self::px('Fonts', 'Text sizes', 'Heading 5', 16, '--font-size-h5', 10, 96),
            'font_size_h6' => self::px('Fonts', 'Text sizes', 'Heading 6', 14, '--font-size-h6', 10, 96),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function cards(): array
    {
        return [
            'card_title' => self::color('Cards', 'Cards', 'Titles',
                fn (Theme $theme): string => $theme->color_secondary, '--color-card-title'),
            'card_text' => self::color('Cards', 'Cards', 'Text', '#475569', '--color-card-text'),
            'card_title_size' => self::px('Cards', 'Cards', 'Title size', 20, '--font-size-card-title', 12, 48),
            'card_text_size' => self::px('Cards', 'Cards', 'Text size', 15.2, '--font-size-card-text', 10, 32),
        ];
    }

    /**
     * The Dark, Light and Primary panel styles that modules and cards can use.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function boxStyles(): array
    {
        $gradient = fn (string $start, string $end): Closure => fn (mixed $value, Theme $theme): array => [
            '--theme-'.explode('_', $start)[0].'-bg' => 'linear-gradient(135deg, '.$theme->option($start).' 0%, '.$theme->option($end).' 100%)',
        ];

        return [
            'dark_background_start' => [...self::color('Panel styles', 'Dark', 'Background (top left)', '#1e3a8a', null),
                'css' => $gradient('dark_background_start', 'dark_background_end')],
            'dark_background_end' => [...self::color('Panel styles', 'Dark', 'Background (bottom right)',
                fn (Theme $theme): string => $theme->color_secondary, null), 'css' => fn (): array => []],
            'dark_title' => self::color('Panel styles', 'Dark', 'Headings', '#ffffff', '--theme-dark-title'),
            'dark_text' => self::color('Panel styles', 'Dark', 'Text', '#ffffff', '--theme-dark-text'),
            'dark_text_muted' => self::color('Panel styles', 'Dark', 'Paragraphs',
                fn (Theme $theme): string => $theme->option('color_text_light'), '--theme-dark-text-muted'),
            'dark_border' => self::rgba('Panel styles', 'Dark', 'Border', 'rgba(255, 255, 255, 0.15)', '--theme-dark-border'),
            'dark_accent' => self::rgba('Panel styles', 'Dark', 'Glow',
                fn (Theme $theme): string => Theme::rgba($theme->color_primary, 0.15), '--theme-dark-accent'),

            'light_background' => self::color('Panel styles', 'Light', 'Background',
                fn (Theme $theme): string => $theme->color_card_background, '--theme-light-bg'),
            'light_title' => self::color('Panel styles', 'Light', 'Headings',
                fn (Theme $theme): string => $theme->color_primary, '--theme-light-title'),
            'light_text_muted' => self::color('Panel styles', 'Light', 'Paragraphs',
                fn (Theme $theme): string => $theme->option('color_text_muted'), '--theme-light-text-muted'),
            'light_border' => self::color('Panel styles', 'Light', 'Border',
                fn (Theme $theme): string => $theme->option('color_border'), '--theme-light-border'),

            'primary_background_start' => [...self::color('Panel styles', 'Primary color', 'Background (top left)',
                fn (Theme $theme): string => $theme->color_primary, null),
                'css' => $gradient('primary_background_start', 'primary_background_end')],
            'primary_background_end' => [...self::color('Panel styles', 'Primary color', 'Background (bottom right)',
                fn (Theme $theme): string => $theme->option('color_primary_hover'), null), 'css' => fn (): array => []],
            'primary_title' => self::color('Panel styles', 'Primary color', 'Headings', '#ffffff', '--theme-primary-title'),
            'primary_text' => self::color('Panel styles', 'Primary color', 'Text', '#ffffff', '--theme-primary-text'),
            'primary_text_muted' => self::rgba('Panel styles', 'Primary color', 'Paragraphs', 'rgba(255, 255, 255, 0.85)', '--theme-primary-text-muted'),
            'primary_border' => self::rgba('Panel styles', 'Primary color', 'Border', 'rgba(255, 255, 255, 0.2)', '--theme-primary-border'),
            'primary_accent' => self::rgba('Panel styles', 'Primary color', 'Glow', 'rgba(255, 255, 255, 0.15)', '--theme-primary-accent'),
        ];
    }

    /**
     * The footer's layout, and seven colors for each footer style. Only the
     * chosen style's colors are shown on the Theme screen.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function footer(): array
    {
        $options = [
            'footer_text_align' => [
                'tab' => 'Footer', 'section' => 'Layout', 'label' => 'Text alignment', 'type' => 'choice',
                'choices' => ['center' => 'Centered', 'left' => 'Left'], 'default' => 'center',
                'css' => fn (string $value): array => [
                    '--footer-text-align' => $value,
                    '--footer-justify' => $value === 'left' ? 'flex-start' : 'center',
                ],
            ],
            'footer_text_size' => self::px('Footer', 'Layout', 'Text size', 13.6, '--footer-font-size', 10, 24),
            'footer_spacing' => self::px('Footer', 'Layout', 'Space above and below', 40, '--footer-padding', 0, 160),
        ];

        $palettes = [
            'dark' => [
                'background' => fn (Theme $theme): string => $theme->color_secondary,
                'text' => '#94a3b8',
                'link' => '#94a3b8',
                'link_hover' => '#ffffff',
                'heading' => '#ffffff',
                'heading_hover' => fn (Theme $theme): string => $theme->color_primary,
                'border' => 'rgba(255, 255, 255, 0.05)',
            ],
            'light' => [
                'background' => '#ffffff',
                'text' => '#64748b',
                'link' => '#64748b',
                'link_hover' => fn (Theme $theme): string => $theme->color_secondary,
                'heading' => fn (Theme $theme): string => $theme->color_secondary,
                'heading_hover' => fn (Theme $theme): string => $theme->color_primary,
                'border' => '#e2e8f0',
            ],
            'primary' => [
                'background' => fn (Theme $theme): string => $theme->color_primary,
                'text' => 'rgba(255, 255, 255, 0.85)',
                'link' => 'rgba(255, 255, 255, 0.85)',
                'link_hover' => '#ffffff',
                'heading' => '#ffffff',
                'heading_hover' => 'rgba(255, 255, 255, 0.9)',
                'border' => fn (Theme $theme): string => $theme->option('color_primary_hover'),
            ],
        ];

        $labels = [
            'background' => 'Background',
            'text' => 'Text',
            'link' => 'Links',
            'link_hover' => 'Links, when pointed at',
            'heading' => 'Column headings',
            'heading_hover' => 'Column headings, when pointed at',
            'border' => 'Line along the top',
        ];

        $cssNames = ['background' => 'bg', 'text' => 'text', 'link' => 'link', 'link_hover' => 'link-hover',
            'heading' => 'header', 'heading_hover' => 'header-hover', 'border' => 'border'];

        foreach ($palettes as $style => $palette) {
            foreach ($palette as $part => $default) {
                $isRgba = is_string($default) && str_starts_with($default, 'rgba');
                $make = $isRgba ? self::rgba(...) : self::color(...);

                $options["footer_{$style}_{$part}"] = [
                    ...$make('Footer', 'Colors', $labels[$part], $default, "--footer-{$style}-{$cssNames[$part]}"),
                    'footer_style' => $style,
                ];
            }
        }

        return $options;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function slider(): array
    {
        return [
            'slider_arrow_background' => self::rgba('Slider', 'Slider', 'Arrow background', 'rgba(0, 0, 0, 0.5)', '--slider-nav-bg'),
            'slider_arrow_background_hover' => self::rgba('Slider', 'Slider', 'Arrow background, when pointed at', 'rgba(0, 0, 0, 0.8)', '--slider-nav-bg-hover'),
            'slider_arrow_color' => self::color('Slider', 'Slider', 'Arrows and dots', '#ffffff', '--slider-nav-color'),
            'slider_slide_seconds' => [
                'tab' => 'Slider', 'section' => 'Slider', 'label' => 'Slide animation length', 'type' => 'seconds',
                'default' => 1, 'min' => 0.1, 'max' => 10,
                'css' => fn (float $value): array => ['--slider-transition' => "transform {$value}s ease-in-out"],
            ],
            'slider_fade_seconds' => [
                'tab' => 'Slider', 'section' => 'Slider', 'label' => 'Fade animation length', 'type' => 'seconds',
                'default' => 2, 'min' => 0.1, 'max' => 10,
                'css' => fn (float $value): array => [
                    '--slider-fade-transition' => "opacity {$value}s ease-in-out",
                    '--slider-fade-duration' => "{$value}s",
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function color(string $tab, string $section, string $label, string|Closure $default, ?string $var, ?string $help = null, ?float $alpha = null): array
    {
        return array_filter([
            'tab' => $tab, 'section' => $section, 'label' => $label, 'type' => 'color',
            'default' => $default, 'var' => $var, 'help' => $help, 'alpha' => $alpha,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rgba(string $tab, string $section, string $label, string|Closure $default, string $var): array
    {
        return ['tab' => $tab, 'section' => $section, 'label' => $label, 'type' => 'rgba', 'default' => $default, 'var' => $var];
    }

    /**
     * @return array<string, mixed>
     */
    private static function px(string $tab, string $section, string $label, float|Closure $default, string $var, float $min, float $max, ?string $help = null): array
    {
        return array_filter([
            'tab' => $tab, 'section' => $section, 'label' => $label, 'type' => 'px',
            'default' => $default, 'var' => $var, 'min' => $min, 'max' => $max, 'help' => $help,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function font(string $label, string $default, string $var): array
    {
        return ['tab' => 'Fonts', 'section' => 'Fonts', 'label' => $label, 'type' => 'font', 'default' => $default, 'var' => $var];
    }
}
