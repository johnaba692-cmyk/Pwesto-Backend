<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Stalls saved by a customer (composite key user_id + vendor_id).
 */
class FavoriteModel extends Model
{
    protected $table = 'pwesto_favorites';
    protected $primary_key = 'user_id';
    protected $fillable = ['user_id', 'vendor_id', 'created_at'];

    /** @return string[] saved vendor IDs, newest first */
    public function vendor_ids($userId)
    {
        $rows = $this->db->table($this->table)
            ->select('vendor_id')
            ->where('user_id', '=', $userId)
            ->order_by('created_at', 'DESC')
            ->get_all();
        return array_map('strval', array_column($rows, 'vendor_id'));
    }

    /** @return bool false when the stall is not publicly listed */
    public function set_saved($userId, $vendorId, $saved)
    {
        if ($this->call->model('VendorModel')->find_public($vendorId) === null) {
            return false;
        }
        $key = ['user_id' => $userId, 'vendor_id' => $vendorId];
        if (!$saved) {
            $this->delete_where($key);
            return true;
        }
        if (!$this->exists($key)) {
            try {
                $this->insert($key + ['created_at' => utc_now()]);
            } catch (PDOException $error) {
                // Saved concurrently by another request; the stall is already a favorite.
                if (strpos($error->getMessage(), '23000') === false) {
                    throw $error;
                }
            }
        }
        return true;
    }
}
