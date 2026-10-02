<?php

namespace Database\Seeders;

use App\Models\FeePlan;
use Illuminate\Database\Seeder;

/** Example fee plans; change the amounts in the app or here before go-live. */
class FeePlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Monthly', 'type' => 'monthly', 'amount' => 1500],
            ['name' => '12-class pack', 'type' => 'pack', 'amount' => 2000, 'classes_count' => 12],
            ['name' => 'Registration', 'type' => 'one_time', 'amount' => 500],
        ] as $plan) {
            FeePlan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
