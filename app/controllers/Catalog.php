<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Public market catalog: listed stalls, their products and product images.
 */
class Catalog extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model(['VendorModel', 'ProductModel']);
        $this->call->library('cloudinary_storage');
    }

    /** GET /api/vendors */
    public function vendors()
    {
        $vendors = $this->db_call(function () {
            return $this->VendorModel->public_listing();
        }, 'Market stalls could not be loaded.');
        $this->respond(['vendors' => $vendors]);
    }

    /** GET /api/products?vendor_id= */
    public function products()
    {
        $vendorId = $this->query('vendor_id');
        if ($vendorId === '') {
            $this->fail('Choose a market stall to view its products.', 422);
        }
        $products = $this->db_call(function () use ($vendorId) {
            return $this->ProductModel->public_for_vendor($vendorId);
        }, 'Products could not be loaded.');
        $this->respond(['products' => $this->present_product_images($products, '/api/products/image')]);
    }

    /** GET /api/products/image?product_id= */
    public function product_image()
    {
        $productId = $this->query('product_id');
        if (!$this->is_hex_id($productId, 32)) {
            $this->fail('Choose a valid product image.', 422);
        }
        $image = $this->db_call(function () use ($productId) {
            return $this->ProductModel->public_image($productId);
        }, 'The product image could not be loaded.');
        $this->stream_product_image($image, false);
    }
}
