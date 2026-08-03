<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $lesson->published && $user->can('view', $lesson->module);
    }

    public function create(User $user): bool
    {
        return $user->canManageOwnedCourses();
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->can('update', $lesson->module);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->can('delete', $lesson->module);
    }
}
