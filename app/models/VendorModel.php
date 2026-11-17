<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Market stalls: public listing, owner dashboard, rental payments and admin review.
 */
class VendorModel extends Model
{
    protected $table = 'pwesto_vendors';
    protected $primary_key = 'id';
    protected $fillable = [
        'id', 'owner_id', 'stall_code', 'name', 'category', 'market', 'specialty', 'rating',
        'latitude', 'longitude', 'icon', 'image', 'status', 'application_document',
        'cedula_document', 'photo_document', 'rental_payment_status', 'rental_checkout_id',
        'rental_checkout_url', 'rental_checkout_amount', 'rental_paid_at', 'rental_paid_until',
        'created_at',
    ];

    private const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=220&h=220&q=80';
    private const DOCUMENT_COLUMNS = [
        'application' => 'application_document',
        'cedula' => 'cedula_document',
        'photo' => 'photo_document',
    ];
    private const STALL_PREFIXES = [
        'Meat' => 'MEAT', 'Vegetables' => 'VEG', 'Fruits' => 'FRU', 'Seafood' => 'SEA',
        'Pantry' => 'PAN', 'Flowers' => 'FLW', 'Produce' => 'PRO',
    ];

    /* ------------------------------------------------------------------
     | Public listing
     | A stall is listed when it is approved, owned by a vendor account and
     | has at least one orderable (active, in stock, non-sample) product.
     * ------------------------------------------------------------------ */

    public function public_listing()
    {
        return $this->listed_stalls();
    }

    public function find_public($id)
    {
        $stalls = $this->listed_stalls($id);
        return $stalls[0] ?? null;
    }

    /** Rows of listed stalls joined with their orderable products (assistant context). */
    public function catalog_rows()
    {
        $stalls = $this->approved_vendor_stalls();
        $productsByStall = [];
        if ($stalls) {
            $products = $this->call->model('ProductModel')->orderable_for_vendors(array_column($stalls, 'id'));
            foreach ($products as $product) {
                $productsByStall[$product['vendor_id']][] = $product;
            }
        }
        $rows = [];
        foreach ($stalls as $stall) {
            foreach ($productsByStall[$stall['id']] ?? [] as $product) {
                $rows[] = [
                    'vendor_id' => $stall['id'],
                    'vendor_name' => $stall['name'],
                    'stall_code' => $stall['stall_code'],
                    'category' => $stall['category'],
                    'market' => $stall['market'],
                    'specialty' => $stall['specialty'],
                    'has_owner' => 1,
                    'product_name' => $product['name'],
                    'product_description' => $product['description'],
                    'price_cents' => $product['price_cents'],
                    'stock' => $product['stock'],
                ];
            }
        }
        usort($rows, function ($a, $b) {
            return [$a['vendor_name'], $a['product_name']] <=> [$b['vendor_name'], $b['product_name']];
        });
        return $rows;
    }

    private function listed_stalls($id = null)
    {
        $stalls = $this->approved_vendor_stalls($id);
        if (!$stalls) {
            return [];
        }
        $productNames = [];
        $products = $this->call->model('ProductModel')->orderable_for_vendors(array_column($stalls, 'id'));
        foreach ($products as $product) {
            $productNames[$product['vendor_id']][] = $product['name'];
        }

        $listed = [];
        foreach ($stalls as $stall) {
            if (empty($productNames[$stall['id']])) {
                continue;
            }
            $stall['product_names'] = implode(' ', $productNames[$stall['id']]);
            $stall['can_message'] = true;
            $stall['can_order'] = true;
            $stall['is_sample_image'] = strpos((string) $stall['image'], 'https://images.unsplash.com/') === 0;
            unset($stall['owner_id']);
            $listed[] = $this->format_listing($stall);
        }
        usort($listed, function ($a, $b) {
            return [$a['category'], $a['stall_code'], $a['name']] <=> [$b['category'], $b['stall_code'], $b['name']];
        });
        return $listed;
    }

    /** Approved stalls whose owner has the vendor role. */
    private function approved_vendor_stalls($id = null)
    {
        $this->db->table('pwesto_vendors v')
            ->select('v.id, v.owner_id, v.stall_code, v.name, v.category, v.market, v.specialty, v.rating, v.latitude AS lat, v.longitude AS lng, v.icon, v.image')
            ->inner_join('pwesto_users u', 'u.id = v.owner_id')
            ->where('v.status', '=', 'approved')
            ->where('u.role', '=', 'vendor');
        if ($id !== null) {
            $this->db->where('v.id', '=', $id);
        }
        return $this->db->get_all();
    }

    /** Vendor name and owner when the stall can receive orders. */
    public function orderable_stall($id)
    {
        return $this->db->table('pwesto_vendors v')
            ->select('v.name, v.owner_id')
            ->inner_join('pwesto_users u', 'u.id = v.owner_id')
            ->where('v.id', '=', $id)
            ->where('v.status', '=', 'approved')
            ->where('u.role', '=', 'vendor')
            ->limit(1)
            ->get() ?: null;
    }

    public function owner_of($id)
    {
        return $this->value('owner_id', ['id' => $id]);
    }

    public function is_owned_by($id, $ownerId)
    {
        return $this->exists(['id' => $id, 'owner_id' => $ownerId]);
    }

    public function markets()
    {
        return array_keys((array) config_item('pwesto_markets'));
    }

    /* ------------------------------------------------------------------
     | Owner dashboard and stall applications
     * ------------------------------------------------------------------ */

    public function for_owner($ownerId)
    {
        $rows = $this->db->table($this->table)
            ->where('owner_id', '=', $ownerId)
            ->order_by('created_at', 'DESC')
            ->get_all();
        return array_map(function ($row) {
            $summary = $this->application_summary($row);
            return [
                'id' => $row['id'],
                'stall_code' => $row['stall_code'],
                'name' => $row['name'],
                'category' => $row['category'],
                'market' => $row['market'],
                'specialty' => $row['specialty'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'rental_payment_status' => $summary['rental_payment_status'],
                'rental_checkout_url' => $row['rental_payment_status'] === 'pending' ? $row['rental_checkout_url'] : null,
                'rental_paid_until' => $row['rental_paid_until'],
                'has_application' => $summary['has_application'],
                'has_cedula' => $summary['has_cedula'],
                'has_photo' => $summary['has_photo'],
            ];
        }, $rows);
    }

    /** Rental and document status for one of the owner's stalls. */
    public function find_for_owner($id, $ownerId)
    {
        $row = $this->db->table($this->table)
            ->where('id', '=', $id)
            ->where('owner_id', '=', $ownerId)
            ->limit(1)
            ->get();
        if (!$row) {
            return null;
        }
        $pending = $row['rental_payment_status'] === 'pending';
        return array_merge(['id' => $row['id']], $this->application_summary($row), [
            'rental_checkout_id' => $pending ? $row['rental_checkout_id'] : null,
            'rental_checkout_url' => $pending ? $row['rental_checkout_url'] : null,
            'rental_checkout_amount' => $pending ? $row['rental_checkout_amount'] : null,
            'rental_paid_until' => $row['rental_paid_until'],
        ]);
    }

    public function create_application(array $vendor)
    {
        $location = config_item('pwesto_markets')[$vendor['market']];
        $this->db->transaction();
        try {
            $this->insert([
                'id' => $vendor['id'],
                'owner_id' => $vendor['owner_id'],
                'stall_code' => $this->next_stall_code($vendor['category']),
                'name' => $vendor['name'],
                'category' => $vendor['category'],
                'market' => $vendor['market'],
                'specialty' => $vendor['specialty'],
                'rating' => 0,
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'icon' => '⌂',
                'image' => self::DEFAULT_IMAGE,
                'application_document' => $vendor['application_document'],
                'cedula_document' => $vendor['cedula_document'],
                'photo_document' => $vendor['photo_document'],
                'status' => 'pending',
                'rental_payment_status' => 'unpaid',
                'created_at' => utc_now(),
            ]);
            $this->call->model('NotificationModel')->notify_admins(
                'rental_request',
                'New stall rental request',
                $vendor['name'] . ' submitted a rental request for ' . $vendor['market'] . '.'
            );
            $this->db->commit();
            return $vendor['id'];
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    public function update_listing($id, $ownerId, array $vendor)
    {
        $location = config_item('pwesto_markets')[$vendor['market']];
        $this->db->transaction();
        try {
            $current = $this->db->table($this->table)
                ->select('category, status')
                ->where('id', '=', $id)
                ->where('owner_id', '=', $ownerId)
                ->limit(1)
                ->get();
            if (!$current) {
                $this->db->roll_back();
                return false;
            }
            $changes = [
                'name' => $vendor['name'],
                'category' => $vendor['category'],
                'market' => $vendor['market'],
                'specialty' => $vendor['specialty'],
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
            ];
            if ($this->stall_prefix($vendor['category']) !== $this->stall_prefix($current['category'])) {
                $changes['stall_code'] = $this->next_stall_code($vendor['category']);
            }
            if ($current['status'] === 'rejected') {
                $changes['status'] = 'pending';
            }
            $this->update($id, $changes);
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    public function document_path($id, $kind)
    {
        if (!isset(self::DOCUMENT_COLUMNS[$kind])) {
            return null;
        }
        $path = $this->value(self::DOCUMENT_COLUMNS[$kind], ['id' => $id]);
        return is_string($path) && $path !== '' ? $path : null;
    }

    /** @return array|false previous document paths that were replaced */
    public function replace_documents($id, $ownerId, array $documents)
    {
        $documents = array_intersect_key($documents, array_flip(self::DOCUMENT_COLUMNS));
        if (!$documents) {
            return false;
        }
        $previous = $this->db->table($this->table)
            ->select('application_document, cedula_document, photo_document')
            ->where('id', '=', $id)
            ->where('owner_id', '=', $ownerId)
            ->limit(1)
            ->get();
        if (!$previous) {
            return false;
        }
        $this->db->table($this->table)
            ->where('id', '=', $id)
            ->where('owner_id', '=', $ownerId)
            ->update($documents);
        return array_intersect_key($previous, $documents);
    }

    /* ------------------------------------------------------------------
     | Rental payments (Xendit)
     * ------------------------------------------------------------------ */

    public function save_rental_checkout($id, $ownerId, $checkoutId, $checkoutUrl, $amountCents)
    {
        $paidUntil = $this->value('rental_paid_until', ['id' => $id, 'owner_id' => $ownerId]);
        if ($paidUntil !== null && $paidUntil >= utc_today()) {
            return false;
        }
        $this->db->table($this->table)
            ->where('id', '=', $id)
            ->where('owner_id', '=', $ownerId)
            ->where('rental_payment_status', '!=', 'pending');
        $this->match_nullable('rental_paid_until', $paidUntil);
        return $this->db->update([
            'rental_checkout_id' => $checkoutId,
            'rental_checkout_url' => $checkoutUrl,
            'rental_checkout_amount' => $amountCents,
            'rental_payment_status' => 'pending',
            'rental_paid_until' => null,
        ]) === 1;
    }

    public function mark_rental_paid($id, $checkoutId, $amountCents)
    {
        $this->db->transaction();
        try {
            $vendor = $this->db->table($this->table)
                ->select('name, market, rental_paid_until')
                ->where('id', '=', $id)
                ->where('rental_checkout_id', '=', $checkoutId)
                ->where('rental_checkout_amount', '=', $amountCents)
                ->where('rental_payment_status', '=', 'pending')
                ->limit(1)
                ->get();
            if (!$vendor || ($vendor['rental_paid_until'] !== null && $vendor['rental_paid_until'] >= utc_today())) {
                $this->db->roll_back();
                return false;
            }
            $this->db->table($this->table)
                ->where('id', '=', $id)
                ->where('rental_payment_status', '=', 'pending');
            $this->match_nullable('rental_paid_until', $vendor['rental_paid_until']);
            $updated = $this->db->update([
                'rental_payment_status' => 'paid',
                'rental_paid_at' => utc_now(),
                'rental_paid_until' => (new DateTimeImmutable('today', new DateTimeZone('UTC')))
                    ->modify('+1 month')->format('Y-m-d'),
            ]);
            if ($updated !== 1) {
                $this->db->roll_back();
                return false;
            }
            $this->call->model('NotificationModel')->notify_admins(
                'rental_payment',
                'Stall rental payment confirmed',
                $vendor['name'] . ' in ' . $vendor['market'] . ' is ready for review.'
            );
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    public function rental_payment_recorded($id, $checkoutId, $amountCents)
    {
        return (bool) $this->db->table($this->table)
            ->where('id', '=', $id)
            ->where('rental_checkout_id', '=', $checkoutId)
            ->where('rental_checkout_amount', '=', $amountCents)
            ->where('rental_payment_status', '=', 'paid')
            ->where('rental_paid_until', '>=', utc_today())
            ->limit(1)
            ->get();
    }

    /* ------------------------------------------------------------------
     | Administration
     * ------------------------------------------------------------------ */

    public function pending_applications()
    {
        $rows = $this->db->table('pwesto_vendors v')
            ->select('v.id, v.stall_code, v.name, v.category, v.market, v.specialty, v.status, v.created_at, v.rental_payment_status, v.rental_checkout_id, v.rental_paid_until, v.application_document, v.cedula_document, v.photo_document, u.name AS owner_name, u.email AS owner_email')
            ->left_join('pwesto_users u', 'u.id = v.owner_id')
            ->where('v.status', '=', 'pending')
            ->order_by('v.created_at', 'ASC')
            ->get_all();
        return array_map(function ($row) {
            $summary = $this->application_summary($row);
            return [
                'id' => $row['id'],
                'stall_code' => $row['stall_code'],
                'name' => $row['name'],
                'category' => $row['category'],
                'market' => $row['market'],
                'specialty' => $row['specialty'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'rental_payment_status' => $summary['rental_payment_status'],
                'has_application' => $summary['has_application'],
                'has_cedula' => $summary['has_cedula'],
                'has_photo' => $summary['has_photo'],
                'owner_name' => $row['owner_name'],
                'owner_email' => $row['owner_email'],
            ];
        }, $rows);
    }

    public function unassigned()
    {
        $rows = $this->db->table($this->table)
            ->select('id, stall_code, name, category, market, status')
            ->where_null('owner_id')
            ->get_all();
        return $this->sort_by_category($rows);
    }

    /** All stalls with owner details for the admin storage page. */
    public function all_with_owners()
    {
        $rows = $this->db->table('pwesto_vendors v')
            ->select('v.id, v.stall_code, v.name, v.category, v.status, v.image, v.rental_payment_status AS stored_rental_payment_status, v.rental_checkout_id, v.rental_checkout_amount, v.rental_paid_at, v.rental_paid_until, v.application_document, v.cedula_document, v.photo_document, u.name AS owner_name, u.email AS owner_email')
            ->left_join('pwesto_users u', 'u.id = v.owner_id')
            ->get_all();
        foreach ($rows as &$row) {
            $row['rental_payment_status'] = $this->rental_status(
                $row['rental_paid_until'],
                $row['rental_checkout_id'],
                $row['stored_rental_payment_status']
            );
            $row['is_sample_image'] = strpos((string) $row['image'], 'https://images.unsplash.com/') === 0;
        }
        unset($row);
        return $this->sort_by_category($rows);
    }

    /** Link an existing (or new) vendor account to a stall without an owner. */
    public function assign_account($id, $email, $name)
    {
        $users = $this->call->model('UserModel');
        $this->db->transaction();
        try {
            $vendor = $this->find($id);
            if (!$vendor) {
                $this->db->roll_back();
                return ['status' => 'not_found'];
            }
            if ($vendor['owner_id'] !== null) {
                $this->db->roll_back();
                return ['status' => 'already_assigned'];
            }
            $user = $users->find_by_email($email);
            $created = false;
            if ($user) {
                if ($user['role'] !== 'vendor') {
                    $this->db->roll_back();
                    return ['status' => 'role_conflict'];
                }
                if ($this->exists(['owner_id' => $user['id']])) {
                    $this->db->roll_back();
                    return ['status' => 'account_in_use'];
                }
                $ownerId = $user['id'];
            } else {
                $ownerId = random_id();
                $now = utc_now();
                $users->insert([
                    'id' => $ownerId,
                    'email' => $email,
                    'name' => $name,
                    'picture' => '',
                    'password_hash' => null,
                    'role' => 'vendor',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $created = true;
            }
            $assigned = $this->db->table($this->table)
                ->where('id', '=', $id)
                ->where_null('owner_id')
                ->update(['owner_id' => $ownerId]);
            if ($assigned !== 1) {
                $this->db->roll_back();
                return ['status' => 'already_assigned'];
            }
            $this->db->commit();
            return ['status' => 'assigned', 'account_created' => $created];
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    /** Approve or reject a pending application. Approval requires documents and paid rent. */
    public function review($id, $decision)
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }
        $this->db->transaction();
        try {
            $vendor = $this->db->table($this->table)
                ->where('id', '=', $id)
                ->where('status', '=', 'pending')
                ->limit(1)
                ->get();
            $ready = $vendor
                && $vendor['application_document'] !== null
                && $vendor['cedula_document'] !== null
                && $vendor['photo_document'] !== null
                && $vendor['rental_paid_until'] !== null
                && $vendor['rental_paid_until'] >= utc_today();
            if (!$vendor || ($decision === 'approved' && !$ready)) {
                $this->db->roll_back();
                return false;
            }
            $updated = $this->db->table($this->table)
                ->where('id', '=', $id)
                ->where('status', '=', 'pending')
                ->update(['status' => $decision]);
            if ($updated !== 1) {
                $this->db->roll_back();
                return false;
            }
            $approved = $decision === 'approved';
            if ($vendor['owner_id'] !== null) {
                if ($approved) {
                    $this->call->model('UserModel')->promote_to_vendor($vendor['owner_id']);
                }
                $this->call->model('NotificationModel')->notify(
                    $vendor['owner_id'],
                    'rental_review',
                    $approved ? 'Your stall rental was approved' : 'Your stall rental was declined',
                    $approved
                        ? $vendor['name'] . ' is approved and now visible to customers.'
                        : $vendor['name'] . ' was not approved. Check your stall dashboard for next steps.'
                );
            }
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    public function count_by_status($status)
    {
        return (int) $this->db->table($this->table)->where('status', '=', $status)->count();
    }

    public function approved_category_breakdown()
    {
        return $this->db->table($this->table)
            ->select('category, COUNT(*) AS stalls')
            ->where('status', '=', 'approved')
            ->group_by('category')
            ->order_by('category', 'ASC')
            ->get_all();
    }

    /* ------------------------------------------------------------------
     | Internal helpers
     * ------------------------------------------------------------------ */

    /** Reserve the next sequential stall code (e.g. MEAT-007). Call inside a transaction. */
    private function next_stall_code($category)
    {
        $prefix = $this->stall_prefix($category);
        $sequences = 'pwesto_stall_sequences';
        $updated = $this->db->table($sequences)->where('prefix', '=', $prefix)->increment('next_number');
        if ($updated !== 1) {
            try {
                $this->db->table($sequences)->insert(['prefix' => $prefix, 'next_number' => 1]);
            } catch (PDOException $error) {
                // Another request created the sequence first; take the next number instead.
                $this->db->table($sequences)->where('prefix', '=', $prefix)->increment('next_number');
            }
        }
        $number = (int) $this->db->table($sequences)
            ->select('next_number')
            ->where('prefix', '=', $prefix)
            ->get()['next_number'];
        return $prefix . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    private function stall_prefix($category)
    {
        return self::STALL_PREFIXES[$category] ?? 'STL';
    }

    private function rental_status($paidUntil, $checkoutId, $storedStatus)
    {
        if ($paidUntil !== null && $paidUntil >= utc_today()) {
            return 'paid';
        }
        if ($checkoutId !== null && $storedStatus === 'pending') {
            return 'pending';
        }
        return 'unpaid';
    }

    private function application_summary(array $row)
    {
        return [
            'rental_payment_status' => $this->rental_status(
                $row['rental_paid_until'],
                $row['rental_checkout_id'],
                $row['rental_payment_status']
            ),
            'has_application' => $row['application_document'] === null ? 0 : 1,
            'has_cedula' => $row['cedula_document'] === null ? 0 : 1,
            'has_photo' => $row['photo_document'] === null ? 0 : 1,
        ];
    }

    /** Optimistic-concurrency condition: the column still holds the value we read. */
    private function match_nullable($column, $value)
    {
        if ($value === null) {
            $this->db->where_null($column);
        } else {
            $this->db->where($column, '=', $value);
        }
    }

    private function sort_by_category(array $rows)
    {
        usort($rows, function ($a, $b) {
            return [$a['category'], (string) $a['stall_code'], $a['name']]
                <=> [$b['category'], (string) $b['stall_code'], $b['name']];
        });
        return $rows;
    }

    private function format_listing(array $vendor)
    {
        $center = ['lat' => 13.4118, 'lng' => 121.1809];
        $lat1 = deg2rad($center['lat']);
        $lat2 = deg2rad((float) $vendor['lat']);
        $latDelta = deg2rad((float) $vendor['lat'] - $center['lat']);
        $lngDelta = deg2rad((float) $vendor['lng'] - $center['lng']);
        $distance = 6371 * 2 * asin(sqrt(
            sin($latDelta / 2) ** 2 + cos($lat1) * cos($lat2) * sin($lngDelta / 2) ** 2
        ));
        $vendor['rating'] = number_format((float) $vendor['rating'], 1);
        $vendor['lat'] = (float) $vendor['lat'];
        $vendor['lng'] = (float) $vendor['lng'];
        $vendor['distance'] = number_format($distance, 1) . ' km';
        return $vendor;
    }
}
