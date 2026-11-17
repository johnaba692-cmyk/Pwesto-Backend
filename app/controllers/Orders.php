<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Customer orders: list, checkout (cash or Xendit GCash) and payment check.
 */
class Orders extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model('OrderModel');
        $this->call->library('xendit');
    }

    /** GET /api/orders */
    public function index()
    {
        $user = $this->require_role('customer');
        $orders = $this->db_call(function () use ($user) {
            return $this->OrderModel->for_customer($user['id']);
        }, 'Your orders could not be loaded.');
        $this->respond(['orders' => $orders]);
    }

    /** POST /api/orders */
    public function store()
    {
        $user = $this->require_role('customer');
        $this->verify_csrf_token();
        $body = $this->input();
        $vendorId = $body['vendor_id'] ?? '';
        $items = $body['items'] ?? null;
        $deliveryMethod = $body['delivery_method'] ?? 'pickup';
        $paymentMethod = $body['payment_method'] ?? 'cash_on_pickup';
        $deliveryAddress = $this->string_input($body, 'delivery_address');

        if (!is_string($vendorId) || $vendorId === '' || !is_array($items) || !$items || count($items) > 30) {
            $this->fail('Choose a stall and at least one product to order.', 422);
        }
        if (!is_string($deliveryMethod) || !in_array($deliveryMethod, ['pickup', 'delivery'], true)) {
            $this->fail('Choose pickup or delivery for your order.', 422);
        }
        if (!is_string($paymentMethod) || !in_array($paymentMethod, OrderModel::PAYMENT_METHODS, true)) {
            $this->fail('Choose cash payment or Xendit GCash checkout.', 422);
        }
        if (($deliveryMethod === 'pickup' && $paymentMethod === 'cash_to_vendor')
            || ($deliveryMethod === 'delivery' && $paymentMethod === 'cash_on_pickup')) {
            $this->fail('Choose a cash payment option that matches your fulfillment method.', 422);
        }
        if ($paymentMethod === 'gcash' && !$this->xendit->configured()) {
            $this->fail('Xendit GCash checkout is not configured. Set the matching XENDIT_MODE and XENDIT_SECRET_KEY, plus XENDIT_WEBHOOK_TOKEN, on the server.', 503);
        }
        if ($deliveryMethod === 'delivery'
            && ($deliveryAddress === '' || $this->text_length($deliveryAddress) > 500)) {
            $this->fail('Enter a delivery address no longer than 500 characters.', 422);
        }
        $pickupAt = null;
        if ($deliveryMethod === 'pickup') {
            $deliveryAddress = null;
            $pickupAt = $this->validated_pickup_time($body['pickup_at'] ?? '');
        }
        $validatedItems = $this->validated_items($items);

        $result = $this->db_call(function () use ($user, $vendorId, $validatedItems, $deliveryMethod, $deliveryAddress, $pickupAt, $paymentMethod) {
            return $this->OrderModel->place($user['id'], $vendorId, $validatedItems, $deliveryMethod, $deliveryAddress, $pickupAt, $paymentMethod);
        }, 'Your order could not be submitted. Please try again.');
        if (isset($result['error'])) {
            $this->fail($result['error'], $result['status']);
        }

        if ($paymentMethod === 'gcash') {
            $this->start_gcash_checkout($result, $user);
        }
        $emailSent = true;
        if ($deliveryMethod === 'delivery') {
            $this->call->library('order_notifier');
            $emailSent = $this->order_notifier->delivery_status(
                $result['customer_email'],
                $result['customer_name'],
                $result['vendor_name'],
                $result['order_id'],
                'pending',
                $deliveryAddress
            );
        }
        $this->respond(['order_id' => $result['order_id'], 'email_sent' => $emailSent, 'payment_status' => 'due'], 201);
    }

    /** POST /api/orders/check-payment (JSON: order_id) */
    public function check_payment()
    {
        $user = $this->require_role('customer');
        $this->verify_csrf_token();
        if (!$this->xendit->configured()) {
            $this->fail('Xendit checkout is not configured on the server.', 503);
        }
        $orderId = $this->input()['order_id'] ?? '';
        if (!$this->is_hex_id($orderId, 32)) {
            $this->fail('Choose a valid order to check.', 422);
        }
        $order = $this->db_call(function () use ($orderId, $user) {
            return $this->OrderModel->checkout_for_customer($orderId, $user['id']);
        }, 'The GCash order could not be loaded.');
        if ($order === null) {
            $this->fail('That GCash order is not available to your account.', 404);
        }
        $this->call->library('order_notifier');
        if ($order['payment_status'] === 'paid') {
            $this->order_notifier->vendor_receipt($orderId);
            $this->respond(['payment_status' => 'paid']);
        }
        $checkoutId = $order['payment_checkout_id'];
        if (!$this->xendit->isCheckoutId($checkoutId)) {
            $this->fail('The Xendit invoice is not attached to this order.', 409);
        }
        $invoice = $this->xendit->getInvoice($checkoutId);
        if ($invoice === null) {
            $this->fail('Xendit could not verify this payment. Try again shortly.', 502);
        }
        if (($invoice['id'] ?? '') !== $checkoutId
            || ($invoice['external_id'] ?? '') !== 'pwesto-order-' . $orderId
            || $this->xendit->amountCents($invoice['amount'] ?? null) !== $order['total_cents']
            || ($invoice['currency'] ?? '') !== 'PHP') {
            $this->fail('The Xendit invoice does not match this order. Contact the Pwesto administrator.', 409);
        }
        if (($invoice['status'] ?? '') !== 'PAID') {
            $this->respond(['payment_status' => 'pending']);
        }
        $recorded = $this->db_call(function () use ($orderId, $checkoutId, $order) {
            return $this->OrderModel->mark_paid($orderId, $checkoutId, $order['total_cents']);
        }, 'The confirmed GCash payment could not be recorded.');
        if (!$recorded) {
            $this->fail('The payment no longer matches this order. Contact the Pwesto administrator.', 409);
        }
        $this->order_notifier->vendor_receipt($orderId);
        $this->respond(['payment_status' => 'paid']);
    }

    /* ------------------------------------------------------------------ */

    private function start_gcash_checkout(array $order, array $user)
    {
        $amount = $order['total_cents'];
        $release = function () use ($order, $user) {
            $this->db_call(function () use ($order, $user) {
                return $this->OrderModel->discard_unpaid_gcash($order['order_id'], $user['id']);
            }, 'The unpaid order could not be released.');
        };
        if ($amount < 100) {
            $release();
            $this->fail('Xendit requires an order total of at least ₱1.00.', 422);
        }
        $invoice = $this->xendit->createInvoice(
            'pwesto-order-' . $order['order_id'],
            $amount,
            'Pwesto product order — ' . $order['vendor_name'],
            ['pwesto_order_id' => $order['order_id'], 'pwesto_payment_type' => 'order'],
            $order['customer_email']
        );
        if ($invoice === null) {
            $release();
            $this->fail('Xendit could not create a GCash checkout. Please try again.', 502);
        }
        $saved = $this->db_call(function () use ($order, $user, $invoice, $amount) {
            return $this->OrderModel->save_checkout($order['order_id'], $user['id'], $invoice['id'], $invoice['url'], $amount);
        }, 'The GCash checkout could not be saved.');
        if (!$saved) {
            $release();
            $this->fail('The GCash checkout could not be attached to this order.', 409);
        }
        $this->respond([
            'order_id' => $order['order_id'],
            'checkout_url' => $invoice['url'],
            'payment_status' => 'pending',
        ], 201);
    }

    /** Pickup time must be a future Asia/Manila date-time in Y-m-d\TH:i format. */
    private function validated_pickup_time($input)
    {
        if (!is_string($input) || $input === '') {
            $this->fail('Choose a pickup date and time.', 422);
        }
        $timezone = new DateTimeZone('Asia/Manila');
        $pickup = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $input, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$pickup
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $pickup->format('Y-m-d\TH:i') !== $input
            || $pickup <= new DateTimeImmutable('now', $timezone)) {
            $this->fail('Choose a valid future pickup date and time.', 422);
        }
        return $pickup->format('Y-m-d H:i:s');
    }

    private function validated_items(array $items)
    {
        $validated = [];
        $seen = [];
        foreach ($items as $item) {
            if (!is_array($item)
                || !isset($item['product_id'], $item['quantity'])
                || !$this->is_hex_id($item['product_id'], 32)
                || !is_int($item['quantity'])
                || $item['quantity'] < 1
                || $item['quantity'] > 50
                || isset($seen[$item['product_id']])) {
                $this->fail('Check the products and quantities in your cart.', 422);
            }
            $seen[$item['product_id']] = true;
            $validated[] = ['product_id' => $item['product_id'], 'quantity' => $item['quantity']];
        }
        return $validated;
    }
}
