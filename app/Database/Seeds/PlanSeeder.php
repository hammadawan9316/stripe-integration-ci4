<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\PlanModel;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $model = new PlanModel();

        $plans = [
            [
                'plan_key' => 'monthly',
                'name' => 'Monthly Subscription',
                'price' => 20.00,
                'currency' => 'usd',
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_MONTHLY_PRICE_ID'),
                'is_active' => 1,
            ],
            [
                'plan_key' => 'yearly',
                'name' => 'Yearly Subscription',
                'price' => 200.00,
                'currency' => 'usd',
                'interval' => 'year',
                'stripe_price_id' => env('STRIPE_YEARLY_PRICE_ID'),
                'is_active' => 1,
            ],
        ];

        foreach ($plans as $plan) {
            $existing = $model->getPlanByKey($plan['plan_key']);
            if ($existing) {
                $model->update($existing['id'], $plan);
                continue;
            }

            $model->insert($plan);
        }
    }
}
