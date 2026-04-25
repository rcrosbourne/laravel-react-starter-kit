<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use function abort_if;
use function abort_unless;
use function auth;
use function bcrypt;

final class TeamInvitationController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('invite', $team);

        /** @var array{email: string, role: string} $validated */
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:owner,member'],
        ]);

        $invitation = $team->invitations()->create($validated);

        $signedUrl = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation->id]);

        Mail::to($validated['email'])->send(new TeamInvitationMail($invitation, $signedUrl));

        return back();
    }

    public function accept(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless((bool) $request->hasValidSignature(), 403);

        $team = $invitation->team;
        abort_if($team === null, 404);

        $user = $request->user();
        $wasGuest = ! $user instanceof User;

        if (! $user instanceof User) {
            $user = User::query()->firstOrCreate(
                ['email' => $invitation->email],
                ['name' => $invitation->email, 'password' => bcrypt(Str::random(32))],
            );
        }

        $team->users()->syncWithoutDetaching([
            $user->id => ['role' => $invitation->role],
        ]);

        $invitation->delete();

        if ($wasGuest) {
            auth()->login($user);
        }

        $user->switchTeam($team);

        return to_route('dashboard');
    }
}
