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
        $response = $this->post('/cadastro', $this->validRegistrationData($product));
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

    public function test_admin_dashboard_allows_selecting_user_role(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);
        User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Student]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Papel')
            ->assertSee('Aluno')
            ->assertSee('Criador')
            ->assertSee('Administrador')
            ->assertSee(route('admin.users.role', User::where('role', UserRole::Student)->first()), false);
    }

    public function test_admin_can_update_user_role_to_student_creator_and_admin(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);
        $user = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Student]);

        $this->actingAs($admin)->patch(route('admin.users.role', $user), ['role' => UserRole::Creator->value])
            ->assertRedirect()
            ->assertSessionHas('success', 'Papel atualizado.');
        $this->assertSame(UserRole::Creator, $user->fresh()->role);

        $this->actingAs($admin)->patch(route('admin.users.role', $user), ['role' => UserRole::Admin->value])->assertRedirect();
        $this->assertSame(UserRole::Admin, $user->fresh()->role);

        $this->actingAs($admin)->patch(route('admin.users.role', $user), ['role' => UserRole::Student->value])->assertRedirect();
        $this->assertSame(UserRole::Student, $user->fresh()->role);
    }

    public function test_admin_role_update_rejects_invalid_role(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);
        $user = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Student]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->patch(route('admin.users.role', $user), ['role' => 'super-admin'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors(['role' => 'Selecione um papel válido.']);

        $this->assertSame(UserRole::Student, $user->fresh()->role);
    }

    public function test_admin_cannot_change_last_active_admin_role_to_student_or_creator(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);
        User::factory()->create(['status' => UserStatus::Blocked, 'role' => UserRole::Admin]);
        User::factory()->create(['status' => UserStatus::Pending, 'role' => UserRole::Admin]);

        foreach ([UserRole::Student, UserRole::Creator] as $role) {
            $this->actingAs($admin)
                ->from(route('admin.dashboard'))
                ->patch(route('admin.users.role', $admin), ['role' => $role->value])
                ->assertRedirect(route('admin.dashboard'))
                ->assertSessionHasErrors(['role' => 'Não é possível alterar o papel do último administrador ativo.']);

            $this->assertSame(UserRole::Admin, $admin->fresh()->role);
        }
    }

    public function test_admin_can_change_admin_role_when_another_active_admin_exists(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);
        $otherAdmin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);

        $this->actingAs($otherAdmin)
            ->patch(route('admin.users.role', $admin), ['role' => UserRole::Creator->value])
            ->assertRedirect()
            ->assertSessionHas('success', 'Papel atualizado.');

        $this->assertSame(UserRole::Creator, $admin->fresh()->role);
        $this->assertSame(UserRole::Admin, $otherAdmin->fresh()->role);
    }

    public function test_registration_required_messages_are_clear_and_in_portuguese(): void
    {
        $response = $this->from('/cadastro')->post('/cadastro', []);

        $response->assertRedirect('/cadastro')->assertSessionHasErrors([
            'name' => 'O nome é obrigatório.',
            'company' => 'A empresa é obrigatória.',
            'city' => 'A cidade é obrigatória.',
            'whatsapp' => 'O WhatsApp é obrigatório.',
            'interested_product_id' => 'O produto de interesse é obrigatório.',
            'email' => 'O e-mail é obrigatório.',
            'password' => 'A senha é obrigatória.',
            'password_confirmation' => 'A confirmação da senha é obrigatória.',
        ]);
    }

    public function test_registration_rejects_short_password_with_friendly_message(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $data = $this->validRegistrationData($product, ['password' => '1234567', 'password_confirmation' => '1234567']);

        $this->from('/cadastro')->post('/cadastro', $data)
            ->assertRedirect('/cadastro')
            ->assertSessionHasErrors(['password' => 'A senha deve ter pelo menos 8 caracteres.']);

        $this->assertDatabaseMissing('users', ['email' => 'maria@example.com']);
    }

    public function test_registration_accepts_password_with_exactly_eight_characters(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $data = $this->validRegistrationData($product, ['password' => '12345678', 'password_confirmation' => '12345678']);

        $this->post('/cadastro', $data)->assertRedirect(route('account.status'));

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_registration_rejects_different_password_confirmation(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $data = $this->validRegistrationData($product, ['password_confirmation' => 'outra-senha']);

        $this->post('/cadastro', $data)
            ->assertSessionHasErrors(['password' => 'A confirmação da senha não corresponde.']);
    }

    public function test_registration_rejects_invalid_and_duplicate_email_with_friendly_messages(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);

        $this->post('/cadastro', $this->validRegistrationData($product, ['email' => 'email-invalido']))
            ->assertSessionHasErrors(['email' => 'Informe um endereço de e-mail válido.']);

        User::factory()->create(['email' => 'maria@example.com']);

        $this->post('/cadastro', $this->validRegistrationData($product))
            ->assertSessionHasErrors(['email' => 'Este e-mail já está cadastrado.']);
    }

    public function test_registration_requires_interested_product(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $data = $this->validRegistrationData($product, ['interested_product_id' => '']);

        $this->post('/cadastro', $data)
            ->assertSessionHasErrors(['interested_product_id' => 'O produto de interesse é obrigatório.']);
    }

    public function test_registration_rejects_inactive_soft_deleted_or_missing_interested_product(): void
    {
        $inactive = Product::create(['name' => 'Produto inativo', 'is_active' => false]);
        $deleted = Product::create(['name' => 'Produto excluído']);
        $deleted->delete();

        foreach ([$inactive->id, $deleted->id, 999999] as $productId) {
            $data = $this->validRegistrationData($inactive, [
                'email' => "maria-{$productId}@example.com",
                'interested_product_id' => $productId,
            ]);

            $this->from('/cadastro')->post('/cadastro', $data)
                ->assertRedirect('/cadastro')
                ->assertSessionHasErrors(['interested_product_id' => 'Selecione um produto de interesse ativo e disponível.']);
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_product_release_rejects_inactive_soft_deleted_or_missing_products_and_preserves_existing_links(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Admin]);
        $student = User::factory()->create(['status' => UserStatus::Approved, 'role' => UserRole::Student]);
        $valid = Product::create(['name' => 'AXXER Optical']);
        $inactive = Product::create(['name' => 'Produto inativo', 'is_active' => false]);
        $deleted = Product::create(['name' => 'Produto excluído']);
        $deleted->delete();
        $student->products()->attach($valid);

        foreach ([$inactive->id, $deleted->id, 999999] as $productId) {
            $this->actingAs($admin)
                ->from(route('admin.dashboard'))
                ->put(route('admin.users.products', $student), ['products' => [$valid->id, $productId]])
                ->assertRedirect(route('admin.dashboard'))
                ->assertSessionHasErrors(['products.1' => 'Selecione somente produtos ativos e disponíveis.']);

            $this->assertTrue($student->fresh()->products()->whereKey($valid->id)->exists());
            $this->assertFalse($student->fresh()->products()->whereKey($productId)->exists());
        }
    }

    public function test_registration_rejects_invalid_whatsapp_with_friendly_message(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $data = $this->validRegistrationData($product, ['whatsapp' => str_repeat('9', 31)]);

        $this->post('/cadastro', $data)
            ->assertSessionHasErrors(['whatsapp' => 'Informe um número de WhatsApp válido.']);
    }

    public function test_registration_preserves_safe_input_and_never_displays_translation_keys(): void
    {
        $product = Product::create(['name' => 'AXXER Optical']);
        $data = $this->validRegistrationData($product, ['password' => 'curta', 'password_confirmation' => 'curta']);

        $response = $this->from('/cadastro')->followingRedirects()->post('/cadastro', $data);

        $response->assertOk()
            ->assertSee('value="Maria Silva"', false)
            ->assertSee('value="maria@example.com"', false)
            ->assertSee('Mínimo de 8 caracteres.')
            ->assertSee('A senha deve ter pelo menos 8 caracteres.')
            ->assertDontSee('validation.', false)
            ->assertDontSee('value="curta"', false);
    }

    private function validRegistrationData(Product $product, array $overrides = []): array
    {
        return array_replace([
            'name' => 'Maria Silva',
            'company' => 'Ótica Luz',
            'city' => 'Curitiba',
            'whatsapp' => '41999999999',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'interested_product_id' => $product->id,
        ], $overrides);
    }
}
