<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\RegisterRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'E-mail ou senha inválidos.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user->isApproved()) {
            return redirect()->route('account.status');
        }

        return redirect()->intended(match (true) {
            $user->isAdmin() => route('admin.dashboard'),
            $user->isCreator() => route('creator.dashboard'),
            default => route('dashboard'),
        });
    }

    public function showRegister(): View
    {
        return view('auth.register', ['products' => Product::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create($data + ['status' => UserStatus::Pending, 'role' => UserRole::Student]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.status');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
