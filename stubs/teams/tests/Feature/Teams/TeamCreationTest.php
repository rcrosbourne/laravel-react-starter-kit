<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a personal team for a newly registered user', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password-12345',
        'password_confirmation' => 'password-12345',
    ]);

    $response->assertRedirect();

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    expect(Team::where('owner_id', $user->id)->where('personal_team', true)->count())->toBe(1);
    expect($user->current_team_id)->not->toBeNull();
});
