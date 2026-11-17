<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Product catalog management for stall owners (create, read, update, delete).
 */
class Products extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model(['VendorModel', 'ProductModel']);
        $this->call->library(['cloudinary_storage', 'upload_validator']);
    }

    /** GET /api/vendors/products?vendor_id= */
    public function index()
    {
        $owner = $this->require_stall_owner();
        $vendorId = $this->query('vendor_id');
        if ($vendorId === '') {
            $this->fail('Choose one of your stalls.', 422);
        }
        $products = $this->db_call(function () use ($vendorId, $owner) {
            return $this->ProductModel->for_owner($vendorId, $owner['id']);
        }, 'Your products could not be loaded.');
        $this->respond(['products' => $this->present_product_images($products, '/api/vendors/products/image')]);
    }

    /** POST (create, or update when product_id is sent) and PUT /api/vendors/products */
    public function save()
    {
        $owner = $this->require_stall_owner();
        $this->verify_csrf_token();
        $body = $this->input();
        $product = $this->validated_product($body);
        $vendorId = $body['vendor_id'] ?? '';
        if (!is_string($vendorId) || $vendorId === '') {
            $this->fail('Choose one of your stalls.', 422);
        }
        $isUpdate = ($_SERVER['REQUEST_METHOD'] ?? '') === 'PUT' || !empty($body['product_id']);
        $productId = $body['product_id'] ?? '';
        if ($isUpdate && !$this->is_hex_id($productId, 32)) {
            $this->fail('Choose a valid product to update.', 422);
        }

        $imageName = $this->upload_image($vendorId);
        try {
            $result = $isUpdate
                ? $this->ProductModel->update_for_owner($productId, $vendorId, $owner['id'], $product, $imageName)
                : $this->ProductModel->create_for_owner($vendorId, $owner['id'], $product, $imageName);
        } catch (Throwable $error) {
            $this->cloudinary_storage->deleteQuietly($imageName);
            error_log('Pwesto product save failed: ' . $error->getMessage());
            $this->fail($isUpdate ? 'The product could not be updated.' : 'The product could not be added.', 503);
        }

        if (!$result) {
            $this->cloudinary_storage->deleteQuietly($imageName);
            $isUpdate
                ? $this->fail('That product is no longer available to your account.', 404)
                : $this->fail('Choose one of your own stalls before adding a product.', 403);
        }
        if (!$isUpdate) {
            $this->respond(['product_id' => $result], 201);
        }
        if ($imageName !== null) {
            $this->delete_local_or_cloud_image($result['previous_image']);
        }
        $this->respond(['success' => true]);
    }

    /** DELETE /api/vendors/products (JSON: product_id) */
    public function destroy()
    {
        $owner = $this->require_stall_owner();
        $this->verify_csrf_token();
        $productId = $this->input()['product_id'] ?? '';
        if (!$this->is_hex_id($productId, 32)) {
            $this->fail('Choose a valid product to remove.', 422);
        }
        $deleted = $this->db_call(function () use ($productId, $owner) {
            return $this->ProductModel->delete_for_owner($productId, $owner['id']);
        }, 'The product could not be removed.');
        if (!$deleted) {
            $this->fail('That product is no longer available to your account.', 404);
        }
        $this->delete_local_or_cloud_image($deleted['image']);
        $this->respond(['success' => true]);
    }

    /** GET /api/vendors/products/image?product_id= */
    public function image()
    {
        $owner = $this->require_stall_owner();
        $productId = $this->query('product_id');
        if (!$this->is_hex_id($productId, 32)) {
            $this->fail('Choose a valid product image.', 422);
        }
        $image = $this->db_call(function () use ($productId, $owner) {
            return $this->ProductModel->image_for_owner($productId, $owner['id']);
        }, 'Your product image could not be loaded.');
        $this->stream_product_image($image, true);
    }

    /* ------------------------------------------------------------------ */

    /** Only accounts with at least one stall application (and not admins) manage products. */
    private function require_stall_owner()
    {
        $user = $this->require_user();
        if (($user['role'] ?? 'customer') === 'admin' || $this->is_admin_email($user['email'] ?? '')) {
            $this->fail('Only a stall owner can manage its products.', 403);
        }
        $stalls = $this->db_call(function () use ($user) {
            return $this->VendorModel->for_owner($user['id']);
        }, 'Your stall applications could not be loaded.');
        if (!$stalls) {
            $this->fail('Submit a stall application before managing products.', 403);
        }
        return $user;
    }

    private function validated_product(array $body)
    {
        $name = $this->string_input($body, 'name');
        $description = $this->string_input($body, 'description');
        $price = $body['price'] ?? null;
        $stock = $body['stock'] ?? null;

        if ($this->text_length($name) < 2 || $this->text_length($name) > 100) {
            $this->fail('Enter a product name between 2 and 100 characters.', 422);
        }
        if ($this->text_length($description) > 500) {
            $this->fail('Product details must be 500 characters or fewer.', 422);
        }
        if (!is_string($price) && !is_int($price) && !is_float($price)) {
            $this->fail('Enter a valid product price in pesos.', 422);
        }
        $price = (string) $price;
        if (!preg_match('/^(?:0|[1-9]\d{0,7})(?:\.\d{1,2})?$/', $price) || (float) $price <= 0) {
            $this->fail('Enter a price greater than ₱0 with up to 2 decimal places.', 422);
        }
        if (is_string($stock) && preg_match('/^(?:0|[1-9]\d{0,6})$/', $stock)) {
            $stock = (int) $stock;
        }
        if (!is_int($stock) || $stock < 0 || $stock > 1000000) {
            $this->fail('Enter available stock as a whole number from 0 to 1,000,000.', 422);
        }
        $parts = explode('.', $price, 2);
        return [
            'name' => $name,
            'description' => $description,
            'price_cents' => ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0'),
            'stock' => $stock,
        ];
    }

    /** Validate and upload the optional product image to Cloudinary. */
    private function upload_image($vendorId)
    {
        try {
            $image = $this->upload_validator->validate('image', [
                'max_bytes' => 5242880,
                'types' => Upload_validator::IMAGE_TYPES,
                'messages' => [
                    'too_large' => 'Product images must be no larger than 5 MB.',
                    'invalid' => 'The selected product image could not be uploaded. Choose a valid image and try again.',
                    'type' => 'Product images must be JPEG, PNG, or WebP files.',
                    'image' => 'The product image is invalid or exceeds 5,000 × 5,000 pixels.',
                ],
            ]);
        } catch (Upload_rejected $rejected) {
            $this->fail($rejected->getMessage(), $rejected->getCode());
        }
        if ($image === null) {
            return null;
        }
        try {
            return $this->cloudinary_storage->upload(
                $image['tmp_name'],
                'cloudinary/products/' . $vendorId . '/' . random_id()
            );
        } catch (Throwable $error) {
            error_log('Pwesto product image upload failed: ' . $error->getMessage());
            $this->fail('The product image could not be saved to Cloudinary. Check its server configuration.', 503);
        }
    }

    /** Remove a replaced image from Cloudinary or the legacy local uploads folder. */
    private function delete_local_or_cloud_image($image)
    {
        if (Cloudinary_storage::isProductObject($image)) {
            $this->cloudinary_storage->deleteQuietly($image);
            return;
        }
        if (is_string($image) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $image)) {
            $path = ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR
                . 'pwesto-products' . DIRECTORY_SEPARATOR . $image;
            if (is_file($path) && !unlink($path)) {
                error_log('Pwesto product image could not be removed: ' . $path);
            }
        }
    }
}
