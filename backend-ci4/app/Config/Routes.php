<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes): void {
    $routes->post('auth/login', 'AuthController::login');
    $routes->get('auth/me', 'AuthController::me', ['filter' => 'jwt']);

    $routes->post('bookings', 'BookingController::create', ['filter' => ['jwt', 'role:admin']]);
    $routes->post('bookings/(:num)/approve', 'BookingController::approve/$1', ['filter' => ['jwt', 'role:approver']]);
    $routes->post('bookings/(:num)/reject', 'BookingController::reject/$1', ['filter' => ['jwt', 'role:approver']]);
    $routes->post('bookings/(:num)/complete', 'BookingController::complete/$1', ['filter' => ['jwt', 'role:admin']]);
});
