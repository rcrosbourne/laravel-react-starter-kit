<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

final class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $team->hasUser($user);
    }

    public function update(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id && ! $team->personal_team;
    }

    public function invite(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function removeMember(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }
}
