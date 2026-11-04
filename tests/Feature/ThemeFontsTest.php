<?php

namespace Tests\Feature;

use App\Filament\Pages\ThemeSettings;
use App\Models\Theme;
use App\Models\User;
use App\ThemeFonts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeFontsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fonts_are_asked_for_with_the_italics_they_have(): void
    {
        $this->assertStringContainsString(
            'family=Vollkorn:ital,wght@0,400;0,500;0,600;0,700;1,400;1,700&',
            ThemeFonts::googleFontsUrl(['Vollkorn']),
        );
    }

    public function test_fonts_without_italics_or_extra_weights_arent_asked_for_them(): void
    {
        $this->assertStringContainsString('family=Outfit:wght@400;500;600;700&', ThemeFonts::googleFontsUrl(['Outfit']));
        $this->assertStringContainsString('family=Lobster:wght@400&', ThemeFonts::googleFontsUrl(['Lobster']));
    }

    public function test_a_font_is_only_asked_for_once(): void
    {
        $url = ThemeFonts::googleFontsUrl(['Lora', 'Lora', 'Inter']);

        $this->assertSame(1, substr_count($url, 'family=Lora'));
        $this->assertStringContainsString('family=Inter', $url);
    }

    public function test_a_font_no_longer_on_the_list_keeps_working(): void
    {
        $this->assertStringContainsString('family=Old+Favorite&', ThemeFonts::googleFontsUrl(['Old Favorite']));
        $this->assertSame("'Old Favorite', sans-serif", ThemeFonts::stack('Old Favorite'));
    }

    public function test_fonts_fall_back_to_a_similar_kind_of_font(): void
    {
        $this->assertSame("'Vollkorn', serif", ThemeFonts::stack('Vollkorn'));
        $this->assertSame("'Noto Sans', sans-serif", ThemeFonts::stack('Noto Sans'));
        $this->assertSame("'Roboto Slab', serif", ThemeFonts::stack('Roboto Slab'));
        $this->assertSame("'JetBrains Mono', monospace", ThemeFonts::stack('JetBrains Mono'));
    }

    public function test_every_font_offered_before_the_list_grew_can_still_be_chosen(): void
    {
        $textFonts = array_merge(...array_values(ThemeFonts::forBodyText()));

        foreach (['Inter', 'Lato', 'Montserrat', 'Noto Sans', 'Open Sans', 'Outfit', 'Poppins', 'Roboto', 'Source Sans 3', 'Work Sans', 'Lora', 'Merriweather', 'Noto Serif', 'Playfair Display', 'Roboto Slab'] as $font) {
            $this->assertArrayHasKey($font, $textFonts);
        }

        foreach (['Fira Code', 'IBM Plex Mono', 'JetBrains Mono', 'Roboto Mono', 'Source Code Pro'] as $font) {
            $this->assertArrayHasKey($font, ThemeFonts::forCode());
        }
    }

    public function test_display_and_handwriting_fonts_can_be_used_for_headings_but_not_body_text(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['Lobster', 'Pacifico'] as $font) {
            Livewire::test(ThemeSettings::class)
                ->fillForm(['font_body' => $font])
                ->call('save')
                ->assertHasFormErrors(['font_body']);

            Livewire::test(ThemeSettings::class)
                ->fillForm(['font_headings' => $font])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame($font, Theme::current()->font_headings);
        }
    }

    public function test_vollkorn_can_be_chosen_for_body_text_and_reaches_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['font_body' => 'Vollkorn'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')
            ->assertSee('family=Vollkorn:ital,wght@', false)
            ->assertSee("--font-family-body: 'Vollkorn', serif", false);
    }

    public function test_only_code_fonts_can_be_chosen_for_code(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options.font_code' => 'Vollkorn'])
            ->call('save')
            ->assertHasFormErrors(['options.font_code']);

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options.font_code' => 'Courier Prime'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Courier Prime', Theme::current()->option('font_code'));
    }

    public function test_a_sample_shows_the_chosen_font_and_follows_changes(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->assertSeeHtml('font-family: &#039;Noto Sans&#039;, sans-serif')
            ->assertSeeHtml('font-family: &#039;Roboto Slab&#039;, serif')
            ->fillForm(['font_body' => 'Vollkorn'])
            ->assertSeeHtml('font-family: &#039;Vollkorn&#039;, serif');
    }
}
