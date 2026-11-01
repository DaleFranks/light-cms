<?php

namespace Tests\Feature;

use App\Markdown;
use App\Models\Card;
use App\Models\Entry;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YoutubeShortcodeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function youtubeLinks(): array
    {
        return [
            'watch link' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s', 'dQw4w9WgXcQ'],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
            'mobile link' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'embed link' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'shorts link' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'live link' => ['https://www.youtube.com/live/dQw4w9WgXcQ?feature=share', 'dQw4w9WgXcQ'],
            'another site' => ['https://vimeo.com/123456789', null],
            'not a link' => ['dQw4w9WgXcQ', null],
            'unsafe id' => ['https://youtu.be/"><script>', null],
        ];
    }

    #[DataProvider('youtubeLinks')]
    public function test_the_video_id_is_found_in_any_usual_youtube_link(string $url, ?string $videoId): void
    {
        $this->assertSame($videoId, Markdown::youtubeVideoId($url));
    }

    public function test_a_video_is_embedded_centered_by_default(): void
    {
        $html = Markdown::toHtml("Watch this:\n\n[youtube:https://youtu.be/dQw4w9WgXcQ]");

        $this->assertStringContainsString('<p>Watch this:</p>', $html);
        $this->assertStringContainsString('<div class="video-container video-center"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"', $html);
        $this->assertStringNotContainsString('<p><div', $html);
    }

    public function test_text_on_the_same_line_as_the_shortcode_becomes_a_paragraph_after_the_video(): void
    {
        $html = Markdown::toHtml('[youtube:https://youtu.be/dQw4w9WgXcQ|right] Text that wraps around it.');

        $this->assertMatchesRegularExpression('#</iframe></div>\s*<p>Text that wraps around it.</p>#', $html);
        $this->assertStringNotContainsString('<p><div', $html);
    }

    public function test_a_video_can_have_a_width_and_alignment_in_either_order(): void
    {
        $this->assertStringContainsString(
            '<div class="video-container video-right" style="width: 400px">',
            Markdown::toHtml('[youtube: https://youtu.be/dQw4w9WgXcQ | 400 | right ]'),
        );

        $this->assertStringContainsString(
            '<div class="video-container video-left" style="width: 50%">',
            Markdown::toHtml('[YouTube:https://youtu.be/dQw4w9WgXcQ|Left|50%]'),
        );
    }

    public function test_a_shortcode_without_a_youtube_link_is_left_as_written(): void
    {
        $html = Markdown::toHtml('[youtube:https://vimeo.com/123]');

        $this->assertStringContainsString('<p>[youtube:https://vimeo.com/123]</p>', $html);
        $this->assertStringNotContainsString('iframe', $html);
    }

    public function test_videos_work_in_pages_modules_and_cards(): void
    {
        Module::factory()->create(['slug' => 'intro', 'body' => '[youtube:https://youtu.be/aaaaaaaaaaa]']);
        Card::factory()->create(['slug' => 'demo', 'body' => '[youtube:https://youtu.be/bbbbbbbbbbb]']);
        Entry::factory()->published()->create([
            'slug' => 'home',
            'body' => "[youtube:https://youtu.be/ccccccccccc]\n\n[module:intro]\n\n[card:demo]",
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('embed/aaaaaaaaaaa', false)
            ->assertSee('embed/bbbbbbbbbbb', false)
            ->assertSee('embed/ccccccccccc', false);
    }
}
