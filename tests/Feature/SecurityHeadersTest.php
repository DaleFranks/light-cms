<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function addresses(): array
    {
        return [
            'a public page' => ['/about'],
            'a page that does not exist' => ['/no-such-page'],
            'the admin login' => ['/admin/login'],
            'the admin' => ['/admin'],
            'the sitemap' => ['/sitemap.xml'],
        ];
    }

    #[DataProvider('addresses')]
    public function test_every_response_carries_the_browser_security_headers(string $address): void
    {
        Entry::factory()->published()->create(['slug' => 'about']);
        $this->actingAs(User::factory()->create());

        $this->get($address)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_redirects_carry_them_too(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->get('http://example.com/about')
            ->assertStatus(301)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
