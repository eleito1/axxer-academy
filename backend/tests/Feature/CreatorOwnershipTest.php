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

class CreatorOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_login_redirects_to_creator_dashboard(): void
    {
        $creator = $this->approvedUser(UserRole::Creator, ['email' => 'creator@example.com']);

        $this->post(route('login.store'), [
            'email' => $creator->email,
            'password' => 'password',
        ])->assertRedirect(route('creator.dashboard'));
    }

    public function test_admin_sees_all_courses_and_creator_sees_only_own_courses(): void
    {
        $admin = $this->approvedUser(UserRole::Admin);
        $creator = $this->approvedUser(UserRole::Creator, ['name' => 'Criadora A']);
        $otherCreator = $this->approvedUser(UserRole::Creator, ['name' => 'Criador B']);
        $product = Product::create(['name' => 'AXXER Optical']);

        $ownCourse = Course::create(['product_id' => $product->id, 'creator_id' => $creator->id, 'title' => 'Curso próprio', 'slug' => 'curso-proprio']);
        Course::create(['product_id' => $product->id, 'creator_id' => $otherCreator->id, 'title' => 'Curso alheio', 'slug' => 'curso-alheio']);
        Course::create(['product_id' => $product->id, 'title' => 'Curso sem criador', 'slug' => 'curso-sem-criador']);

        $this->actingAs($admin)->get(route('admin.products.courses.index', $product))
            ->assertOk()
            ->assertSee('Curso próprio')
            ->assertSee('Curso alheio')
            ->assertSee('Curso sem criador')
            ->assertSee('Criador: Criadora A');

        $this->actingAs($creator)->get(route('creator.dashboard'))
            ->assertOk()
            ->assertSee('Meus cursos')
            ->assertSee('Curso próprio')
            ->assertDontSee('Curso alheio')
            ->assertDontSee('Curso sem criador')
            ->assertSee(route('creator.products.courses.modules.index', [$product, $ownCourse]), false);

        $this->actingAs($creator)->get(route('creator.products.courses.index', $product))
            ->assertOk()
            ->assertSee('Curso próprio')
            ->assertDontSee('Curso alheio')
            ->assertDontSee('Curso sem criador');
    }

    public function test_creator_and_student_access_boundaries_are_enforced(): void
    {
        $creator = $this->approvedUser(UserRole::Creator);
        $student = $this->approvedUser(UserRole::Student);
        [$product, $ownCourse] = $this->courseFor($creator, 'Curso próprio');
        $foreignCourse = Course::create(['product_id' => $product->id, 'creator_id' => $this->approvedUser(UserRole::Creator)->id, 'title' => 'Curso alheio', 'slug' => 'curso-alheio']);

        $this->actingAs($creator)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($student)->get(route('creator.dashboard'))->assertForbidden();

        $this->actingAs($creator)->get(route('creator.products.courses.edit', [$product, $ownCourse]))->assertOk();
        $this->actingAs($creator)->get(route('creator.products.courses.edit', [$product, $foreignCourse]))->assertForbidden();
    }

    public function test_creator_creates_course_as_self_and_cannot_forge_creator_id(): void
    {
        $creator = $this->approvedUser(UserRole::Creator);
        $otherCreator = $this->approvedUser(UserRole::Creator);
        $product = Product::create(['name' => 'AXXER Optical']);

        $this->actingAs($creator)->post(route('creator.products.courses.store', $product), $this->coursePayload([
            'title' => 'Curso do creator',
            'slug' => 'curso-do-creator',
        ]))->assertRedirect(route('creator.products.courses.index', $product));

        $this->assertDatabaseHas('courses', [
            'product_id' => $product->id,
            'creator_id' => $creator->id,
            'title' => 'Curso do creator',
        ]);

        $this->actingAs($creator)
            ->from(route('creator.products.courses.create', $product))
            ->post(route('creator.products.courses.store', $product), $this->coursePayload([
                'creator_id' => $otherCreator->id,
                'title' => 'Curso forjado',
                'slug' => 'curso-forjado',
            ]))
            ->assertRedirect(route('creator.products.courses.create', $product))
            ->assertSessionHasErrors(['creator_id' => 'O criador responsável não pode ser alterado por este usuário.']);

        $this->assertDatabaseMissing('courses', ['title' => 'Curso forjado']);
    }

    public function test_creator_cannot_change_creator_id_when_updating_course(): void
    {
        $creator = $this->approvedUser(UserRole::Creator);
        $otherCreator = $this->approvedUser(UserRole::Creator);
        [$product, $course] = $this->courseFor($creator, 'Curso próprio');

        $this->actingAs($creator)
            ->from(route('creator.products.courses.edit', [$product, $course]))
            ->put(route('creator.products.courses.update', [$product, $course]), $this->coursePayload([
                'creator_id' => $otherCreator->id,
                'title' => 'Curso alterado',
                'slug' => $course->slug,
            ]))
            ->assertRedirect(route('creator.products.courses.edit', [$product, $course]))
            ->assertSessionHasErrors(['creator_id' => 'O criador responsável não pode ser alterado por este usuário.']);

        $this->assertSame($creator->id, $course->fresh()->creator_id);
        $this->assertSame('Curso próprio', $course->fresh()->title);
    }

    public function test_admin_creates_and_changes_course_creator_but_cannot_assign_student(): void
    {
        $admin = $this->approvedUser(UserRole::Admin);
        $creator = $this->approvedUser(UserRole::Creator, ['name' => 'Criadora A']);
        $otherCreator = $this->approvedUser(UserRole::Creator, ['name' => 'Criador B']);
        $student = $this->approvedUser(UserRole::Student);
        $product = Product::create(['name' => 'AXXER Optical']);

        $this->actingAs($admin)->post(route('admin.products.courses.store', $product), $this->coursePayload([
            'creator_id' => $creator->id,
            'title' => 'Curso com criador',
            'slug' => 'curso-com-criador',
        ]))->assertRedirect(route('admin.products.courses.index', $product));

        $course = Course::where('slug', 'curso-com-criador')->firstOrFail();
        $this->assertSame($creator->id, $course->creator_id);

        $this->actingAs($admin)->put(route('admin.products.courses.update', [$product, $course]), $this->coursePayload([
            'creator_id' => $otherCreator->id,
            'title' => 'Curso com novo criador',
            'slug' => 'curso-com-criador',
        ]))->assertRedirect(route('admin.products.courses.index', $product));

        $this->assertSame($otherCreator->id, $course->fresh()->creator_id);

        $this->actingAs($admin)
            ->from(route('admin.products.courses.edit', [$product, $course]))
            ->put(route('admin.products.courses.update', [$product, $course]), $this->coursePayload([
                'creator_id' => $student->id,
                'title' => 'Curso inválido',
                'slug' => 'curso-com-criador',
            ]))
            ->assertRedirect(route('admin.products.courses.edit', [$product, $course]))
            ->assertSessionHasErrors(['creator_id' => 'Selecione um criador válido.']);

        $this->assertSame($otherCreator->id, $course->fresh()->creator_id);
    }

    public function test_creator_can_manage_own_modules_and_lessons_but_not_foreign_content(): void
    {
        $creator = $this->approvedUser(UserRole::Creator);
        [$product, $course, $module, $lesson] = $this->fullCourseFor($creator);
        [$foreignProduct, $foreignCourse, $foreignModule, $foreignLesson] = $this->fullCourseFor($this->approvedUser(UserRole::Creator), 'Outro curso');

        $this->actingAs($creator)->get(route('creator.products.courses.modules.index', [$product, $course]))->assertOk()->assertSee($module->title);
        $this->actingAs($creator)->get(route('creator.products.courses.modules.lessons.index', [$product, $course, $module]))->assertOk()->assertSee($lesson->title);

        $this->actingAs($creator)->get(route('creator.products.courses.modules.index', [$foreignProduct, $foreignCourse]))->assertForbidden();
        $this->actingAs($creator)->get(route('creator.products.courses.modules.lessons.index', [$foreignProduct, $foreignCourse, $foreignModule]))->assertForbidden();
        $this->actingAs($creator)->get(route('creator.products.courses.modules.lessons.edit', [$foreignProduct, $foreignCourse, $foreignModule, $foreignLesson]))->assertForbidden();
    }

    public function test_forged_nested_payload_does_not_override_parent_relationships(): void
    {
        $creator = $this->approvedUser(UserRole::Creator);
        [$product, $course, $module] = $this->fullCourseFor($creator);
        [, $foreignCourse, $foreignModule] = $this->fullCourseFor($this->approvedUser(UserRole::Creator), 'Curso externo');
        Storage::fake('video_public');
        config(['filesystems.disks.video_public.root' => '/fake-video-root', 'filesystems.disks.video_public.url' => 'https://axxeracademy.com.br/videos']);

        $this->actingAs($creator)->post(route('creator.products.courses.modules.store', [$product, $course]), [
            'course_id' => $foreignCourse->id,
            'title' => 'Módulo seguro',
            'description' => 'Ignora course_id do payload.',
            'order' => 3,
        ])->assertRedirect(route('creator.products.courses.modules.index', [$product, $course]));

        $this->assertDatabaseHas('modules', [
            'course_id' => $course->id,
            'title' => 'Módulo seguro',
        ]);

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), [
            'module_id' => $foreignModule->id,
            'title' => 'Aula segura',
            'video' => UploadedFile::fake()->create('aula-segura.mp4', 1024, 'video/mp4'),
            'duration' => 120,
            'order' => 2,
            'published' => 1,
        ])->assertRedirect(route('creator.products.courses.modules.lessons.index', [$product, $course, $module]));

        $this->assertDatabaseHas('lessons', [
            'module_id' => $module->id,
            'title' => 'Aula segura',
        ]);
    }

    public function test_nested_bindings_stay_protected_for_creator_routes(): void
    {
        $creator = $this->approvedUser(UserRole::Creator);
        [$product, $course, $module] = $this->fullCourseFor($creator);
        [$otherProduct, $otherCourse, $otherModule, $otherLesson] = $this->fullCourseFor($creator, 'Curso em outro produto');

        $this->actingAs($creator)->get(route('creator.products.courses.edit', [$product, $otherCourse]))->assertNotFound();
        $this->actingAs($creator)->get(route('creator.products.courses.modules.edit', [$product, $course, $otherModule]))->assertNotFound();
        $this->actingAs($creator)->get(route('creator.products.courses.modules.lessons.edit', [$otherProduct, $otherCourse, $otherModule, $otherLesson]))->assertOk();
        $this->actingAs($creator)->get(route('creator.products.courses.modules.lessons.edit', [$product, $course, $module, $otherLesson]))->assertNotFound();
    }

    private function approvedUser(UserRole $role, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => $role, 'status' => UserStatus::Approved]);
    }

    private function courseFor(User $creator, string $title): array
    {
        $product = Product::create(['name' => $title.' Produto']);
        $course = Course::create([
            'product_id' => $product->id,
            'creator_id' => $creator->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'published' => true,
        ]);

        return [$product, $course];
    }

    private function fullCourseFor(User $creator, string $title = 'Curso próprio'): array
    {
        [$product, $course] = $this->courseFor($creator, $title);
        $module = Module::create(['course_id' => $course->id, 'title' => $title.' módulo', 'order' => 1]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => $title.' aula',
            'video_url' => 'https://youtu.be/abcdefghijk',
            'duration' => 90,
            'order' => 1,
            'published' => true,
        ]);

        return [$product, $course, $module, $lesson];
    }

    private function coursePayload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Curso base',
            'slug' => 'curso-base',
            'description' => 'Descrição',
            'cover_image' => null,
            'order' => 1,
            'published' => 1,
        ];
    }
}
