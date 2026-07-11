<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_apply_to_become_an_instructor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $response = $this->actingAs($user)->postJson('/api/v1/instructor-applications', [
            'bio' => 'I have ten years of professional teaching experience in this field.',
            'expertise' => ['PHP', 'Laravel'],
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('instructor_applications', ['user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_a_user_cannot_submit_a_second_pending_application(): void
    {
        $user = User::factory()->create();
        $user->instructorApplications()->create([
            'status' => 'pending',
            'bio' => 'Existing pending application bio text here.',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/instructor-applications', [
            'bio' => 'Another application bio that is long enough.',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_admin_can_approve_an_application_which_grants_the_instructor_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $applicant = User::factory()->create();
        $application = $applicant->instructorApplications()->create([
            'status' => 'pending',
            'bio' => 'A bio that is definitely long enough to pass validation.',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/instructor-applications/{$application->id}/approve");

        $response->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertTrue($applicant->fresh()->hasRole('instructor'));
    }

    public function test_admin_can_reject_an_application_with_a_reason(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $applicant = User::factory()->create();
        $application = $applicant->instructorApplications()->create([
            'status' => 'pending',
            'bio' => 'A bio that is definitely long enough to pass validation.',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/instructor-applications/{$application->id}/reject", [
                'rejection_reason' => 'Portfolio did not demonstrate sufficient expertise.',
            ]);

        $response->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertFalse($applicant->fresh()->hasRole('instructor'));
    }

    public function test_a_non_admin_cannot_review_applications(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $applicant = User::factory()->create();
        $application = $applicant->instructorApplications()->create([
            'status' => 'pending',
            'bio' => 'A bio that is definitely long enough to pass validation.',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson("/api/v1/admin/instructor-applications/{$application->id}/approve")
            ->assertForbidden();
    }
}
