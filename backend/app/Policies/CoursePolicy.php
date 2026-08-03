<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->canManageOwnedCourses();
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isCreator()) {
            return $course->creator_id === $user->id;
        }

        return $course->published && $course->product->is_active && $user->products()->whereKey($course->product_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canManageOwnedCourses();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isCreator() && $course->creator_id === $user->id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isCreator() && $course->creator_id === $user->id;
    }
}
