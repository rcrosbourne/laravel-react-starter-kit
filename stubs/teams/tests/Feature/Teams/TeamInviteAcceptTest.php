<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('accepts an invitation for an existing user', function (): void {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $team = Team::create(['name' => 'Team', 'owner_id' => $owner->id]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'role' => 'member',
    ]);

    $url = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation->id]);

    $this->actingAs($invitee)->get($url)->assertRedirect();

    expect($team->fresh()->users()->where('user_id', $invitee->id)->exists())->toBeTrue();
    expect(TeamInvitation::find($invitation->id))->toBeNull();
});

it('creates a user when accepting an invitation as a guest', function (): void {
    $owner = User::factory()->create();
    $team = Team::create(['name' => 'Team', 'owner_id' => $owner->id]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'newuser@example.com',
        'role' => 'member',
    ]);

    $url = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation->id]);

    $this->get($url)->assertRedirect();

    $user = User::where('email', 'newuser@example.com')->firstOrFail();
    expect($team->fresh()->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects invitation accept with invalid signature', function (): void {
    $owner = User::factory()->create();
    $team = Team::create(['name' => 'Team', 'owner_id' => $owner->id]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'x@example.com',
        'role' => 'member',
    ]);

    $this->get("/team-invitations/{$invitation->id}/accept")
        ->assertForbidden();
});
