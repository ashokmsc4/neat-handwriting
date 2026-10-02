<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Creates the teacher's login from OWNER_* in .env. Safe to run again. */
class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        $owner = config('school.owner');

        if (User::where('email', $owner['email'])->exists()) {
            return;
        }

        $password = $owner['password'] ?: Str::password(14, symbols: false);

        User::create([
            'name' => $owner['name'],
            'email' => $owner['email'],
            'role' => 'owner',
            'password' => $password,
        ]);

        if (! $owner['password']) {
            $this->command?->warn("Owner login: {$owner['email']} / {$password} (change it after first login)");
        }
    }
}
