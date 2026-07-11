<?php

namespace Tests\Feature;

use App\Enums\EnrollmentSource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    private function publishedCourseWithLesson(): array
    {
        $course = Course::factory()->published()->create(['price' => 0]);
        $section = $course->sections()->create(['title' => 'Section 1', 'position' => 0]);
        $lesson = $section->lessons()->create([
            'title' => 'Lesson 1',
            'type' => 'article',
            'position' => 0,
            'is_previewable' => false,
        ]);
        $lesson->article()->create(['body_html' => '<p>Full content</p>']);

        return [$course, $lesson];
    }

    public function test_enrolled_student_sees_unlocked_curriculum_content(): void
    {
        [$course, $lesson] = $this->publishedCourseWithLesson();
        $student = User::factory()->create();
        $student->enrollments()->create([
            'course_id' => $course->id,
            'source' => EnrollmentSource::Free,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson("/api/v1/courses/{$course->slug}/curriculum");

        $lessonData = collect($response->json('data.sections.0.lessons'))->firstWhere('id', $lesson->id);
        $this->assertFalse($lessonData['locked']);
        $this->assertSame('<p>Full content</p>', $lessonData['article']['body_html']);
    }

    public function test_non_enrolled_student_still_sees_locked_content(): void
    {
        [$course, $lesson] = $this->publishedCourseWithLesson();
        $student = User::factory()->create();

        $response = $this->actingAs($student)->getJson("/api/v1/courses/{$course->slug}/curriculum");

        $lessonData = collect($response->json('data.sections.0.lessons'))->firstWhere('id', $lesson->id);
        $this->assertTrue($lessonData['locked']);
    }

    public function test_marking_a_lesson_complete_updates_enrollment_progress(): void
    {
        [$course, $lesson] = $this->publishedCourseWithLesson();
        $student = User::factory()->create();
        $enrollment = $student->enrollments()->create([
            'course_id' => $course->id,
            'source' => EnrollmentSource::Free,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($student)->postJson("/api/v1/lessons/{$lesson->id}/complete");

        $response->assertOk()->assertJsonPath('enrollment.progress_percent', 100);
        $this->assertSame(100, $enrollment->fresh()->progress_percent);
        $this->assertNotNull($enrollment->fresh()->completed_at);
    }

    public function test_a_non_enrolled_user_cannot_mark_a_lesson_complete(): void
    {
        [$course, $lesson] = $this->publishedCourseWithLesson();
        $student = User::factory()->create();

        $this->actingAs($student)
            ->postJson("/api/v1/lessons/{$lesson->id}/complete")
            ->assertUnprocessable();
    }

    public function test_video_progress_position_is_saved_without_completing(): void
    {
        $course = Course::factory()->published()->create(['price' => 0]);
        $section = $course->sections()->create(['title' => 'Section 1', 'position' => 0]);
        $lesson = $section->lessons()->create([
            'title' => 'Video Lesson',
            'type' => 'video',
            'position' => 0,
            'is_previewable' => false,
        ]);

        $student = User::factory()->create();
        $student->enrollments()->create([
            'course_id' => $course->id,
            'source' => EnrollmentSource::Free,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson("/api/v1/lessons/{$lesson->id}/progress", ['last_position_seconds' => 42])
            ->assertOk();

        $this->assertDatabaseHas('lesson_progress', [
            'lesson_id' => $lesson->id,
            'last_position_seconds' => 42,
            'completed_at' => null,
        ]);
    }
}
