<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

it('switches the current team when the user is a member', function (): void {
    $user = User::factory()->create();
    $teamA = Team::query()->create(['name' => 'A', 'owner_id' => $user->id, 'personal_team' => true]);
    $teamB = Team::query()->create(['name' => 'B', 'owner_id' => $user->id, 'personal_team' => false]);

    $user->forceFill(['current_team_id' => $teamA->id])->save();

    $this->actingAs($user)->post(sprintf('/teams/%d/switch', $teamB->id));

    expect($user->fresh()->current_team_id)->toBe($teamB->id);
});

it('rejects switching to a team the user does not belong to', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignTeam = Team::query()->create(['name' => 'Foreign', 'owner_id' => $other->id]);

    $this->actingAs($user)
        ->post(sprintf('/teams/%d/switch', $foreignTeam->id))
        ->assertForbidden();
});
