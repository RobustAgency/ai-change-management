<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->word(),
            'status' => $this->faker->randomElement(ProjectStatus::cases()),
            'launch_date' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}
