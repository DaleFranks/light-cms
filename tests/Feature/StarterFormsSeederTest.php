<?php

namespace Tests\Feature;

use App\Models\Form;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StarterFormsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StarterFormsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_site_starts_with_a_contact_form_and_a_newsletter_form(): void
    {
        $this->seed(DatabaseSeeder::class);

        $contact = Form::query()->where('slug', 'contact')->sole();
        $newsletter = Form::query()->where('slug', 'newsletter')->sole();

        $this->assertSame(['name', 'email', 'message'], $contact->formFields()->pluck('key')->all());
        $this->assertTrue($contact->formFields()->every(fn (array $field): bool => $field['required']));
        $this->assertSame('Send message', $contact->submit_label);
        $this->assertSame(['email'], $newsletter->formFields()->pluck('key')->all());
        $this->assertSame('Subscribe', $newsletter->submit_label);
        $this->assertSame([], $contact->notifyAddresses());
    }

    public function test_forms_the_site_already_has_are_left_alone(): void
    {
        Form::factory()->create(['slug' => 'contact', 'name' => 'Get in touch']);

        $this->seed(StarterFormsSeeder::class);
        $this->seed(StarterFormsSeeder::class);

        $this->assertSame('Get in touch', Form::query()->where('slug', 'contact')->sole()->name);
        $this->assertSame(2, Form::query()->count());
    }

    public function test_the_starter_forms_can_be_placed_in_a_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = Form::query()->where('slug', 'newsletter')->sole()->html();

        $this->assertStringContainsString('id="form-newsletter"', $html);
        $this->assertStringContainsString('>Subscribe</button>', $html);
    }
}
