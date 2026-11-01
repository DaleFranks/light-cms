<?php

namespace Tests\Feature;

use App\Filament\Pages\ThemeSettings;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/theme-settings')->assertRedirect('/admin/login');
    }

    public function test_the_theme_can_be_saved(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm([
                'site_name' => 'Acme Studio',
                'color_primary' => '#ff0000',
                'font_headings' => 'Playfair Display',
                'footer_style' => 'light',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $theme = Theme::current();
        $this->assertSame('Acme Studio', $theme->site_name);
        $this->assertSame('#ff0000', $theme->color_primary);
        $this->assertSame('Playfair Display', $theme->font_headings);
        $this->assertSame('light', $theme->footer_style->value);
    }

    public function test_a_color_must_be_a_hex_code(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['color_primary' => 'red; } body { display: none'])
            ->call('save')
            ->assertHasFormErrors(['color_primary']);
    }

    public function test_only_fonts_from_the_list_can_be_chosen(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['font_body' => 'Comic Sans MS'])
            ->call('save')
            ->assertHasFormErrors(['font_body']);
    }

    public function test_a_jpg_can_be_the_browser_tab_icon(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->create());

        Livewire::test(ThemeSettings::class)
            ->fillForm(['favicon_path' => UploadedFile::fake()->image('icon.jpg', 64, 64)])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringEndsWith('.jpg', Theme::current()->favicon_path);
    }

    public function test_reset_restores_the_default_look_but_keeps_the_site_name(): void
    {
        $this->actingAs(User::factory()->create());
        Theme::current()->update(['site_name' => 'Acme Studio', 'color_primary' => '#ff0000', 'custom_css' => 'p {}']);

        Livewire::test(ThemeSettings::class)
            ->callAction('resetToDefaults')
            ->assertSchemaStateSet(['color_primary' => Theme::DEFAULTS['color_primary']]);

        $theme = Theme::current();
        $this->assertSame('Acme Studio', $theme->site_name);
        $this->assertSame(Theme::DEFAULTS['color_primary'], $theme->color_primary);
        $this->assertNull($theme->custom_css);
    }
}
