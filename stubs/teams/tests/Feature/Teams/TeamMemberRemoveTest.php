<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

it('removes a member from a team', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::query()->create(['name' => 'Team', 'owner_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);

    $this->actingAs($owner)->delete("/teams/{$team->id}/members/{$member->id}");

    expect($team->users()->where('user_id', $member->id)->exists())->toBeFalse();
});

it('refuses member removal for non-owner', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::query()->create(['name' => 'Team', 'owner_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);

    $this->actingAs($member)
        ->delete("/teams/{$team->id}/members/{$member->id}")
        ->assertForbidden();
});
