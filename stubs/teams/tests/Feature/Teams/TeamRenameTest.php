<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renames a team', function (): void {
    $owner = User::factory()->create();
    $team = Team::create(['name' => 'Old', 'owner_id' => $owner->id]);

    $this->actingAs($owner)->patch("/teams/{$team->id}", ['name' => 'New']);

    expect($team->fresh()->name)->toBe('New');
});

it('refuses rename for non-owner', function (): void {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $team = Team::create(['name' => 'Old', 'owner_id' => $owner->id]);

    $this->actingAs($stranger)
        ->patch("/teams/{$team->id}", ['name' => 'New'])
        ->assertForbidden();
});
