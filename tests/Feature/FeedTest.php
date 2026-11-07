<?php

namespace Tests\Feature;

use App\Http\Controllers\FeedController;
use App\Models\Entry;
use App\Models\Image;
use App\Models\Redirect;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_blogs_feed_lists_its_published_posts_newest_first_as_summaries(): void
    {
        Theme::current()->update(['site_name' => 'Acme']);
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'news', 'title' => 'News', 'meta_description' => 'What we are up to.']);
        Entry::factory()->post($blog)->published()->create([
            'slug' => 'older',
            'title' => 'Older post',
            'published_at' => '2026-10-01 09:00:00',
        ]);
        Entry::factory()->post($blog)->published()->create([
            'slug' => 'newer',
            'title' => 'Fish & chips',
            'published_at' => '2026-10-05 18:30:00',
            'body' => "One. Two. Three. Four.\n\n[form:contact]",
        ]);

        $response = $this->get('/news/feed.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'The feed is well-formed XML.');
        $this->assertSame('News | Acme', (string) $xml->channel->title);
        $this->assertSame(url('news'), (string) $xml->channel->link);
        $this->assertSame('What we are up to.', (string) $xml->channel->description);
        $this->assertSame('Mon, 05 Oct 2026 18:30:00 +0000', (string) $xml->channel->lastBuildDate);
        $this->assertCount(2, $xml->channel->item);

        $newest = $xml->channel->item[0];
        $this->assertSame('Fish & chips', (string) $newest->title);
        $this->assertSame(url('news/newer'), (string) $newest->link);
        $this->assertSame(url('news/newer'), (string) $newest->guid);
        $this->assertSame('Mon, 05 Oct 2026 18:30:00 +0000', (string) $newest->pubDate);
        $this->assertSame('<p>One. Two. Three.</p>', (string) $newest->description);
        $this->assertSame('Older post', (string) $xml->channel->item[1]->title);
    }

    public function test_the_feed_leaves_out_drafts_and_posts_scheduled_for_later(): void
    {
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'news']);
        Entry::factory()->post($blog)->create(['title' => 'Unfinished']);
        Entry::factory()->post($blog)->published()->create(['title' => 'Coming soon', 'published_at' => now()->addWeek()]);
        Entry::factory()->post($blog)->published()->create(['title' => 'Ready']);

        $this->get('/news/feed.xml')
            ->assertOk()
            ->assertSee('Ready')
            ->assertDontSee('Unfinished')
            ->assertDontSee('Coming soon');
    }

    public function test_the_feed_lists_only_the_newest_posts(): void
    {
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'news']);
        Entry::factory()->post($blog)->published()->count(FeedController::LISTED_POSTS + 3)->create();

        $xml = simplexml_load_string($this->get('/news/feed.xml')->getContent());

        $this->assertCount(FeedController::LISTED_POSTS, $xml->channel->item);
    }

    public function test_each_post_shows_its_featured_image_at_its_full_address(): void
    {
        Image::factory()->create(['filename' => 'boat.jpg']);
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'news']);
        Entry::factory()->post($blog)->published()->create(['body' => "Sailing.\n\n[image:boat.jpg]"]);

        $xml = simplexml_load_string($this->get('/news/feed.xml')->getContent());

        $this->assertStringStartsWith(
            '<p><img src="'.request()->getSchemeAndHttpHost().'/uploads/images/boat.jpg',
            (string) $xml->channel->item[0]->description,
        );
    }

    public function test_a_blog_at_a_nested_address_has_a_feed(): void
    {
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'company/news']);

        $this->get('/company/news/feed.xml')->assertOk();
        $this->assertSame(url('company/news/feed.xml'), $blog->feedUrl());
    }

    public function test_a_draft_blog_a_page_and_an_unknown_address_have_no_feed(): void
    {
        Entry::factory()->blog()->create(['slug' => 'hidden']);
        Entry::factory()->published()->create(['slug' => 'about']);

        $this->get('/hidden/feed.xml')->assertNotFound();
        $this->get('/about/feed.xml')->assertNotFound();
        $this->get('/nothing/feed.xml')->assertNotFound();
    }

    public function test_a_redirect_from_an_old_feed_address_still_works(): void
    {
        Redirect::create(['from_path' => 'blog/feed.xml', 'to' => '/news/feed.xml']);

        $this->get('/blog/feed.xml')->assertRedirect('/news/feed.xml');
    }

    public function test_control_characters_in_a_title_dont_break_the_feed(): void
    {
        $blog = Entry::factory()->blog()->published()->create(['slug' => 'news']);
        Entry::factory()->post($blog)->published()->create(['title' => "Bad\x0Btitle"]);

        $xml = simplexml_load_string($this->get('/news/feed.xml')->getContent());

        $this->assertSame('Badtitle', (string) $xml->channel->item[0]->title);
    }

    public function test_every_page_points_to_each_published_blogs_feed(): void
    {
        Entry::factory()->published()->create(['slug' => 'about']);
        Entry::factory()->blog()->published()->create(['slug' => 'news', 'title' => 'News']);
        Entry::factory()->blog()->create(['slug' => 'hidden', 'title' => 'Hidden']);

        $this->get('/about')
            ->assertOk()
            ->assertSee('<link rel="alternate" type="application/rss+xml" title="News" href="'.url('news/feed.xml').'">', false)
            ->assertDontSee(url('hidden/feed.xml'), false);
    }
}
