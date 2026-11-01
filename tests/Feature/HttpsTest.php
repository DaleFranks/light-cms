<?php

namespace Tests\Feature;

use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Entry::factory()->published()->create(['slug' => 'about']);
    }

    public function test_an_https_site_sends_plain_http_visits_to_https(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->get('http://example.com/about?ref=old')
            ->assertStatus(301)
            ->assertRedirect('https://example.com/about?ref=old');
    }

    public function test_an_https_site_marks_the_login_cookie_https_only(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->get('https://example.com/admin/login')->assertOk();

        $this->assertTrue(config('session.secure'));
    }

    public function test_https_reported_by_the_hosts_front_server_is_accepted(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->get('http://example.com/about', ['X-Forwarded-Proto' => 'https'])->assertOk();
    }

    public function test_a_site_without_https_is_left_alone(): void
    {
        config(['app.url' => 'http://127.0.0.1:8001']);

        $this->get('http://127.0.0.1:8001/about')->assertOk();
        $this->assertNotTrue(config('session.secure'));
    }
}
