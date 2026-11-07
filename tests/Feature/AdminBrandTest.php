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

    public function test_authenticator_apps_list_the_site_without_a_colon_in_its_name(): void
    {
        Theme::factory()->create(['site_name' => 'Dale Franks']);

        $this->assertSame('RamaSite Admin - Dale Franks', AppAuthentication::make()->getBrandName());
    }
}
