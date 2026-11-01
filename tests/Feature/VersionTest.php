<?php

namespace Tests\Feature;

use App\Models\User;
use App\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class VersionTest extends TestCase
{
    use RefreshDatabase;

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        // A folder of its own for the VERSION file, so the real one is never touched
        $this->basePath = sys_get_temp_dir().'/light-cms-version-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->basePath);
        $this->app->setBasePath($this->basePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_a_release_reports_the_version_its_build_wrote(): void
    {
        File::put($this->basePath.'/VERSION', "v0.4.0-alpha\n");

        $this->assertSame('v0.4.0-alpha', Version::current());
        $this->assertSame('Light CMS v0.4.0-alpha', Version::label());
    }

    public function test_a_copy_not_from_a_release_is_the_development_version(): void
    {
        $this->assertSame('development', Version::current());
    }

    public function test_the_admin_shows_the_version(): void
    {
        File::put($this->basePath.'/VERSION', "v0.4.0-alpha\n");
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk()->assertSee('Light CMS v0.4.0-alpha');
    }
}
