<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes a non-personal team', function (): void {
    $owner = User::factory()->create();
    $team = Team::create(['name' => 'Project', 'owner_id' => $owner->id, 'personal_team' => false]);

    $this->actingAs($owner)->delete("/teams/{$team->id}");

    expect(Team::find($team->id))->toBeNull();
});

it('refuses to delete a personal team', function (): void {
    $owner = User::factory()->create();
    $team = Team::create(['name' => 'Personal', 'owner_id' => $owner->id, 'personal_team' => true]);

    $this->actingAs($owner)
        ->delete("/teams/{$team->id}")
        ->assertForbidden();
});
