<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    /**
     * Public course catalog: published courses only, with search/filter/sort.
     */
    public function index(Request $request): JsonResponse
    {
        $courses = Course::query()
            ->published()
            ->with(['instructor', 'category'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('subtitle', 'like', $term));
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
            })
            ->when($request->filled('level'), fn ($query) => $query->where('level', $request->string('level')))
            ->when($request->query('price') === 'free', fn ($query) => $query->where('price', 0))
            ->when($request->query('price') === 'paid', fn ($query) => $query->where('price', '>', 0))
            ->when(
                $request->query('sort') === 'price_asc',
                fn ($query) => $query->orderBy('price'),
                fn ($query) => $request->query('sort') === 'price_desc'
                    ? $query->orderByDesc('price')
                    : $query->latest('published_at')
            )
            ->paginate($request->integer('per_page', 12));

        return CourseResource::collection($courses)->response();
    }

    /**
     * Public course detail page. Published courses are visible to anyone;
     * draft/pending/rejected courses are only visible to their owner or an admin.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $course = $this->findVisibleBySlug($request, $slug, ['instructor', 'category']);

        return (new CourseResource($course))->response();
    }

    /**
     * Course curriculum (sections + lessons). Lesson content is locked unless
     * the requester owns the course, is an admin, or the lesson is previewable.
     */
    public function curriculum(Request $request, string $slug): JsonResponse
    {
        $course = $this->findVisibleBySlug($request, $slug);

        $canViewLockedContent = $request->user()?->id === $course->instructor_id
            || $request->user()?->hasRole('admin');
        $request->attributes->set('can_view_locked_lesson_content', $canViewLockedContent);

        $course->load(['sections.lessons.videoDetail', 'sections.lessons.article', 'sections.lessons.attachments']);

        return (new CourseResource($course))->response();
    }

    private function findVisibleBySlug(Request $request, string $slug, array $with = []): Course
    {
        $course = Course::query()->with($with)->where('slug', $slug)->first();

        if (! $course) {
            throw new NotFoundHttpException();
        }

        $isOwnerOrAdmin = $request->user()?->id === $course->instructor_id || $request->user()?->hasRole('admin');

        if ($course->status !== CourseStatus::Published && ! $isOwnerOrAdmin) {
            throw new NotFoundHttpException();
        }

        return $course;
    }
}
