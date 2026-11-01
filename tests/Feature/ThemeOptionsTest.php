<?php

namespace Tests\Feature;

use App\Filament\Pages\ThemeSettings;
use App\Models\Theme;
use App\Models\User;
use App\ThemeOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_untouched_settings_reproduce_the_original_stylesheet(): void
    {
        $variables = Theme::current()->cssVariables();

        $this->assertSame('#2563eb', $variables['--color-link']);
        $this->assertSame('rgba(255, 255, 255, 0.95)', $variables['--header-bg-color-scrolled']);
        $this->assertSame('linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%)', $variables['--theme-dark-bg']);
        $this->assertSame('36px', $variables['--font-size-h1']);
        $this->assertSame('17.6px', $variables['--font-size-h4']);
        $this->assertSame("'Fira Code', monospace", $variables['--font-family-code']);
        $this->assertSame('none', $variables['--header-bg-image']);
        $this->assertSame('transform 1s ease-in-out', $variables['--slider-transition']);
        $this->assertSame('center', $variables['--footer-text-align']);
    }

    public function test_empty_settings_follow_the_main_colors(): void
    {
        $theme = Theme::current();
        $theme->update(['color_primary' => '#16a34a', 'color_secondary' => '#3b0764']);

        $variables = $theme->cssVariables();

        $this->assertSame('#16a34a', $variables['--color-link']);
        $this->assertSame('#16a34a', $variables['--header-nav-link-hover-color']);
        $this->assertSame('#3b0764', $variables['--footer-dark-bg']);
        $this->assertSame('#3b0764', $variables['--header-logo-text-color']);
        $this->assertSame('rgba(22, 163, 74, 0.15)', $variables['--theme-dark-accent']);
    }

    public function test_a_chosen_value_overrides_just_that_setting_and_others_built_on_it_follow(): void
    {
        $theme = Theme::current();
        $theme->update(['options' => ['color_text_muted' => '#111111', 'dark_background_end' => '#000000', 'font_size_h1' => 48]]);

        $variables = $theme->cssVariables();

        $this->assertSame('#111111', $variables['--color-text-muted']);
        $this->assertSame('#111111', $variables['--header-nav-link-color']);
        $this->assertSame('#111111', $variables['--mobile-nav-link-color']);
        $this->assertSame('linear-gradient(135deg, #1e3a8a 0%, #000000 100%)', $variables['--theme-dark-bg']);
        $this->assertSame('48px', $variables['--font-size-h1']);
        $this->assertSame('#2563eb', $variables['--color-link']);
    }

    public function test_footer_layout_settings(): void
    {
        $theme = Theme::current();
        $theme->update(['options' => ['footer_text_align' => 'left', 'footer_text_size' => 16, 'footer_spacing' => 64]]);

        $variables = $theme->cssVariables();

        $this->assertSame('left', $variables['--footer-text-align']);
        $this->assertSame('flex-start', $variables['--footer-justify']);
        $this->assertSame('16px', $variables['--footer-font-size']);
        $this->assertSame('64px', $variables['--footer-padding']);
    }

    public function test_a_header_image_and_slider_speeds(): void
    {
        $theme = Theme::current();
        $theme->update(['options' => ['header_background_image' => 'theme/sky.jpg', 'slider_fade_seconds' => 0.5]]);

        $variables = $theme->cssVariables();

        $this->assertSame("url('/uploads/theme/sky.jpg')", $variables['--header-bg-image']);
        $this->assertSame('opacity 0.5s ease-in-out', $variables['--slider-fade-transition']);
        $this->assertSame('0.5s', $variables['--slider-fade-duration']);
    }

    public function test_the_chosen_quote_display_and_code_fonts_are_loaded(): void
    {
        $theme = Theme::current();
        $theme->update(['options' => ['font_code' => 'JetBrains Mono', 'font_quotes' => 'Lora']]);

        $url = $theme->googleFontsUrl();

        $this->assertStringContainsString('family=JetBrains+Mono', $url);
        $this->assertStringContainsString('family=Lora', $url);
        $this->assertStringContainsString('family=Outfit', $url);
        $this->assertStringNotContainsString('Fira+Code', $url);
    }

    public function test_the_variables_reach_the_page(): void
    {
        Theme::current()->update(['options' => ['color_link' => '#ff00ff']]);

        $this->get('/')->assertSee('--color-link: #ff00ff;', false);
    }

    public function test_every_setting_has_a_working_automatic_value(): void
    {
        $theme = Theme::current();

        foreach (array_keys(ThemeOptions::all()) as $key) {
            $value = $theme->automaticOption($key);

            $this->assertTrue($value !== null || $key === 'header_background_image', "No automatic value for {$key}");
        }

        $this->assertNotEmpty($theme->cssVariables());
    }

    public function test_detailed_settings_are_saved_and_empty_ones_are_left_automatic(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options' => [
                'color_link' => '#ff0000',
                'dark_border' => 'rgba(0, 0, 0, 0.5)',
                'font_size_h2' => 30,
                'footer_text_align' => 'left',
                'logo_hover_animation' => false,
                'card_text' => null,
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $options = Theme::current()->options;

        $this->assertSame('#ff0000', $options['color_link']);
        $this->assertSame('rgba(0, 0, 0, 0.5)', $options['dark_border']);
        $this->assertEquals(30, $options['font_size_h2']);
        $this->assertSame('left', $options['footer_text_align']);
        $this->assertFalse($options['logo_hover_animation']);
        $this->assertArrayNotHasKey('card_text', $options);
        $this->assertSame('0', Theme::current()->cssVariables()['--logo-hover-animation-enabled']);
    }

    public function test_settings_that_are_not_colors_or_sensible_sizes_are_refused(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options' => [
                'color_link' => 'red; } body { display: none',
                'dark_border' => 'rgba(0,0,0,1); } *',
                'font_size_h1' => 500,
                'slider_slide_seconds' => 0,
            ]])
            ->call('save')
            ->assertHasFormErrors(['options.color_link', 'options.dark_border', 'options.font_size_h1', 'options.slider_slide_seconds']);
    }

    public function test_the_empty_settings_show_their_automatic_value(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->assertSee('Automatic: #2563eb')
            ->assertSee('Automatic: 36 px')
            ->assertSee('Automatic: Centered');
    }

    public function test_only_the_chosen_footer_styles_colors_are_shown(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['footer_style' => 'light'])
            ->assertFormFieldVisible('options.footer_light_background')
            ->assertFormFieldHidden('options.footer_dark_background')
            ->assertFormFieldHidden('options.footer_primary_background');
    }

    public function test_reset_puts_every_detailed_setting_back_to_automatic(): void
    {
        $this->actingAs(User::factory()->create());
        Theme::current()->update(['options' => ['color_link' => '#ff0000']]);

        Livewire::test(ThemeSettings::class)->callAction('resetToDefaults');

        $this->assertNull(Theme::current()->options);
    }
}
