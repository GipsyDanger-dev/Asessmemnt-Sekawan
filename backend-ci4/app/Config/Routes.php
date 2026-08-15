<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->options('api/(:any)', static fn () => service('response')->setStatusCode(204), ['filter' => 'cors']);

$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'cors'], static function ($routes): void {
    $routes->post('auth/login', 'AuthController::login');
    $routes->get('auth/me', 'AuthController::me', ['filter' => 'jwt']);

    $routes->post('bookings', 'BookingController::create', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('bookings', 'BookingController::index', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('bookings/(:num)', 'BookingController::show/$1', ['filter' => 'jwt']);
    $routes->get('bookings/(:num)/approval-history', 'BookingController::approvalHistory/$1', ['filter' => 'jwt']);
    $routes->post('bookings/(:num)/approve', 'BookingController::approve/$1', ['filter' => ['jwt', 'role:approver']]);
    $routes->post('bookings/(:num)/reject', 'BookingController::reject/$1', ['filter' => ['jwt', 'role:approver']]);
    $routes->post('bookings/(:num)/complete', 'BookingController::complete/$1', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('approvals/inbox', 'ApprovalController::inbox', ['filter' => ['jwt', 'role:approver']]);
    $routes->get('dashboard/summary', 'DashboardController::summary', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('dashboard/booking-trend', 'DashboardController::bookingTrend', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('dashboard/vehicle-usage', 'DashboardController::vehicleUsage', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('reports/bookings', 'ReportController::bookings', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('reports/bookings/export', 'ReportController::export', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('activity-logs', 'ActivityLogController::index', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('admin/(:segment)', 'AdminMasterController::index/$1', ['filter' => ['jwt', 'role:admin']]);
    $routes->post('admin/(:segment)', 'AdminMasterController::create/$1', ['filter' => ['jwt', 'role:admin']]);
    $routes->put('admin/(:segment)/(:num)', 'AdminMasterController::update/$1/$2', ['filter' => ['jwt', 'role:admin']]);
    $routes->delete('admin/(:segment)/(:num)', 'AdminMasterController::delete/$1/$2', ['filter' => ['jwt', 'role:admin']]);

    $routes->get('regions', 'MasterDataController::regions', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('vehicles', 'MasterDataController::vehicles', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('drivers', 'MasterDataController::drivers', ['filter' => ['jwt', 'role:admin']]);
    $routes->get('approvers', 'MasterDataController::approvers', ['filter' => ['jwt', 'role:admin']]);
});
