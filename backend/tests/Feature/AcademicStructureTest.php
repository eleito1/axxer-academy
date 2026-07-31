<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_the_complete_academic_hierarchy(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Approved]);
        $product = Product::create(['name' => 'AXXER Optical']);

        $this->actingAs($admin)->post(route('admin.products.courses.store', $product), [
            'product_id' => $product->id, 'title' => 'Técnicas de Vendas', 'slug' => 'tecnicas-de-vendas',
            'description' => 'Curso', 'order' => 2, 'published' => 1,
        ])->assertRedirect();

        $course = Course::firstOrFail();
        $this->actingAs($admin)->post(route('admin.products.courses.modules.store', [$product, $course]), [
            'title' => 'Módulo 1', 'description' => 'Introdução', 'order' => 1,
        ])->assertRedirect();

        $module = Module::firstOrFail();
        $this->actingAs($admin)->post(route('admin.products.courses.modules.lessons.store', [$product, $course, $module]), [
            'title' => 'Aula 1', 'video_url' => 'https://youtu.be/abcdefghijk', 'duration' => 300,
            'order' => 1, 'published' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('lessons', ['module_id' => $module->id, 'title' => 'Aula 1', 'published' => true]);
        $this->assertSame('Aula 1', $product->courses->first()->modules->first()->lessons->first()->title);
    }

    public function test_student_only_sees_published_content_from_released_products(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->academicTree();
        $student->products()->attach($product);

        $this->actingAs($student)->get(route('academy.products.show', $product))->assertOk()->assertSee($course->title);
        $this->actingAs($student)->get(route('academy.lessons.show', [$product, $course, $module, $lesson]))->assertOk()->assertSee($lesson->title);

        $lesson->update(['published' => false]);
        $this->actingAs($student)->get(route('academy.lessons.show', [$product, $course, $module, $lesson]))->assertForbidden();
    }

    public function test_student_cannot_open_product_without_permission(): void
    {
        [$student, $product] = $this->academicTree();
        $this->actingAs($student)->get(route('academy.products.show', $product))->assertForbidden();
    }

    public function test_scoped_binding_rejects_course_from_another_product(): void
    {
        [$student, $product] = $this->academicTree();
        $other = Product::create(['name' => 'DIRETOCOM']);
        $otherCourse = Course::create(['product_id' => $other->id, 'title' => 'Outro', 'slug' => 'outro', 'published' => true]);
        $student->products()->attach([$product->id, $other->id]);

        $this->actingAs($student)->get(route('academy.courses.show', [$product, $otherCourse]))->assertNotFound();
    }

    private function academicTree(): array
    {
        $student = User::factory()->create(['role' => UserRole::Student, 'status' => UserStatus::Approved]);
        $product = Product::create(['name' => 'AXXER Optical']);
        $course = Course::create(['product_id' => $product->id, 'title' => 'Vendas', 'slug' => 'vendas', 'published' => true]);
        $module = Module::create(['course_id' => $course->id, 'title' => 'Introdução']);
        $lesson = Lesson::create(['module_id' => $module->id, 'title' => 'Boas-vindas', 'video_url' => 'https://youtu.be/abcdefghijk', 'published' => true]);

        return [$student, $product, $course, $module, $lesson];
    }
}
