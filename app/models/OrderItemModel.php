<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Line items of a product order (price is copied at order time).
 */
class OrderItemModel extends Model
{
    protected $table = 'pwesto_order_items';
    protected $primary_key = 'id';
    protected $fillable = ['id', 'order_id', 'product_id', 'product_name', 'unit_price_cents', 'quantity'];

    public function add($orderId, $productId, $productName, $unitPriceCents, $quantity)
    {
        $this->insert([
            'id' => random_id(),
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => $productName,
            'unit_price_cents' => $unitPriceCents,
            'quantity' => $quantity,
        ]);
    }

    public function for_order($orderId)
    {
        $items = $this->db->table($this->table)
            ->select('product_name, unit_price_cents, quantity')
            ->where('order_id', '=', $orderId)
            ->order_by('product_name', 'ASC')
            ->get_all();
        return array_map(function ($item) {
            $item['unit_price_cents'] = (int) $item['unit_price_cents'];
            $item['quantity'] = (int) $item['quantity'];
            return $item;
        }, $items);
    }

    /** Items that still reference a product, used to restore stock. */
    public function stock_lines($orderId)
    {
        return $this->db->table($this->table)
            ->select('product_id, quantity')
            ->where('order_id', '=', $orderId)
            ->where_not_null('product_id')
            ->get_all();
    }
}
