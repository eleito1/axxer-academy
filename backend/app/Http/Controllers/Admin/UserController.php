<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(AdminDashboardService $dashboard): View
    {
        return view('admin.dashboard', [
            'users' => User::query()->with(['products', 'interestedProduct'])->latest()->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => UserRole::cases(),
            'stats' => $dashboard->statistics(),
            'topCourses' => $dashboard->topCourses(),
            'activeStudents' => $dashboard->activeStudents(),
            'latestAccesses' => $dashboard->latestAccesses(),
        ]);
    }

    public function status(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pendente,aprovado,bloqueado']]);
        $user->update(['status' => UserStatus::from($data['status'])]);

        return back()->with('success', 'Status atualizado.');
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(
            ['role' => ['required', Rule::enum(UserRole::class)]],
            ['role' => 'Selecione um papel válido.']
        );

        $role = UserRole::from($data['role']);
        if ($role !== UserRole::Admin && $this->isLastActiveAdmin($user)) {
            return back()->withErrors(['role' => 'Não é possível alterar o papel do último administrador ativo.']);
        }

        $user->update(['role' => $role]);

        return back()->with('success', 'Papel atualizado.');
    }

    public function products(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(
            [
                'products' => ['array'],
                'products.*' => [
                    'integer',
                    Rule::exists('products', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
                ],
            ],
            ['products.*.exists' => 'Selecione somente produtos ativos e disponíveis.']
        );
        $user->products()->sync($data['products'] ?? []);

        return back()->with('success', 'Produtos liberados atualizados.');
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if ($user->role !== UserRole::Admin || $user->status !== UserStatus::Approved) {
            return false;
        }

        return User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Approved)
            ->count() === 1;
    }
}
