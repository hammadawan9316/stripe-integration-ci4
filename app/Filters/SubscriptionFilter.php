<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\SubscriptionModel;

class SubscriptionFilter implements FilterInterface
{
    /**
     * Check if user has active subscription before allowing access
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get user ID from session
        $session = session();
        $userId = $session->get('user_id');
        
        log_message('info', "SubscriptionFilter: Checking subscription for user_id: {$userId}");

        // Check if request is AJAX
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        // If user is not logged in, redirect to login
        if (!$userId) {
            log_message('info', 'SubscriptionFilter: No user_id in session, redirecting to login');
            if ($isAjax) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Please log in to access this resource'
                    ]);
            }
            return redirect()->to('/login')->with('error', 'Please log in to continue');
        }

        // Check subscription status
        $userModel = new UserModel();
        $subscriptionModel = new SubscriptionModel();

        $subscription = $subscriptionModel->getActiveSubscription($userId);
        
        log_message('info', 'SubscriptionFilter: Active subscription check result: ' . ($subscription ? json_encode($subscription) : 'NULL'));

        // If no active subscription, redirect to subscription page
        if (!$subscription) {
            log_message('info', 'SubscriptionFilter: No active subscription found, redirecting to plans');
            if ($isAjax) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Active subscription required to access this resource'
                    ]);
            }
            return redirect()->to('/subscription/plans')->with('error', 'Please subscribe to access this content');
        }

        // Check if subscription has expired
        $periodEnd = strtotime($subscription['current_period_end']);
        log_message('info', "SubscriptionFilter: Period end: {$subscription['current_period_end']} (timestamp: {$periodEnd}, now: " . time() . ")");
        
        if ($periodEnd < time()) {
            // Subscription expired - allow access to subscription renewal page only
            $currentRoute = $request->getUri()->getPath();
            log_message('info', "SubscriptionFilter: Subscription expired, current route: {$currentRoute}");
            
            // Allow access to subscription-related pages
            if (strpos($currentRoute, 'subscription') !== false) {
                log_message('info', 'SubscriptionFilter: Allowing access to subscription route');
                return $request;
            }
            
            log_message('info', 'SubscriptionFilter: Subscription expired and not on subscription route, redirecting');
            if ($isAjax) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Your subscription has expired'
                    ]);
            }
            return redirect()->to('/subscription/plans')->with('error', 'Your subscription has expired. Please renew.');
        }

        log_message('info', 'SubscriptionFilter: All checks passed, allowing access');
        // User has active subscription, allow access
        return $request;
    }

    /**
     * After filter (not used)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
