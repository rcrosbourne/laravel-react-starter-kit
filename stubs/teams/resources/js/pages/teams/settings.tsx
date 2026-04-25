import { Head, router, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface TeamMember {
    id: number;
    name: string;
    email: string;
    pivot: { role: string };
}

interface TeamInvitation {
    id: number;
    email: string;
    role: string;
}

interface TeamShape {
    id: number;
    name: string;
    owner_id: number;
    personal_team: boolean;
    users: TeamMember[];
    invitations: TeamInvitation[];
}

interface PageProps {
    team: TeamShape;
}

export default function TeamSettings({ team }: PageProps) {
    const renameForm = useForm({ name: team.name });
    const inviteForm = useForm({ email: '', role: 'member' });

    return (
        <>
            <Head title={`Team — ${team.name}`} />

            <div className="space-y-8">
                <Heading
                    variant="small"
                    title={team.name}
                    description="Manage team name, members, and invitations."
                />

                <section className="space-y-4">
                    <Heading
                        variant="small"
                        title="Team name"
                        description="Update the display name for this team."
                    />

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            renameForm.patch(`/teams/${team.id}`, {
                                preserveScroll: true,
                            });
                        }}
                        className="space-y-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="team-name">Name</Label>
                            <Input
                                id="team-name"
                                type="text"
                                value={renameForm.data.name}
                                onChange={(e) =>
                                    renameForm.setData('name', e.target.value)
                                }
                                required
                                autoComplete="off"
                            />
                            <InputError message={renameForm.errors.name} />
                        </div>

                        <Button
                            disabled={renameForm.processing}
                            data-test="team-rename-button"
                        >
                            Save
                        </Button>
                    </form>
                </section>

                <section className="space-y-4">
                    <Heading
                        variant="small"
                        title="Members"
                        description="People with access to this team."
                    />

                    <ul className="divide-y divide-border rounded-md border">
                        {team.users.map((user) => (
                            <li
                                key={user.id}
                                className="flex items-center justify-between px-4 py-3"
                            >
                                <div>
                                    <p className="font-medium">{user.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {user.email} ({user.pivot.role})
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    onClick={() =>
                                        router.delete(
                                            `/teams/${team.id}/members/${user.id}`,
                                            { preserveScroll: true },
                                        )
                                    }
                                    data-test={`remove-member-${user.id}`}
                                >
                                    Remove
                                </Button>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="space-y-4">
                    <Heading
                        variant="small"
                        title="Invite a member"
                        description="Send an invitation to join this team."
                    />

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            inviteForm.post(`/teams/${team.id}/invitations`, {
                                preserveScroll: true,
                                onSuccess: () => inviteForm.reset('email'),
                            });
                        }}
                        className="space-y-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="invite-email">Email address</Label>
                            <Input
                                id="invite-email"
                                type="email"
                                placeholder="email@example.com"
                                value={inviteForm.data.email}
                                onChange={(e) =>
                                    inviteForm.setData('email', e.target.value)
                                }
                                required
                                autoComplete="off"
                            />
                            <InputError message={inviteForm.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="invite-role">Role</Label>
                            <select
                                id="invite-role"
                                value={inviteForm.data.role}
                                onChange={(e) =>
                                    inviteForm.setData('role', e.target.value)
                                }
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="member">Member</option>
                                <option value="owner">Owner</option>
                            </select>
                            <InputError message={inviteForm.errors.role} />
                        </div>

                        <Button
                            disabled={inviteForm.processing}
                            data-test="invite-member-button"
                        >
                            Send invitation
                        </Button>
                    </form>
                </section>

                <section className="space-y-4">
                    <Heading
                        variant="small"
                        title="Pending invitations"
                        description="Invitations that have not yet been accepted."
                    />

                    {team.invitations.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No pending invitations.
                        </p>
                    ) : (
                        <ul className="divide-y divide-border rounded-md border">
                            {team.invitations.map((invitation) => (
                                <li
                                    key={invitation.id}
                                    className="px-4 py-3 text-sm"
                                >
                                    <span className="font-medium">
                                        {invitation.email}
                                    </span>{' '}
                                    <span className="text-muted-foreground">
                                        ({invitation.role})
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {!team.personal_team && (
                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title="Delete team"
                            description="Permanently delete this team and all of its data."
                        />

                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => {
                                if (
                                    confirm(
                                        'Are you sure you want to delete this team? This action cannot be undone.',
                                    )
                                ) {
                                    router.delete(`/teams/${team.id}`);
                                }
                            }}
                            data-test="delete-team-button"
                        >
                            Delete team
                        </Button>
                    </section>
                )}
            </div>
        </>
    );
}

TeamSettings.layout = {
    breadcrumbs: [
        {
            title: 'Team settings',
            href: '/teams',
        },
    ],
};
