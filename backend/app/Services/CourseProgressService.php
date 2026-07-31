<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class CourseProgressService
{
    public function forCourse(User $user, Course $course): array
    {
        $lessonIds = $course->lessons()->published()->pluck('lessons.id');
        $total = $lessonIds->count();
        $completed = $user->lessonProgress()->whereIn('lesson_id', $lessonIds)->whereNotNull('completed_at')->count();
        $last = $user->lessonProgress()->whereIn('lesson_id', $lessonIds)
            ->orderByRaw('CASE WHEN last_accessed_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_accessed_at')->orderByDesc('updated_at')->orderByDesc('id')->with('lesson.module')->first();
        $percentage = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return compact('total', 'completed', 'percentage', 'last') + ['certificate_available' => $total > 0 && $percentage === 100];
    }

    public function dashboard(User $user): array
    {
        $products = $user->products()->where('is_active', true)->orderBy('name')->get();
        $courses = Course::query()->published()->whereIn('product_id', $products->pluck('id'))->with('product')->orderBy('order')->get();
        $courseCards = $courses->map(fn (Course $course) => ['course' => $course] + $this->forCourse($user, $course));
        $total = $courseCards->sum('total');
        $completedLessons = $courseCards->sum('completed');
        $lastProgress = $user->lessonProgress()
            ->whereHas('lesson', fn ($query) => $query->published())
            ->whereHas('lesson.module.course', fn ($query) => $query->whereIn('product_id', $products->pluck('id'))->published())
            ->orderByRaw('CASE WHEN last_accessed_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_accessed_at')->orderByDesc('updated_at')->orderByDesc('id')
            ->with('lesson.module.course.product')->first();

        return [
            'products' => $products,
            'in_progress' => $courseCards->filter(fn ($item) => $item['percentage'] > 0 && $item['percentage'] < 100)->values(),
            'completed_courses' => $courseCards->filter(fn ($item) => $item['certificate_available'])->values(),
            'available_courses' => $courseCards,
            'last_progress' => $lastProgress,
            'general_percentage' => $total > 0 ? (int) round(($completedLessons / $total) * 100) : 0,
        ];
    }

    public function orderedLessons(Course $course): Collection
    {
        return Lesson::query()->published()->whereHas('module', fn ($query) => $query->where('course_id', $course->id))
            ->with('module')->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->orderBy('modules.order')->orderBy('modules.id')->orderBy('lessons.order')->orderBy('lessons.id')
            ->select('lessons.*')->get();
    }

    public function recordOpened(User $user, Lesson $lesson): LessonProgress
    {
        $progress = LessonProgress::firstOrCreate(['user_id' => $user->id, 'lesson_id' => $lesson->id], ['last_seconds' => 0]);
        $progress->forceFill(['last_accessed_at' => now()])->save();

        return $progress;
    }

    public function update(User $user, Lesson $lesson, int $seconds, bool $completed = false): LessonProgress
    {
        $progress = $this->recordOpened($user, $lesson);
        $seconds = $lesson->duration ? min($seconds, $lesson->duration) : $seconds;
        $autoCompleted = $lesson->duration && $seconds >= (int) ceil($lesson->duration * .9);
        $progress->last_seconds = $seconds;
        if (($completed || $autoCompleted) && ! $progress->completed_at) {
            $progress->completed_at = now();
        }
        $progress->save();

        return $progress;
    }
}
