<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Basic', 'price' => 5, 'duration_days' => 30, 'max_borrow_limit' => 2, 'description' => 'Entry level monthly plan'],
            ['name' => 'Standard', 'price' => 10, 'duration_days' => 30, 'max_borrow_limit' => 5, 'description' => 'Most popular monthly plan'],
            ['name' => 'Premium', 'price' => 25, 'duration_days' => 90, 'max_borrow_limit' => 10, 'description' => 'Quarterly high-access plan'],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
