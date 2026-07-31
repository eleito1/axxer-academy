<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function statistics(): array
    {
        return [
            'products' => DB::table('products')->whereNull('deleted_at')->count(),
            'courses' => DB::table('courses')->whereNull('deleted_at')->count(),
            'modules' => DB::table('modules')->whereNull('deleted_at')->count(),
            'lessons' => DB::table('lessons')->whereNull('deleted_at')->count(),
            'students' => DB::table('users')->where('role', 'aluno')->count(),
            'pending' => DB::table('users')->where('status', 'pendente')->count(),
            'published_courses' => DB::table('courses')->whereNull('deleted_at')->where('published', true)->count(),
            'completed_lessons' => DB::table('lesson_progress')->whereNotNull('completed_at')->count(),
        ];
    }

    public function topCourses()
    {
        $courses = Course::query()->select('courses.id', 'courses.title')
            ->join('modules', 'modules.course_id', '=', 'courses.id')
            ->join('lessons', 'lessons.module_id', '=', 'modules.id')
            ->leftJoin('lesson_progress', 'lesson_progress.lesson_id', '=', 'lessons.id')
            ->whereNull('modules.deleted_at')->whereNull('lessons.deleted_at')
            ->groupBy('courses.id', 'courses.title')->selectRaw('COUNT(lesson_progress.id) as accesses')
            ->orderByDesc('accesses')->limit(5)->get();
        $largest = max(1, (int) $courses->max('accesses'));
        $courses->each(fn (Course $course) => $course->setAttribute('chart_percentage', (int) round(($course->accesses / $largest) * 100)));

        return $courses;
    }

    public function activeStudents(): int
    {
        return LessonProgress::query()->where(function ($query) {
            $query->where('last_accessed_at', '>=', now()->subDays(30))
                ->orWhere(fn ($fallback) => $fallback->whereNull('last_accessed_at')->where('updated_at', '>=', now()->subDays(30)));
        })->distinct()->count('user_id');
    }

    public function latestAccesses()
    {
        return LessonProgress::query()->whereHas('user')->whereHas('lesson')->with(['user', 'lesson'])
            ->orderByRaw('CASE WHEN last_accessed_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_accessed_at')->orderByDesc('updated_at')->orderByDesc('id')->limit(5)->get();
    }
}
