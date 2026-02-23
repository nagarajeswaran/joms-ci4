<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'Products::index');

// Orders routes
$routes->group('orders', [], function($routes) {
    $routes->get('/', 'Orders::index');
    $routes->get('index', 'Orders::index');
    $routes->get('add', 'Orders::add_order');
    $routes->post('create', 'Orders::create');
    $routes->get('view/(:num)', 'Orders::view_order/$1');
    $routes->get('edit/(:num)', 'Orders::edit_order/$1');
    $routes->post('update/(:num)', 'Orders::update/$1');
    $routes->get('delete/(:num)', 'Orders::delete_order/$1');
});

// Login routes
// Disabled: Login as home
$routes->match(['get', 'post'], 'login', 'Login::index');
$routes->post('login/authenticate', 'Login::authenticate');
$routes->get('logout', 'Login::logout');

// Clients routes
$routes->group('clients', [], function($routes) {
    $routes->get('/', 'Clients::index');
    $routes->get('add', 'Clients::add_client');
    $routes->post('create', 'Clients::create');
    $routes->get('edit/(:num)', 'Clients::edit_client/$1');
    $routes->post('update/(:num)', 'Clients::update/$1');
    $routes->get('delete/(:num)', 'Clients::delete_client/$1');
});

// Products routes
$routes->group('products', [], function($routes) {
    $routes->get('/', 'Products::index');
    $routes->get('add', 'Products::add_product');
    $routes->post('create', 'Products::create');
    $routes->post('save_product', 'Products::save_product');
    $routes->post('get_variations_by_product_type', 'Products::get_variations_by_product_type');
    $routes->post('get_bodies', 'Products::get_bodies');
    $routes->post('get_parts', 'Products::get_parts');
    $routes->get('view/(:num)', 'Products::view_product/$1');
    $routes->get('edit/(:num)', 'Products::edit/$1');
    $routes->post('update/(:num)', 'Products::update/$1');
    $routes->get('delete/(:num)', 'Products::delete/$1');
});

// Parts routes
$routes->group('parts', [], function($routes) {
    $routes->get('/', 'Parts::index');
    $routes->get('add', 'Parts::add_part');
    $routes->post('create', 'Parts::create');
    $routes->get('edit/(:num)', 'Parts::edit_part/$1');
    $routes->post('update/(:num)', 'Parts::update/$1');
    $routes->get('delete/(:num)', 'Parts::delete_part/$1');
});

// Departments routes
$routes->group('departments', [], function($routes) {
    $routes->get('/', 'Departments::index');
    $routes->get('add', 'Departments::add_department');
    $routes->post('create', 'Departments::create');
    $routes->get('edit/(:num)', 'Departments::edit_department/$1');
    $routes->post('update/(:num)', 'Departments::update/$1');
    $routes->get('delete/(:num)', 'Departments::delete_department/$1');
});

// Users routes
$routes->group('users', [], function($routes) {
    $routes->get('/', 'Users::index');
    $routes->get('add', 'Users::add_user');
    $routes->post('create', 'Users::create');
    $routes->get('edit/(:num)', 'Users::edit_user/$1');
    $routes->post('update/(:num)', 'Users::update/$1');
    $routes->get('delete/(:num)', 'Users::delete_user/$1');
});

// PDF Reports
$routes->group('PDFReport', [], function($routes) {
    $routes->get('/', 'PDFReport::index');
    $routes->get('issueReport/(:num)', 'PDFReport::issueReport/$1');
    $routes->get('orderSummary/(:num)', 'PDFReport::orderSummary/$1');
    $routes->get('productSummary/(:num)', 'PDFReport::productSummary/$1');
    $routes->get('departmentSummary/(:num)/(:any)', 'PDFReport::departmentSummary/$1/$2');
});
