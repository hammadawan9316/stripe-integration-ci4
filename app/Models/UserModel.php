<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['email', 'password', 'name', 'stripe_customer_id'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'email'    => 'required|valid_email|is_unique[users.email,id,{id}]',
        'name'     => 'required|min_length[3]|max_length[255]',
        'password' => 'required|min_length[8]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    protected $beforeUpdate   = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * Get user with active subscription
     */
    public function getUserWithSubscription($userId)
    {
        return $this->select('users.*, subscriptions.status as subscription_status, subscriptions.plan_type, subscriptions.current_period_end')
                    ->join('subscriptions', 'subscriptions.user_id = users.id', 'left')
                    ->where('users.id', $userId)
                    ->first();
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription($userId)
    {
        $subscriptionModel = new \App\Models\SubscriptionModel();
        return $subscriptionModel->where('user_id', $userId)
                                  ->where('status', 'active')
                                  ->first() !== null;
    }
}
