<?php

namespace Tests\Feature;

use App\EntryStatus;
use App\Filament\Pages\Analytics;
use App\Models\AnalyticsSettings;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLE_VERIFICATION = 'aBcD1234efGh5678ijKl9012mnOp3456qrSt7890uvW';

    private const BING_VERIFICATION = '0123456789ABCDEF0123456789ABCDEF';

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/analytics')->assertRedirect('/admin/login');
    }

    public function test_the_screen_loads(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/analytics')
            ->assertOk()
            ->assertSee('Analytics &amp; Verification', false)
            ->assertSee('For Plausible, Fathom, Matomo and similar services');
    }

    public function test_the_ids_are_picked_out_of_the_snippets_each_service_gives(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Analytics::class)
            ->fillForm([
                'google_analytics_id' => '<script async src="https://www.googletagmanager.com/gtag/js?id=G-AB12CD34EF"></script> <script> gtag(\'config\', \'G-AB12CD34EF\'); </script>',
                'google_tag_manager_id' => "})(window,document,'script','dataLayer','gtm-ab12cd3');</script>",
                'meta_pixel_id' => "fbq('init', '1234567890123456'); fbq('track', 'PageView');",
                'google_verification' => '<meta name="google-site-verification" content="'.self::GOOGLE_VERIFICATION.'" />',
                'bing_verification' => '<meta name="msvalidate.01" content="'.strtolower(self::BING_VERIFICATION).'" />',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSchemaStateSet([
                'google_analytics_id' => 'G-AB12CD34EF',
                'google_tag_manager_id' => 'GTM-AB12CD3',
            ]);

        $settings = AnalyticsSettings::current();
        $this->assertSame('G-AB12CD34EF', $settings->google_analytics_id);
        $this->assertSame('GTM-AB12CD3', $settings->google_tag_manager_id);
        $this->assertSame('1234567890123456', $settings->meta_pixel_id);
        $this->assertSame(self::GOOGLE_VERIFICATION, $settings->google_verification);
        $this->assertSame(self::BING_VERIFICATION, $settings->bing_verification);
    }

    public function test_bare_ids_and_other_forms_are_accepted(): void
    {
        $this->assertSame('G-AB12CD34EF', AnalyticsSettings::googleAnalyticsIdFrom(' G-AB12CD34EF '));
        $this->assertSame('1234567890123456', AnalyticsSettings::metaPixelIdFrom('1234567890123456'));
        $this->assertSame('1234567890123456', AnalyticsSettings::metaPixelIdFrom('<img src="https://www.facebook.com/tr?id=1234567890123456&ev=PageView&noscript=1"/>'));
        $this->assertSame(self::GOOGLE_VERIFICATION, AnalyticsSettings::googleVerificationFrom(self::GOOGLE_VERIFICATION));
        $this->assertSame(self::GOOGLE_VERIFICATION, AnalyticsSettings::googleVerificationFrom('google-site-verification='.self::GOOGLE_VERIFICATION));
        $this->assertSame(self::BING_VERIFICATION, AnalyticsSettings::bingVerificationFrom(self::BING_VERIFICATION));
    }

    public function test_something_that_isnt_an_id_is_refused(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Analytics::class)
            ->fillForm([
                'google_analytics_id' => 'UA-12345-1',
                'google_tag_manager_id' => 'G-AB12CD34EF',
                'meta_pixel_id' => 'my pixel',
                'google_verification' => '"><script>alert(1)</script>',
                'bing_verification' => 'not-a-code',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'google_analytics_id',
                'google_tag_manager_id',
                'meta_pixel_id',
                'google_verification',
                'bing_verification',
            ]);

        $this->assertNull(AnalyticsSettings::current()->google_verification);
    }

    public function test_emptying_a_box_clears_it(): void
    {
        $this->actingAs(User::factory()->create());
        AnalyticsSettings::current()->update(['google_analytics_id' => 'G-AB12CD34EF']);

        Livewire::test(Analytics::class)
            ->fillForm(['google_analytics_id' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(AnalyticsSettings::current()->google_analytics_id);
    }

    public function test_visitors_get_the_tracking_and_verification_code_in_the_right_places(): void
    {
        $this->fillInEverything();
        Entry::factory()->published()->create(['slug' => 'home']);

        $html = $this->get('/')->assertOk()->getContent();

        $head = substr($html, 0, strpos($html, '</head>'));
        $bodyStart = substr($html, strpos($html, '<body'), 600);

        $this->assertStringContainsString('<meta name="google-site-verification" content="'.self::GOOGLE_VERIFICATION.'">', $head);
        $this->assertStringContainsString('<meta name="msvalidate.01" content="'.self::BING_VERIFICATION.'">', $head);
        $this->assertStringContainsString("'dataLayer','GTM-AB12CD3'", $head);
        $this->assertStringContainsString('gtag/js?id=G-AB12CD34EF', $head);
        $this->assertStringContainsString("fbq('init','1234567890123456')", $head);
        $this->assertMatchesRegularExpression('/<script defer data-domain="example.com"[^>]*><\/script>\s*$/', $head);

        $this->assertStringContainsString('googletagmanager.com/ns.html?id=GTM-AB12CD3', $bodyStart);
        $this->assertStringContainsString('facebook.com/tr?id=1234567890123456', $bodyStart);

        $this->assertMatchesRegularExpression('/<script>window\.chatWidget = true;<\/script>\s*<\/body>/', $html);
    }

    public function test_the_not_found_page_is_tracked(): void
    {
        $this->fillInEverything();

        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('gtag/js?id=G-AB12CD34EF', false)
            ->assertSee('data-domain="example.com"', false);
    }

    public function test_nothing_is_tracked_while_signed_in_to_the_admin_but_verification_stays(): void
    {
        $this->fillInEverything();
        Entry::factory()->published()->create(['slug' => 'home']);
        $this->actingAs(User::factory()->create());

        $this->get('/')
            ->assertOk()
            ->assertSee('google-site-verification', false)
            ->assertSee('msvalidate.01', false)
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('fbq(', false)
            ->assertDontSee('data-domain', false)
            ->assertDontSee('chatWidget', false);
    }

    public function test_previews_are_never_tracked(): void
    {
        $this->fillInEverything();
        $this->actingAs(User::factory()->create());
        Entry::factory()->create(['slug' => 'plans', 'status' => EntryStatus::Draft]);

        $this->get('/plans')
            ->assertOk()
            ->assertSee('This page is a draft', false)
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('data-domain', false);
    }

    public function test_turning_tracking_off_keeps_verification(): void
    {
        $this->fillInEverything();
        AnalyticsSettings::current()->update(['tracking_off' => true]);
        Entry::factory()->published()->create(['slug' => 'home']);

        $this->get('/')
            ->assertOk()
            ->assertSee('google-site-verification', false)
            ->assertSee('msvalidate.01', false)
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('fbq(', false)
            ->assertDontSee('data-domain', false)
            ->assertDontSee('chatWidget', false);

        // Turning it off clears nothing
        $this->assertSame('G-AB12CD34EF', AnalyticsSettings::current()->google_analytics_id);
    }

    public function test_pages_have_none_of_it_until_it_is_set_up(): void
    {
        Entry::factory()->published()->create(['slug' => 'home']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('google-site-verification', false)
            ->assertDontSee('msvalidate.01', false)
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('fbq(', false);
    }

    private function fillInEverything(): void
    {
        AnalyticsSettings::current()->update([
            'google_analytics_id' => 'G-AB12CD34EF',
            'google_tag_manager_id' => 'GTM-AB12CD3',
            'meta_pixel_id' => '1234567890123456',
            'google_verification' => self::GOOGLE_VERIFICATION,
            'bing_verification' => self::BING_VERIFICATION,
            'head_code' => '<script defer data-domain="example.com" src="https://plausible.io/js/script.js"></script>',
            'footer_code' => '<script>window.chatWidget = true;</script>',
        ]);
    }
}
