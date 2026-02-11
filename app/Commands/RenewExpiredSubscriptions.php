<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\SubscriptionModel;
use App\Models\UserModel;
use Stripe\Stripe;

class RenewExpiredSubscriptions extends BaseCommand
{
    protected $group       = 'Subscriptions';
    protected $name        = 'subscriptions:renew-expired';
    protected $description = 'Check and process automatic renewal for expired subscriptions';
    protected $usage       = 'php spark subscriptions:renew-expired [--days=7]';
    protected $arguments   = [];
    protected $options     = [
        'days' => 'Number of days before expiration to mark for renewal (default: 0 for expired)',
    ];

    public function run(array $params)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $subscriptionModel = new SubscriptionModel();
        $userModel = new UserModel();

        // Get option for days before expiration
        $daysBeforeExpiration = (int)($params['days'] ?? 0);
        
        if ($daysBeforeExpiration > 0) {
            // Check subscriptions expiring in next N days
            $futureDate = date('Y-m-d H:i:s', strtotime("+{$daysBeforeExpiration} days"));
            $expiredSubscriptions = $subscriptionModel
                ->where('status', 'active')
                ->where('current_period_end <=', $futureDate)
                ->where('current_period_end >', date('Y-m-d H:i:s'))
                ->findAll();
            
            CLI::write("Checking subscriptions expiring in next {$daysBeforeExpiration} days", 'yellow');
            
            if (!empty($expiredSubscriptions)) {
                CLI::write('Found ' . count($expiredSubscriptions) . ' subscriptions expiring soon', 'cyan');
                
                foreach ($expiredSubscriptions as $subscription) {
                    $user = $userModel->find($subscription['user_id']);
                    if ($user) {
                        CLI::write("  - User: {$user['email']}, Expires: {$subscription['current_period_end']}", 'cyan');
                    }
                }
            } else {
                CLI::write('No subscriptions expiring soon', 'green');
            }
            return;
        }

        // Get all expired subscriptions (status = active but period_end < now)
        $expiredSubscriptions = $subscriptionModel->getExpiredSubscriptions();

        if (empty($expiredSubscriptions)) {
            CLI::write('No expired subscriptions found.', 'green');
            return;
        }

        CLI::write('Found ' . count($expiredSubscriptions) . ' expired subscriptions', 'yellow');

        $processed = 0;
        $failed = 0;

        foreach ($expiredSubscriptions as $subscription) {
            try {
                $user = $userModel->find($subscription['user_id']);
                if (!$user) {
                    CLI::write("❌ User not found for subscription {$subscription['id']}", 'red');
                    $failed++;
                    continue;
                }

                // Retrieve Stripe subscription to check its status
                $stripeSubscription = \Stripe\Subscription::retrieve($subscription['stripe_subscription_id']);
                
                if ($stripeSubscription->status === 'active') {
                    // Subscription is still active in Stripe, update local database
                    if ($stripeSubscription->items->data[0]) {
                        $item = $stripeSubscription->items->data[0];
                        $periodStart = date('Y-m-d H:i:s', $item->current_period_start);
                        $periodEnd = date('Y-m-d H:i:s', $item->current_period_end);
                        
                        $subscriptionModel->where('id', $subscription['id'])
                                         ->set([
                                             'current_period_start' => $periodStart,
                                             'current_period_end' => $periodEnd,
                                             'status' => 'active'
                                         ])
                                         ->update();
                        
                        CLI::write("✓ User {$user['email']}: Subscription renewed by Stripe", 'green');
                        $processed++;
                    }
                } else {
                    // Subscription inactive in Stripe, mark as expired
                    $subscriptionModel->where('id', $subscription['id'])
                                     ->set(['status' => 'expired'])
                                     ->update();
                    
                    CLI::write("⚠ User {$user['email']}: Subscription marked as expired (Stripe status: {$stripeSubscription->status})", 'yellow');
                    $processed++;
                }

            } catch (\Exception $e) {
                CLI::write("❌ Error processing subscription {$subscription['id']}: " . $e->getMessage(), 'red');
                $failed++;
            }
        }

        CLI::write("", 'white');
        CLI::write("Renewal Summary:", 'cyan');
        CLI::write("  Processed: {$processed}", 'green');
        CLI::write("  Failed: {$failed}", $failed > 0 ? 'red' : 'green');
        CLI::write("  Total: " . count($expiredSubscriptions), 'white');
    }
}
