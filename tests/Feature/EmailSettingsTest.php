<?php

namespace Tests\Feature;

use App\Filament\Pages\EmailSettings;
use App\Mail\TestEmail;
use App\Models\MailSettings;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class EmailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/email-settings')->assertRedirect('/admin/login');
    }

    public function test_by_default_email_comes_from_noreply_at_the_sites_domain_with_the_site_name(): void
    {
        Theme::factory()->create(['site_name' => 'Acme']);
        $this->get('http://www.acme.test/');

        $settings = MailSettings::current();

        $this->assertSame('noreply@acme.test', $settings->fromAddress());
        $this->assertSame('Acme', $settings->fromName());
        $this->assertFalse($settings->usesSmtp());
    }

    public function test_smtp_settings_are_saved_with_the_password_encrypted(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EmailSettings::class)
            ->fillForm([
                'from_address' => 'hello@acme.test',
                'use_smtp' => true,
                'smtp_host' => 'smtp.acme.test',
                'smtp_encryption' => 'ssl',
                'smtp_username' => 'hello@acme.test',
                'smtp_password' => 'secret-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = MailSettings::current();
        $this->assertSame('secret-password', $settings->smtp_password);
        $this->assertNotSame('secret-password', DB::table('mail_settings')->value('smtp_password'));
    }

    public function test_leaving_the_password_empty_keeps_the_saved_one(): void
    {
        $this->actingAs(User::factory()->create());
        MailSettings::current()->update(['use_smtp' => true, 'smtp_host' => 'smtp.acme.test', 'smtp_password' => 'kept']);

        Livewire::test(EmailSettings::class)
            ->assertSchemaStateSet(['smtp_password' => null])
            ->fillForm(['smtp_username' => 'someone'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('kept', MailSettings::current()->smtp_password);
    }

    public function test_smtp_needs_a_server(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EmailSettings::class)
            ->fillForm(['use_smtp' => true, 'smtp_host' => ''])
            ->call('save')
            ->assertHasFormErrors(['smtp_host' => 'required']);
    }

    public function test_applying_smtp_settings_switches_the_mailer_to_that_server(): void
    {
        MailSettings::current()->update([
            'from_address' => 'hello@acme.test',
            'use_smtp' => true,
            'smtp_host' => 'smtp.acme.test',
            'smtp_encryption' => 'ssl',
            'smtp_username' => 'user',
            'smtp_password' => 'pass',
        ]);

        MailSettings::current()->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('hello@acme.test', config('mail.from.address'));
        $this->assertSame(['smtps', 'smtp.acme.test', 465, 'user', 'pass'], [
            config('mail.mailers.smtp.scheme'),
            config('mail.mailers.smtp.host'),
            config('mail.mailers.smtp.port'),
            config('mail.mailers.smtp.username'),
            config('mail.mailers.smtp.password'),
        ]);
    }

    public function test_a_test_email_is_sent_to_the_logged_in_user(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['email' => 'me@acme.test']));

        Livewire::test(EmailSettings::class)
            ->callAction('sendTestEmail')
            ->assertNotified('Test email sent to me@acme.test');

        Mail::assertSent(TestEmail::class, fn (TestEmail $mail): bool => $mail->hasTo('me@acme.test'));
    }

    public function test_saving_a_screen_opened_before_the_settings_changed_saves_nothing_and_asks(): void
    {
        $this->actingAs(User::factory()->create());

        $staleScreen = Livewire::test(EmailSettings::class);

        MailSettings::current()->update(['from_address' => 'newer@acme.test']);

        $staleScreen
            ->fillForm(['from_name' => 'Stale name'])
            ->call('save')
            ->assertActionMounted('settingsChanged');

        $settings = MailSettings::current();
        $this->assertSame('newer@acme.test', $settings->from_address);
        $this->assertNull($settings->from_name);
    }

    public function test_a_stale_screen_can_save_its_version_anyway(): void
    {
        $this->actingAs(User::factory()->create());

        $staleScreen = Livewire::test(EmailSettings::class);

        MailSettings::current()->update(['from_address' => 'newer@acme.test']);

        $staleScreen
            ->fillForm(['from_name' => 'Stale name'])
            ->call('save')
            ->callMountedAction();

        $settings = MailSettings::current();
        $this->assertNull($settings->from_address);
        $this->assertSame('Stale name', $settings->from_name);
    }

    public function test_a_stale_screen_asks_before_sending_a_test_email(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());

        $staleScreen = Livewire::test(EmailSettings::class);

        MailSettings::current()->update(['from_address' => 'newer@acme.test']);

        $staleScreen
            ->fillForm(['from_name' => 'Stale name'])
            ->callAction('sendTestEmail')
            ->assertActionMounted('settingsChanged');

        Mail::assertNothingSent();
        $this->assertNull(MailSettings::current()->from_name);
    }

    public function test_saving_repeatedly_doesnt_warn(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EmailSettings::class)
            ->fillForm(['from_name' => 'First'])
            ->call('save')
            ->assertActionNotMounted('settingsChanged')
            ->fillForm(['from_name' => 'Second', 'smtp_password' => 'secret-password'])
            ->call('save')
            ->assertActionNotMounted('settingsChanged')
            ->fillForm(['from_name' => 'Third'])
            ->call('save')
            ->assertActionNotMounted('settingsChanged');

        $this->assertSame('Third', MailSettings::current()->from_name);
    }
}
