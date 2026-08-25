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
        return $cake->is_active || $user?->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->canAccess('cakes');
    }

    public function update(User $user, Cake $cake): bool
    {
        return $user->canAccess('cakes');
    }

    public function delete(User $user, Cake $cake): bool
    {
        return $user->canAccess('cakes');
    }
}
