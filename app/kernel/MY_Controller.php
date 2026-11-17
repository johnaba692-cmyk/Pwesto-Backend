<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Base controller for the Pwesto JSON API.
 *
 * LavaLust loads app/kernel/MY_*.php automatically, so API controllers can
 * extend this class without require/include. It connects the database,
 * loads the session and the LavaLust Api library, and provides the shared
 * authentication, CSRF and response helpers used by every endpoint.
 */
class MY_Controller extends Controller
{
    public function before_action()
    {
        $this->call->database();
        $this->call->library('session');
        $this->call->library('api');
    }

    /* ------------------------------------------------------------------
     | Request and response (LavaLust Api library)
     * ------------------------------------------------------------------ */

    protected function respond($data, $status = 200)
    {
        $this->api->respond($data, $status);
    }

    protected function fail($message, $status = 400)
    {
        $this->api->respond(['error' => $message], $status);
    }

    /** Request body (JSON, multipart or form) parsed by the Api library. */
    protected function input()
    {
        return $this->api->body();
    }

    /** A single query-string value parsed by the Api library. */
    protected function query($key)
    {
        $value = $this->api->get_query_params()[$key] ?? '';
        return is_string($value) ? $value : '';
    }

    /**
     * Run a database operation and turn unexpected failures into a JSON 503
     * instead of leaking SQL or stack traces to the client.
     */
    protected function db_call(callable $operation, $message)
    {
        try {
            return $operation();
        } catch (Throwable $error) {
            error_log('Pwesto database operation failed: ' . $error->getMessage());
            $this->fail($message, 503);
        }
    }

    /* ------------------------------------------------------------------
     | Authentication and authorization
     * ------------------------------------------------------------------ */

    protected function current_user()
    {
        $user = $this->session->userdata('pwesto_user');
        return is_array($user) ? $user : null;
    }

    protected function require_user()
    {
        $user = $this->current_user();
        if ($user === null) {
            $this->fail('Sign in to continue.', 401);
        }
        return $user;
    }

    protected function require_role($role)
    {
        $user = $this->require_user();
        if (($user['role'] ?? 'customer') !== $role
            || ($role === 'admin' && !$this->is_admin_email($user['email'] ?? ''))) {
            $this->fail('You do not have permission to access this page.', 403);
        }
        return $user;
    }

    protected function is_admin_email($email)
    {
        foreach ((array) config_item('pwesto_admin_emails') as $adminEmail) {
            if (strcasecmp($adminEmail, (string) $email) === 0) {
                return true;
            }
        }
        return false;
    }

    /* ------------------------------------------------------------------
     | CSRF protection for the single-page frontend
     * ------------------------------------------------------------------ */

    protected function csrf_token()
    {
        if (!$this->session->has_userdata('pwesto_csrf')) {
            $this->session->set_userdata('pwesto_csrf', bin2hex(random_bytes(32)));
        }
        return $this->session->userdata('pwesto_csrf');
    }

    protected function rotate_csrf_token()
    {
        $this->session->set_userdata('pwesto_csrf', bin2hex(random_bytes(32)));
        return $this->session->userdata('pwesto_csrf');
    }

    protected function verify_csrf_token()
    {
        $expected = $this->session->userdata('pwesto_csrf');
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($expected) || !is_string($provided) || $provided === ''
            || !hash_equals($expected, $provided)) {
            $this->fail('Your session has expired. Refresh the page and try again.', 403);
        }
    }

    /* ------------------------------------------------------------------
     | Product images (requires the cloudinary_storage library)
     * ------------------------------------------------------------------ */

    /** Add an image_url to each product: a streaming endpoint for Cloudinary images. */
    protected function present_product_images(array $products, $imageEndpoint)
    {
        foreach ($products as &$product) {
            $product['is_sample'] = !empty($product['is_sample']);
            $image = $product['image'] ?? null;
            if (Cloudinary_storage::isProductObject($image) && $this->is_hex_id($product['id'] ?? null, 32)) {
                $product['image_url'] = $imageEndpoint . '?product_id=' . rawurlencode($product['id']);
            } elseif (is_string($image) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $image)) {
                $product['image_url'] = '/uploads/pwesto-products/' . $image;
            } else {
                $product['image_url'] = null;
            }
        }
        unset($product);
        return $products;
    }

    /** Send a Cloudinary product image after checking its real MIME type. */
    protected function stream_product_image($objectName, $isPrivate)
    {
        if (!Cloudinary_storage::isProductObject($objectName)) {
            $this->fail('That product image is not available.', 404);
        }
        try {
            $content = $this->cloudinary_storage->download($objectName);
        } catch (Throwable $error) {
            error_log('Pwesto product image download failed: ' . $error->getMessage());
            $this->fail('The product image could not be loaded from Cloudinary.', 503);
        }
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo === false ? false : finfo_buffer($fileInfo, $content);
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $this->fail('That product image is not available.', 404);
        }
        $this->send_file($content, $mimeType, $isPrivate ? 'private, no-store' : 'public, max-age=3600');
    }

    protected function send_file($content, $mimeType, $cacheControl, $disposition = null)
    {
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: ' . $cacheControl);
        header('X-Content-Type-Options: nosniff');
        if ($disposition !== null) {
            header('Content-Disposition: ' . $disposition);
        }
        echo $content;
        exit;
    }

    /* ------------------------------------------------------------------
     | Small validation helpers shared by several controllers
     * ------------------------------------------------------------------ */

    protected function text_length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    protected function string_input(array $body, $key)
    {
        return isset($body[$key]) && is_string($body[$key]) ? trim($body[$key]) : '';
    }

    protected function normalized_email($value)
    {
        if (!is_string($value) || strlen($value) > 254) {
            return null;
        }
        $email = strtolower(trim($value));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function is_hex_id($value, $length)
    {
        return is_string($value) && preg_match('/^[a-f0-9]{' . (int) $length . '}$/', $value) === 1;
    }

    protected function monthly_rental_fee()
    {
        $configured = (string) config_item('pwesto_monthly_rent');
        if (!ctype_digit($configured) || (int) $configured < 1 || (int) $configured > 1000000) {
            $this->fail('The configured monthly stall rent is invalid. Contact the Pwesto administrator.', 503);
        }
        return (int) $configured;
    }
}
