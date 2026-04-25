import { router, usePage } from '@inertiajs/react';

interface SharedTeam {
    id: number;
    name: string;
}

export default function TeamSwitcher() {
    const { auth } = usePage().props;
    const user = auth.user as
        | (typeof auth.user & {
              current_team_id: number | null;
              all_teams: SharedTeam[];
          })
        | null;

    if (!user || !user.all_teams || user.all_teams.length <= 1) {
        return null;
    }

    return (
        <select
            value={user.current_team_id ?? ''}
            onChange={(e) =>
                router.post(
                    `/teams/${e.target.value}/switch`,
                    {},
                    { preserveScroll: true },
                )
            }
            className="flex h-9 rounded-md border border-input bg-background px-2 py-1 text-sm"
            aria-label="Switch team"
        >
            {user.all_teams.map((team) => (
                <option key={team.id} value={team.id}>
                    {team.name}
                </option>
            ))}
        </select>
    );
}
