<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Module $module): bool
    {
        return $user->can('view', $module->course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Module $module): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->isAdmin();
    }
}
