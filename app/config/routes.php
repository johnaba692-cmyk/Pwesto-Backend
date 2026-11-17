<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/
/** @var object $router **/


// Pages
$router->get('/', 'Welcome::index');
$router->get('/pwesto', 'Welcome::marketplace');
$router->get('/migrate', 'Migrate::index');

// Session and authentication
$router->get('/api/session', 'Auth::session');
$router->post('/api/auth/google', 'Auth::google');
$router->post('/api/auth/register', 'Auth::register');
$router->post('/api/auth/login', 'Auth::login');
$router->post('/api/auth/logout', 'Auth::logout');
$router->post('/api/auth/password/forgot', 'Auth::forgot_password');
$router->post('/api/auth/password/reset', 'Auth::reset_password');
$router->post('/api/profile', 'Profile::update');

// Shopping assistant
$router->get('/api/assistant', 'Assistant::status');
$router->post('/api/assistant', 'Assistant::ask');

// Public catalog
$router->get('/api/vendors', 'Catalog::vendors');
$router->get('/api/products', 'Catalog::products');
$router->get('/api/products/image', 'Catalog::product_image');

// Vendor product management (CRUD)
$router->get('/api/vendors/products', 'Products::index');
$router->post('/api/vendors/products', 'Products::save');
$router->put('/api/vendors/products', 'Products::save');
$router->delete('/api/vendors/products', 'Products::destroy');
$router->get('/api/vendors/products/image', 'Products::image');

// Customer orders
$router->get('/api/orders', 'Orders::index');
$router->post('/api/orders', 'Orders::store');
$router->post('/api/orders/check-payment', 'Orders::check_payment');

// Vendor order fulfilment
$router->get('/api/vendor/orders', 'Vendor_orders::index');
$router->patch('/api/vendor/orders', 'Vendor_orders::update');

// Planned visits and saved stalls
$router->get('/api/appointments', 'Appointments::index');
$router->post('/api/appointments', 'Appointments::store');
$router->delete('/api/appointments', 'Appointments::destroy');
$router->get('/api/favorites', 'Favorites::index');
$router->post('/api/favorites', 'Favorites::update');

// Stall applications and rental payments
$router->get('/api/vendors/mine', 'Stalls::mine');
$router->post('/api/vendors', 'Stalls::apply');
$router->post('/api/vendors/update', 'Stalls::update');
$router->post('/api/vendors/requirements', 'Stalls::documents');
$router->post('/api/vendors/rental-payment', 'Stalls::rental_payment');
$router->post('/api/vendors/rental-payment/check', 'Stalls::check_rental_payment');
$router->post('/api/xendit/webhook', 'Payments::xendit_webhook');

// Messages and notifications
$router->get('/api/messages', 'Messages::index');
$router->post('/api/messages', 'Messages::send');
$router->get('/api/messages/thread', 'Messages::thread');
$router->get('/api/notifications', 'Notifications::index');
$router->post('/api/notifications', 'Notifications::update');

// Administration
$router->get('/api/admin/vendors', 'Admin::dashboard');
$router->post('/api/admin/vendors', 'Admin::vendors');
$router->get('/api/admin/storage', 'Admin::storage');
$router->get('/api/admin/product-image', 'Admin::product_image');
$router->get('/api/admin/vendor-document', 'Admin::vendor_document');
