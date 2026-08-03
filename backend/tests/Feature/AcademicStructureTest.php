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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_the_complete_academic_hierarchy(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Approved]);
        $product = Product::create(['name' => 'AXXER Optical']);
        Storage::fake('video_public');
        config(['filesystems.disks.video_public.root' => '/fake-video-root', 'filesystems.disks.video_public.url' => 'https://axxeracademy.com.br/videos']);

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
            'title' => 'Aula 1', 'video' => UploadedFile::fake()->create('aula.mp4', 1024, 'video/mp4'), 'duration' => 300,
            'order' => 1, 'published' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('lessons', ['module_id' => $module->id, 'title' => 'Aula 1', 'published' => true, 'video_provider' => 'hostinger']);
        $this->assertSame('Aula 1', $product->courses->first()->modules->first()->lessons->first()->title);
    }

    public function test_course_uses_nested_route_product_as_source_of_truth_when_payload_is_forged(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Approved]);
        $routeProduct = Product::create(['name' => 'AXXER Optical']);
        $forgedProduct = Product::create(['name' => 'DIRETOCOM']);
        Course::create(['product_id' => $forgedProduct->id, 'title' => 'Duplicado', 'slug' => 'vendas', 'published' => true]);

        $this->actingAs($admin)->post(route('admin.products.courses.store', $routeProduct), [
            'product_id' => $forgedProduct->id,
            'title' => 'Vendas',
            'slug' => 'vendas',
            'description' => 'Curso',
            'order' => 1,
            'published' => 1,
        ])->assertRedirect(route('admin.products.courses.index', $routeProduct));

        $this->assertDatabaseHas('courses', [
            'product_id' => $routeProduct->id,
            'title' => 'Vendas',
            'slug' => 'vendas',
        ]);
    }

    public function test_course_update_keeps_nested_route_product_even_when_payload_tries_to_move_it(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Approved]);
        $routeProduct = Product::create(['name' => 'AXXER Optical']);
        $forgedProduct = Product::create(['name' => 'DIRETOCOM']);
        $course = Course::create(['product_id' => $routeProduct->id, 'title' => 'Vendas', 'slug' => 'vendas', 'published' => true]);

        $this->actingAs($admin)->put(route('admin.products.courses.update', [$routeProduct, $course]), [
            'product_id' => $forgedProduct->id,
            'title' => 'Vendas atualizadas',
            'slug' => 'vendas-atualizadas',
            'description' => 'Curso',
            'order' => 2,
            'published' => 1,
        ])->assertRedirect(route('admin.products.courses.index', $routeProduct));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'product_id' => $routeProduct->id,
            'title' => 'Vendas atualizadas',
            'slug' => 'vendas-atualizadas',
        ]);
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
