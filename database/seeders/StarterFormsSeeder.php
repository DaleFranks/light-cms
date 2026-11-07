<?php

namespace Database\Seeders;

use App\Models\Form;
use Illuminate\Database\Seeder;

/**
 * Creates the old flat-file CMS's two forms, Contact and Newsletter, as ordinary
 * forms to start from. Each is skipped if the site already has a form with its
 * slug, and can be edited or deleted like any other.
 */
class StarterFormsSeeder extends Seeder
{
    public function run(): void
    {
        Form::query()->firstOrCreate(['slug' => 'contact'], [
            'name' => 'Contact',
            'submit_label' => 'Send message',
            'success_message' => 'Thank you! Your message has been received, and we\'ll get back to you shortly.',
            'fields' => [
                ['label' => 'Name', 'type' => 'text', 'required' => true],
                ['label' => 'Email', 'type' => 'email', 'required' => true],
                ['label' => 'Message', 'type' => 'textarea', 'required' => true],
            ],
        ]);

        Form::query()->firstOrCreate(['slug' => 'newsletter'], [
            'name' => 'Newsletter',
            'submit_label' => 'Subscribe',
            'success_message' => 'Thank you! You\'ve been subscribed to our newsletter.',
            'fields' => [
                ['label' => 'Email', 'type' => 'email', 'required' => true],
            ],
        ]);
    }
}
