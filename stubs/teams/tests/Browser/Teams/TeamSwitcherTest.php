<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Browser\visit;

uses(RefreshDatabase::class);

it('switches teams via the navbar dropdown', function (): void {
    $user = User::factory()->create();
    $teamA = Team::create(['name' => 'Alpha', 'owner_id' => $user->id, 'personal_team' => true]);
    $teamB = Team::create(['name' => 'Beta', 'owner_id' => $user->id]);
    $user->forceFill(['current_team_id' => $teamA->id])->save();

    $page = visit('/dashboard')->actingAs($user);

    $page->select('select[aria-label="Switch team"]', (string) $teamB->id);

    expect($user->fresh()->current_team_id)->toBe($teamB->id);
});
