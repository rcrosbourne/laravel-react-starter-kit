<?php

declare(strict_types=1);

use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends an invitation email to a new address', function (): void {
    Mail::fake();

    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Team', 'owner_id' => $owner->id]);

    $this->actingAs($owner)->post(sprintf('/teams/%d/invitations', $team->id), [
        'email' => 'new@example.com',
        'role' => 'member',
    ]);

    expect(TeamInvitation::query()->where('email', 'new@example.com')->count())->toBe(1);
    Mail::assertSent(TeamInvitationMail::class);
});

it('refuses invitation send for non-owner', function (): void {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $team = Team::query()->create(['name' => 'Team', 'owner_id' => $owner->id]);

    $this->actingAs($stranger)
        ->post(sprintf('/teams/%d/invitations', $team->id), ['email' => 'x@example.com', 'role' => 'member'])
        ->assertForbidden();
});
