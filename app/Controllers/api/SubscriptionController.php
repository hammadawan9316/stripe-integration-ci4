<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session;
use Stripe\Subscription;

class SubscriptionController extends BaseController
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
     * Get subscription pricing plans
     */
    public function getPlans()
    {
        $planModel = new PlanModel();
        $plans = $planModel->getActivePlans();

        return sendApiResponse($plans, 'Subscription plans fetched successfully', 200);
    }

    /**
     * Create subscription checkout session
     */
    public function createCheckoutSession()
    {
        try {
            $request = $this->request->getJSON(true);

            if (empty($request['user_id']) || empty($request['plan_type'])) {
                return sendApiResponse(null, 'User ID and plan type are required', 400);
            }

            $userId = $request['user_id'];
            $planType = $request['plan_type'];

            // Validate plan type
            if (!in_array($planType, ['monthly', 'yearly'])) {
                return sendApiResponse(null, 'Invalid plan type', 400);
            }

            $planModel = new PlanModel();
            $plan = $planModel->getPlanByKey($planType);

            if (!$plan || (int)$plan['is_active'] !== 1) {
                return sendApiResponse(null, 'Selected plan is not available', 400);
            }

            // Get user
            $user = $this->userModel->find($userId);
            if (!$user) {
                return sendApiResponse(null, 'User not found', 404);
            }

            // Check if user already has active subscription
            $activeSubscription = $this->subscriptionModel->getActiveSubscription($userId);
            if ($activeSubscription) {
                // Check if subscription is actually expired
                $periodEnd = strtotime($activeSubscription['current_period_end']);
                if ($periodEnd >= time()) {
                    return sendApiResponse(null, 'You already have an active subscription', 400);
                }
                // Subscription expired, allow renewal
                log_message('info', 'Expired subscription found, allowing renewal for user ' . $userId);
            }

            // Get or create Stripe customer
            if (empty($user['stripe_customer_id'])) {
                $customer = Customer::create([
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'metadata' => [
                        'user_id' => $userId
                    ]
                ]);

                // Save customer ID
                $this->userModel->update($userId, ['stripe_customer_id' => $customer->id]);
            } else {
                $customer = Customer::retrieve($user['stripe_customer_id']);
            }

            // Get price ID based on plan type
            $priceId = $plan['stripe_price_id'];

            // Create Checkout Session
            $checkoutSession = Session::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => base_url('subscription/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => base_url('subscription/cancel'),
                'metadata' => [
                    'user_id' => $userId,
                    'plan_type' => $planType,
                ]
            ]);

            return sendApiResponse([
                'session_id' => $checkoutSession->id,
                'session_url' => $checkoutSession->url,
            ], 'Checkout session created successfully', 200);

        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get user subscription status
     */
    public function getSubscriptionStatus($userId)
    {
        try {
            $subscription = $this->subscriptionModel->getActiveSubscription($userId);

            if (!$subscription) {
                return sendApiResponse([
                    'has_subscription' => false,
                    'subscription' => null
                ], 'No active subscription found', 200);
            }

            return sendApiResponse([
                'has_subscription' => true,
                'subscription' => $subscription
            ], 'Subscription status retrieved successfully', 200);

        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription()
    {
        try {
            $request = $this->request->getJSON(true);

            if (empty($request['user_id'])) {
                return sendApiResponse(null, 'User ID is required', 400);
            }

            $userId = $request['user_id'];

            // Get active subscription
            $subscription = $this->subscriptionModel->getActiveSubscription($userId);

            if (!$subscription) {
                return sendApiResponse(null, 'No active subscription found', 404);
            }

            // Cancel subscription in Stripe
            $stripeSubscription = Subscription::retrieve($subscription['stripe_subscription_id']);
            $stripeSubscription->cancel();

            // Update subscription in database
            $this->subscriptionModel->cancelSubscription($userId);

            return sendApiResponse(null, 'Subscription canceled successfully', 200);

        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Create customer portal session for managing subscription
     */
    public function createPortalSession()
    {
        try {
            $request = $this->request->getJSON(true);

            if (empty($request['user_id'])) {
                return sendApiResponse(null, 'User ID is required', 400);
            }

            $userId = $request['user_id'];
            $user = $this->userModel->find($userId);

            if (!$user || empty($user['stripe_customer_id'])) {
                return sendApiResponse(null, 'User or Stripe customer not found', 404);
            }

            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $user['stripe_customer_id'],
                'return_url' => base_url('dashboard'),
            ]);

            return sendApiResponse([
                'url' => $session->url
            ], 'Portal session created successfully', 200);

        } catch (\Exception $e) {
            return sendApiResponse(null, $e->getMessage(), 500);
        }
    }
}
