<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'answers' => [
                'name' => ['label' => 'Name', 'value' => fake()->name()],
                'email' => ['label' => 'Email', 'value' => fake()->safeEmail()],
                'message' => ['label' => 'Message', 'value' => fake()->sentence()],
            ],
        ];
    }
}
