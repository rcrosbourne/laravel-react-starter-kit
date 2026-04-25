<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use function abort_unless;
use function auth;
use function bcrypt;

final class TeamInvitationController extends Controller
{
    public function store(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('invite', $team);

        /** @var array{email: string, role: string} $validated */
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:owner,member',
        ]);

        $invitation = $team->invitations()->create($validated);

        $signedUrl = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation->id]);

        Mail::to($validated['email'])->send(new TeamInvitationMail($invitation, $signedUrl));

        return back();
    }

    public function accept(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $user = $request->user();

        if (! $user instanceof User) {
            $user = User::firstOrCreate(
                ['email' => $invitation->email],
                ['name' => $invitation->email, 'password' => bcrypt(Str::random(32))],
            );
        }

        $invitation->team->users()->syncWithoutDetaching([
            $user->id => ['role' => $invitation->role],
        ]);

        $invitationTeam = $invitation->team;
        $invitation->delete();

        if (! $request->user() instanceof User) {
            auth()->login($user);
        }

        $user->switchTeam($invitationTeam);

        return redirect()->route('dashboard');
    }
}
