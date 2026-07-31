<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_pending_student_and_blocks_dashboard(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $response = $this->post('/cadastro', ['name' => 'Maria Silva', 'company' => 'Ótica Luz', 'city' => 'Curitiba', 'whatsapp' => '41999999999', 'email' => 'maria@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'interested_product_id' => $product->id]);
        $user = User::where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertSame(UserRole::Student, $user->role);
        $response->assertRedirect(route('account.status'));
        $this->get('/dashboard')->assertRedirect(route('account.status'));
    }

    public function test_approved_student_can_access_dashboard(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Student]);
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_blocked_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Blocked]);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('account.status'));
    }

    public function test_student_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Student]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_approve_user(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);
        $student = User::factory()->create(['status' => UserStatus::Pending]);
        $this->actingAs($admin)->patch("/admin/usuarios/{$student->id}/status", ['status' => 'aprovado'])->assertRedirect();
        $this->assertSame(UserStatus::Approved, $student->fresh()->status);
    }
}
