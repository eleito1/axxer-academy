<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThreeReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_indicators_exactly_reflect_existing_academic_records(): void
    {
        [$product, $course, $module, $lesson] = $this->academicTree();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Approved]);

        $expected = ['products' => 1, 'courses' => 1, 'modules' => 1, 'lessons' => 1, 'published_courses' => 1];
        $statistics = app(AdminDashboardService::class)->statistics();

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $statistics[$key], "Indicador {$key} incorreto.");
        }

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertViewHas('stats', function (array $stats) use ($expected) {
            foreach ($expected as $key => $value) {
                if ($stats[$key] !== $value) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_admin_indicators_ignore_soft_deleted_content_and_count_drafts_separately(): void
    {
        [$product, $published, $module, $lesson] = $this->academicTree();
        Course::create(['product_id' => $product->id, 'title' => 'Rascunho', 'slug' => 'rascunho', 'published' => false]);
        $deleted = Course::create(['product_id' => $product->id, 'title' => 'Excluído', 'slug' => 'excluido', 'published' => true]);
        $deleted->delete();

        $stats = app(AdminDashboardService::class)->statistics();
        $this->assertSame(2, $stats['courses']);
        $this->assertSame(1, $stats['published_courses']);
        $this->assertSame(1, $stats['modules']);
        $this->assertSame(1, $stats['lessons']);
    }

    public function test_student_dashboard_has_consistent_percentage_last_lesson_and_continue_target(): void
    {
        [$product, $course, $module, $first] = $this->academicTree();
        $second = Lesson::create(['module_id' => $module->id, 'title' => 'Aula 2', 'video_url' => 'https://youtu.be/bbbbbbbbbbb', 'duration' => 100, 'order' => 2, 'published' => true]);
        $student = User::factory()->create(['status' => UserStatus::Approved]);
        $student->products()->attach($product);
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $first->id, 'completed_at' => now(), 'last_seconds' => 100]);
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $second->id, 'last_seconds' => 37]);

        $this->actingAs($student)->get(route('dashboard'))->assertOk()
            ->assertViewHas('general_percentage', 50)
            ->assertViewHas('last_progress', fn (LessonProgress $progress) => $progress->lesson_id === $second->id)
            ->assertSee('50%')->assertSee('Continuar assistindo');

        $this->actingAs($student)->get(route('academy.courses.show', [$product, $course]))
            ->assertRedirect(route('academy.lessons.show', [$product, $course, $module, $second]));
    }

    public function test_reopening_lesson_updates_last_access_and_rewound_position_is_persisted_exactly(): void
    {
        [$product, $course, $module, $first] = $this->academicTree();
        $second = Lesson::create(['module_id' => $module->id, 'title' => 'Aula 2', 'video_url' => 'https://youtu.be/bbbbbbbbbbb', 'duration' => 100, 'published' => true]);
        $student = User::factory()->create(['status' => UserStatus::Approved]);
        $student->products()->attach($product);
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $first->id, 'last_seconds' => 80, 'updated_at' => now()->subHour()]);
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $second->id, 'last_seconds' => 50]);

        $this->actingAs($student)->get(route('academy.lessons.show', [$product, $course, $module, $first]))->assertOk();
        $this->actingAs($student)->putJson(route('academy.lessons.progress', [$product, $course, $module, $first]), ['last_seconds' => 20])->assertOk();

        $progress = LessonProgress::whereBelongsTo($student)->whereBelongsTo($first)->firstOrFail();
        $this->assertSame(20, $progress->last_seconds);
        $this->actingAs($student)->get(route('dashboard'))->assertViewHas('last_progress', fn (LessonProgress $last) => $last->lesson_id === $first->id);
    }

    public function test_unpublished_lesson_is_excluded_from_last_lesson_and_progress_percentage(): void
    {
        [$product, $course, $module, $published] = $this->academicTree();
        $hidden = Lesson::create(['module_id' => $module->id, 'title' => 'Oculta', 'video_url' => 'https://youtu.be/ccccccccccc', 'published' => false]);
        $student = User::factory()->create(['status' => UserStatus::Approved]);
        $student->products()->attach($product);
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $published->id, 'completed_at' => now(), 'last_seconds' => 100, 'updated_at' => now()->subHour()]);
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $hidden->id, 'last_seconds' => 10]);

        $this->actingAs($student)->get(route('dashboard'))->assertViewHas('general_percentage', 100)
            ->assertViewHas('last_progress', fn (LessonProgress $last) => $last->lesson_id === $published->id);
    }

    private function academicTree(): array
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $course = Course::create(['product_id' => $product->id, 'title' => 'Curso', 'slug' => 'curso', 'published' => true]);
        $module = Module::create(['course_id' => $course->id, 'title' => 'Módulo']);
        $lesson = Lesson::create(['module_id' => $module->id, 'title' => 'Aula', 'video_url' => 'https://youtu.be/abcdefghijk', 'duration' => 100, 'published' => true]);

        return [$product, $course, $module, $lesson];
    }
}
