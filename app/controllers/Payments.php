<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Xendit invoice callbacks for GCash product orders and stall rent.
 * Authenticated with the X-Callback-Token header instead of a session.
 */
class Payments extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model(['OrderModel', 'VendorModel']);
        $this->call->library('xendit');
    }

    /** POST /api/xendit/webhook */
    public function xendit_webhook()
    {
        if (!$this->xendit->webhookConfigured()) {
            error_log('Pwesto Xendit callback token is not configured.');
            $this->fail('Xendit webhook is not configured.', 503);
        }
        if (!$this->xendit->webhookTokenMatches($_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '')) {
            $this->fail('Xendit webhook authentication failed.', 401);
        }
        $event = $this->input();
        if (!$event) {
            $this->fail('Invalid webhook request body.', 400);
        }
        if (($event['status'] ?? '') !== 'PAID') {
            $this->respond(['received' => true]);
        }

        $checkoutId = $event['id'] ?? '';
        $externalId = $event['external_id'] ?? '';
        $amount = $this->xendit->amountCents($event['amount'] ?? null);
        if (!$this->xendit->isCheckoutId($checkoutId) || !is_string($externalId) || $externalId === ''
            || !is_int($amount) || $amount < 100 || ($event['currency'] ?? '') !== 'PHP') {
            $this->fail('Xendit invoice details are invalid.', 422);
        }

        if (preg_match('/^pwesto-order-([a-f0-9]{32})$/', $externalId, $matches)) {
            $orderId = $matches[1];
            $updated = $this->db_call(function () use ($orderId, $checkoutId, $amount) {
                return $this->OrderModel->mark_paid($orderId, $checkoutId, $amount);
            }, 'The confirmed GCash order payment could not be recorded.');
            if (!$updated) {
                $this->fail('No matching unpaid GCash order was found.', 404);
            }
            $this->call->library('order_notifier');
            $this->order_notifier->vendor_receipt($orderId);
            $this->respond(['received' => true]);
        }

        if (!preg_match('/^pwesto-rent-([a-f0-9]{24})-[a-f0-9]{12}$/', $externalId, $matches)) {
            $this->fail('Xendit invoice does not match an expected Pwesto payment.', 422);
        }
        $vendorId = $matches[1];
        $recorded = $this->db_call(function () use ($vendorId, $checkoutId, $amount) {
            return $this->VendorModel->mark_rental_paid($vendorId, $checkoutId, $amount)
                || $this->VendorModel->rental_payment_recorded($vendorId, $checkoutId, $amount);
        }, 'The confirmed Xendit payment could not be recorded.');
        if (!$recorded) {
            $this->fail('No matching stall application was found for this payment.', 404);
        }
        $this->respond(['received' => true]);
    }
}
