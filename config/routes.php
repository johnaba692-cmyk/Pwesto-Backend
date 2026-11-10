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

$router->get('/', 'Welcome::index');
$router->get('/pwesto', 'Welcome::marketplace');

// Session and public catalog
$router->get('/api/session', 'Pwesto_api::session_info');
$router->get('/api/assistant', 'Pwesto_assistant::index');
$router->post('/api/assistant', 'Pwesto_assistant::index');
$router->get('/api/vendors', 'Pwesto_api::vendors');
$router->get('/api/products', 'Pwesto_api::products');
$router->get('/api/products/image', 'Pwesto_api::product_image');

// Vendor products
$router->get('/api/vendors/products', 'Pwesto_api::vendor_products');
$router->post('/api/vendors/products', 'Pwesto_api::vendor_products');
$router->put('/api/vendors/products', 'Pwesto_api::vendor_products');
$router->delete('/api/vendors/products', 'Pwesto_api::vendor_products');
$router->get('/api/vendors/products/image', 'Pwesto_api::vendor_product_image');

// Orders
$router->get('/api/orders', 'Pwesto_api::orders');
$router->post('/api/orders', 'Pwesto_api::orders');
$router->post('/api/orders/check-payment', 'Pwesto_api::check_order_payment');
$router->get('/api/vendor/orders', 'Pwesto_api::vendor_orders');
$router->patch('/api/vendor/orders', 'Pwesto_api::vendor_orders');

// Appointments and favorites
$router->get('/api/appointments', 'Pwesto_api::appointments');
$router->delete('/api/appointments', 'Pwesto_api::appointments');
$router->get('/api/favorites', 'Pwesto_api::favorites');
$router->post('/api/favorites', 'Pwesto_api::favorites');

// Vendor management
$router->get('/api/vendors/mine', 'Pwesto_api::my_vendors');
$router->post('/api/vendors', 'Pwesto_api::create_vendor');
$router->post('/api/vendors/update', 'Pwesto_api::update_vendor');
$router->post('/api/vendors/requirements', 'Pwesto_api::update_vendor_documents');

// Rental payments
$router->post('/api/vendors/rental-payment', 'Pwesto_api::rental_payment');
$router->post('/api/vendors/rental-payment/check', 'Pwesto_api::check_rental_payment');
$router->post('/api/xendit/webhook', 'Pwesto_api::xendit_webhook');

// Profile, messages, and notifications
$router->post('/api/profile', 'Pwesto_api::update_profile');
$router->get('/api/messages', 'Pwesto_api::messages');
$router->post('/api/messages', 'Pwesto_api::messages');
$router->get('/api/messages/thread', 'Pwesto_api::message_thread');
$router->get('/api/notifications', 'Pwesto_api::notifications');
$router->post('/api/notifications', 'Pwesto_api::notifications');

// Administration
$router->get('/api/admin/vendors', 'Pwesto_api::admin_vendors');
$router->post('/api/admin/vendors', 'Pwesto_api::admin_vendors');
$router->get('/api/admin/storage', 'Pwesto_api::admin_storage');
$router->get('/api/admin/product-image', 'Pwesto_api::admin_product_image');
$router->get('/api/admin/vendor-document', 'Pwesto_api::vendor_document');

// Authentication
$router->post('/api/auth/google', 'Pwesto_api::google_sign_in');
$router->post('/api/auth/register', 'Pwesto_api::register');
$router->post('/api/auth/login', 'Pwesto_api::email_sign_in');
$router->post('/api/auth/password/forgot', 'Pwesto_api::request_password_reset');
$router->post('/api/auth/password/reset', 'Pwesto_api::reset_password');
$router->post('/api/auth/logout', 'Pwesto_api::logout');
$router->post('/api/appointments', 'Pwesto_api::appointments');