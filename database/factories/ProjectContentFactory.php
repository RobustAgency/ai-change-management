<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectContent>
 */
class ProjectContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'slides_content' => json_encode($this->faker->sentences(3)),
            'faqs' => json_encode($this->faker->words(5)),
            'video_script' => json_encode($this->faker->paragraphs(2)),
        ];
    }
}
