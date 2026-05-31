<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;

class ClubPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Club $club): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false; // Only admin via before()
    }

    public function update(User $user, Club $club): bool
    {
        return false;
    }

    public function delete(User $user, Club $club): bool
    {
        return false;
    }
}
