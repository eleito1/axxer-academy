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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_lesson_registers_access_and_progress_endpoint_saves_position(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->tree();
        $url = route('academy.lessons.show', [$product, $course, $module, $lesson]);
        $progressUrl = route('academy.lessons.progress', [$product, $course, $module, $lesson]);

        $this->actingAs($student)->get($url)->assertOk();
        $this->assertDatabaseHas('lesson_progress', ['user_id' => $student->id, 'lesson_id' => $lesson->id, 'last_seconds' => 0]);

        $this->actingAs($student)->putJson($progressUrl, ['last_seconds' => 42])->assertOk()->assertJson(['completed' => false]);
        $this->assertDatabaseHas('lesson_progress', ['user_id' => $student->id, 'lesson_id' => $lesson->id, 'last_seconds' => 42]);
    }

    public function test_watching_ninety_percent_completes_lesson_and_enables_certificate_at_one_hundred_percent(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->tree();
        $progressUrl = route('academy.lessons.progress', [$product, $course, $module, $lesson]);

        $this->actingAs($student)->putJson($progressUrl, ['last_seconds' => 90])->assertOk()
            ->assertJson(['completed' => true, 'percentage' => 100, 'certificate_available' => true]);
        $this->assertNotNull(LessonProgress::firstOrFail()->completed_at);
    }

    public function test_course_reopens_at_last_accessed_lesson(): void
    {
        [$student, $product, $course, $module, $first] = $this->tree();
        $second = Lesson::create(['module_id' => $module->id, 'title' => 'Aula 2', 'video_url' => 'https://youtu.be/bbbbbbbbbbb', 'duration' => 100, 'order' => 2, 'published' => true]);
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $second->id, 'last_seconds' => 35]);

        $this->actingAs($student)->get(route('academy.courses.show', [$product, $course]))
            ->assertRedirect(route('academy.lessons.show', [$product, $course, $module, $second]));
    }

    public function test_student_cannot_write_progress_for_unreleased_product(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->tree(false);
        $this->actingAs($student)->putJson(route('academy.lessons.progress', [$product, $course, $module, $lesson]), ['last_seconds' => 10])->assertForbidden();
    }

    public function test_admin_dashboard_displays_operational_statistics(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->tree();
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $lesson->id, 'completed_at' => now(), 'last_seconds' => 100]);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Approved]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Cursos mais assistidos')->assertSee($course->title);
    }

    private function tree(bool $releaseProduct = true): array
    {
        $student = User::factory()->create(['role' => UserRole::Student, 'status' => UserStatus::Approved]);
        $product = Product::create(['name' => 'AXXER Optical']);
        $course = Course::create(['product_id' => $product->id, 'title' => 'Vendas', 'slug' => 'vendas', 'published' => true]);
        $module = Module::create(['course_id' => $course->id, 'title' => 'Introdução']);
        $lesson = Lesson::create(['module_id' => $module->id, 'title' => 'Aula 1', 'video_url' => 'https://youtu.be/abcdefghijk', 'duration' => 100, 'order' => 1, 'published' => true]);
        if ($releaseProduct) {
            $student->products()->attach($product);
        }

        return [$student, $product, $course, $module, $lesson];
    }
}
