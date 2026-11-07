<?php

namespace App\Http\Controllers;

use App\EntryType;
use App\Http\Middleware\UseSiteFolder;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

/**
 * The sitemap search engines read to find every page, and the robots.txt that
 * points them to it. Both are built from what's published, so they're always
 * up to date with no settings to manage.
 */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $entries = Entry::query()
            ->published()
            ->where(fn (Builder $query): Builder => $query
                ->where('type', '!=', EntryType::Post)
                ->orWhereHas('blog', fn (Builder $blog): Builder => $blog->published()))
            ->orderByRaw('path = ? desc', [Entry::HOME_PATH])
            ->orderBy('path')
            ->get(['id', 'type', 'path', 'blog_id', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            $xml .= "    <url>\n"
                .'        <loc>'.e($entry->canonicalUrl())."</loc>\n"
                .'        <lastmod>'.$entry->updated_at->toAtomString()."</lastmod>\n"
                ."    </url>\n";
        }

        $xml .= "</urlset>\n";

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: '.UseSiteFolder::path().'/admin/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
