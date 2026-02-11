<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'cors'], function($routes) {
    // Payment routes
    $routes->post('payments/intent', 'PaymentController::createPaymentIntent');
    
    // Subscription routes
    $routes->get('subscription/plans', 'SubscriptionController::getPlans');
    $routes->post('subscription/checkout', 'SubscriptionController::createCheckoutSession');
    $routes->get('subscription/status/(:num)', 'SubscriptionController::getSubscriptionStatus/$1');
    $routes->post('subscription/cancel', 'SubscriptionController::cancelSubscription');
    $routes->post('subscription/portal', 'SubscriptionController::createPortalSession');
    
    // Renewal routes
    $routes->get('renewal/status', 'RenewalController::getStatus');
    $routes->post('renewal/process', 'RenewalController::processRenewal');
    $routes->get('renewal/check/(:num)', 'RenewalController::checkAndRenew/$1');
    
    // Webhook route (no CORS filter)
    $routes->post('webhook', 'WebhookController::handle');
});

// Remove CORS filter from webhook
$routes->post('api/webhook', 'Api\WebhookController::handle');

// Authentication routes
$routes->get('register', 'AuthController::register');
$routes->post('auth/register', 'AuthController::processRegister');
$routes->get('login', 'AuthController::login');
$routes->post('auth/login', 'AuthController::processLogin');
$routes->get('logout', 'AuthController::logout');

// Subscription view routes
$routes->get('subscription/plans', 'SubscriptionViewController::plans');
$routes->get('subscription/success', 'SubscriptionViewController::success');
$routes->get('subscription/cancel', 'SubscriptionViewController::cancel');

// Protected routes (require active subscription)
$routes->get('dashboard', 'SubscriptionViewController::dashboard', ['filter' => 'subscription']);

// Home route
$routes->get('/', 'Home::index');

// Enable auto-routing for backward compatibility
$routes->setAutoRoute(true);
$routes->setDefaultNamespace('App\Controllers');


