<?php

namespace Tests\Feature;

use App\BoxStyle;
use App\Models\Entry;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleShortcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_module_is_shown_where_its_shortcode_is_written(): void
    {
        Module::factory()->create(['slug' => 'cta', 'body' => "## Ready?\n\nGet in **touch**.", 'style' => BoxStyle::Dark]);
        $entry = Entry::factory()->published()->create(['body' => "Before.\n\n[module:cta]\n\nAfter."]);

        $html = $entry->bodyHtml();

        $this->assertStringContainsString(
            "<p>Before.</p>\n<div class=\"module cta-module theme-dark\">\n<h2>Ready?</h2>\n<p>Get in <strong>touch</strong>.</p>\n\n</div>\n<p>After.</p>",
            $html,
        );
    }

    public function test_a_module_without_a_style_has_no_theme_class(): void
    {
        Module::factory()->create(['slug' => 'note', 'body' => 'Hello.']);
        $entry = Entry::factory()->published()->create(['body' => '[module:note]']);

        $this->assertStringContainsString('<div class="module note-module">', $entry->bodyHtml());
    }

    public function test_the_shortcode_ignores_case_and_an_old_style_md_extension(): void
    {
        Module::factory()->create(['slug' => 'cta', 'body' => 'Hello.']);
        $entry = Entry::factory()->published()->create(['body' => "[Module:CTA.md]\n\n[module: cta ]"]);

        $this->assertSame(2, substr_count($entry->bodyHtml(), '<div class="module cta-module">'));
    }

    public function test_a_shortcode_for_a_missing_module_is_left_as_written(): void
    {
        $entry = Entry::factory()->published()->create(['body' => '[module:nothing-here]']);

        $this->assertStringContainsString('[module:nothing-here]', $entry->bodyHtml());
    }

    public function test_modules_inside_modules_are_not_expanded(): void
    {
        Module::factory()->create(['slug' => 'outer', 'body' => '[module:inner]']);
        Module::factory()->create(['slug' => 'inner', 'body' => 'Inner text.']);
        $entry = Entry::factory()->published()->create(['body' => '[module:outer]']);

        $html = $entry->bodyHtml();

        $this->assertStringContainsString('[module:inner]', $html);
        $this->assertStringNotContainsString('Inner text.', $html);
    }

    public function test_a_module_on_a_page_can_use_navigation_and_the_site_name(): void
    {
        Entry::factory()->published()->create(['slug' => 'about', 'title' => 'About', 'nav_order' => 1]);
        Module::factory()->create(['slug' => 'links', 'body' => "[navigation:flat]\n\n[site-name]"]);
        Entry::factory()->published()->create(['slug' => 'home', 'body' => '[module:links]']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<div class="module links-module">', false)
            ->assertSee('<a href="'.url('about').'">About</a>', false);
    }
}
