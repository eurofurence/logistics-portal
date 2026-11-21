<?php

namespace Database\Factories;

use App\Models\ClubMember;
use App\Models\ClubMemberAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClubMemberAccount>
 */
class ClubMemberAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_member_id' => ClubMember::factory(),
            'login_id' => (string) Str::uuid(),
            'password' => 'member-password-test',
        ];
    }

    public function awaitingActivation(): static
    {
        return $this->state(['password' => null]);
    }
}
