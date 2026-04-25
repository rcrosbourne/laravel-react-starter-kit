<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use function abort_unless;
use function assert;

final class TeamController extends Controller
{
    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        return Inertia::render('teams/settings', [
            'team' => $team->load('users', 'invitations'),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        /** @var array{name: string} $validated */
        $validated = $request->validate(['name' => 'required|string|max:255']);

        $team->update($validated);

        return back();
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('dashboard');
    }

    public function switch(Request $request, Team $team): RedirectResponse
    {
        $user = $request->user();

        assert($user instanceof \App\Models\User);

        abort_unless($user->switchTeam($team), 403);

        return back();
    }
}
