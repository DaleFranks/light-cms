<?php

namespace Database\Seeders;

use App\EntryStatus;
use App\EntryType;
use App\Models\Entry;
use Illuminate\Database\Seeder;

/**
 * Creates a placeholder home page, unless the site already has one.
 */
class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        Entry::query()->firstOrCreate(['path' => Entry::HOME_PATH], [
            'type' => EntryType::Page,
            'title' => 'Home',
            'slug' => Entry::HOME_PATH,
            'status' => EntryStatus::Published,
            'nav_order' => 1,
            'body' => <<<'MARKDOWN'
                Your new site is up and running. This is the home page, and you can change it in the admin panel under **Pages & Posts**.

                ## Getting started

                1. Log in to the admin panel at `/admin`.
                2. Open **Theme** to set your site name, logo, colors and fonts.
                3. Edit this page, or add new pages, blogs and posts.
                MARKDOWN,
        ]);
    }
}
