<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Order emails: delivery status updates for customers and GCash payment
 * receipts for vendors. Uses the Mailer library and OrderModel.
 *
 * Load with: $this->call->library('order_notifier');
 */
class Order_notifier
{
    private const DELIVERY_LABELS = [
        'pending' => 'Order received',
        'accepted' => 'Order accepted',
        'preparing' => 'Order is being prepared',
        'out_for_delivery' => 'Order is out for delivery',
        'delivered' => 'Order delivered',
        'cancelled' => 'Order cancelled',
    ];

    private $lava;

    public function __construct()
    {
        $this->lava = lava_instance();
        $this->lava->call->library('mailer');
        $this->lava->call->model('OrderModel');
    }

    /** @return bool whether the email was accepted by SMTP */
    public function delivery_status($email, $name, $vendorName, $orderId, $status, $address)
    {
        $label = self::DELIVERY_LABELS[$status] ?? 'Order updated';
        $reference = substr((string) $orderId, 0, 8);
        $addressHtml = ($address === null || $address === '')
            ? ''
            : '<p>Delivery address: ' . $this->escape($address) . '</p>';
        $html = '<p>Hello ' . $this->escape($name) . ',</p><p><strong>' . $label . '</strong> from '
            . $this->escape($vendorName) . ' (order #' . $this->escape($reference) . ').</p>' . $addressHtml;
        $text = 'Hello ' . $name . ",\n\n" . $label . ' from ' . $vendorName
            . ' (order #' . $reference . ").\n"
            . ($address === null || $address === '' ? '' : 'Delivery address: ' . $address . "\n");
        try {
            $this->lava->mailer->send($email, $name, 'Pwesto delivery update: ' . $label, $html, $text);
            return true;
        } catch (Throwable $error) {
            error_log('Pwesto delivery email failed for order #' . $reference . ': ' . $error->getMessage());
            return false;
        }
    }

    /**
     * Email the vendor once per paid GCash order. The claim in OrderModel keeps
     * concurrent requests from sending duplicates.
     */
    public function vendor_receipt($orderId)
    {
        $orders = $this->lava->OrderModel;
        try {
            $receipt = $orders->claim_receipt_email($orderId);
        } catch (Throwable $error) {
            error_log('Pwesto vendor payment email could not be prepared: ' . $error->getMessage());
            return false;
        }
        if ($receipt === null) {
            return true;
        }

        $reference = substr((string) $receipt['id'], 0, 8);
        $itemLines = [];
        $itemHtml = '';
        foreach ($receipt['items'] as $item) {
            $lineTotal = '₱' . number_format($item['unit_price_cents'] * $item['quantity'] / 100, 2);
            $itemLines[] = $item['quantity'] . ' × ' . $item['product_name'] . ' — ' . $lineTotal;
            $itemHtml .= '<li>' . (int) $item['quantity'] . ' × ' . $this->escape($item['product_name'])
                . ' — ' . $lineTotal . '</li>';
        }
        $fulfillment = $receipt['delivery_method'] === 'delivery'
            ? 'Delivery to: ' . (string) $receipt['delivery_address']
            : 'Pickup' . ($receipt['pickup_at'] ? ' at ' . $receipt['pickup_at'] : '');
        $total = '₱' . number_format($receipt['total_cents'] / 100, 2);
        $html = '<p>Hello ' . $this->escape($receipt['vendor_name']) . ',</p>'
            . '<p>GCash payment has been confirmed for order <strong>#' . $this->escape($reference) . '</strong>.</p>'
            . '<p>Customer: ' . $this->escape($receipt['customer_name']) . '<br>Fulfillment: ' . $this->escape($fulfillment) . '</p>'
            . '<ul>' . $itemHtml . '</ul>'
            . '<p><strong>Total paid: ' . $total . '</strong></p>'
            . '<p>This is a Pwesto payment confirmation for your order records.</p>';
        $text = 'Hello ' . $receipt['vendor_name'] . ",\n\n"
            . 'GCash payment has been confirmed for order #' . $reference . ".\n"
            . 'Customer: ' . $receipt['customer_name'] . "\n" . $fulfillment . "\n\n"
            . implode("\n", $itemLines) . "\n\nTotal paid: " . $total . "\n\n"
            . 'This is a Pwesto payment confirmation for your order records.';

        $sent = false;
        try {
            $this->lava->mailer->send(
                $receipt['vendor_email'],
                $receipt['vendor_name'],
                'Pwesto GCash payment confirmed · Order #' . $reference,
                $html,
                $text
            );
            $sent = true;
        } catch (Throwable $error) {
            error_log('Pwesto vendor GCash email failed for order #' . $reference . ': ' . $error->getMessage());
        }
        try {
            if (!$orders->finish_receipt_email($orderId, $sent)) {
                error_log('Pwesto vendor GCash email status could not be updated for order #' . $reference);
            }
        } catch (Throwable $error) {
            error_log('Pwesto vendor GCash email status could not be saved: ' . $error->getMessage());
        }
        return $sent;
    }

    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
