<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SubscriptionModel;
use CodeIgniter\Config\Services;

class SubscriptionViewController extends BaseController
{
    protected $subscriptionModel;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Show subscription plans
     */
    public function plans()
    {
        $plans = [];

        try {
            $client = Services::curlrequest();
            $response = $client->get(base_url('api/subscription/plans'));
            
            $payload = json_decode($response->getBody(), true);

            if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
                $plans = $payload['data'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to fetch plans from API: ' . $e->getMessage());
        }

       

        return view('subscription/plans', ['plans' => $plans]);
    }

    /**
     * Show success page after checkout
     */
    public function success()
    {
        $sessionId = $this->request->getGet('session_id');
        return view('subscription/success', ['session_id' => $sessionId]);
    }

    /**
     * Show cancel page
     */
    public function cancel()
    {
        return view('subscription/cancel');
    }

    /**
     * Show dashboard (protected by subscription filter)
     */
    public function dashboard()
    {
        $userId = session()->get('user_id');
        $subscription = $this->subscriptionModel->getActiveSubscription($userId);
        
        return view('dashboard', ['subscription' => $subscription]);
    }
}
