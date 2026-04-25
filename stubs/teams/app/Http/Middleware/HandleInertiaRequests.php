<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => fn (): ?array => $this->resolveAuthUser($request->user()),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Build the shared user payload, augmented with team data.
     *
     * @return array<string, mixed>|null
     */
    private function resolveAuthUser(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        return [
            ...$user->toArray(),
            'current_team_id' => $user->current_team_id,
            'all_teams' => $user->allTeams()
                ->map(fn (Team $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
