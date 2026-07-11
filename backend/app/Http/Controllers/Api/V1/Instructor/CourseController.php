<?php

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    /**
     * The authenticated instructor's own courses.
     */
    public function index(Request $request): JsonResponse
    {
        $courses = $request->user()->courses()
            ->with('category')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return CourseResource::collection($courses)->response();
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        // Explicit defaults rather than relying on the DB column default: an
        // enum-cast attribute left unset after create() reads as null in the
        // in-memory model (Eloquent doesn't refetch DB-applied defaults).
        $course = $request->user()->courses()->create([
            'level' => CourseLevel::AllLevels,
            'language' => 'en',
            ...$request->validated(),
            'status' => CourseStatus::Draft,
        ]);

        return (new CourseResource($course))->response()->setStatusCode(201);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $course->update($request->validated());

        return (new CourseResource($course->fresh('category')))->response();
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return response()->json(['message' => 'Course deleted.']);
    }

    /**
     * Move a draft/rejected course into the admin review queue.
     */
    public function submitForReview(Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        if (! in_array($course->status, [CourseStatus::Draft, CourseStatus::Rejected], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or rejected courses can be submitted for review.',
            ]);
        }

        if (! $course->sections()->whereHas('lessons')->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Add at least one section with a lesson before submitting for review.',
            ]);
        }

        $course->update(['status' => CourseStatus::PendingReview, 'rejection_reason' => null]);

        return (new CourseResource($course))->response();
    }
}
