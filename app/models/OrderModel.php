<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Product orders: checkout, GCash payment state, vendor fulfilment and receipts.
 */
class OrderModel extends Model
{
    protected $table = 'pwesto_orders';
    protected $primary_key = 'id';
    protected $fillable = [
        'id', 'customer_id', 'vendor_id', 'status', 'delivery_method', 'delivery_address',
        'pickup_at', 'payment_method', 'payment_status', 'payment_checkout_id',
        'payment_checkout_url', 'vendor_receipt_email_status', 'vendor_receipt_email_attempted_at',
        'total_cents', 'created_at', 'updated_at',
    ];

    public const PAYMENT_METHODS = ['cash_on_pickup', 'cash_to_vendor', 'gcash'];

    private const TRANSITIONS = [
        'delivery' => [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['out_for_delivery', 'cancelled'],
            'out_for_delivery' => ['delivered'],
        ],
        'pickup' => [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['completed'],
        ],
    ];

    /** Seconds after which an unfinished receipt email may be retried. */
    private const EMAIL_RETRY_AFTER = 600;

    /**
     * Place an order: validate products and stock, take stock atomically,
     * copy prices into order items and notify the vendor.
     *
     * @return array order summary, or ['error' => ..., 'status' => ...]
     */
    public function place($customerId, $vendorId, array $items, $deliveryMethod, $deliveryAddress, $pickupAt, $paymentMethod)
    {
        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            return ['error' => 'Choose a supported payment method.', 'status' => 422];
        }
        $products = $this->call->model('ProductModel');
        $orderItems = $this->call->model('OrderItemModel');

        $this->db->transaction();
        try {
            $vendor = $this->call->model('VendorModel')->orderable_stall($vendorId);
            if (!$vendor) {
                $this->db->roll_back();
                return ['error' => 'This vendor is not available for orders.', 'status' => 404];
            }

            $lines = [];
            $totalCents = 0;
            foreach ($items as $item) {
                $product = $products->for_order($item['product_id'], $vendorId);
                if (!$product || (int) $product['stock'] < $item['quantity']) {
                    $this->db->roll_back();
                    return ['error' => 'One or more products are unavailable or have insufficient stock. Refresh the products and try again.', 'status' => 409];
                }
                if (ProductModel::is_sample($product)) {
                    $this->db->roll_back();
                    return ['error' => 'This product has sample pricing and stock. The vendor must confirm its listing before it can be ordered.', 'status' => 409];
                }
                $lineTotal = (int) $product['price_cents'] * $item['quantity'];
                if ($lineTotal > PHP_INT_MAX - $totalCents) {
                    $this->db->roll_back();
                    return ['error' => 'The order total is too large.', 'status' => 422];
                }
                $totalCents += $lineTotal;
                $lines[] = [$product, $item['quantity']];
            }

            $orderId = random_id();
            $paymentStatus = $paymentMethod === 'gcash' ? 'pending' : 'due';
            $now = utc_now();
            $this->insert([
                'id' => $orderId,
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'status' => 'pending',
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'pickup_at' => $pickupAt,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'total_cents' => $totalCents,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($lines as [$product, $quantity]) {
                if (!$products->reserve_stock($product['id'], $quantity)) {
                    $this->db->roll_back();
                    return ['error' => 'Product stock changed while placing your order. Refresh and try again.', 'status' => 409];
                }
                $orderItems->add($orderId, $product['id'], $product['name'], (int) $product['price_cents'], $quantity);
            }
            if ($paymentMethod !== 'gcash') {
                $this->call->model('NotificationModel')->notify(
                    $vendor['owner_id'],
                    'product_order',
                    'New product order',
                    'A customer placed order #' . substr($orderId, 0, 8) . ' at ' . $vendor['name'] . '.'
                );
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }

        $customer = $this->call->model('UserModel')->find($customerId);
        return [
            'order_id' => $orderId,
            'customer_email' => $customer['email'] ?? '',
            'customer_name' => $customer['name'] ?? '',
            'vendor_name' => $vendor['name'],
            'delivery_method' => $deliveryMethod,
            'delivery_address' => $deliveryAddress,
            'pickup_at' => $pickupAt,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'total_cents' => $totalCents,
        ];
    }

    public function save_checkout($orderId, $customerId, $checkoutId, $checkoutUrl, $amountCents)
    {
        return $this->db->table($this->table)
            ->where('id', '=', $orderId)
            ->where('customer_id', '=', $customerId)
            ->where('payment_method', '=', 'gcash')
            ->where('payment_status', '=', 'pending')
            ->where('total_cents', '=', $amountCents)
            ->update([
                'payment_checkout_id' => $checkoutId,
                'payment_checkout_url' => $checkoutUrl,
                'updated_at' => utc_now(),
            ]) === 1;
    }

    /** Delete an unpaid GCash order and give its stock back. */
    public function discard_unpaid_gcash($orderId, $customerId)
    {
        $products = $this->call->model('ProductModel');
        $this->db->transaction();
        try {
            $unpaid = $this->exists([
                'id' => $orderId,
                'customer_id' => $customerId,
                'payment_method' => 'gcash',
                'payment_status' => 'pending',
            ]);
            if (!$unpaid) {
                $this->db->roll_back();
                return false;
            }
            foreach ($this->call->model('OrderItemModel')->stock_lines($orderId) as $line) {
                $products->restore_stock($line['product_id'], (int) $line['quantity']);
            }
            $this->delete($orderId);
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    public function mark_paid($orderId, $checkoutId, $amountCents)
    {
        $this->db->transaction();
        try {
            $order = $this->db->table('pwesto_orders o')
                ->select('o.payment_status, o.payment_checkout_id, o.total_cents, v.owner_id, v.name AS vendor_name')
                ->inner_join('pwesto_vendors v', 'v.id = o.vendor_id')
                ->where('o.id', '=', $orderId)
                ->where('o.payment_method', '=', 'gcash')
                ->limit(1)
                ->get();
            if (!$order || $order['payment_checkout_id'] !== $checkoutId
                || (int) $order['total_cents'] !== $amountCents) {
                $this->db->roll_back();
                return false;
            }
            if ($order['payment_status'] === 'paid') {
                $this->db->commit();
                return true;
            }
            $updated = $this->db->table($this->table)
                ->where('id', '=', $orderId)
                ->where('payment_status', '=', 'pending')
                ->update(['payment_status' => 'paid', 'updated_at' => utc_now()]);
            if ($updated !== 1) {
                $this->db->roll_back();
                return false;
            }
            $this->call->model('NotificationModel')->notify(
                $order['owner_id'],
                'product_order',
                'Paid GCash order',
                'GCash payment confirmed for order #' . substr($orderId, 0, 8) . ' at ' . $order['vendor_name'] . '.'
            );
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    public function checkout_for_customer($orderId, $customerId)
    {
        $order = $this->db->table($this->table)
            ->select('payment_checkout_id, payment_status, total_cents')
            ->where('id', '=', $orderId)
            ->where('customer_id', '=', $customerId)
            ->where('payment_method', '=', 'gcash')
            ->limit(1)
            ->get();
        if (!$order) {
            return null;
        }
        $order['total_cents'] = (int) $order['total_cents'];
        return $order;
    }

    public function for_customer($customerId)
    {
        $this->order_listing()->where('o.customer_id', '=', $customerId);
        return $this->with_items($this->db->order_by('o.created_at', 'DESC')->get_all());
    }

    /** Orders of the owner's stalls; unpaid GCash orders stay hidden from vendors. */
    public function for_owner($ownerId)
    {
        $this->order_listing()
            ->where('v.owner_id', '=', $ownerId)
            ->grouped(function ($query) {
                $query->where('o.payment_method', '!=', 'gcash')
                    ->or_where('o.payment_status', '=', 'paid');
            });
        return $this->with_items($this->db->order_by('o.created_at', 'DESC')->get_all());
    }

    /**
     * Move an order to the next status (vendor action). Cancelling restores stock.
     *
     * @return array|false
     */
    public function change_status($orderId, $ownerId, $status)
    {
        $this->db->transaction();
        try {
            $order = $this->db->table('pwesto_orders o')
                ->select('o.status, o.customer_id, o.delivery_method, o.delivery_address, u.email AS customer_email, u.name AS customer_name, v.name AS vendor_name')
                ->inner_join('pwesto_vendors v', 'v.id = o.vendor_id')
                ->inner_join('pwesto_users u', 'u.id = o.customer_id')
                ->where('o.id', '=', $orderId)
                ->where('v.owner_id', '=', $ownerId)
                ->limit(1)
                ->get();
            $flow = $order && $order['delivery_method'] === 'delivery' ? 'delivery' : 'pickup';
            if (!$order || !in_array($status, self::TRANSITIONS[$flow][$order['status']] ?? [], true)) {
                $this->db->roll_back();
                return false;
            }
            $updated = $this->db->table($this->table)
                ->where('id', '=', $orderId)
                ->where('status', '=', $order['status'])
                ->update(['status' => $status, 'updated_at' => utc_now()]);
            if ($updated !== 1) {
                $this->db->roll_back();
                return false;
            }
            if ($status === 'cancelled') {
                $products = $this->call->model('ProductModel');
                foreach ($this->call->model('OrderItemModel')->stock_lines($orderId) as $line) {
                    $products->restore_stock($line['product_id'], (int) $line['quantity']);
                }
            }
            $this->call->model('NotificationModel')->notify(
                $order['customer_id'],
                'product_order_update',
                'Order ' . $status,
                $order['vendor_name'] . ' updated your order to ' . $status . '.'
            );
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
        return [
            'order_id' => $orderId,
            'status' => $status,
            'previous_status' => $order['status'],
            'delivery_method' => $order['delivery_method'],
            'delivery_address' => $order['delivery_address'],
            'customer_email' => $order['customer_email'],
            'customer_name' => $order['customer_name'],
            'vendor_name' => $order['vendor_name'],
        ];
    }

    public function count_open()
    {
        return (int) $this->db->table($this->table)
            ->not_in('status', ['completed', 'delivered', 'cancelled'])
            ->count();
    }

    /* ------------------------------------------------------------------
     | Vendor receipt emails for paid GCash orders
     | A receipt is "claimed" (pending -> sending) before the email is sent
     | so that concurrent requests never email the vendor twice.
     * ------------------------------------------------------------------ */

    /** @return array|null receipt details when this request claimed the email */
    public function claim_receipt_email($orderId)
    {
        $claim = ['vendor_receipt_email_status' => 'sending', 'vendor_receipt_email_attempted_at' => utc_now()];
        $claimed = $this->paid_gcash($orderId)
            ->where('vendor_receipt_email_status', '=', 'pending')
            ->update($claim) === 1;
        if (!$claimed) {
            $claimed = $this->paid_gcash($orderId)
                ->where('vendor_receipt_email_status', '=', 'sending')
                ->where('vendor_receipt_email_attempted_at', '<', $this->stale_before())
                ->update($claim) === 1;
        }
        if (!$claimed) {
            return null;
        }

        $receipt = $this->db->table('pwesto_orders o')
            ->select('o.id, o.total_cents, o.delivery_method, o.delivery_address, o.pickup_at, o.payment_checkout_id, v.name AS vendor_name, owner.email AS vendor_email, customer.name AS customer_name')
            ->inner_join('pwesto_vendors v', 'v.id = o.vendor_id')
            ->inner_join('pwesto_users owner', "owner.id = v.owner_id AND owner.role = 'vendor'")
            ->inner_join('pwesto_users customer', 'customer.id = o.customer_id')
            ->where('o.id', '=', $orderId)
            ->limit(1)
            ->get();
        if (!$receipt) {
            $this->finish_receipt_email($orderId, false);
            return null;
        }
        $receipt['total_cents'] = (int) $receipt['total_cents'];
        $receipt['items'] = $this->call->model('OrderItemModel')->for_order($orderId);
        return $receipt;
    }

    public function finish_receipt_email($orderId, $sent)
    {
        return $this->db->table($this->table)
            ->where('id', '=', $orderId)
            ->where('vendor_receipt_email_status', '=', 'sending')
            ->update($sent
                ? ['vendor_receipt_email_status' => 'sent']
                : ['vendor_receipt_email_status' => 'pending', 'vendor_receipt_email_attempted_at' => null]) === 1;
    }

    /** IDs of the owner's paid GCash orders whose receipt email still needs sending. */
    public function pending_receipt_emails($ownerId)
    {
        $rows = $this->db->table('pwesto_orders o')
            ->select('o.id, o.vendor_receipt_email_status, o.vendor_receipt_email_attempted_at')
            ->inner_join('pwesto_vendors v', 'v.id = o.vendor_id')
            ->where('v.owner_id', '=', $ownerId)
            ->where('o.payment_method', '=', 'gcash')
            ->where('o.payment_status', '=', 'paid')
            ->in('o.vendor_receipt_email_status', ['pending', 'sending'])
            ->order_by('o.updated_at', 'DESC')
            ->get_all();
        $staleBefore = $this->stale_before();
        $ids = [];
        foreach ($rows as $row) {
            if ($row['vendor_receipt_email_status'] === 'pending'
                || (string) $row['vendor_receipt_email_attempted_at'] < $staleBefore) {
                $ids[] = $row['id'];
            }
        }
        return array_slice($ids, 0, 10);
    }

    /* ------------------------------------------------------------------ */

    private function order_listing()
    {
        return $this->db->table('pwesto_orders o')
            ->select('o.id, o.customer_id, o.vendor_id, o.status, o.delivery_method, o.delivery_address, o.pickup_at, o.payment_method, o.payment_status, o.payment_checkout_url, o.total_cents, o.created_at, v.name AS vendor_name, u.name AS customer_name')
            ->inner_join('pwesto_vendors v', 'v.id = o.vendor_id')
            ->inner_join('pwesto_users u', 'u.id = o.customer_id');
    }

    private function with_items(array $orders)
    {
        $items = $this->call->model('OrderItemModel');
        foreach ($orders as &$order) {
            $order['items'] = $items->for_order($order['id']);
            $order['total_cents'] = (int) $order['total_cents'];
        }
        unset($order);
        return $orders;
    }

    private function paid_gcash($orderId)
    {
        return $this->db->table($this->table)
            ->where('id', '=', $orderId)
            ->where('payment_method', '=', 'gcash')
            ->where('payment_status', '=', 'paid');
    }

    private function stale_before()
    {
        return gmdate('Y-m-d H:i:s', time() - self::EMAIL_RETRY_AFTER);
    }
}
