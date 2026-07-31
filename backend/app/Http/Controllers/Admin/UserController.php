<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(AdminDashboardService $dashboard): View
    {
        return view('admin.dashboard', [
            'users' => User::query()->with(['products', 'interestedProduct'])->latest()->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
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

    public function products(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['products' => ['array'], 'products.*' => ['integer', 'exists:products,id']]);
        $user->products()->sync($data['products'] ?? []);

        return back()->with('success', 'Produtos liberados atualizados.');
    }
}
