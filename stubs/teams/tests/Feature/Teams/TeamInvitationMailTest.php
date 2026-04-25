<?php

declare(strict_types=1);

use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('builds the envelope and content for an invitation mail', function (): void {
    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Globex', 'owner_id' => $owner->id]);
    $invitation = TeamInvitation::query()->create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'role' => 'member',
    ]);

    $mail = new TeamInvitationMail($invitation, 'https://example.test/accept');

    expect($mail->envelope()->subject)->toBe("You're invited to join Globex");
    expect($mail->content()->view)->toBe('mail.team-invitation');
    expect($mail->content()->with)->toMatchArray(['acceptUrl' => 'https://example.test/accept']);
});

it('falls back when the invitation team is missing', function (): void {
    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Initech', 'owner_id' => $owner->id]);
    $invitation = TeamInvitation::query()->create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'role' => 'member',
    ]);

    $team->delete();
    $invitation->refresh();

    $mail = new TeamInvitationMail($invitation, 'https://example.test/accept');

    expect($mail->envelope()->subject)->toBe("You're invited to join a team");
});
