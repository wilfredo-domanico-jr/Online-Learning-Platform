<?php

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Http\Resources\LessonResource;
use App\Models\CourseSection;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function store(StoreLessonRequest $request, CourseSection $section): JsonResponse
    {
        $this->authorize('update', $section->course);

        $lesson = $section->lessons()->create([
            'title' => $request->validated('title'),
            'type' => $request->validated('type'),
            'is_previewable' => $request->boolean('is_previewable'),
            'position' => $section->lessons()->max('position') + 1,
        ]);

        return $this->respond($request, $lesson);
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('update', $lesson->section->course);

        $lesson->update($request->validated());

        return $this->respond($request, $lesson);
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $this->authorize('update', $lesson->section->course);

        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted.']);
    }

    /**
     * Persist a new lesson ordering within a section.
     *
     * Body: { "lesson_ids": [5, 3, 4] } — the array order becomes position 0..n.
     */
    public function reorder(Request $request, CourseSection $section): JsonResponse
    {
        $this->authorize('update', $section->course);

        $ids = $request->validate([
            'lesson_ids' => ['required', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ])['lesson_ids'];

        $lessons = $section->lessons()->whereIn('id', $ids)->pluck('id');

        foreach ($ids as $position => $id) {
            if ($lessons->contains($id)) {
                Lesson::whereKey($id)->update(['position' => $position]);
            }
        }

        return LessonResource::collection($section->lessons()->get())->response();
    }

    private function respond(Request $request, Lesson $lesson): JsonResponse
    {
        $request->attributes->set('can_view_locked_lesson_content', true);

        return (new LessonResource($lesson))->response();
    }
}
