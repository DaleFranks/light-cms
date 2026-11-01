<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => fake()->unique()->slug(2).'.jpg',
            'alt_text' => fake()->sentence(4),
            'width' => 1200,
            'height' => 800,
            'size' => fake()->numberBetween(50_000, 500_000),
            'mime_type' => 'image/jpeg',
        ];
    }
}
