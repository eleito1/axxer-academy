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

class UxExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_premium_learning_sections(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->learningTree();
        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $lesson->id, 'last_seconds' => 45]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Continuar assistindo')
            ->assertSee('Cursos disponíveis')
            ->assertSee('Meus produtos')
            ->assertSee('Cursos em andamento')
            ->assertSee('course-card', false)
            ->assertSee('progressbar', false)
            ->assertSee($product->name)
            ->assertSee($course->title);
    }

    public function test_lesson_player_keeps_mobile_player_first_with_visible_compact_curriculum(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->learningTree();
        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Segunda venda',
            'description' => 'Aula de continuidade.',
            'video_url' => 'https://youtu.be/bbbbbbbbbbb',
            'duration' => 120,
            'order' => 2,
            'published' => true,
        ]);

        $response = $this->actingAs($student)->get(route('academy.lessons.show', [$product, $course, $module, $lesson]))
            ->assertOk()
            ->assertSee('id="lesson-player"', false)
            ->assertSee('id="lesson-title"', false)
            ->assertSee('id="course-curriculum"', false)
            ->assertSee('Conteúdo da aula')
            ->assertSee('lesson-list', false)
            ->assertSee('lesson-status', false)
            ->assertSee('Em andamento')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Aula atual:')
            ->assertSee('?focus=player', false)
            ->assertSee('scrollToPlayerFromLessonList')
            ->assertSee("url.searchParams.delete('focus')", false)
            ->assertSee('prefers-reduced-motion', false)
            ->assertDontSee('.player::before', false)
            ->assertDontSee('.player::after', false)
            ->assertDontSee('<details', false)
            ->assertDontSee('aria-expanded', false)
            ->assertDontSee('background: #091329', false);

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'id="course-curriculum"'), strpos($content, 'id="lesson-player-card"'));
        $this->assertLessThan(strpos($content, 'id="lesson-title"'), strpos($content, 'id="lesson-player"'));
        $this->assertLessThan(strpos($content, 'class="lesson-actions"'), strpos($content, 'id="lesson-title"'));
        $this->assertLessThan(strpos($content, 'id="course-curriculum"'), strpos($content, 'class="lesson-actions"'));
        $lessonListPosition = strpos($content, '<div class="lesson-list">');
        $progressPosition = strpos($content, 'id="course-progress"');
        $this->assertTrue($progressPosition > $lessonListPosition);

        $this->assertSame(1, substr_count($content, 'aria-current="page"'));
        $this->assertSame(1, substr_count($content, 'Aula atual:'));
        $this->assertSame(1, substr_count($content, 'role="progressbar"'));
    }

    public function test_completed_lesson_keeps_compact_buttons_progress_and_certificate_state(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->learningTree();
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'completed_at' => now(),
            'last_seconds' => 100,
        ]);

        $this->actingAs($student)->get(route('academy.lessons.show', [$product, $course, $module, $lesson]))
            ->assertOk()
            ->assertSee('Aula concluída')
            ->assertSee('disabled', false)
            ->assertSee('Certificado disponível')
            ->assertSee('100%')
            ->assertSee('1/1 aulas');
    }

    public function test_empty_dashboard_states_are_clear_for_student_without_released_products(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student, 'status' => UserStatus::Approved]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nenhum curso disponível')
            ->assertSee('Nenhum produto liberado')
            ->assertSee('Nenhum curso iniciado')
            ->assertSee('Nenhum curso concluído ainda')
            ->assertSee('empty-state', false);
    }

    private function learningTree(): array
    {
        $student = User::factory()->create(['role' => UserRole::Student, 'status' => UserStatus::Approved]);
        $product = Product::create(['name' => 'AXXER Optical', 'description' => 'Treinamento premium para óticas.']);
        $course = Course::create([
            'product_id' => $product->id,
            'title' => 'Vendas consultivas',
            'slug' => 'vendas-consultivas',
            'description' => 'Aprenda uma rotina comercial mais clara.',
            'published' => true,
        ]);
        $module = Module::create(['course_id' => $course->id, 'title' => 'Fundamentos', 'description' => 'Base do método.']);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Primeira venda',
            'description' => 'Aula de abertura.',
            'video_url' => 'https://youtu.be/abcdefghijk',
            'duration' => 100,
            'published' => true,
        ]);
        $student->products()->attach($product);

        return [$student, $product, $course, $module, $lesson];
    }
}
