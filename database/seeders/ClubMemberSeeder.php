<?php

namespace Database\Seeders;

use App\Models\ClubMember;
use Illuminate\Database\Seeder;

class ClubMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClubMember::factory()->count(10)->create();
    }
}
