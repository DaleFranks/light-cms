<?php

namespace Tests\Feature;

use App\Filament\Auth\RequestPasswordReset;
use App\Models\MailSettings;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_screen_links_to_the_forgot_password_screen(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('/admin/password-reset/request', false);

        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_a_reset_link_is_emailed_using_the_sites_email_settings(): void
    {
        Notification::fake();
        MailSettings::current()->update(['from_address' => 'site@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com']);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'admin@example.com'])
            ->call('request')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertSame('site@example.com', config('mail.from.address'));
    }

    public function test_the_reset_link_leads_to_the_sites_saved_address_whatever_domain_the_request_claimed(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'admin@example.com']);

        // The request is for this test's own address; the site's saved address is elsewhere
        $requestAddress = url('/admin');
        config(['app.url' => 'https://acme.test/blog']);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'admin@example.com'])
            ->call('request')
            ->assertHasNoFormErrors();

        Notification::assertSentTo(User::query()->sole(), ResetPassword::class, function (ResetPassword $notification): bool {
            return str_starts_with($notification->url, 'https://acme.test/blog/admin/password-reset/reset?');
        });

        // Addresses on the screen itself still follow the request
        $this->assertSame($requestAddress, url('/admin'));
    }

    public function test_a_reset_link_that_cant_be_sent_says_so_and_what_to_do_instead(): void
    {
        Notification::fake();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Mail server down'));
        User::factory()->create(['email' => 'admin@example.com']);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'admin@example.com'])
            ->call('request')
            ->assertNotified('The email couldn\'t be sent');
    }

    public function test_an_email_address_is_still_required(): void
    {
        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => ''])
            ->call('request')
            ->assertHasFormErrors(['email' => 'required']);
    }
}
