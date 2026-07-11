<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_enroll_in_a_free_published_course(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create(['price' => 0]);

        $response = $this->actingAs($student)->postJson("/api/v1/courses/{$course->slug}/enroll");

        $response->assertCreated()
            ->assertJsonPath('data.source', 'free')
            ->assertJsonPath('data.progress_percent', 0);
        $this->assertDatabaseHas('enrollments', ['user_id' => $student->id, 'course_id' => $course->id]);
    }

    public function test_enrolling_twice_is_idempotent_not_a_duplicate(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create(['price' => 0]);

        $this->actingAs($student)->postJson("/api/v1/courses/{$course->slug}/enroll")->assertCreated();
        $this->actingAs($student)->postJson("/api/v1/courses/{$course->slug}/enroll")->assertOk();

        $this->assertSame(1, \App\Models\Enrollment::where('user_id', $student->id)->count());
    }

    public function test_cannot_enroll_in_a_paid_course_yet(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create(['price' => 49.99]);

        $this->actingAs($student)
            ->postJson("/api/v1/courses/{$course->slug}/enroll")
            ->assertUnprocessable();

        $this->assertDatabaseMissing('enrollments', ['user_id' => $student->id, 'course_id' => $course->id]);
    }

    public function test_cannot_enroll_in_an_unpublished_course(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['price' => 0]); // draft

        $this->actingAs($student)
            ->postJson("/api/v1/courses/{$course->slug}/enroll")
            ->assertNotFound();
    }

    public function test_my_enrollments_lists_enrolled_courses(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create(['price' => 0]);
        $this->actingAs($student)->postJson("/api/v1/courses/{$course->slug}/enroll");

        $response = $this->actingAs($student)->getJson('/api/v1/my/enrollments');

        $response->assertOk()->assertJsonPath('data.0.course.id', $course->id);
    }
}
