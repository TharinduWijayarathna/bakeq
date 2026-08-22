<?php

namespace App\Policies;

use App\Models\Cake;
use App\Models\User;

class CakePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Cake $cake): bool
    {
        return $cake->is_active || $user?->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Cake $cake): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Cake $cake): bool
    {
        return $user->isAdmin();
    }
}
