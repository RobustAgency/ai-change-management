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
            'name' => $this->faker->sentence(3),
            'launch_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'type' => $this->faker->randomElement([
                'new system', 'process', 'structure', 'strategy', 'culture', 'org design', 'other',
            ]),
            'sponsor_name' => $this->faker->name(),
            'sponsor_title' => $this->faker->jobTitle(),
            'business_goals' => $this->faker->paragraph(),
            'summary' => $this->faker->sentence(10),
            'expected_outcomes' => $this->faker->paragraph(),
            'stakeholders' => [
                [
                    'department' => $this->faker->company(),
                    'role_level' => $this->faker->randomElement(['C-Suite', 'Leader', 'Manager', 'Individual Contributor']),
                ],
            ],
            'client_organization' => $this->faker->company(),
            'status' => $this->faker->randomElement(array_column(ProjectStatus::cases(), 'value')),
        ];
    }
}
