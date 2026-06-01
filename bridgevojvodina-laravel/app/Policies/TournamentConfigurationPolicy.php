<?php

namespace App\Policies;

use App\Models\TournamentConfiguration;
use App\Models\User;

class TournamentConfigurationPolicy
{
    /**
     * Perform pre-authorization checks.
     */
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

    public function view(?User $user, TournamentConfiguration $tournamentConfiguration): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isDirector();
    }

    public function update(User $user, TournamentConfiguration $tournamentConfiguration): bool
    {
        return $user->isAdmin() || ($user->isDirector() && $user->id === $tournamentConfiguration->user_id);
    }

    public function delete(User $user, TournamentConfiguration $tournamentConfiguration): bool
    {
        return $user->isAdmin() || ($user->isDirector() && $user->id === $tournamentConfiguration->user_id);
    }
}
