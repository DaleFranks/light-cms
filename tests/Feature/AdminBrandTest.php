<?php

namespace Tests\Feature;

use App\Filament\Auth\AppAuthentication;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_names_the_product_and_the_site(): void
    {
        Theme::factory()->create(['site_name' => 'Dale Franks']);
        $this->actingAs(User::factory()->create());

        $this->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Dashboard', '-', 'RamaSite Admin: Dale Franks', '</title>'], false)
            ->assertSeeInOrder(['</head>', 'RamaSite Admin: Dale Franks'], false);
    }

    public function test_the_sign_in_screen_names_the_product_and_the_site(): void
    {
        Theme::factory()->create(['site_name' => 'Dale Franks']);

        $this->get('/admin/login')->assertOk()->assertSee('RamaSite Admin: Dale Franks');
    }

    public function test_a_site_with_no_name_shows_just_the_product(): void
    {
        Theme::factory()->create(['site_name' => '']);
        $this->actingAs(User::factory()->create());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('RamaSite Admin')
            ->assertDontSee('RamaSite Admin:');
    }

    public function test_the_brand_ignores_the_app_name_in_the_settings_file(): void
    {
        config(['app.name' => 'Some Other Name']);
        Theme::factory()->create(['site_name' => 'Dale Franks']);
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk()->assertDontSee('Some Other Name');
    }

    public function test_the_mark_sits_beside_the_name_at_the_top_of_the_admin_and_on_the_sign_in_screen(): void
    {
        Theme::factory()->create(['site_name' => 'Dale Franks']);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSeeInOrder(['class="ramasite-brand"', 'class="ramasite-mark"', 'RamaSite Admin: Dale Franks'], false);

        $this->actingAs(User::factory()->create());

        $this->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['class="ramasite-brand"', 'class="ramasite-mark"', 'RamaSite Admin: Dale Franks'], false);
    }

    public function test_each_mark_on_a_page_cuts_its_gaps_with_its_own_mask(): void
    {
        $this->actingAs(User::factory()->create());

        $page = $this->get('/admin')->assertOk()->getContent();
        preg_match_all('/<mask id="(ramasite-gaps-[^"]+)"/', $page, $masks);

        $this->assertNotEmpty($masks[1]);
        $this->assertSame($masks[1], array_unique($masks[1]));
    }

    public function test_the_admin_tab_icon_is_the_mark_in_light_and_dark_colors(): void
    {
        $page = $this->get('/admin/login')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<link rel="icon" href="data:image\/svg\+xml;base64,([^"]+)"/', $page, $icon));
        $svg = base64_decode($icon[1]);
        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('.rs-bracket{fill:#2C6098}', $svg);
        $this->assertStringContainsString('@media (prefers-color-scheme: dark){.rs-bracket{fill:#46C8E4}', $svg);
    }

    public function test_authenticator_apps_list_the_site_without_a_colon_in_its_name(): void
    {
        Theme::factory()->create(['site_name' => 'Dale Franks']);

        $this->assertSame('RamaSite Admin - Dale Franks', AppAuthentication::make()->getBrandName());
    }
}
