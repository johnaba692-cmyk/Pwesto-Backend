<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Stall products: public catalog, vendor CRUD and stock changes.
 */
class ProductModel extends Model
{
    protected $table = 'pwesto_products';
    protected $primary_key = 'id';
    protected $fillable = [
        'id', 'vendor_id', 'name', 'description', 'price_cents', 'stock',
        'image', 'active', 'created_at', 'updated_at',
    ];

    /** Marker used by illustrative catalog rows that cannot be ordered. */
    public const SAMPLE_MARKER = 'Sample dataset:';

    /** Active, in-stock, non-sample products of the given stalls. */
    public function orderable_for_vendors(array $vendorIds)
    {
        if (!$vendorIds) {
            return [];
        }
        return $this->db->table($this->table)
            ->select('id, vendor_id, name, description, price_cents, stock, image')
            ->in('vendor_id', $vendorIds)
            ->where('active', '=', 1)
            ->where('stock', '>', 0)
            ->not_like('description', '%' . self::SAMPLE_MARKER . '%')
            ->order_by('name', 'ASC')
            ->get_all();
    }

    /** Public product list for a listed stall. */
    public function public_for_vendor($vendorId)
    {
        if ($this->call->model('VendorModel')->find_public($vendorId) === null) {
            return [];
        }
        return array_map(function ($product) {
            $product['is_sample'] = 0;
            return $product;
        }, $this->orderable_for_vendors([$vendorId]));
    }

    public function public_image($productId)
    {
        $product = $this->find($productId);
        if (!$product || (int) $product['active'] !== 1 || (int) $product['stock'] < 1
            || strpos((string) $product['description'], self::SAMPLE_MARKER) !== false
            || $this->call->model('VendorModel')->find_public($product['vendor_id']) === null) {
            return null;
        }
        return $this->non_empty($product['image']);
    }

    public function image_for_admin($productId)
    {
        return $this->non_empty($this->value('image', ['id' => $productId]));
    }

    public function image_for_owner($productId, $ownerId)
    {
        $row = $this->owned($productId, $ownerId);
        return $row ? $this->non_empty($row['image']) : null;
    }

    public function for_owner($vendorId, $ownerId)
    {
        return $this->db->table('pwesto_products p')
            ->select('p.id, p.vendor_id, p.name, p.description, p.price_cents, p.stock, p.active, p.image')
            ->inner_join('pwesto_vendors v', 'v.id = p.vendor_id')
            ->where('p.vendor_id', '=', $vendorId)
            ->where('v.owner_id', '=', $ownerId)
            ->order_by('p.name', 'ASC')
            ->get_all();
    }

    /** @return string|false the new product ID, or false when the stall is not the owner's */
    public function create_for_owner($vendorId, $ownerId, array $product, $image = null)
    {
        if (!$this->call->model('VendorModel')->is_owned_by($vendorId, $ownerId)) {
            return false;
        }
        $id = random_id();
        $now = utc_now();
        $this->insert([
            'id' => $id,
            'vendor_id' => $vendorId,
            'name' => $product['name'],
            'description' => $product['description'],
            'price_cents' => $product['price_cents'],
            'stock' => $product['stock'],
            'image' => $image,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $id;
    }

    /** @return array|false ['previous_image' => ...] or false when the product is not the owner's */
    public function update_for_owner($productId, $vendorId, $ownerId, array $product, $image = null)
    {
        $existing = $this->owned($productId, $ownerId);
        if (!$existing || $existing['vendor_id'] !== $vendorId) {
            return false;
        }
        $changes = [
            'name' => $product['name'],
            'description' => $product['description'],
            'price_cents' => $product['price_cents'],
            'stock' => $product['stock'],
            'updated_at' => utc_now(),
        ];
        if ($image !== null) {
            $changes['image'] = $image;
        }
        $updated = $this->db->table($this->table)
            ->where('id', '=', $productId)
            ->where('vendor_id', '=', $vendorId)
            ->update($changes);
        return $updated > 0 ? ['previous_image' => $existing['image']] : false;
    }

    /** @return array|false ['image' => ...] of the deleted product */
    public function delete_for_owner($productId, $ownerId)
    {
        $this->db->transaction();
        try {
            $existing = $this->owned($productId, $ownerId);
            if (!$existing) {
                $this->db->roll_back();
                return false;
            }
            $this->delete($productId);
            $this->db->commit();
            return ['image' => $existing['image']];
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    /** Orderable product of a stall, used while placing an order. */
    public function for_order($productId, $vendorId)
    {
        return $this->db->table($this->table)
            ->select('id, name, description, price_cents, stock')
            ->where('id', '=', $productId)
            ->where('vendor_id', '=', $vendorId)
            ->where('active', '=', 1)
            ->limit(1)
            ->get() ?: null;
    }

    /** Atomically take stock; returns false when not enough is left. */
    public function reserve_stock($productId, $quantity)
    {
        $taken = $this->db->table($this->table)
            ->where('id', '=', $productId)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);
        if ($taken !== 1) {
            return false;
        }
        $this->update($productId, ['updated_at' => utc_now()]);
        return true;
    }

    public function restore_stock($productId, $quantity)
    {
        $this->db->table($this->table)->where('id', '=', $productId)->increment('stock', $quantity);
        $this->update($productId, ['updated_at' => utc_now()]);
    }

    /** Products with an image, for the admin storage page. */
    public function with_images()
    {
        $rows = $this->db->table('pwesto_products p')
            ->select('p.id, p.vendor_id, p.name AS product_name, p.image, p.description, v.name AS vendor_name, v.status AS vendor_status')
            ->inner_join('pwesto_vendors v', 'v.id = p.vendor_id')
            ->where_not_null('p.image')
            ->where('p.image', '!=', '')
            ->get_all();
        usort($rows, function ($a, $b) {
            return [$a['vendor_name'], $a['product_name']] <=> [$b['vendor_name'], $b['product_name']];
        });
        return $rows;
    }

    /** Full catalog for the admin storage page. */
    public function admin_catalog()
    {
        $rows = $this->db->table('pwesto_products p')
            ->select('p.name AS product_name, p.description, p.price_cents, p.stock, p.active, v.name AS vendor_name, v.stall_code, v.category AS vendor_category, v.status AS vendor_status')
            ->inner_join('pwesto_vendors v', 'v.id = p.vendor_id')
            ->get_all();
        usort($rows, function ($a, $b) {
            return [$a['vendor_category'], (string) $a['stall_code'], $a['product_name']]
                <=> [$b['vendor_category'], (string) $b['stall_code'], $b['product_name']];
        });
        return $rows;
    }

    public function count_active_on_approved_stalls()
    {
        $row = $this->db->table('pwesto_products p')
            ->select('COUNT(*) AS total')
            ->inner_join('pwesto_vendors v', 'v.id = p.vendor_id')
            ->where('p.active', '=', 1)
            ->where('v.status', '=', 'approved')
            ->get();
        return (int) ($row['total'] ?? 0);
    }

    public static function is_sample(array $product)
    {
        return strpos((string) ($product['description'] ?? ''), self::SAMPLE_MARKER) !== false;
    }

    private function owned($productId, $ownerId)
    {
        return $this->db->table('pwesto_products p')
            ->select('p.id, p.vendor_id, p.image')
            ->inner_join('pwesto_vendors v', 'v.id = p.vendor_id')
            ->where('p.id', '=', $productId)
            ->where('v.owner_id', '=', $ownerId)
            ->limit(1)
            ->get() ?: null;
    }

    private function non_empty($value)
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
