<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_a_pending_review_course(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $course = Course::factory()->pendingReview()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/approve");

        $response->assertOk()->assertJsonPath('data.status', 'published');
        $this->assertNotNull($course->fresh()->published_at);
    }

    public function test_admin_can_reject_a_pending_review_course_with_a_reason(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $course = Course::factory()->pendingReview()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/reject", [
            'rejection_reason' => 'Audio quality is too poor to publish.',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertSame('Audio quality is too poor to publish.', $course->fresh()->rejection_reason);
    }

    public function test_cannot_approve_a_course_that_is_not_pending_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $course = Course::factory()->create(); // draft

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/courses/{$course->id}/approve")
            ->assertUnprocessable();
    }

    public function test_a_non_admin_cannot_moderate_courses(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');
        $course = Course::factory()->pendingReview()->create();

        $this->actingAs($instructor)
            ->postJson("/api/v1/admin/courses/{$course->id}/approve")
            ->assertForbidden();
    }
}
