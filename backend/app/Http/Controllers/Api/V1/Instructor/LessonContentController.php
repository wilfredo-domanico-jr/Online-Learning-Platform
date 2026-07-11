<?php

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LessonContentController extends Controller
{
    /**
     * Upload/replace the video file for a video-type lesson.
     *
     * Direct upload through the API for now — swapping to S3 presigned PUT
     * URLs (so large files don't proxy through PHP) is a later hardening step.
     */
    public function uploadVideo(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('update', $lesson->section->course);
        $this->ensureType($lesson, LessonType::Video);

        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:512000'],
        ]);

        $path = $request->file('video')->store('lessons/'.$lesson->id.'/video', 'public');

        $lesson->videoDetail()->updateOrCreate([], [
            'disk' => 'public',
            'path' => $path,
        ]);

        return $this->respond($request, $lesson);
    }

    /**
     * Create/replace the rich-text body for an article-type lesson.
     */
    public function updateArticle(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('update', $lesson->section->course);
        $this->ensureType($lesson, LessonType::Article);

        $data = $request->validate([
            'body_html' => ['required', 'string'],
        ]);

        $lesson->article()->updateOrCreate([], $data);

        return $this->respond($request, $lesson);
    }

    /**
     * Attach a downloadable file to a lesson (any lesson type).
     */
    public function storeAttachment(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('update', $lesson->section->course);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $file = $request->file('file');
        $path = $file->store('lessons/'.$lesson->id.'/attachments', 'public');

        $lesson->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return $this->respond($request, $lesson);
    }

    public function destroyAttachment(Request $request, LessonAttachment $attachment): JsonResponse
    {
        $lesson = $attachment->lesson;
        $this->authorize('update', $lesson->section->course);

        $attachment->delete();

        return $this->respond($request, $lesson);
    }

    private function ensureType(Lesson $lesson, LessonType $expected): void
    {
        if ($lesson->type !== $expected) {
            throw ValidationException::withMessages([
                'type' => "This lesson is not a {$expected->value} lesson.",
            ]);
        }
    }

    private function respond(Request $request, Lesson $lesson): JsonResponse
    {
        $request->attributes->set('can_view_locked_lesson_content', true);

        return (new LessonResource($lesson->fresh(['videoDetail', 'article', 'attachments'])))->response();
    }
}
