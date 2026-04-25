<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

use function Pest\Browser\visit;

it('renames a team via the settings page', function (): void {
    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Old Name', 'owner_id' => $owner->id]);

    $page = visit("/teams/{$team->id}")->actingAs($owner);

    $page->fill('input#team-name', 'New Name');
    $page->click('button:has-text("Save")');

    expect($team->fresh()->name)->toBe('New Name');
});
