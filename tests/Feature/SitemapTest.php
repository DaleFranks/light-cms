<?php

namespace Tests\Feature;

use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sitemap_lists_every_published_page_blog_and_post_with_the_home_page_first(): void
    {
        Entry::factory()->published()->create(['slug' => 'services']);
        Entry::factory()->published()->create(['slug' => 'home', 'updated_at' => '2026-10-01 12:00:00']);
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'articles']);
        Entry::factory()->post($blog)->published()->create(['slug' => 'first-post']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
            ->assertSeeInOrder([
                '<loc>'.url('/').'</loc>',
                '<lastmod>2026-10-01T12:00:00+00:00</lastmod>',
                '<loc>'.url('articles').'</loc>',
                '<loc>'.url('articles/first-post').'</loc>',
                '<loc>'.url('services').'</loc>',
            ], false);
    }

    public function test_the_sitemap_leaves_out_drafts_future_posts_and_posts_in_unpublished_blogs(): void
    {
        Entry::factory()->create(['slug' => 'draft-page']);
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'news']);
        Entry::factory()->post($blog)->published()->create(['slug' => 'coming-soon', 'published_at' => now()->addWeek()]);
        $hiddenBlog = Entry::factory()->blog()->create(['slug' => 'hidden']);
        Entry::factory()->post($hiddenBlog)->published()->create(['slug' => 'orphan']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url('news').'</loc>', false)
            ->assertDontSee('draft-page')
            ->assertDontSee('coming-soon')
            ->assertDontSee('hidden')
            ->assertDontSee('orphan');
    }

    public function test_robots_txt_points_to_the_sitemap_and_keeps_out_of_the_admin(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame("User-agent: *\nDisallow: /admin/\n\nSitemap: ".url('sitemap.xml')."\n", $response->getContent());
    }
}
