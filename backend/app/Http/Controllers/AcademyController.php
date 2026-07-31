<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLessonProgressRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Services\CourseProgressService;
use App\Services\VideoEmbedUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademyController extends Controller
{
    public function index(CourseProgressService $progress): View
    {
        return view('dashboard', $progress->dashboard(request()->user()));
    }

    public function product(Product $product, CourseProgressService $progress): View
    {
        $this->authorize('view', $product);
        $courses = $product->courses()->published()->get()->map(fn (Course $course) => ['course' => $course] + $progress->forCourse(request()->user(), $course));

        return view('academy.product', compact('product', 'courses'));
    }

    public function course(Product $product, Course $course, CourseProgressService $progress): View|RedirectResponse
    {
        $this->authorize('view', $course);
        $stats = $progress->forCourse(request()->user(), $course);
        $lesson = $stats['last']?->lesson ?? $progress->orderedLessons($course)->first();

        if ($lesson) {
            return redirect()->route('academy.lessons.show', [$product, $course, $lesson->module, $lesson]);
        }

        return view('academy.course', ['product' => $product, 'course' => $course->load('modules')]);
    }

    public function module(Product $product, Course $course, Module $module): View
    {
        $this->authorize('view', $module);

        return view('academy.module', ['product' => $product, 'course' => $course, 'module' => $module->load(['lessons' => fn ($query) => $query->published()])]);
    }

    public function lesson(Product $product, Course $course, Module $module, Lesson $lesson, VideoEmbedUrl $embed, CourseProgressService $progress): View
    {
        $this->authorize('view', $lesson);
        $user = request()->user();
        $currentProgress = $progress->recordOpened($user, $lesson);
        $lessons = $progress->orderedLessons($course);
        $position = $lessons->search(fn (Lesson $item) => $item->is($lesson));
        $progressByLesson = $user->lessonProgress()->whereIn('lesson_id', $lessons->pluck('id'))->get()->keyBy('lesson_id');
        $sidebar = $lessons->groupBy('module_id')->map(function ($items) use ($lesson, $progressByLesson, $product, $course) {
            $first = $items->first();

            return ['module' => $first->module, 'lessons' => $items->map(function (Lesson $item) use ($lesson, $progressByLesson, $product, $course) {
                $itemProgress = $progressByLesson->get($item->id);

                return ['lesson' => $item, 'current' => $item->is($lesson), 'completed' => $itemProgress?->isCompleted() ?? false,
                    'url' => route('academy.lessons.show', [$product, $course, $item->module, $item]),
                    'duration' => $item->duration ? gmdate('i:s', $item->duration) : '--:--'];
            })];
        })->values();

        $previous = $position !== false && $position > 0 ? $lessons[$position - 1] : null;
        $next = $position !== false && $position < $lessons->count() - 1 ? $lessons[$position + 1] : null;

        return view('academy.lesson', [
            'product' => $product, 'course' => $course, 'module' => $module, 'lesson' => $lesson,
            'embedUrl' => $embed->from($lesson->video_url, $currentProgress->last_seconds), 'progress' => $currentProgress,
            'progressUrl' => route('academy.lessons.progress', [$product, $course, $module, $lesson]),
            'courseProgress' => $progress->forCourse($user, $course), 'sidebar' => $sidebar,
            'previousUrl' => $previous ? route('academy.lessons.show', [$product, $course, $previous->module, $previous]) : null,
            'nextUrl' => $next ? route('academy.lessons.show', [$product, $course, $next->module, $next]) : null,
        ]);
    }

    public function updateProgress(UpdateLessonProgressRequest $request, Product $product, Course $course, Module $module, Lesson $lesson, CourseProgressService $service): JsonResponse
    {
        $lessonProgress = $service->update($request->user(), $lesson, $request->integer('last_seconds'), $request->boolean('completed'));
        $courseProgress = $service->forCourse($request->user(), $course);

        return response()->json(['completed' => $lessonProgress->isCompleted(), 'percentage' => $courseProgress['percentage'], 'certificate_available' => $courseProgress['certificate_available']]);
    }
}
