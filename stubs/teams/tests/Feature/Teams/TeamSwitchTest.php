<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('switches the current team when the user is a member', function (): void {
    $user = User::factory()->create();
    $teamA = Team::create(['name' => 'A', 'owner_id' => $user->id, 'personal_team' => true]);
    $teamB = Team::create(['name' => 'B', 'owner_id' => $user->id, 'personal_team' => false]);

    $user->forceFill(['current_team_id' => $teamA->id])->save();

    $this->actingAs($user)->post("/teams/{$teamB->id}/switch");

    expect($user->fresh()->current_team_id)->toBe($teamB->id);
});

it('rejects switching to a team the user does not belong to', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignTeam = Team::create(['name' => 'Foreign', 'owner_id' => $other->id]);

    $this->actingAs($user)
        ->post("/teams/{$foreignTeam->id}/switch")
        ->assertForbidden();
});
