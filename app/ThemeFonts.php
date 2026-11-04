<?php

namespace App;

/**
 * The Google Fonts offered on the Theme screen. They load from Google; no font
 * files are kept with the site.
 *
 * Each one says which of the weights and italics the site uses actually exist,
 * so that only those are asked for. A browser fetches a weight or italic only
 * when a page uses it.
 */
class ThemeFonts
{
    /**
     * @var array<string, array{group: string, fallback: string, weights: list<int>, italics: list<int>}>
     */
    private const FONTS = [
        // Serif
        'Bitter' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Cormorant Garamond' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Crimson Pro' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'EB Garamond' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Libre Baskerville' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Literata' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Lora' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Merriweather' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Noto Serif' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Playfair Display' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'PT Serif' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 700], 'italics' => [400, 700]],
        'Source Serif 4' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Spectral' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Vollkorn' => ['group' => 'Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        // Sans Serif
        'DM Sans' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Figtree' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'IBM Plex Sans' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Inter' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Lato' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 700], 'italics' => [400, 700]],
        'Montserrat' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Noto Sans' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Nunito' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Open Sans' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Oswald' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'Outfit' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'Poppins' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Raleway' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Roboto' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Source Sans 3' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Work Sans' => ['group' => 'Sans Serif', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        // Slab Serif
        'Aleo' => ['group' => 'Slab Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Arvo' => ['group' => 'Slab Serif', 'fallback' => 'serif', 'weights' => [400, 700], 'italics' => [400, 700]],
        'Roboto Slab' => ['group' => 'Slab Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'Zilla Slab' => ['group' => 'Slab Serif', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        // Display
        'Abril Fatface' => ['group' => 'Display', 'fallback' => 'serif', 'weights' => [400], 'italics' => []],
        'Bebas Neue' => ['group' => 'Display', 'fallback' => 'sans-serif', 'weights' => [400], 'italics' => []],
        'Cinzel' => ['group' => 'Display', 'fallback' => 'serif', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'Comfortaa' => ['group' => 'Display', 'fallback' => 'sans-serif', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'Lobster' => ['group' => 'Display', 'fallback' => 'cursive', 'weights' => [400], 'italics' => []],
        // Handwriting
        'Caveat' => ['group' => 'Handwriting', 'fallback' => 'cursive', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'Dancing Script' => ['group' => 'Handwriting', 'fallback' => 'cursive', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'Great Vibes' => ['group' => 'Handwriting', 'fallback' => 'cursive', 'weights' => [400], 'italics' => []],
        'Pacifico' => ['group' => 'Handwriting', 'fallback' => 'cursive', 'weights' => [400], 'italics' => []],
        // Monospace
        'Courier Prime' => ['group' => 'Monospace', 'fallback' => 'monospace', 'weights' => [400, 700], 'italics' => [400, 700]],
        'Fira Code' => ['group' => 'Monospace', 'fallback' => 'monospace', 'weights' => [400, 500, 600, 700], 'italics' => []],
        'IBM Plex Mono' => ['group' => 'Monospace', 'fallback' => 'monospace', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'JetBrains Mono' => ['group' => 'Monospace', 'fallback' => 'monospace', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Roboto Mono' => ['group' => 'Monospace', 'fallback' => 'monospace', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
        'Source Code Pro' => ['group' => 'Monospace', 'fallback' => 'monospace', 'weights' => [400, 500, 600, 700], 'italics' => [400, 700]],
    ];

    /**
     * Fonts easy to read in paragraphs, by group, for body text.
     *
     * @return array<string, array<string, string>>
     */
    public static function forBodyText(): array
    {
        return self::inGroups(['Serif', 'Sans Serif', 'Slab Serif']);
    }

    /**
     * Every font except the code ones, by group, for headings and other short text.
     *
     * @return array<string, array<string, string>>
     */
    public static function forHeadings(): array
    {
        return self::inGroups(['Serif', 'Sans Serif', 'Slab Serif', 'Display', 'Handwriting']);
    }

    /**
     * Fonts for code, all monospaced.
     *
     * @return array<string, string>
     */
    public static function forCode(): array
    {
        return self::inGroups(['Monospace'])['Monospace'];
    }

    /**
     * The font as CSS, with a similar kind of font to show if it can't load.
     */
    public static function stack(string $font): string
    {
        return "'{$font}', ".(self::FONTS[$font]['fallback'] ?? 'sans-serif');
    }

    /**
     * The Google Fonts stylesheet address for these fonts.
     *
     * @param  iterable<string>  $fonts
     */
    public static function googleFontsUrl(iterable $fonts): string
    {
        $families = collect($fonts)
            ->unique()
            ->map(fn (string $font): string => 'family='.str_replace(' ', '+', $font).self::googleVariants($font))
            ->implode('&');

        return 'https://fonts.googleapis.com/css2?'.$families.'&display=swap';
    }

    /**
     * Google refuses to mix in italics a font doesn't have, and a font no longer
     * on the list is asked for plainly, so a site that chose it keeps working.
     */
    private static function googleVariants(string $font): string
    {
        $details = self::FONTS[$font] ?? null;

        if ($details === null) {
            return '';
        }

        if ($details['italics'] === []) {
            return ':wght@'.implode(';', $details['weights']);
        }

        $upright = array_map(fn (int $weight): string => "0,{$weight}", $details['weights']);
        $italic = array_map(fn (int $weight): string => "1,{$weight}", $details['italics']);

        return ':ital,wght@'.implode(';', [...$upright, ...$italic]);
    }

    /**
     * @param  list<string>  $groups
     * @return array<string, array<string, string>>
     */
    private static function inGroups(array $groups): array
    {
        return collect($groups)
            ->mapWithKeys(fn (string $group): array => [$group => collect(self::FONTS)
                ->filter(fn (array $details): bool => $details['group'] === $group)
                ->map(fn (array $details, string $font): string => $font)
                ->all()])
            ->all();
    }
}
