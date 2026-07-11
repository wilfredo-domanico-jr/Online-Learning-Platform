<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'instructor_id' => User::factory(),
            'title' => $title,
            'subtitle' => fake()->sentence(8),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->randomElement([0, 19.99, 49.99, 99.99]),
            'status' => CourseStatus::Draft,
            'level' => CourseLevel::AllLevels,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::PendingReview]);
    }
}
