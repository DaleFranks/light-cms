<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Visitor tracking and search engine verification, edited on the Analytics
 * screen. Ready-made fields cover the common services, so only an ID is
 * needed; the head and footer code boxes take any other service's snippet.
 *
 * Tracking (the analytics services and both code boxes) is left out of
 * previews and of visits by anyone signed in to the admin, so only visitors
 * are counted. Verification tags are always included.
 */
#[Fillable([
    'tracking_off',
    'google_analytics_id',
    'google_tag_manager_id',
    'meta_pixel_id',
    'google_verification',
    'bing_verification',
    'head_code',
    'footer_code',
])]
class AnalyticsSettings extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tracking_off' => 'boolean',
        ];
    }

    /**
     * The site's analytics settings, created empty the first time they're needed.
     */
    public static function current(): self
    {
        return self::query()->oldest('id')->firstOr(fn (): self => self::query()->create());
    }

    /**
     * Whether this visit should be tracked: tracking isn't turned off, and the
     * visitor isn't signed in to the admin (who is most likely testing).
     */
    public function tracksThisVisit(): bool
    {
        return ! $this->tracking_off && ! auth()->check();
    }

    /**
     * A Google Analytics 4 Measurement ID (G-AB12CD34EF), from the ID itself
     * or from the Google tag snippet it's in.
     */
    public static function googleAnalyticsIdFrom(?string $pasted): ?string
    {
        return preg_match('/\bG-[A-Z0-9]{4,20}\b/i', (string) $pasted, $match) === 1
            ? strtoupper($match[0])
            : null;
    }

    /**
     * A Google Tag Manager Container ID (GTM-AB12CD3), from the ID itself or
     * from either of the snippets it's in.
     */
    public static function googleTagManagerIdFrom(?string $pasted): ?string
    {
        return preg_match('/\bGTM-[A-Z0-9]{4,20}\b/i', (string) $pasted, $match) === 1
            ? strtoupper($match[0])
            : null;
    }

    /**
     * A Meta Pixel ID (a long number), from the ID itself or from the pixel
     * snippet it's in.
     */
    public static function metaPixelIdFrom(?string $pasted): ?string
    {
        $pasted = trim((string) $pasted);

        $patterns = [
            '/^(\d{8,20})$/',
            '/fbq\(\s*[\'"]init[\'"]\s*,\s*[\'"]?(\d{8,20})/',
            '/[?&](?:amp;)?id=(\d{8,20})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $pasted, $match) === 1) {
                return $match[1];
            }
        }

        return null;
    }

    /**
     * A Google Search Console verification code, from the code itself, from
     * the meta tag it's in, or from its "google-site-verification=" DNS form.
     */
    public static function googleVerificationFrom(?string $pasted): ?string
    {
        $code = preg_replace('/^google-site-verification=/i', '', self::metaContent($pasted));

        return preg_match('/^[A-Za-z0-9_-]{20,100}$/', $code) === 1 ? $code : null;
    }

    /**
     * A Bing Webmaster Tools verification code (32 letters and digits), from
     * the code itself or from the meta tag it's in.
     */
    public static function bingVerificationFrom(?string $pasted): ?string
    {
        $code = self::metaContent($pasted);

        return preg_match('/^[A-F0-9]{32}$/i', $code) === 1 ? strtoupper($code) : null;
    }

    /**
     * What's in a pasted meta tag's content="...", or the pasted text itself
     * when it isn't a tag.
     */
    private static function metaContent(?string $pasted): string
    {
        $pasted = trim((string) $pasted);

        if (str_contains($pasted, '<') && preg_match('/\bcontent\s*=\s*["\']([^"\']*)["\']/i', $pasted, $match) === 1) {
            return trim($match[1]);
        }

        return $pasted;
    }
}
