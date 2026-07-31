<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@axxer.com.br')], ['name' => 'Administrador AXXER', 'company' => 'AXXER', 'city' => 'Não informado', 'whatsapp' => 'Não informado', 'password' => env('ADMIN_PASSWORD', 'ChangeMe123!'), 'status' => UserStatus::Approved, 'role' => UserRole::Admin]);
    }
}
