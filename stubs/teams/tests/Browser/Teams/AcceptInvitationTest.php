<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\URL;

use function Pest\Browser\visit;

it('accepts an invitation through the browser', function (): void {
    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Team', 'owner_id' => $owner->id]);
    $invitation = TeamInvitation::query()->create([
        'team_id' => $team->id,
        'email' => 'guest@example.com',
        'role' => 'member',
    ]);

    $url = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation->id]);

    visit($url);

    expect(User::query()->where('email', 'guest@example.com')->exists())->toBeTrue();
});
