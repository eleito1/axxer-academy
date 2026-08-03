<?php

use App\Http\Controllers\AcademyController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->name('register.store');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::view('/conta/status', 'account-status')->name('account.status');
    Route::middleware('approved')->scopeBindings()->group(function () {
        Route::get('/dashboard', [AcademyController::class, 'index'])->name('dashboard');
        Route::get('/produtos/{product}', [AcademyController::class, 'product'])->name('academy.products.show');
        Route::get('/produtos/{product}/cursos/{course}', [AcademyController::class, 'course'])->name('academy.courses.show');
        Route::get('/produtos/{product}/cursos/{course}/modulos/{module}', [AcademyController::class, 'module'])->name('academy.modules.show');
        Route::get('/produtos/{product}/cursos/{course}/modulos/{module}/aulas/{lesson}', [AcademyController::class, 'lesson'])->name('academy.lessons.show');
        Route::put('/produtos/{product}/cursos/{course}/modulos/{module}/aulas/{lesson}/progresso', [AcademyController::class, 'updateProgress'])->name('academy.lessons.progress');
        Route::prefix('creator')->name('creator.')->middleware('creator')->group(function () {
            Route::get('/', [CourseController::class, 'mine'])->name('dashboard');
            Route::resource('products.courses', CourseController::class)->except('show');
            Route::resource('products.courses.modules', ModuleController::class)->except('show');
            Route::resource('products.courses.modules.lessons', LessonController::class)->except('show');
        });
        Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('dashboard');
            Route::patch('/usuarios/{user}/status', [UserController::class, 'status'])->name('users.status');
            Route::patch('/usuarios/{user}/papel', [UserController::class, 'role'])->name('users.role');
            Route::put('/usuarios/{user}/produtos', [UserController::class, 'products'])->name('users.products');
            Route::resource('products', ProductController::class)->except('show');
            Route::resource('products.courses', CourseController::class)->except('show');
            Route::resource('products.courses.modules', ModuleController::class)->except('show');
            Route::resource('products.courses.modules.lessons', LessonController::class)->except('show');
        });
    });
});
