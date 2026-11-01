<?php

namespace App;

use App\Models\Card;
use App\Models\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns Markdown into HTML, filling in shortcodes:
 * - [youtube:url|width|align] embeds a YouTube video
 * - [module:slug], [card:slug] and [cards: slug-1, slug-2] place modules and cards (pages only)
 *
 * Each shortcode is swapped for a placeholder before the Markdown is converted
 * and filled in afterwards, so its HTML is never converted twice. These are all
 * blocks, so each placeholder gets a paragraph of its own: text written on the
 * same line carries on after the block instead of ending up inside it. A
 * shortcode naming something that doesn't exist is left as written, so the
 * mistake shows.
 */
class Markdown
{
    private const SHORTCODE_PATTERN = '/\[(youtube|module|card|cards):([^\]]+)\]/i';

    private const PLACEHOLDER_PATTERN = '/<!--shortcode:(\d+)-->/';

    /**
     * @param  bool  $withBlocks  Whether modules and cards may be placed. They can't be placed inside each other.
     */
    public static function toHtml(string $markdown, bool $withBlocks = false): string
    {
        /** @var list<array{kind: string, value: string, original: string}> $shortcodes */
        $shortcodes = [];

        $markdown = preg_replace_callback(self::SHORTCODE_PATTERN, function (array $match) use (&$shortcodes, $withBlocks): string {
            $kind = strtolower($match[1]);

            if (! $withBlocks && $kind !== 'youtube') {
                return $match[0];
            }

            $shortcodes[] = ['kind' => $kind, 'value' => trim($match[2]), 'original' => $match[0]];

            return "\n\n<!--shortcode:".array_key_last($shortcodes)."-->\n\n";
        }, $markdown);

        $html = Str::markdown($markdown, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        if ($shortcodes === []) {
            return $html;
        }

        $modules = self::load(Module::class, $shortcodes, ['module']);
        $cards = self::load(Card::class, $shortcodes, ['card', 'cards']);

        return preg_replace_callback(self::PLACEHOLDER_PATTERN, function (array $match) use ($shortcodes, $modules, $cards): string {
            $shortcode = $shortcodes[(int) $match[1]] ?? null;

            if ($shortcode === null) {
                return $match[0];
            }

            $html = match ($shortcode['kind']) {
                'youtube' => self::youtube($shortcode['value']),
                'module' => $modules->get(self::slugs($shortcode['value'])[0] ?? '')?->html(),
                'card' => $cards->get(self::slugs($shortcode['value'])[0] ?? '')?->html(),
                'cards' => self::cardGrid(self::slugs($shortcode['value']), $cards),
            };

            return $html ?? '<p>'.e($shortcode['original']).'</p>';
        }, $html);
    }

    /**
     * The slugs named in a shortcode, lowercased and without the ".md" the old CMS allowed.
     *
     * @return list<string>
     */
    private static function slugs(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $slug): string => strtolower(preg_replace('/\.md$/i', '', trim($slug))))
            ->filter(fn (string $slug): bool => (bool) preg_match('/^[a-z0-9-]+$/', $slug))
            ->values()
            ->all();
    }

    /**
     * Load, in one query, every module or card the shortcodes of the given kinds name.
     *
     * @template TModel of Module|Card
     *
     * @param  class-string<TModel>  $model
     * @param  list<array{kind: string, value: string, original: string}>  $shortcodes
     * @param  list<string>  $kinds
     * @return Collection<string, TModel>
     */
    private static function load(string $model, array $shortcodes, array $kinds): Collection
    {
        $slugs = collect($shortcodes)
            ->filter(fn (array $shortcode): bool => in_array($shortcode['kind'], $kinds, true))
            ->flatMap(fn (array $shortcode): array => self::slugs($shortcode['value']))
            ->unique();

        if ($slugs->isEmpty()) {
            return collect();
        }

        return $model::query()->whereIn('slug', $slugs)->get()->keyBy('slug')->toBase();
    }

    /**
     * The named cards side by side, skipping any that don't exist.
     *
     * @param  list<string>  $slugs
     * @param  Collection<string, Card>  $cards
     */
    private static function cardGrid(array $slugs, Collection $cards): ?string
    {
        $gridCards = collect($slugs)->map(fn (string $slug): ?Card => $cards->get($slug))->filter();

        if ($gridCards->isEmpty()) {
            return null;
        }

        return '<div class="card-grid">'."\n".$gridCards->map->html()->implode("\n")."\n".'</div>';
    }

    /**
     * An embedded YouTube video from [youtube:url|width|align], where width is
     * pixels ("560") or a percentage ("50%") and align is left, center or right.
     */
    private static function youtube(string $value): ?string
    {
        $parts = array_map(trim(...), explode('|', $value));
        $url = array_shift($parts);
        $width = null;
        $align = 'center';

        foreach ($parts as $part) {
            if (in_array(strtolower($part), ['left', 'center', 'right'], true)) {
                $align = strtolower($part);
            } elseif (preg_match('/^(\d+)(px|%)?$/i', $part, $size)) {
                $width = $size[1].strtolower(($size[2] ?? '') ?: 'px');
            }
        }

        $videoId = self::youtubeVideoId($url);

        if ($videoId === null) {
            return null;
        }

        $style = $width === null ? '' : ' style="width: '.$width.'"';

        return '<div class="video-container video-'.$align.'"'.$style.'>'
            .'<iframe src="https://www.youtube.com/embed/'.$videoId.'" title="YouTube video" loading="lazy" '
            .'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" '
            .'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>'
            .'</div>';
    }

    /**
     * The video ID from any usual form of YouTube link: watch?v=, youtu.be/,
     * /embed/, /shorts/ or /live/.
     */
    public static function youtubeVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower(preg_replace('/^(www\.|m\.)/i', '', $parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = ltrim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if (preg_match('#^/(embed|shorts|live)/([^/]+)#', $path, $match)) {
                $videoId = $match[2];
            } else {
                parse_str($parts['query'] ?? '', $query);
                $videoId = is_string($query['v'] ?? null) ? $query['v'] : null;
            }
        }

        return $videoId !== null && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $videoId) ? $videoId : null;
    }
}
