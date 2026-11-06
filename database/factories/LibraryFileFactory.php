<?php

namespace Database\Factories;

use App\Models\LibraryFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryFile>
 */
class LibraryFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => fake()->unique()->slug(2).'.pdf',
            'size' => fake()->numberBetween(20_000, 2_000_000),
            'mime_type' => 'application/pdf',
        ];
    }
}
