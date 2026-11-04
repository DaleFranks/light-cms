<?php

namespace Tests\Feature;

use App\BoxStyle;
use App\Models\Card;
use App\Models\Entry;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardShortcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_card_is_shown_where_its_shortcode_is_written(): void
    {
        Card::factory()->featured()->create(['slug' => 'seo', 'body' => '### SEO', 'style' => BoxStyle::Dark]);
        $entry = Entry::factory()->published()->create(['body' => "Before.\n\n[card:seo]\n\nAfter."]);

        $this->assertStringContainsString(
            "<p>Before.</p>\n<div class=\"card featured-card theme-dark\">\n<h3>SEO</h3>\n\n</div>\n<p>After.</p>",
            $entry->bodyHtml(),
        );
    }

    public function test_a_card_without_a_style_or_highlight_has_only_the_card_class(): void
    {
        Card::factory()->create(['slug' => 'plain']);
        $entry = Entry::factory()->published()->create(['body' => '[card:plain]']);

        $this->assertStringContainsString('<div class="card">', $entry->bodyHtml());
    }

    public function test_a_grid_shows_its_cards_in_the_order_written_and_skips_missing_ones(): void
    {
        Card::factory()->create(['slug' => 'design', 'body' => 'Design.']);
        Card::factory()->create(['slug' => 'development', 'body' => 'Development.']);
        $entry = Entry::factory()->published()->create(['body' => '[cards: development, missing, Design.md]']);

        $html = $entry->bodyHtml();

        $this->assertStringContainsString('<div class="card-grid columns-2">', $html);
        $this->assertSame(2, substr_count($html, '<div class="card">'));
        $this->assertLessThan(strpos($html, 'Design.'), strpos($html, 'Development.'));
    }

    public function test_the_number_of_cards_decides_the_columns_so_rows_are_even(): void
    {
        $slugs = collect(range(1, 7))->map(fn (int $number): string => Card::factory()->create(['slug' => "card-{$number}"])->slug);

        $columnsFor = fn (int $count): string => preg_match(
            '/card-grid columns-(\d)/',
            Entry::factory()->make(['body' => '[cards: '.$slugs->take($count)->implode(', ').']'])->bodyHtml(),
            $match,
        ) ? $match[1] : 'none';

        $this->assertSame(['1', '2', '3', '2', '3', '3', '3'], array_map($columnsFor, range(1, 7)));
    }

    public function test_shortcodes_naming_no_existing_card_are_left_as_written(): void
    {
        $entry = Entry::factory()->published()->create(['body' => "[card:nope]\n\n[cards: nope, nada]"]);

        $html = $entry->bodyHtml();

        $this->assertStringContainsString('[card:nope]', $html);
        $this->assertStringContainsString('[cards: nope, nada]', $html);
        $this->assertStringNotContainsString('card-grid', $html);
    }

    public function test_cards_and_modules_can_be_used_on_the_same_page(): void
    {
        Card::factory()->create(['slug' => 'seo', 'body' => 'Card text.']);
        Module::factory()->create(['slug' => 'cta', 'body' => 'Module text.']);
        Entry::factory()->published()->create(['slug' => 'home', 'body' => "[module:cta]\n\n[cards: seo]"]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Card text.')
            ->assertSee('Module text.');
    }

    public function test_a_cards_text_alignment_is_added_to_its_classes(): void
    {
        $card = Card::factory()->create(['style' => 'dark', 'text_alignment' => 'left']);

        $this->assertStringContainsString('class="card theme-dark card-align-left"', $card->html());
    }

    public function test_a_card_with_no_text_alignment_uses_its_styles(): void
    {
        $card = Card::factory()->create(['style' => 'dark']);

        $this->assertStringContainsString('class="card theme-dark"', $card->html());
    }
}
