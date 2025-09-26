<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'cors'], function($routes) {
    $routes->post('payments/intent', 'PaymentController::createPaymentIntent');
});
// $routes->post('/payments/intent', 'PaymentController::createPaymentIntent');
$routes->get('/', 'Home::index');
$routes->setAutoRoute(true);
$routes->setDefaultNamespace('App\Controllers');

