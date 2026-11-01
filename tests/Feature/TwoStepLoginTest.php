<?php

namespace Tests\Feature;

use App\Filament\Auth\EmailAuthentication;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\MailSettings;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class TwoStepLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_two_step_login_the_password_is_enough(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'admin@example.com', 'password' => 'secret-pass'])
            ->call('authenticate');

        $this->assertAuthenticated();
    }

    public function test_with_an_authenticator_app_the_password_alone_does_not_log_in(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass']);
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        Livewire::test(Login::class)
            ->fillForm(['email' => 'admin@example.com', 'password' => 'secret-pass'])
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_with_emailed_codes_a_code_is_sent_using_the_sites_email_settings(): void
    {
        Notification::fake();
        MailSettings::current()->update(['from_address' => 'site@example.com']);
        $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass', 'has_email_authentication' => true]);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'admin@example.com', 'password' => 'secret-pass'])
            ->call('authenticate');

        $this->assertGuest();
        Notification::assertSentTo($user, VerifyEmailAuthentication::class);
        $this->assertSame('site@example.com', config('mail.from.address'));
    }

    public function test_a_code_that_cant_be_emailed_says_so_instead_of_failing(): void
    {
        Notification::fake();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Mail server down'));
        $user = User::factory()->create(['has_email_authentication' => true]);

        $this->assertFalse(EmailAuthentication::make()->sendCode($user));
    }

    public function test_the_profile_offers_two_step_login(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/profile')
            ->assertOk()
            ->assertSee('Authenticator app')
            ->assertSee('Email verification codes');
    }

    public function test_an_admin_can_turn_off_another_admins_two_step_login_but_not_their_own(): void
    {
        $me = User::factory()->create(['has_email_authentication' => true]);
        $other = User::factory()->create(['has_email_authentication' => true]);
        $other->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $other->saveAppAuthenticationRecoveryCodes(['code-one']);
        $this->actingAs($me);

        Livewire::test(ManageUsers::class)
            ->assertActionHidden(TestAction::make('turnOffTwoStepLogin')->table($me))
            ->callAction(TestAction::make('turnOffTwoStepLogin')->table($other))
            ->assertNotified('Two-step login turned off');

        $other->refresh();
        $this->assertFalse($other->hasTwoStepLogin());
        $this->assertNull($other->getAppAuthenticationRecoveryCodes());
        $this->assertTrue($me->refresh()->hasTwoStepLogin());
    }
}
