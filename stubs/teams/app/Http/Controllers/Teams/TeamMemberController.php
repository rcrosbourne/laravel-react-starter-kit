<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

final class TeamMemberController extends Controller
{
    use AuthorizesRequests;

    public function destroy(Team $team, User $user): RedirectResponse
    {
        $this->authorize('removeMember', $team);

        $team->users()->detach($user->id);

        return back();
    }
}
