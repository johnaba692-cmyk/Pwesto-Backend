<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Administration: dashboard, stall application review, vendor account
 * assignment and the storage overview of images and private documents.
 */
class Admin extends MY_Controller
{
    private const DOCUMENT_KINDS = [
        'application_document' => ['application', 'Application form'],
        'cedula_document' => ['cedula', 'Barangay cedula'],
        'photo_document' => ['photo', '2×2 photo'],
    ];
    private const DOCUMENT_MIME_TYPES = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];

    public function before_action()
    {
        parent::before_action();
        $this->require_role('admin');
        $this->call->model(['UserModel', 'VendorModel', 'ProductModel', 'OrderModel', 'AppointmentModel']);
        $this->call->library('cloudinary_storage');
    }

    /** GET /api/admin/vendors */
    public function dashboard()
    {
        $this->respond($this->dashboard_data());
    }

    /** POST /api/admin/vendors (JSON: action assign_account, or vendor_id + decision) */
    public function vendors()
    {
        $this->verify_csrf_token();
        $body = $this->input();
        if (($body['action'] ?? '') === 'assign_account') {
            $this->assign_account($body);
        }

        $vendorId = $body['vendor_id'] ?? '';
        $decision = $body['decision'] ?? '';
        if (!$this->is_hex_id($vendorId, 24) || !is_string($decision) || !in_array($decision, ['approved', 'rejected'], true)) {
            $this->fail('Choose an application and a valid review decision.', 422);
        }
        $reviewed = $this->db_call(function () use ($vendorId, $decision) {
            return $this->VendorModel->review($vendorId, $decision);
        }, 'This application could not be reviewed. Please try again.');
        if (!$reviewed) {
            $this->fail('This application has already been reviewed or no longer exists.', 404);
        }
        $this->respond($this->dashboard_data());
    }

    /** GET /api/admin/storage */
    public function storage()
    {
        [$products, $catalog, $stalls] = $this->db_call(function () {
            return [
                $this->ProductModel->with_images(),
                $this->ProductModel->admin_catalog(),
                $this->VendorModel->all_with_owners(),
            ];
        }, 'Storage files could not be loaded.');

        $images = [];
        foreach ($products as $product) {
            $imageUrl = $this->product_image_url($product);
            if ($imageUrl !== null) {
                $images[] = [
                    'product_name' => $product['product_name'],
                    'vendor_name' => $product['vendor_name'],
                    'vendor_status' => $product['vendor_status'],
                    'is_sample' => ProductModel::is_sample($product),
                    'image_url' => $imageUrl,
                ];
            }
        }

        $rentalFeeCents = $this->monthly_rental_fee() * 100;
        $stallImages = [];
        $documents = [];
        $applications = [];
        foreach ($stalls as $stall) {
            $image = (string) $stall['image'];
            $stallImages[] = [
                'stall_code' => $stall['stall_code'],
                'name' => $stall['name'],
                'category' => $stall['category'],
                'status' => $stall['status'],
                'is_sample_image' => $stall['is_sample_image'],
                'image_url' => filter_var($image, FILTER_VALIDATE_URL) && strtolower((string) parse_url($image, PHP_URL_SCHEME)) === 'https'
                    ? $image
                    : '',
            ];

            $links = [];
            foreach (self::DOCUMENT_KINDS as $column => [$kind, $label]) {
                $url = $this->is_document_path($stall[$column])
                    ? '/api/admin/vendor-document?vendor_id=' . rawurlencode($stall['id']) . '&kind=' . $kind
                    : null;
                $links[$kind] = $url;
                if ($url !== null) {
                    $documents[] = [
                        'vendor_name' => $stall['name'],
                        'vendor_status' => $stall['status'],
                        'label' => $label,
                        'url' => $url,
                    ];
                }
            }
            $applications[] = [
                'stall_code' => $stall['stall_code'],
                'name' => $stall['name'],
                'category' => $stall['category'],
                'status' => $stall['status'],
                'owner_name' => $stall['owner_name'],
                'owner_email' => $stall['owner_email'],
                'rental_payment_status' => $stall['rental_payment_status'],
                'rental_amount_cents' => (int) $stall['rental_checkout_amount'] > 0
                    ? (int) $stall['rental_checkout_amount']
                    : $rentalFeeCents,
                'rental_paid_at' => $stall['rental_paid_at'],
                'rental_paid_until' => $stall['rental_paid_until'],
                'documents' => $links,
            ];
        }

        $this->respond([
            'stalls' => $stallImages,
            'catalog' => array_map(function ($product) {
                return [
                    'product_name' => $product['product_name'],
                    'description' => $product['description'],
                    'price_cents' => (int) $product['price_cents'],
                    'stock' => (int) $product['stock'],
                    'active' => (bool) $product['active'],
                    'is_sample' => ProductModel::is_sample($product),
                    'vendor_name' => $product['vendor_name'],
                    'stall_code' => $product['stall_code'],
                    'vendor_category' => $product['vendor_category'],
                    'vendor_status' => $product['vendor_status'],
                ];
            }, $catalog),
            'products' => $images,
            'documents' => $documents,
            'applications' => $applications,
        ]);
    }

    /** GET /api/admin/product-image?product_id= */
    public function product_image()
    {
        $productId = $this->query('product_id');
        if (!$this->is_hex_id($productId, 32)) {
            $this->fail('Choose a valid product image.', 422);
        }
        $image = $this->db_call(function () use ($productId) {
            return $this->ProductModel->image_for_admin($productId);
        }, 'The product image could not be loaded.');
        $this->stream_product_image($image, true);
    }

    /** GET /api/admin/vendor-document?vendor_id=&kind= (served through the controller, never a public URL) */
    public function vendor_document()
    {
        $vendorId = $this->query('vendor_id');
        $kind = $this->query('kind');
        if (!$this->is_hex_id($vendorId, 24) || !in_array($kind, ['application', 'cedula', 'photo'], true)) {
            $this->fail('Choose a valid vendor requirement document.', 422);
        }
        $path = $this->db_call(function () use ($vendorId, $kind) {
            return $this->VendorModel->document_path($vendorId, $kind);
        }, 'The vendor requirement document could not be loaded.');
        if (!$this->is_document_path($path)) {
            $this->fail('That requirement document is not available.', 404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (Cloudinary_storage::isVendorDocumentObject($path)) {
            try {
                $content = $this->cloudinary_storage->download($path);
            } catch (Throwable $error) {
                error_log('Pwesto private vendor document download failed: ' . $error->getMessage());
                $this->fail('The vendor requirement could not be loaded from Cloudinary.', 503);
            }
        } else {
            // Documents uploaded before Cloudinary are kept outside the public web root.
            $file = ROOT_DIR . 'runtime' . DIRECTORY_SEPARATOR . 'pwesto-documents' . DIRECTORY_SEPARATOR . $path;
            if (!is_file($file) || !is_readable($file)) {
                $this->fail('That requirement document is not available on the server.', 404);
            }
            $content = file_get_contents($file);
        }
        $this->send_file(
            $content,
            self::DOCUMENT_MIME_TYPES[$extension],
            'private, no-store',
            'inline; filename="' . $kind . '-' . $vendorId . '.' . $extension . '"'
        );
    }

    /* ------------------------------------------------------------------ */

    private function assign_account(array $body)
    {
        $vendorId = $body['vendor_id'] ?? '';
        $email = $this->normalized_email($body['email'] ?? null);
        $name = $this->string_input($body, 'name');
        if (!is_string($vendorId) || !preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/i', $vendorId)
            || $email === null || strlen($name) < 2 || strlen($name) > 120) {
            $this->fail('Enter the vendor’s real name and a valid email address for this stall.', 422);
        }
        $assignment = $this->db_call(function () use ($vendorId, $email, $name) {
            return $this->VendorModel->assign_account($vendorId, $email, $name);
        }, 'The vendor account could not be linked to this stall.');
        $errors = [
            'not_found' => ['This stall no longer exists.', 404],
            'already_assigned' => ['This stall already has a vendor account. Refresh the admin page.', 409],
            'role_conflict' => ['That email belongs to a non-vendor account. Use a vendor account email.', 409],
            'account_in_use' => ['That vendor account is already linked to another stall. Use a separate account email.', 409],
        ];
        if ($assignment['status'] !== 'assigned') {
            [$message, $status] = $errors[$assignment['status']] ?? ['The vendor account could not be linked.', 409];
            $this->fail($message, $status);
        }
        $this->respond($this->dashboard_data() + ['account_created' => $assignment['account_created']]);
    }

    private function dashboard_data()
    {
        return $this->db_call(function () {
            return [
                'summary' => [
                    'users' => $this->UserModel->count_by_role(),
                    'customers' => $this->UserModel->count_by_role('customer'),
                    'vendors' => $this->UserModel->count_by_role('vendor'),
                    'approved_stalls' => $this->VendorModel->count_by_status('approved'),
                    'pending_applications' => $this->VendorModel->count_by_status('pending'),
                    'visits' => (int) $this->AppointmentModel->count(),
                    'active_products' => $this->ProductModel->count_active_on_approved_stalls(),
                    'open_orders' => $this->OrderModel->count_open(),
                    'category_breakdown' => $this->VendorModel->approved_category_breakdown(),
                ],
                'applications' => $this->VendorModel->pending_applications(),
                'unassigned_stalls' => $this->VendorModel->unassigned(),
            ];
        }, 'Admin dashboard data could not be loaded.');
    }

    private function product_image_url(array $product)
    {
        if (Cloudinary_storage::isProductObject($product['image'])) {
            return '/api/admin/product-image?product_id=' . rawurlencode($product['id']);
        }
        if (is_string($product['image']) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $product['image'])) {
            return '/uploads/pwesto-products/' . rawurlencode($product['image']);
        }
        return null;
    }

    private function is_document_path($path)
    {
        return Cloudinary_storage::isVendorDocumentObject($path)
            || (is_string($path) && preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $path) === 1);
    }
}
