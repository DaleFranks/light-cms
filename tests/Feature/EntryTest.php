<?php

namespace Tests\Feature;

use App\EntryStatus;
use App\EntryType;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_published_scope_only_returns_entries_that_are_live(): void
    {
        $live = Entry::factory()->published()->create();
        $liveWithoutDate = Entry::factory()->create([
            'status' => EntryStatus::Published,
            'published_at' => null,
        ]);
        Entry::factory()->create(['status' => EntryStatus::Draft]);
        Entry::factory()->published()->create(['published_at' => now()->addDay()]);

        $this->assertEqualsCanonicalizing(
            [$live->id, $liveWithoutDate->id],
            Entry::published()->pluck('id')->all(),
        );
    }

    public function test_deleting_a_parent_moves_its_children_to_the_top_level(): void
    {
        $parent = Entry::factory()->create();
        $child = Entry::factory()->create(['parent_id' => $parent->id]);

        $parent->delete();

        $this->assertNull($child->refresh()->parent_id);
    }

    public function test_changing_a_blogs_slug_moves_its_posts(): void
    {
        $blog = Entry::factory()->blog()->create(['slug' => 'blog']);
        $post = Entry::factory()->post($blog)->create(['slug' => 'hello']);
        $otherPost = Entry::factory()->post()->create(['slug' => 'hello']);
        $this->assertSame('blog/hello', $post->path);

        $blog->update(['slug' => 'articles']);

        $this->assertSame('articles/hello', $post->refresh()->path);
        $this->assertSame($otherPost->blog->slug.'/hello', $otherPost->refresh()->path);
    }

    public function test_an_entry_that_is_not_a_post_has_no_blog(): void
    {
        $blog = Entry::factory()->blog()->create(['slug' => 'news']);
        $post = Entry::factory()->post($blog)->create(['slug' => 'about']);

        $post->update(['type' => EntryType::Page]);

        $this->assertNull($post->blog_id);
        $this->assertSame('about', $post->path);
    }
}
