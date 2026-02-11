<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table = 'plans';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'plan_key',
        'name',
        'price',
        'currency',
        'interval',
        'stripe_price_id',
        'is_active',
    ];
    protected $useTimestamps = true;

    public function getActivePlans(): array
    {
        return $this->where('is_active', 1)->orderBy('id', 'ASC')->findAll();
    }

    public function getPlanByKey(string $planKey): ?array
    {
        return $this->where('plan_key', $planKey)->first();
    }
}
