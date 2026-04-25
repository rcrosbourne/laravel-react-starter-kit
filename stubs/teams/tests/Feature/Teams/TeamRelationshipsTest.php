<?php

declare(strict_types=1);

use App\Listeners\CreatePersonalTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;

it('exposes a Team owner relation', function (): void {
    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Acme', 'owner_id' => $owner->id]);

    expect($team->owner)->not->toBeNull();
    expect($team->owner->id)->toBe($owner->id);
});

it('exposes a User currentTeam relation', function (): void {
    $owner = User::factory()->create();
    $team = Team::query()->create(['name' => 'Acme', 'owner_id' => $owner->id]);
    $owner->forceFill(['current_team_id' => $team->id])->save();

    expect($owner->fresh()->currentTeam)->not->toBeNull();
    expect($owner->fresh()->currentTeam->id)->toBe($team->id);
});

it('skips personal team creation when the registered authenticatable is not a User', function (): void {
    $listener = new CreatePersonalTeam();

    /** @var Authenticatable $impostor */
    $impostor = new class implements Authenticatable
    {
        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): int
        {
            return 0;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getRememberToken(): string
        {
            return '';
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return '';
        }
    };

    $listener->handle(new Registered($impostor));

    expect(Team::query()->count())->toBe(0);
});
