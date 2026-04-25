<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

it('creates a personal team for a newly registered user', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password-12345',
        'password_confirmation' => 'password-12345',
    ]);

    $response->assertRedirect();

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect(Team::query()->where('owner_id', $user->id)->where('personal_team', true)->count())->toBe(1);
    expect($user->current_team_id)->not->toBeNull();
});
