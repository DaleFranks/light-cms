<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'body' => fake()->paragraph(),
        ];
    }

    /**
     * The site footer.
     */
    public function footer(): static
    {
        return $this->state([
            'name' => 'Footer',
            'slug' => Module::FOOTER_SLUG,
            'body' => Module::FOOTER_DEFAULT_BODY,
        ]);
    }
}
