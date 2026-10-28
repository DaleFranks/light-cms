<?php

namespace Database\Factories;

use App\EntryStatus;
use App\EntryType;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'type' => EntryType::Page,
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => fake()->paragraphs(3, true),
            'status' => EntryStatus::Draft,
            'meta_description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the entry is a blog post.
     */
    public function post(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Post,
        ]);
    }

    /**
     * Indicate that the entry is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EntryStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }
}
