<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Browser\visit;

uses(RefreshDatabase::class);

it('renames a team via the settings page', function (): void {
    $owner = User::factory()->create();
    $team = Team::create(['name' => 'Old Name', 'owner_id' => $owner->id]);

    $page = visit("/teams/{$team->id}")->actingAs($owner);

    $page->fill('input#team-name', 'New Name');
    $page->click('button:has-text("Save")');

    expect($team->fresh()->name)->toBe('New Name');
});
