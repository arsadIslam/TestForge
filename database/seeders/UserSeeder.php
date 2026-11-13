<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo logins (password for everyone: "password"):
 *   admin@testforge.test, teacher1..3@testforge.test, student1@testforge.test (has results),
 *   student2@testforge.test (fresh: nothing bought, no attempts)
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@testforge.test',
        ]);

        foreach (range(1, 3) as $i) {
            User::factory()->teacher()->create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@testforge.test",
            ]);
        }

        User::factory()->student()->create([
            'name' => 'Demo Student',
            'email' => 'student1@testforge.test',
        ]);

        // Has bought nothing and taken no tests: use it to try the paid-series checkout.
        User::factory()->student()->create([
            'name' => 'New Student',
            'email' => 'student2@testforge.test',
        ]);

        User::factory()->student()->count(48)->create();
    }
}
