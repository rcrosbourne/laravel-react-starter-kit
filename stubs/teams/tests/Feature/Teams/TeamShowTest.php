<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

it('shows the team settings page to a member', function (): void {
    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Acme', 'owner_id' => $owner->id]);

    $this->actingAs($owner)
        ->get('/teams/'.$team->id)
        ->assertOk();
});

it('forbids the team settings page for non-members', function (): void {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $team = Team::query()->create(['name' => 'Acme', 'owner_id' => $owner->id]);

    $this->actingAs($stranger)
        ->get('/teams/'.$team->id)
        ->assertForbidden();
});
