<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Models\User;
use App\Services\Videos\VideoStorage;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class NativeVideoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_form_replaces_video_url_with_native_upload_and_configured_limit(): void
    {
        config(['videos.max_megabytes' => 500]);
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)->get(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->assertOk()
            ->assertSee('Escolher vídeo')
            ->assertSee('Arrastar vídeo')
            ->assertSee('MP4, MOV ou WEBM até 500 MB.')
            ->assertSee('name="video"', false)
            ->assertDontSee('URL do vídeo');
    }

    public function test_creator_can_upload_valid_mp4_video_for_new_lesson(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
            'title' => 'Criando usuários e permissões',
            'video' => UploadedFile::fake()->create('video original.mp4', 1024, 'video/mp4'),
        ]))->assertRedirect(route('creator.products.courses.modules.lessons.index', [$product, $course, $module]));

        $lesson = Lesson::firstOrFail();

        $this->assertSame('hostinger', $lesson->video_provider);
        $this->assertSame('video original.mp4', $lesson->video_original_name);
        $this->assertSame(1024 * 1024, $lesson->video_size);
        $this->assertSame('mp4', $lesson->video_extension);
        $this->assertNull($lesson->video_duration);
        $this->assertNotNull($lesson->video_uploaded_at);
        $this->assertStringStartsWith("creator-{$creator->id}/curso-{$course->id}/aula-{$lesson->id}/", $lesson->video_path);
        $this->assertMatchesRegularExpression('~/criando-usuarios-e-permissoes-[a-z0-9]{6}\.mp4$~', $lesson->video_path);
        $this->assertSame('https://axxeracademy.com.br/videos/'.$lesson->video_path, $lesson->video_url);
        $this->assertSame($lesson->video_url, $lesson->videoUrl());
        Storage::disk('video_public')->assertExists($lesson->video_path);
    }

    public function test_valid_mov_and_webm_uploads_are_accepted(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();

        foreach ([['aula.mov', 'video/quicktime', 'mov'], ['aula.webm', 'video/webm', 'webm']] as [$name, $mime, $extension]) {
            $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
                'title' => 'Aula '.$extension,
                'video' => UploadedFile::fake()->create($name, 1024, $mime),
                'order' => Lesson::count() + 1,
            ]))->assertRedirect();
        }

        $this->assertDatabaseHas('lessons', ['video_extension' => 'mov']);
        $this->assertDatabaseHas('lessons', ['video_extension' => 'webm']);
    }

    public function test_upload_rejects_invalid_extension_even_with_video_mimetype(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)
            ->from(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
                'video' => UploadedFile::fake()->create('payload.php', 1024, 'video/mp4'),
            ]))
            ->assertRedirect(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->assertSessionHasErrors(['video']);

        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_upload_rejects_invalid_mimetype_even_with_allowed_extension(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)
            ->from(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
                'video' => UploadedFile::fake()->create('arquivo.mp4', 1024, 'application/zip'),
            ]))
            ->assertRedirect(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->assertSessionHasErrors(['video']);

        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_upload_rejects_disguised_executable_file(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)
            ->from(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
                'video' => UploadedFile::fake()->create('shell.mp4', 1024, 'application/x-php'),
            ]))
            ->assertRedirect(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->assertSessionHasErrors(['video']);

        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_upload_rejects_configured_size_limit(): void
    {
        $this->configureVideoDisk();
        config(['videos.max_megabytes' => 1]);
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)
            ->from(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
                'video' => UploadedFile::fake()->create('grande.mp4', 1025, 'video/mp4'),
            ]))
            ->assertRedirect(route('creator.products.courses.modules.lessons.create', [$product, $course, $module]))
            ->assertSessionHasErrors(['video']);
    }

    public function test_generated_filename_is_unique_and_never_uses_original_name_or_traversal(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();

        foreach (['../primeiro arquivo.mp4', 'segundo arquivo.mp4'] as $originalName) {
            $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
                'title' => 'Criando usuários',
                'video' => UploadedFile::fake()->create($originalName, 1024, 'video/mp4'),
                'order' => Lesson::count() + 1,
            ]))->assertRedirect();
        }

        $paths = Lesson::orderBy('id')->pluck('video_path');

        $this->assertCount(2, $paths);
        $this->assertNotSame($paths[0], $paths[1]);
        $this->assertStringContainsString('/criando-usuarios-', $paths[0]);
        $this->assertStringNotContainsString('primeiro', $paths[0]);
        $this->assertStringNotContainsString('segundo', $paths[1]);
        $this->assertStringNotContainsString('..', $paths[0]);
    }

    public function test_video_path_is_isolated_by_creator_course_and_lesson(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();
        [$otherCreator, $otherProduct, $otherCourse, $otherModule] = $this->ownedModule('Outro curso');

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
            'video' => UploadedFile::fake()->create('aula.mp4', 1024, 'video/mp4'),
        ]))->assertRedirect();

        $this->actingAs($otherCreator)->post(route('creator.products.courses.modules.lessons.store', [$otherProduct, $otherCourse, $otherModule]), $this->lessonPayload([
            'video' => UploadedFile::fake()->create('aula.webm', 1024, 'video/webm'),
        ]))->assertRedirect();

        $first = Lesson::orderBy('id')->firstOrFail();
        $second = Lesson::orderBy('id')->skip(1)->firstOrFail();

        $this->assertStringStartsWith("creator-{$creator->id}/curso-{$course->id}/aula-{$first->id}/", $first->video_path);
        $this->assertStringStartsWith("creator-{$otherCreator->id}/curso-{$otherCourse->id}/aula-{$second->id}/", $second->video_path);
        $this->assertNotSame(dirname($first->video_path), dirname($second->video_path));
    }

    public function test_admin_can_upload_video_to_valid_lesson(): void
    {
        $this->configureVideoDisk();
        $admin = $this->approvedUser(UserRole::Admin);
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($admin)->post(route('admin.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
            'video' => UploadedFile::fake()->create('admin.mp4', 1024, 'video/mp4'),
        ]))->assertRedirect();

        $lesson = Lesson::firstOrFail();

        $this->assertStringStartsWith("creator-{$creator->id}/curso-{$course->id}/aula-{$lesson->id}/", $lesson->video_path);
    }

    public function test_creator_student_and_blocked_user_boundaries_are_enforced(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();
        $student = $this->approvedUser(UserRole::Student);
        $blockedCreator = User::factory()->create(['role' => UserRole::Creator, 'status' => UserStatus::Blocked]);
        [$foreignCreator, $foreignProduct, $foreignCourse, $foreignModule] = $this->ownedModule('Curso alheio');

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$foreignProduct, $foreignCourse, $foreignModule]), $this->lessonPayload())
            ->assertForbidden();

        $this->actingAs($student)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload())
            ->assertForbidden();

        $this->actingAs($blockedCreator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload())
            ->assertRedirect(route('account.status'));

        $this->assertNotSame($creator->id, $foreignCreator->id);
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_manipulated_payload_cannot_change_target_module_or_video_url(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();
        [, , , $foreignModule] = $this->ownedModule('Curso externo');

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload([
            'module_id' => $foreignModule->id,
            'video_url' => 'https://evil.example/video.mp4',
            'video' => UploadedFile::fake()->create('aula.mp4', 1024, 'video/mp4'),
        ]))->assertRedirect();

        $lesson = Lesson::firstOrFail();

        $this->assertSame($module->id, $lesson->module_id);
        $this->assertNotSame('https://evil.example/video.mp4', $lesson->video_url);
        $this->assertSame('hostinger', $lesson->video_provider);
    }

    public function test_lesson_is_created_before_upload_path_uses_lesson_id(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload())
            ->assertRedirect();

        $lesson = Lesson::firstOrFail();

        $this->assertStringContainsString("/aula-{$lesson->id}/", $lesson->video_path);
    }

    public function test_upload_failure_rolls_back_lesson_and_removes_partial_file(): void
    {
        $this->configureVideoDisk();
        $this->app->bind(VideoStorage::class, fn () => new class implements VideoStorage
        {
            public function putFileAs(string $directory, UploadedFile $file, string $filename): string
            {
                $path = trim($directory.'/'.$filename, '/');
                Storage::disk('video_public')->put($path, 'partial');

                throw new RuntimeException('Falha simulada no upload.');
            }

            public function delete(string $path): void
            {
                Storage::disk('video_public')->delete($path);
            }

            public function url(string $path): string
            {
                return 'https://axxeracademy.com.br/videos/'.$path;
            }
        });
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload())
            ->assertServerError();

        $this->assertDatabaseCount('lessons', 0);
        $this->assertSame([], Storage::disk('video_public')->allFiles());
    }

    public function test_database_failure_after_upload_removes_new_file_and_rolls_back_lesson(): void
    {
        $this->configureVideoDisk();
        $this->bindFailingMetadataUploadService();
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload())
            ->assertServerError();

        $this->assertDatabaseCount('lessons', 0);
        $this->assertSame([], Storage::disk('video_public')->allFiles());
    }

    public function test_updating_lesson_without_video_preserves_existing_video_data(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();
        $lesson = $this->nativeLesson($module, 'creator-1/curso-1/aula-1/aula-antiga-a1b2c3.mp4');

        $this->actingAs($creator)->put(route('creator.products.courses.modules.lessons.update', [$product, $course, $module, $lesson]), $this->lessonPayload([
            'title' => 'Aula renomeada',
        ], includeVideo: false))->assertRedirect();

        $lesson->refresh();

        $this->assertSame('Aula renomeada', $lesson->title);
        $this->assertSame('hostinger', $lesson->video_provider);
        $this->assertSame('creator-1/curso-1/aula-1/aula-antiga-a1b2c3.mp4', $lesson->video_path);
        $this->assertSame('original.mp4', $lesson->video_original_name);
    }

    public function test_video_replacement_removes_old_file_only_after_success(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();
        $oldPath = 'creator-'.$creator->id.'/curso-'.$course->id.'/aula-1/antigo-a1b2c3.mp4';
        Storage::disk('video_public')->put($oldPath, 'old');
        $lesson = $this->nativeLesson($module, $oldPath);

        $this->actingAs($creator)->put(route('creator.products.courses.modules.lessons.update', [$product, $course, $module, $lesson]), $this->lessonPayload([
            'video' => UploadedFile::fake()->create('novo.mp4', 1024, 'video/mp4'),
        ]))->assertRedirect();

        $lesson->refresh();

        Storage::disk('video_public')->assertMissing($oldPath);
        Storage::disk('video_public')->assertExists($lesson->video_path);
        $this->assertNotSame($oldPath, $lesson->video_path);
    }

    public function test_video_replacement_keeps_old_video_when_database_update_fails(): void
    {
        $this->configureVideoDisk();
        [$creator, $product, $course, $module] = $this->ownedModule();
        $oldPath = 'creator-'.$creator->id.'/curso-'.$course->id.'/aula-1/antigo-a1b2c3.mp4';
        Storage::disk('video_public')->put($oldPath, 'old');
        $lesson = $this->nativeLesson($module, $oldPath);
        $this->bindFailingMetadataUploadService();

        $this->actingAs($creator)->put(route('creator.products.courses.modules.lessons.update', [$product, $course, $module, $lesson]), $this->lessonPayload([
            'video' => UploadedFile::fake()->create('novo.mp4', 1024, 'video/mp4'),
        ]))->assertServerError();

        $lesson->refresh();

        $this->assertSame($oldPath, $lesson->video_path);
        Storage::disk('video_public')->assertExists($oldPath);
        $this->assertSame([$oldPath], Storage::disk('video_public')->allFiles());
    }

    public function test_legacy_youtube_and_google_drive_lessons_continue_to_resolve_external_urls(): void
    {
        [$creator, $product, $course, $module] = $this->ownedModule();
        $youtube = Lesson::create(['module_id' => $module->id, 'title' => 'YouTube', 'video_url' => 'https://youtu.be/abcdefghijk', 'published' => true]);
        $drive = Lesson::create(['module_id' => $module->id, 'title' => 'Drive', 'video_url' => 'https://drive.google.com/file/d/file-id-123/view?usp=sharing', 'published' => true]);

        $this->assertSame('https://youtu.be/abcdefghijk', $youtube->videoUrl());
        $this->assertSame('https://drive.google.com/file/d/file-id-123/view?usp=sharing', $drive->videoUrl());
        $this->assertSame($creator->id, $course->creator_id);
        $this->assertSame($product->id, $course->product_id);
    }

    public function test_missing_video_storage_config_fails_clearly(): void
    {
        Storage::fake('video_public');
        config(['filesystems.disks.video_public.root' => null, 'filesystems.disks.video_public.url' => null]);
        [$creator, $product, $course, $module] = $this->ownedModule();

        $this->actingAs($creator)->post(route('creator.products.courses.modules.lessons.store', [$product, $course, $module]), $this->lessonPayload())
            ->assertServerError();

        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_storage_rejects_unsafe_paths_and_code_has_no_production_absolute_path(): void
    {
        $this->configureVideoDisk();
        $storage = app(VideoStorage::class);

        $this->expectException(RuntimeException::class);
        $storage->delete('../outside.mp4');
    }

    public function test_no_production_absolute_path_is_hardcoded_in_application_code(): void
    {
        $files = [
            app_path(),
            config_path(),
            database_path('migrations'),
            resource_path('views'),
        ];

        foreach ($files as $path) {
            $this->assertStringNotContainsString('/home/u192805272/domains/axxeracademy.com.br/public_html/videos', $this->directoryContents($path));
        }
    }

    private function configureVideoDisk(): void
    {
        Storage::fake('video_public');
        config([
            'filesystems.disks.video_public.root' => '/fake-video-root',
            'filesystems.disks.video_public.url' => 'https://axxeracademy.com.br/videos',
            'videos.storage.disk' => 'video_public',
            'videos.storage.directory' => '',
            'videos.max_megabytes' => 500,
        ]);
    }

    private function bindFailingMetadataUploadService(): void
    {
        $this->app->instance(VideoUploadService::class, new class extends VideoUploadService
        {
            public function __construct() {}

            public function upload(UploadedFile $file, User $uploader, Course $course, Lesson $lesson): array
            {
                $path = "creator-{$uploader->id}/curso-{$course->id}/aula-{$lesson->id}/novo-a1b2c3.mp4";
                Storage::disk('video_public')->put($path, 'new');

                return [
                    'module_id' => null,
                    'video_provider' => 'hostinger',
                    'video_path' => $path,
                    'video_url' => 'https://axxeracademy.com.br/videos/'.$path,
                    'video_original_name' => $file->getClientOriginalName(),
                    'video_size' => $file->getSize(),
                    'video_extension' => 'mp4',
                    'video_duration' => null,
                    'video_uploaded_at' => now(),
                ];
            }

            public function delete(string $path): void
            {
                Storage::disk('video_public')->delete($path);
            }
        });
    }

    private function nativeLesson(Module $module, string $path): Lesson
    {
        return Lesson::create([
            'module_id' => $module->id,
            'title' => 'Aula antiga',
            'video_url' => 'https://axxeracademy.com.br/videos/'.$path,
            'video_provider' => 'hostinger',
            'video_path' => $path,
            'video_original_name' => 'original.mp4',
            'video_size' => 123,
            'video_extension' => 'mp4',
            'order' => 1,
        ]);
    }

    private function ownedModule(string $title = 'Curso base'): array
    {
        $creator = $this->approvedUser(UserRole::Creator);
        $product = Product::create(['name' => $title.' Produto']);
        $course = Course::create([
            'product_id' => $product->id,
            'creator_id' => $creator->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
        ]);
        $module = Module::create(['course_id' => $course->id, 'title' => $title.' Módulo', 'order' => 1]);

        return [$creator, $product, $course, $module];
    }

    private function approvedUser(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => UserStatus::Approved]);
    }

    private function lessonPayload(array $overrides = [], bool $includeVideo = true): array
    {
        $payload = [
            'title' => 'Aula base',
            'description' => 'Descrição',
            'duration' => 120,
            'support_material' => null,
            'order' => 1,
            'published' => 1,
        ];

        if ($includeVideo) {
            $payload['video'] = UploadedFile::fake()->create('aula.mp4', 1024, 'video/mp4');
        }

        return $overrides + $payload;
    }

    private function directoryContents(string $directory): string
    {
        $content = '';

        foreach (File::allFiles($directory) as $file) {
            try {
                $content .= $file->getPathname()."\n".File::get($file->getPathname())."\n";
            } catch (Throwable) {
            }
        }

        return $content;
    }
}
