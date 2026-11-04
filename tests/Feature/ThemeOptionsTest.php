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

    public function test_the_header_is_taller_automatically_when_the_site_name_is_below_the_logo(): void
    {
        $theme = Theme::current();

        $this->assertSame('70px', $theme->cssVariables()['--header-height']);

        $theme->update(['site_name_position' => 'below']);

        $this->assertSame('96px', $theme->cssVariables()['--header-height']);
        $this->assertSame('96px', $theme->cssVariables()['--header-height-scrolled']);
        $this->assertStringContainsString('site-name-below', $theme->bodyClasses());

        $theme->update(['options' => ['header_height_scrolled' => 72]]);

        $this->assertSame('72px', $theme->cssVariables()['--header-height-scrolled']);
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

    public function test_line_height_starts_at_1_6_and_can_be_changed(): void
    {
        $this->assertSame('1.6', Theme::current()->cssVariables()['--line-height-content']);

        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options.line_height' => 1.7])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('1.7', Theme::current()->cssVariables()['--line-height-content']);
        $this->get('/')->assertSee('--line-height-content: 1.7', false);
    }

    public function test_the_line_height_box_always_shows_a_number(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->assertSchemaStateSet(['options.line_height' => 1.6])
            ->set('data.options.line_height', '')
            ->assertSchemaStateSet(['options.line_height' => 1.6]);
    }

    public function test_a_line_height_left_at_the_automatic_value_isnt_saved_as_a_choice(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['site_name' => 'Acme'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertArrayNotHasKey('line_height', Theme::current()->options ?? []);
    }

    public function test_reset_puts_line_height_back_to_1_6(): void
    {
        $this->actingAs(User::factory()->create());
        Theme::current()->update(['options' => ['line_height' => 2]]);

        Livewire::test(ThemeSettings::class)
            ->assertSchemaStateSet(['options.line_height' => 2])
            ->callAction('resetToDefaults')
            ->assertSchemaStateSet(['options.line_height' => 1.6]);

        $this->assertSame('1.6', Theme::current()->cssVariables()['--line-height-content']);
    }

    public function test_a_line_height_between_the_arrow_steps_can_be_saved(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options.line_height' => 1.65])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('1.65', Theme::current()->cssVariables()['--line-height-content']);
    }

    public function test_line_heights_outside_the_range_are_refused(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([1.1, 2.6, '1.5; } body { display: none'] as $lineHeight) {
            Livewire::test(ThemeSettings::class)
                ->fillForm(['options.line_height' => $lineHeight])
                ->call('save')
                ->assertHasFormErrors(['options.line_height']);
        }
    }

    public function test_text_alignment_starts_at_left_and_can_be_justified(): void
    {
        $this->assertSame('inherit', Theme::current()->cssVariables()['--text-align-content']);

        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->assertSchemaStateSet(['options.text_alignment' => 'left'])
            ->fillForm(['options.text_alignment' => 'justified'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('justify', Theme::current()->cssVariables()['--text-align-content']);
        $this->get('/')->assertSee('--text-align-content: justify', false);
    }

    public function test_only_left_or_justified_can_be_chosen_for_text_alignment(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options.text_alignment' => 'center'])
            ->call('save')
            ->assertHasFormErrors(['options.text_alignment']);
    }

    public function test_text_alignment_left_at_left_isnt_saved_as_a_choice(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['site_name' => 'Acme'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertArrayNotHasKey('text_alignment', Theme::current()->options ?? []);
    }

    public function test_heading_levels_follow_the_headings_settings_until_given_their_own(): void
    {
        $theme = Theme::current();
        $theme->update(['font_headings' => 'Lora', 'options' => ['heading_color' => '#111111', 'heading_weight' => '500']]);

        $variables = $theme->cssVariables();
        $this->assertSame("'Lora', serif", $variables['--font-family-h3']);
        $this->assertSame('#111111', $variables['--color-h3']);
        $this->assertSame('500', $variables['--font-weight-h3']);

        $theme->update(['options' => ['heading_color' => '#111111', 'heading_weight' => '500', 'heading_font_h3' => 'Lobster', 'heading_color_h3' => '#222222', 'heading_weight_h3' => '400']]);

        $variables = $theme->cssVariables();
        $this->assertSame("'Lobster', cursive", $variables['--font-family-h3']);
        $this->assertSame('#222222', $variables['--color-h3']);
        $this->assertSame('400', $variables['--font-weight-h3']);
        $this->assertSame("'Lora', serif", $variables['--font-family-h4']);
        $this->assertSame('#111111', $variables['--color-h4']);
    }

    public function test_untouched_headings_and_page_titles_keep_their_original_look(): void
    {
        $variables = Theme::current()->cssVariables();

        $this->assertSame(Theme::current()->color_secondary, $variables['--color-heading']);
        $this->assertSame('700', $variables['--font-weight-heading']);
        $this->assertSame('1.25', $variables['--line-height-heading']);
        $this->assertSame('36px', $variables['--font-size-h1']);
        $this->assertSame('2.5rem', $variables['--page-title-size']);
        $this->assertSame('1.15', $variables['--page-title-line-height']);
    }

    public function test_page_titles_follow_a_chosen_heading_size_and_line_height(): void
    {
        $theme = Theme::current();
        $theme->update(['options' => ['font_size_h1' => 50, 'heading_line_height' => 1.4]]);

        $variables = $theme->cssVariables();
        $this->assertSame('50px', $variables['--page-title-size']);
        $this->assertSame('50px', $variables['--font-size-h1']);
        $this->assertSame('1.4', $variables['--page-title-line-height']);
        $this->assertSame('1.4', $variables['--line-height-heading']);
    }

    public function test_a_font_chosen_for_one_heading_level_is_loaded(): void
    {
        $theme = Theme::current();
        $theme->update(['options' => ['heading_font_h4' => 'Lobster']]);

        $this->assertSame(1, substr_count($theme->googleFontsUrl(), 'family=Lobster'));
        $this->assertSame(1, substr_count($theme->googleFontsUrl(), 'family=Noto+Serif'));
    }

    public function test_heading_settings_are_validated(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['options' => [
                'heading_line_height' => 2.5,
                'heading_weight' => '900',
                'heading_weight_h2' => '100',
                'heading_font_h2' => 'Comic Sans MS',
                'heading_color_h2' => 'red; } *',
            ]])
            ->call('save')
            ->assertHasFormErrors(['options.heading_line_height', 'options.heading_weight', 'options.heading_weight_h2', 'options.heading_font_h2', 'options.heading_color_h2']);
    }

    public function test_heading_settings_can_be_saved_through_the_screen(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->assertSchemaStateSet(['options.heading_weight' => '700', 'options.heading_line_height' => 1.25])
            ->fillForm(['options' => [
                'heading_weight_h2' => '500',
                'heading_font_h2' => 'Lobster',
                'heading_color_h2' => '#528bbc',
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $options = Theme::current()->options;
        $this->assertEquals(500, $options['heading_weight_h2']);
        $this->assertSame('Lobster', $options['heading_font_h2']);
        $this->assertSame('#528bbc', $options['heading_color_h2']);
        $this->assertArrayNotHasKey('heading_weight', $options);
        $this->assertArrayNotHasKey('heading_line_height', $options);
    }

    public function test_footer_menu_links_keep_their_sizes_until_a_footer_text_size_is_chosen(): void
    {
        $variables = Theme::current()->cssVariables();

        $this->assertSame('13.6px', $variables['--footer-font-size']);
        $this->assertSame('0.9375rem', $variables['--footer-link-size']);
        $this->assertSame('0.875rem', $variables['--footer-column-link-size']);
        $this->assertSame('1rem', $variables['--footer-column-heading-size']);
    }

    public function test_footer_menu_links_follow_a_chosen_footer_text_size(): void
    {
        $theme = Theme::current();
        $theme->update(['options' => ['footer_text_size' => 20]]);

        $variables = $theme->cssVariables();

        $this->assertSame('20px', $variables['--footer-font-size']);
        $this->assertSame('20px', $variables['--footer-link-size']);
        $this->assertSame('20px', $variables['--footer-column-link-size']);
        $this->assertSame('23px', $variables['--footer-column-heading-size']);
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
            ->assertSee('Auto: #2563eb')
            ->assertSee('Auto: 36 px')
            ->assertSee('Auto: Centered');
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
