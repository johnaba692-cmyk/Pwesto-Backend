<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Orders received by a vendor's stalls and their fulfilment status.
 */
class Vendor_orders extends MY_Controller
{
    private const STATUSES = ['accepted', 'preparing', 'ready', 'out_for_delivery', 'completed', 'delivered', 'cancelled'];

    public function before_action()
    {
        parent::before_action();
        $this->call->model('OrderModel');
        $this->call->library('order_notifier');
    }

    /** GET /api/vendor/orders */
    public function index()
    {
        $user = $this->require_role('vendor');
        $pending = $this->db_call(function () use ($user) {
            return $this->OrderModel->pending_receipt_emails($user['id']);
        }, 'Pending payment emails could not be loaded.');
        foreach ($pending as $orderId) {
            $this->order_notifier->vendor_receipt($orderId);
        }
        $orders = $this->db_call(function () use ($user) {
            return $this->OrderModel->for_owner($user['id']);
        }, 'Customer orders could not be loaded.');
        $this->respond(['orders' => $orders]);
    }

    /** PATCH /api/vendor/orders (JSON: order_id, status) */
    public function update()
    {
        $user = $this->require_role('vendor');
        $this->verify_csrf_token();
        $body = $this->input();
        $orderId = $body['order_id'] ?? '';
        $status = $body['status'] ?? '';
        if (!$this->is_hex_id($orderId, 32) || !is_string($status) || !in_array($status, self::STATUSES, true)) {
            $this->fail('Choose a valid order and next status.', 422);
        }
        $updated = $this->db_call(function () use ($orderId, $user, $status) {
            return $this->OrderModel->change_status($orderId, $user['id'], $status);
        }, 'The order status could not be updated.');
        if (!$updated) {
            $this->fail('This order cannot move to that status or does not belong to your stall.', 409);
        }
        $emailSent = true;
        if ($updated['delivery_method'] === 'delivery') {
            $emailSent = $this->order_notifier->delivery_status(
                $updated['customer_email'],
                $updated['customer_name'],
                $updated['vendor_name'],
                $updated['order_id'],
                $updated['status'],
                $updated['delivery_address']
            );
        }
        $this->respond(['success' => true, 'email_sent' => $emailSent]);
    }
}
