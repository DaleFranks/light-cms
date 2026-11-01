<?php

namespace Tests\Feature;

use App\DatabaseUpdate;
use App\Installation;
use App\Models\Entry;
use App\Models\Form;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installation_check' => true]);

        // A storage folder of its own, so the real site's "installed" marker is never touched
        $this->storagePath = sys_get_temp_dir().'/light-cms-setup-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->storagePath.'/app');
        $this->app->useStoragePath($this->storagePath);

        // The database these tests use is already up to date
        DatabaseUpdate::markApplied();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storagePath);

        parent::tearDown();
    }

    private function setUpDetails(array $overrides = []): array
    {
        return [
            'site_name' => 'Acme Studio',
            'name' => 'Ann Admin',
            'email' => 'ann@acme.test',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
            ...$overrides,
        ];
    }

    public function test_until_setup_is_done_every_visit_goes_to_the_setup_screen(): void
    {
        $this->get('/')->assertRedirect(route('setup.show'));
        $this->get('/about')->assertRedirect(route('setup.show'));
        $this->get('/admin/login')->assertRedirect(route('setup.show'));
        $this->get('/up')->assertOk();
    }

    public function test_the_setup_screen_asks_for_the_site_name_and_an_admin_account(): void
    {
        $this->get('/setup')
            ->assertOk()
            ->assertSee('Set up your site')
            ->assertSee('name="site_name"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_finishing_setup_creates_the_account_and_starting_content_and_logs_in(): void
    {
        $this->post('/setup', $this->setUpDetails())->assertRedirect('/admin');

        $user = User::query()->sole();
        $this->assertSame(['Ann Admin', 'ann@acme.test'], [$user->name, $user->email]);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Acme Studio', Theme::current()->site_name);
        $this->assertTrue(Entry::query()->where('path', Entry::HOME_PATH)->exists());
        $this->assertSame(['contact', 'newsletter'], Form::query()->orderBy('slug')->pluck('slug')->all());
        $this->assertFileExists($this->storagePath.'/app/installed');
    }

    public function test_once_setup_is_done_the_site_works_and_the_setup_screen_is_gone(): void
    {
        $this->post('/setup', $this->setUpDetails());
        auth()->logout();

        $this->get('/')->assertOk()->assertSee('Acme Studio');
        $this->get('/admin/login')->assertOk();
        $this->get('/setup')->assertNotFound();
        $this->post('/setup', $this->setUpDetails(['email' => 'intruder@evil.test']))->assertNotFound();
        $this->assertSame(1, User::query()->count());
    }

    public function test_mistakes_are_shown_and_nothing_is_created(): void
    {
        $this->from('/setup')
            ->post('/setup', $this->setUpDetails(['site_name' => '', 'email' => 'not-an-email', 'password_confirmation' => 'different']))
            ->assertRedirect('/setup');

        $this->assertSame(0, User::query()->count());
        $this->assertFalse(Installation::isComplete());

        $this->get('/setup')
            ->assertSee('The site name field is required.')
            ->assertSee('The email address field must be a valid email address.')
            ->assertSee('The password field confirmation does not match.');
    }

    public function test_passwords_must_be_at_least_eight_characters(): void
    {
        $this->from('/setup')->post('/setup', $this->setUpDetails(['password' => 'short', 'password_confirmation' => 'short']));

        $this->get('/setup')->assertSee('The password field must be at least 8 characters.');
    }

    public function test_a_site_that_already_has_an_admin_account_counts_as_set_up(): void
    {
        User::factory()->create();

        $this->get('/setup')->assertNotFound();
        $this->assertFileExists($this->storagePath.'/app/installed');
    }

    public function test_reserved_addresses_include_the_setup_screen_and_uploads(): void
    {
        $this->assertContains('setup', Entry::RESERVED_PATHS);
        $this->assertContains('uploads', Entry::RESERVED_PATHS);
        $this->assertContains('forms', Entry::RESERVED_PATHS);
    }
}
