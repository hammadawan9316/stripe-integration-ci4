<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\SubscriptionModel;
use Stripe\Stripe;
use Stripe\Subscription;

class RenewalController extends BaseController
{
    protected $userModel;
    protected $subscriptionModel;

    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $this->userModel = new UserModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Check subscription status and auto-renew if expired
     * GET /api/renewal/check/:user_id
     */
    public function checkAndRenew($userId)
    {
        try {
            $user = $this->userModel->find($userId);
            if (!$user) {
                return sendApiResponse(null, 'User not found', 404);
            }

            $subscription = $this->subscriptionModel->getActiveSubscription($userId);
            
            if (!$subscription) {
                return sendApiResponse([
                    'status' => 'no_subscription',
                    'message' => 'No active subscription found'
                ], 'No active subscription', 200);
            }

            // Check if expired
            $periodEnd = strtotime($subscription['current_period_end']);
            if ($periodEnd >= time()) {
                return sendApiResponse([
                    'status' => 'active',
                    'subscription' => [
                        'plan_type' => $subscription['plan_type'],
                        'current_period_end' => $subscription['current_period_end'],
                        'days_remaining' => ceil(($periodEnd - time()) / 86400)
                    ]
                ], 'Subscription is still active', 200);
            }

            // Subscription expired, check Stripe status
            try {
                $stripeSubscription = Subscription::retrieve($subscription['stripe_subscription_id']);
                
                // Update local database with latest Stripe info
                if ($stripeSubscription->items->data[0]) {
                    $item = $stripeSubscription->items->data[0];
                    $newPeriodStart = date('Y-m-d H:i:s', $item->current_period_start);
                    $newPeriodEnd = date('Y-m-d H:i:s', $item->current_period_end);
                    
                    $this->subscriptionModel->where('user_id', $userId)
                                           ->where('status', 'active')
                                           ->set([
                                               'current_period_start' => $newPeriodStart,
                                               'current_period_end' => $newPeriodEnd,
                                               'status' => $stripeSubscription->status
                                           ])
                                           ->update();
                    
                    log_message('info', "Auto-renewal: Updated subscription for user {$userId}");
                    
                    return sendApiResponse([
                        'status' => 'renewed',
                        'subscription' => [
                            'plan_type' => $subscription['plan_type'],
                            'new_period_start' => $newPeriodStart,
                            'new_period_end' => $newPeriodEnd,
                            'stripe_status' => $stripeSubscription->status
                        ]
                    ], 'Subscription auto-renewed by Stripe', 200);
                }
                
            } catch (\Exception $e) {
                log_message('error', "Auto-renewal error for user {$userId}: " . $e->getMessage());
                return sendApiResponse(null, 'Failed to check renewal status: ' . $e->getMessage(), 500);
            }

            return sendApiResponse([
                'status' => 'expired',
                'message' => 'Subscription has expired, please renew'
            ], 'Subscription expired', 200);

        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get renewal status for current user
     * GET /api/renewal/status
     */
    public function getStatus()
    {
        try {
            $userId = session()->get('user_id');
            if (!$userId) {
                return sendApiResponse(null, 'Not authenticated', 401);
            }

            $subscription = $this->subscriptionModel->getActiveSubscription($userId);
            
            if (!$subscription) {
                return sendApiResponse([
                    'status' => 'no_subscription',
                    'message' => 'No active subscription'
                ], 'No subscription found', 200);
            }

            $periodEnd = strtotime($subscription['current_period_end']);
            $daysRemaining = ceil(($periodEnd - time()) / 86400);
            
            if ($daysRemaining < 0) {
                return sendApiResponse([
                    'status' => 'expired',
                    'expired_days' => abs($daysRemaining),
                    'expired_since' => $subscription['current_period_end'],
                    'plan_type' => $subscription['plan_type']
                ], 'Subscription has expired', 200);
            }

            return sendApiResponse([
                'status' => 'active',
                'plan_type' => $subscription['plan_type'],
                'current_period_end' => $subscription['current_period_end'],
                'days_remaining' => $daysRemaining,
                'renews_on' => date('F j, Y', $periodEnd)
            ], 'Subscription is active', 200);

        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Manual trigger for renewal check
     * POST /api/renewal/process
     */
    public function processRenewal()
    {
        try {
            $userId = session()->get('user_id');
            if (!$userId) {
                return sendApiResponse(null, 'Not authenticated', 401);
            }

            return $this->checkAndRenew($userId);

        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }
}
