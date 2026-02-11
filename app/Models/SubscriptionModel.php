<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table            = 'subscriptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'plan_type',
        'status',
        'current_period_start',
        'current_period_end',
        'canceled_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'user_id'   => 'required|integer',
        'plan_type' => 'required|in_list[monthly,yearly]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Get active subscription for user
     */
    public function getActiveSubscription($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('status', 'active')
                    ->first();
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription($userId)
    {
        return $this->where('user_id', $userId)
                    ->set([
                        'status'      => 'canceled',
                        'canceled_at' => date('Y-m-d H:i:s')
                    ])
                    ->update();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired($userId)
    {
        $subscription = $this->where('user_id', $userId)
                            ->where('status', 'active')
                            ->first();
        
        if (!$subscription) {
            return true; // No active subscription = expired
        }
        
        // Check if current period end has passed
        return strtotime($subscription['current_period_end']) < time();
    }

    /**
     * Get expired subscriptions that need renewal
     */
    public function getExpiredSubscriptions()
    {
        $db = \Config\Database::connect();
        
        return $this->where('status', 'active')
                    ->where('current_period_end <', date('Y-m-d H:i:s'))
                    ->findAll();
    }

    /**
     * Mark subscription as needing renewal
     */
    public function markForRenewal($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('status', 'active')
                    ->set(['status' => 'expired'])
                    ->update();
    }
}
