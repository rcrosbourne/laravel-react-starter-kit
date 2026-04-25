<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

final class CreatePersonalTeam
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $team = Team::query()->create([
            'name' => "{$user->name}'s Team",
            'owner_id' => $user->id,
            'personal_team' => true,
        ]);

        $user->forceFill(['current_team_id' => $team->id])->save();
    }
}
