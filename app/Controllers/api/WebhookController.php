<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\SubscriptionModel;
use Stripe\Stripe;
use Stripe\Webhook;

class WebhookController extends BaseController
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
     * Handle Stripe webhooks
     */
    public function handle()
    {
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET'); // Set this in .env

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );

            // Log the event for debugging
            log_message('info', 'Stripe Webhook Event: ' . $event->type);

            // Handle the event
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;

                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($event->data->object);
                    break;

                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event->data->object);
                    break;

                default:
                    log_message('info', 'Unhandled event type: ' . $event->type);
            }

            return $this->response->setStatusCode(200)->setJSON(['status' => 'success']);

        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            log_message('error', 'Webhook Error: Invalid payload - ' . $e->getMessage());
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            log_message('error', 'Webhook Error: Invalid signature - ' . $e->getMessage());
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            log_message('error', 'Webhook Error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle checkout session completed
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        log_message('info', 'Checkout Session Completed: ' . json_encode($session));

        $userId = $session->metadata->user_id ?? null;
        $planType = $session->metadata->plan_type ?? 'monthly';

        if (!$userId) {
            log_message('error', 'No user_id in checkout session metadata');
            return;
        }

        // The subscription will be created in the subscription.created event
        // This event is mainly for confirmation
    }

    /**
     * Handle subscription created
     */
    protected function handleSubscriptionCreated($subscription)
    {
        log_message('info', 'Subscription Created: ' . json_encode($subscription));

        // Get user from stripe_customer_id in database
        $user = $this->userModel->where('stripe_customer_id', $subscription->customer)->first();

        if (!$user) {
            log_message('error', 'No user found for customer: ' . $subscription->customer);
            return;
        }

        $userId = $user['id'];

        // Determine plan type from price ID
        $priceId = $subscription->items->data[0]->price->id;
        $monthlyPriceId = env('STRIPE_MONTHLY_PRICE_ID');
        $yearlyPriceId = env('STRIPE_YEARLY_PRICE_ID');
        
        log_message('info', "Price ID from Stripe: {$priceId}");
        log_message('info', "Monthly Price ID from env: {$monthlyPriceId}");
        log_message('info', "Yearly Price ID from env: {$yearlyPriceId}");
        
        $planType = ($priceId === $monthlyPriceId) ? 'monthly' : 'yearly';
        
        log_message('info', "Determined plan type: {$planType}");
        
        // Get timestamps from subscription item (the correct location)
        $subscriptionItem = $subscription->items->data[0];
        $periodStartTimestamp = (int)($subscriptionItem->current_period_start ?? 0);
        $periodEndTimestamp = (int)($subscriptionItem->current_period_end ?? 0);
        
        log_message('info', "Period start timestamp: {$periodStartTimestamp}");
        log_message('info', "Period end timestamp: {$periodEndTimestamp}");

        // Create subscription record
        $periodStart = !empty($periodStartTimestamp) ? date('Y-m-d H:i:s', $periodStartTimestamp) : date('Y-m-d H:i:s');
        $periodEnd = !empty($periodEndTimestamp) ? date('Y-m-d H:i:s', $periodEndTimestamp) : date('Y-m-d H:i:s');
        
        log_message('info', "Period start formatted: {$periodStart}");
        log_message('info', "Period end formatted: {$periodEnd}");
        
        // Check if user already has a subscription - if so, update it instead of creating new
        $existingSubscription = $this->subscriptionModel->where('user_id', $userId)->where('status', 'active')->first();
        
        $insertData = [
            'user_id' => $userId,
            'stripe_subscription_id' => $subscription->id,
            'stripe_price_id' => $priceId,
            'plan_type' => $planType,
            'status' => $subscription->status,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
        ];
        
        log_message('info', 'Inserting subscription data: ' . json_encode($insertData));
        
        if ($existingSubscription) {
            // Cancel old subscription and create new one for plan changes
            $this->subscriptionModel->where('id', $existingSubscription['id'])->set(['status' => 'canceled', 'canceled_at' => date('Y-m-d H:i:s')])->update();
            log_message('info', "Old subscription {$existingSubscription['id']} marked as canceled for plan change");
        }
        
        $this->subscriptionModel->insert($insertData);

        log_message('info', "Subscription created in database for user {$userId}");
    }

    /**
     * Handle subscription updated
     */
    protected function handleSubscriptionUpdated($subscription)
    {
        log_message('info', 'Subscription Updated: ' . json_encode($subscription));
        
        // Get timestamps from subscription item (the correct location)
        $subscriptionItem = $subscription->items->data[0];
        $periodStartTimestamp = (int)($subscriptionItem->current_period_start ?? 0);
        $periodEndTimestamp = (int)($subscriptionItem->current_period_end ?? 0);
        
        log_message('info', "Period start timestamp: {$periodStartTimestamp}");
        log_message('info', "Period end timestamp: {$periodEndTimestamp}");

        $periodStart = !empty($periodStartTimestamp) ? date('Y-m-d H:i:s', $periodStartTimestamp) : date('Y-m-d H:i:s');
        $periodEnd = !empty($periodEndTimestamp) ? date('Y-m-d H:i:s', $periodEndTimestamp) : date('Y-m-d H:i:s');

        $updateData = [
            'status' => $subscription->status,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
        ];
        
        // Check if this is a renewal (plan price ID or item ID changed)
        $priceId = $subscription->items->data[0]->price->id;
        $monthlyPriceId = env('STRIPE_MONTHLY_PRICE_ID');
        $yearlyPriceId = env('STRIPE_YEARLY_PRICE_ID');
        $planType = ($priceId === $monthlyPriceId) ? 'monthly' : 'yearly';
        
        // Update plan type if it changed
        $updateData['plan_type'] = $planType;
        $updateData['stripe_price_id'] = $priceId;

        $this->subscriptionModel->updateFromStripe($subscription->id, $updateData);

        log_message('info', "Subscription {$subscription->id} updated in database - New period: {$periodStart} to {$periodEnd}");
    }

    /**
     * Handle subscription deleted (canceled)
     */
    protected function handleSubscriptionDeleted($subscription)
    {
        log_message('info', 'Subscription Deleted: ' . json_encode($subscription));

        $this->subscriptionModel->updateFromStripe($subscription->id, [
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s'),
        ]);

        log_message('info', "Subscription {$subscription->id} marked as canceled");
    }

    /**
     * Handle invoice payment succeeded
     */
    protected function handleInvoicePaymentSucceeded($invoice)
    {
        log_message('info', 'Invoice Payment Succeeded: ' . json_encode($invoice));

        if (isset($invoice->subscription)) {
            // Update subscription status to active
            $this->subscriptionModel->updateFromStripe($invoice->subscription, [
                'status' => 'active',
            ]);

            log_message('info', "Subscription {$invoice->subscription} payment successful");
        }
    }

    /**
     * Handle invoice payment failed
     */
    protected function handleInvoicePaymentFailed($invoice)
    {
        log_message('info', 'Invoice Payment Failed: ' . json_encode($invoice));

        if (isset($invoice->subscription)) {
            // Update subscription status to past_due
            $this->subscriptionModel->updateFromStripe($invoice->subscription, [
                'status' => 'past_due',
            ]);

            log_message('error', "Subscription {$invoice->subscription} payment failed");

            // You can send email notification to user here
        }
    }
}
