<?php

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => CourseSection::factory(),
            'title' => fake()->sentence(3),
            'type' => LessonType::Article,
            'position' => 0,
            'is_previewable' => false,
        ];
    }
}
