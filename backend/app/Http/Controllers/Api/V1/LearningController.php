<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LearningController extends Controller
{
    /**
     * Mark a lesson complete for the requester's enrollment in its course,
     * then recompute the enrollment's overall progress percentage.
     */
    public function complete(Request $request, Lesson $lesson): JsonResponse
    {
        $enrollment = $this->enrollmentFor($request, $lesson);

        $progress = $enrollment->lessonProgress()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );

        $enrollment->recalculateProgress();

        return response()->json([
            'lesson_id' => $lesson->id,
            'completed_at' => $progress->completed_at,
            'enrollment' => [
                'progress_percent' => $enrollment->fresh()->progress_percent,
                'completed_at' => $enrollment->fresh()->completed_at,
            ],
        ]);
    }

    /**
     * Record a video's resume position without marking the lesson complete.
     */
    public function updateProgress(Request $request, Lesson $lesson): JsonResponse
    {
        $enrollment = $this->enrollmentFor($request, $lesson);

        $data = $request->validate([
            'last_position_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $enrollment->lessonProgress()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            ['last_position_seconds' => $data['last_position_seconds']]
        );

        return response()->json(['message' => 'Progress saved.']);
    }

    private function enrollmentFor(Request $request, Lesson $lesson)
    {
        $course = $lesson->section->course;

        $enrollment = $course->enrollments()->where('user_id', $request->user()->id)->first();

        if (! $enrollment) {
            throw ValidationException::withMessages([
                'enrollment' => 'You must be enrolled in this course to track progress.',
            ]);
        }

        return $enrollment;
    }
}
