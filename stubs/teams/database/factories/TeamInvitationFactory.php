<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamInvitation>
 */
final class TeamInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => 1,
            'email' => fake()->unique()->safeEmail(),
            'role' => 'member',
        ];
    }
}
