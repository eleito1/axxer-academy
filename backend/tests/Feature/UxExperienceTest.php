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

    public function test_lesson_player_uses_mobile_curriculum_accordion_without_duplicate_current_state(): void
    {
        [$student, $product, $course, $module, $lesson] = $this->learningTree();

        $response = $this->actingAs($student)->get(route('academy.lessons.show', [$product, $course, $module, $lesson]))
            ->assertOk()
            ->assertSee('class="player"', false)
            ->assertSee('<details class="curriculum-panel" open>', false)
            ->assertSee('Conteúdo do curso')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Aula atual:');

        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="page"'));
        $this->assertSame(1, substr_count($response->getContent(), 'Aula atual:'));
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
