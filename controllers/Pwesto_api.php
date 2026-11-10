<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'libraries/Pwesto_store.php';
require_once APP_DIR . 'libraries/Pwesto_cloudinary_storage.php';

class Pwesto_api extends Controller
{
    private $session;
    private $store;
    private $cloudinaryStorage;

    public function __construct()
    {
        parent::__construct();
        $this->session = $this->call->library('session');
        try {
            $this->store = new Pwesto_store();
        } catch (Throwable $error) {
            error_log('Pwesto database initialization failed: ' . $error->getMessage());
            $this->respond(['error' => 'Pwesto could not initialize its database. Check the database driver and connection settings; for SQLite, verify PDO_SQLite and writable storage.'], 503);
        }
    }

    public function session_info()
    {
        $this->require_method('GET');
        $this->ensure_csrf_token();
        $user = $this->current_user();
        $accountData = $user
            ? $this->store_call(function () use ($user) { return $this->store->accountSessionData($user['id']); }, 'Your account data could not be loaded.')
            : ['favorites' => [], 'appointments' => []];
        $this->respond([
            'user' => $user,
            'csrf_token' => $_SESSION['pwesto_csrf'],
            'favorites' => $accountData['favorites'],
            'appointments' => $accountData['appointments'],
        ]);
    }

    public function vendors()
    {
        $this->require_method('GET');
        $vendors = $this->store_call(function () { return $this->store->vendors(); }, 'Market stalls could not be loaded.');
        $this->respond(['vendors' => $vendors]);
    }

    public function products()
    {
        $this->require_method('GET');
        $vendorId = $_GET['vendor_id'] ?? '';
        if (!is_string($vendorId) || $vendorId === '') {
            $this->respond(['error' => 'Choose a market stall to view its products.'], 422);
        }
        $products = $this->store_call(function () use ($vendorId) {
            return $this->store->products($vendorId);
        }, 'Products could not be loaded.');
        $this->respond(['products' => $this->present_product_images($products)]);
    }

    public function product_image()
    {
        $this->require_method('GET');
        $productId = $_GET['product_id'] ?? '';
        if (!is_string($productId) || !preg_match('/^[a-f0-9]{32}$/', $productId)) {
            $this->respond(['error' => 'Choose a valid product image.'], 422);
        }
        $objectName = $this->store_call(function () use ($productId) {
            return $this->store->publicProductImage($productId);
        }, 'The product image could not be loaded.');
        $this->stream_product_image($objectName, false);
    }

    public function vendor_product_image()
    {
        $user = $this->require_vendor_workspace();
        $this->require_method('GET');
        $productId = $_GET['product_id'] ?? '';
        if (!is_string($productId) || !preg_match('/^[a-f0-9]{32}$/', $productId)) {
            $this->respond(['error' => 'Choose a valid product image.'], 422);
        }
        $objectName = $this->store_call(function () use ($productId, $user) {
            return $this->store->productImageForOwner($productId, $user['id']);
        }, 'Your product image could not be loaded.');
        $this->stream_product_image($objectName, true);
    }

    public function admin_product_image()
    {
        $this->require_role('admin');
        $this->require_method('GET');
        $productId = $_GET['product_id'] ?? '';
        if (!is_string($productId) || !preg_match('/^[a-f0-9]{32}$/', $productId)) {
            $this->respond(['error' => 'Choose a valid product image.'], 422);
        }
        $objectName = $this->store_call(function () use ($productId) {
            return $this->store->productImageForAdmin($productId);
        }, 'The product image could not be loaded.');
        $this->stream_product_image($objectName, true);
    }

    private function stream_product_image($objectName, $isPrivate)
    {
        if (!Pwesto_cloudinary_storage::isProductObject($objectName)) {
            $this->respond(['error' => 'That product image is not available.'], 404);
        }
        try {
            $content = $this->cloudinary_storage()->download($objectName);
        } catch (Throwable $error) {
            error_log('Pwesto product image download failed: ' . $error->getMessage());
            $this->respond(['error' => 'The product image could not be loaded from Cloudinary.'], 503);
        }
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo === false ? false : finfo_buffer($fileInfo, $content);
        if ($fileInfo !== false) {
            finfo_close($fileInfo);
        }
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $this->respond(['error' => 'That product image is not available.'], 404);
        }
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: ' . ($isPrivate ? 'private, no-store' : 'public, max-age=3600'));
        header('X-Content-Type-Options: nosniff');
        echo $content;
        exit;
    }

    public function vendor_products()
    {
        $user = $this->require_vendor_workspace();
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'GET') {
            $vendorId = $_GET['vendor_id'] ?? '';
            if (!is_string($vendorId) || $vendorId === '') {
                $this->respond(['error' => 'Choose one of your stalls.'], 422);
            }
            $products = $this->store_call(function () use ($vendorId, $user) {
                return $this->store->productsForOwner($vendorId, $user['id']);
            }, 'Your products could not be loaded.');
            $this->respond(['products' => $this->present_product_images($products, $user['id'])]);
        }

        $this->verify_csrf_token();
        if ($method === 'DELETE') {
            $body = $this->json_body();
            $productId = $body['product_id'] ?? '';
            if (!is_string($productId) || !preg_match('/^[a-f0-9]{32}$/', $productId)) {
                $this->respond(['error' => 'Choose a valid product to remove.'], 422);
            }
            $deleted = $this->store_call(function () use ($productId, $user) {
                return $this->store->deleteProduct($productId, $user['id']);
            }, 'The product could not be removed.');
            if (!$deleted) {
                $this->respond(['error' => 'That product is no longer available to your account.'], 404);
            }
            $this->delete_product_image($deleted['image']);
            $this->respond(['success' => true]);
        }
        if (!in_array($method, ['POST', 'PUT'], true)) {
            header('Allow: GET, POST, PUT, DELETE');
            $this->respond(['error' => 'Method not allowed.'], 405);
        }

        $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        $body = strpos($contentType, 'multipart/form-data') === 0 ? $_POST : $this->json_body();
        $product = $this->validated_product($body);
        $vendorId = $body['vendor_id'] ?? '';
        if (!is_string($vendorId) || $vendorId === '') {
            $this->respond(['error' => 'Choose one of your stalls.'], 422);
        }
        $imageUpload = $this->validate_product_image();
        $imageName = $imageUpload === null ? null : $this->persist_product_image($imageUpload, $vendorId);
        $isUpdate = $method === 'PUT' || !empty($body['product_id']);
        if ($isUpdate) {
            $productId = $body['product_id'] ?? '';
            if (!is_string($productId) || !preg_match('/^[a-f0-9]{32}$/', $productId)) {
                $this->delete_product_image($imageName);
                $this->respond(['error' => 'Choose a valid product to update.'], 422);
            }
            $updated = $this->store_call(function () use ($productId, $vendorId, $user, $product, $imageName) {
                try {
                    return $this->store->updateProduct($productId, $vendorId, $user['id'], $product, $imageName);
                } catch (Throwable $error) {
                    $this->delete_product_image($imageName);
                    throw $error;
                }
            }, 'The product could not be updated.');
            if (!$updated) {
                $this->delete_product_image($imageName);
                $this->respond(['error' => 'That product is no longer available to your account.'], 404);
            }
            if ($imageName !== null) {
                $this->delete_product_image($updated['previous_image']);
            }
            $this->respond(['success' => true]);
        }

        $productId = $this->store_call(function () use ($vendorId, $user, $product, $imageName) {
            try {
                return $this->store->createProduct($vendorId, $user['id'], $product, $imageName);
            } catch (Throwable $error) {
                $this->delete_product_image($imageName);
                throw $error;
            }
        }, 'The product could not be added.');
        if (!$productId) {
            $this->delete_product_image($imageName);
            $this->respond(['error' => 'Choose one of your own stalls before adding a product.'], 403);
        }
        $this->respond(['product_id' => $productId], 201);
    }

    public function orders()
    {
        $this->require_role('customer');
        $user = $this->current_user();
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'GET') {
            $orders = $this->store_call(function () use ($user) {
                return $this->store->ordersForCustomer($user['id']);
            }, 'Your orders could not be loaded.');
            $this->respond(['orders' => $orders]);
        }
        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $vendorId = $body['vendor_id'] ?? '';
        $items = $body['items'] ?? null;
        $deliveryMethod = $body['delivery_method'] ?? 'pickup';
        $paymentMethod = $body['payment_method'] ?? 'cash_on_pickup';
        $deliveryAddress = isset($body['delivery_address']) && is_string($body['delivery_address'])
            ? trim($body['delivery_address'])
            : '';
        $pickupAtInput = $body['pickup_at'] ?? '';
        if (!is_string($vendorId) || $vendorId === '' || !is_array($items) || !$items || count($items) > 30) {
            $this->respond(['error' => 'Choose a stall and at least one product to order.'], 422);
        }
        if (!is_string($deliveryMethod) || !in_array($deliveryMethod, ['pickup', 'delivery'], true)) {
            $this->respond(['error' => 'Choose pickup or delivery for your order.'], 422);
        }
        if (!is_string($paymentMethod) || !in_array($paymentMethod, ['cash_on_pickup', 'cash_to_vendor', 'gcash'], true)) {
            $this->respond(['error' => 'Choose cash payment or Xendit GCash checkout.'], 422);
        }
        if (($deliveryMethod === 'pickup' && $paymentMethod === 'cash_to_vendor')
            || ($deliveryMethod === 'delivery' && $paymentMethod === 'cash_on_pickup')) {
            $this->respond(['error' => 'Choose a cash payment option that matches your fulfillment method.'], 422);
        }
        if ($paymentMethod === 'gcash' && !$this->xendit_configured()) {
            $this->respond(['error' => 'Xendit GCash checkout is not configured. Set the matching XENDIT_MODE and XENDIT_SECRET_KEY, plus XENDIT_WEBHOOK_TOKEN, on the server.'], 503);
        }
        if ($deliveryMethod === 'delivery'
            && ($deliveryAddress === '' || $this->text_length($deliveryAddress) > 500)) {
            $this->respond(['error' => 'Enter a delivery address no longer than 500 characters.'], 422);
        }
        if ($deliveryMethod === 'pickup') {
            $deliveryAddress = null;
            if (!is_string($pickupAtInput) || $pickupAtInput === '') {
                $this->respond(['error' => 'Choose a pickup date and time.'], 422);
            }
            $pickupTimezone = new DateTimeZone('Asia/Manila');
            $pickupDateTime = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $pickupAtInput, $pickupTimezone);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$pickupDateTime
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $pickupDateTime->format('Y-m-d\TH:i') !== $pickupAtInput
                || $pickupDateTime <= new DateTimeImmutable('now', $pickupTimezone)) {
                $this->respond(['error' => 'Choose a valid future pickup date and time.'], 422);
            }
            $pickupAt = $pickupDateTime->format('Y-m-d H:i:s');
        } else {
            $pickupAt = null;
        }

        $validatedItems = [];
        $seenProducts = [];
        foreach ($items as $item) {
            if (!is_array($item)
                || !isset($item['product_id'], $item['quantity'])
                || !is_string($item['product_id'])
                || !preg_match('/^[a-f0-9]{32}$/', $item['product_id'])
                || !is_int($item['quantity'])
                || $item['quantity'] < 1
                || $item['quantity'] > 50
                || isset($seenProducts[$item['product_id']])) {
                $this->respond(['error' => 'Check the products and quantities in your cart.'], 422);
            }
            $seenProducts[$item['product_id']] = true;
            $validatedItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ];
        }
        $result = $this->store_call(function () use ($user, $vendorId, $validatedItems, $deliveryMethod, $deliveryAddress, $pickupAt, $paymentMethod) {
            return $this->store->createOrder(
                $user['id'],
                $vendorId,
                $validatedItems,
                $deliveryMethod,
                $deliveryAddress,
                $pickupAt,
                $paymentMethod
            );
        }, 'Your order could not be submitted. Please try again.');
        if (isset($result['error'])) {
            $this->respond(['error' => $result['error']], $result['status']);
        }
        if ($paymentMethod === 'gcash') {
            $amount = $result['total_cents'];
            if ($amount < 100) {
                $this->store_call(function () use ($result, $user) {
                    return $this->store->discardUnpaidGcashOrder($result['order_id'], $user['id']);
                }, 'The unpaid order could not be released.');
                $this->respond(['error' => 'Xendit requires an order total of at least ₱1.00.'], 422);
            }
            $externalId = 'pwesto-order-' . $result['order_id'];
            $response = $this->xendit_request('POST', '/v2/invoices', [
                'external_id' => $externalId,
                'amount' => $amount / 100,
                'currency' => 'PHP',
                'invoice_duration' => 86400,
                'description' => 'Pwesto product order — ' . $result['vendor_name'],
                'payer_email' => $result['customer_email'],
                'payment_methods' => ['GCASH'],
                'metadata' => [
                    'pwesto_order_id' => $result['order_id'],
                    'pwesto_payment_type' => 'order',
                ],
            ]);
            $invoice = $response['body'];
            $checkoutId = $invoice['id'] ?? '';
            $checkoutUrl = $invoice['invoice_url'] ?? '';
            if ($response['status'] < 200 || $response['status'] >= 300
                || !is_string($checkoutId) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $checkoutId)
                || !$this->is_xendit_checkout_url($checkoutUrl)
                || ($invoice['external_id'] ?? '') !== $externalId
                || $this->xendit_amount_cents($invoice['amount'] ?? null) !== $amount
                || ($invoice['currency'] ?? '') !== 'PHP') {
                $this->store_call(function () use ($result, $user) {
                    return $this->store->discardUnpaidGcashOrder($result['order_id'], $user['id']);
                }, 'The unpaid order could not be released.');
                error_log('Pwesto Xendit order invoice creation failed with HTTP ' . $response['status']);
                $this->respond(['error' => 'Xendit could not create a GCash checkout. Please try again.'], 502);
            }
            $saved = $this->store_call(function () use ($result, $user, $checkoutId, $checkoutUrl, $amount) {
                return $this->store->saveOrderCheckout(
                    $result['order_id'],
                    $user['id'],
                    $checkoutId,
                    $checkoutUrl,
                    $amount
                );
            }, 'The GCash checkout could not be saved.');
            if (!$saved) {
                $this->store_call(function () use ($result, $user) {
                    return $this->store->discardUnpaidGcashOrder($result['order_id'], $user['id']);
                }, 'The unpaid order could not be released.');
                $this->respond(['error' => 'The GCash checkout could not be attached to this order.'], 409);
            }
            $this->respond([
                'order_id' => $result['order_id'],
                'checkout_url' => $checkoutUrl,
                'payment_status' => 'pending',
            ], 201);
        }
        $emailSent = true;
        if ($deliveryMethod === 'delivery') {
            $emailSent = $this->sendDeliveryStatusEmail(
                $result['customer_email'],
                $result['customer_name'],
                $result['vendor_name'],
                $result['order_id'],
                'pending',
                $deliveryAddress
            );
        }
        $this->respond([
            'order_id' => $result['order_id'],
            'email_sent' => $emailSent,
            'payment_status' => 'due',
        ], 201);
    }

    public function vendor_orders()
    {
        $this->require_role('vendor');
        $user = $this->current_user();
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'GET') {
            $pendingEmailIds = $this->store_call(function () use ($user) {
                return $this->store->pendingVendorPaymentEmails($user['id']);
            }, 'Pending payment emails could not be loaded.');
            foreach ($pendingEmailIds as $orderId) {
                $this->sendVendorPaymentReceipt($orderId);
            }
            $orders = $this->store_call(function () use ($user) {
                return $this->store->ordersForOwner($user['id']);
            }, 'Customer orders could not be loaded.');
            $this->respond(['orders' => $orders]);
        }
        $this->require_method('PATCH');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $orderId = $body['order_id'] ?? '';
        $status = $body['status'] ?? '';
        if (!is_string($orderId) || !preg_match('/^[a-f0-9]{32}$/', $orderId)
            || !is_string($status)
            || !in_array($status, ['accepted', 'preparing', 'ready', 'out_for_delivery', 'completed', 'delivered', 'cancelled'], true)) {
            $this->respond(['error' => 'Choose a valid order and next status.'], 422);
        }
        $updated = $this->store_call(function () use ($orderId, $user, $status) {
            return $this->store->updateOrderStatus($orderId, $user['id'], $status);
        }, 'The order status could not be updated.');
        if (!$updated) {
            $this->respond(['error' => 'This order cannot move to that status or does not belong to your stall.'], 409);
        }
        $emailSent = true;
        if ($updated['delivery_method'] === 'delivery') {
            $emailSent = $this->sendDeliveryStatusEmail(
                $updated['customer_email'],
                $updated['customer_name'],
                $updated['vendor_name'],
                $updated['order_id'],
                $updated['status'],
                $updated['delivery_address']
            );
        }
        $this->respond(['success' => true, 'email_sent' => $emailSent]);
    }

    public function google_sign_in()
    {
        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $credential = $body['credential'] ?? '';
        $clientId = config_item('google_client_id') ?: '';

        if ($clientId === '') {
            $this->respond(['error' => 'Google sign-in is not configured. Set GOOGLE_CLIENT_ID on the server.'], 503);
        }
        if (!is_string($credential) || $credential === '' || strlen($credential) > 8192) {
            $this->respond(['error' => 'A valid Google sign-in credential is required.'], 400);
        }

        $claims = $this->verify_google_credential($credential);
        if ($claims === null) {
            $this->respond(['error' => 'Google could not verify this sign-in. Please try again.'], 401);
        }

        $googleUser = [
            'id' => $claims['sub'],
            'name' => $claims['name'],
            'email' => $claims['email'],
            'picture' => $claims['picture'],
            'role' => $this->isAdminEmail($claims['email']) ? 'admin' : 'customer',
        ];
        $user = $this->store_call(function () use ($googleUser) {
            return $this->store->saveUser($googleUser);
        }, 'Your account could not be saved. Please try again.');

        $this->start_user_session($user);
    }

    public function register()
    {
        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $email = $this->normalized_email($body['email'] ?? null);
        $name = isset($body['name']) && is_string($body['name']) ? trim($body['name']) : '';
        $password = $body['password'] ?? null;
        $confirmPassword = $body['confirm_password'] ?? null;
        $requestedRole = $body['role'] ?? 'customer';

        if ($email === null) {
            $this->respond(['error' => 'Enter a valid email address.'], 422);
        }
        if ($name === '' || $this->text_length($name) < 2 || $this->text_length($name) > 80) {
            $this->respond(['error' => 'Enter your name (2 to 80 characters).'], 422);
        }
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            $this->respond(['error' => 'Choose a password between 8 and 72 characters.'], 422);
        }
        if (!is_string($confirmPassword) || !hash_equals($password, $confirmPassword)) {
            $this->respond(['error' => 'Your passwords do not match.'], 422);
        }
        if (!is_string($requestedRole) || !in_array($requestedRole, ['customer', 'vendor'], true)) {
            $this->respond(['error' => 'Choose a customer or vendor account.'], 422);
        }
        $role = $this->isAdminEmail($email) ? 'admin' : $requestedRole;

        try {
            $user = $this->store->registerEmailUser($email, $name, $password, $role);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                $this->respond(['error' => 'An account with this email already exists. Sign in instead.'], 409);
            }
            error_log('Pwesto email account registration failed: ' . $error->getMessage());
            $this->respond(['error' => 'Your account could not be created. Please try again.'], 503);
        } catch (Throwable $error) {
            error_log('Pwesto email account registration failed: ' . $error->getMessage());
            $this->respond(['error' => 'Your account could not be created. Please try again.'], 503);
        }

        $this->start_user_session($user);
    }

    public function email_sign_in()
    {
        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $email = $this->normalized_email($body['email'] ?? null);
        $password = $body['password'] ?? null;
        if ($email === null || !is_string($password) || $password === '' || strlen($password) > 72) {
            $this->respond(['error' => 'Enter a valid email address and password.'], 422);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $allowed = $this->store_call(function () use ($email, $ip) {
            return $this->store->emailAttemptAllowed($email, $ip);
        }, 'Sign-in is temporarily unavailable. Please try again later.');
        if (!$allowed) {
            $this->respond(['error' => 'Too many sign-in attempts. Please wait 15 minutes before trying again.'], 429);
        }

        $user = $this->store_call(function () use ($email, $password) {
            return $this->store->authenticateEmailUser($email, $password);
        }, 'Sign-in is temporarily unavailable. Please try again later.');
        if ($user === null) {
            $this->store_call(function () use ($email, $ip) {
                $this->store->recordFailedEmailAttempt($email, $ip);
            }, 'Sign-in is temporarily unavailable. Please try again later.');
            $this->respond(['error' => 'The email or password is incorrect.'], 401);
        }

        $this->store_call(function () use ($email, $ip) {
            $this->store->clearEmailAttempts($email, $ip);
        }, 'Sign-in is temporarily unavailable. Please try again later.');
        if ($this->isAdminEmail($email)) {
            $user = $this->store_call(function () use ($user) {
                return $this->store->grantAdminRole($user['id']);
            }, 'Your administrator role could not be verified. Please try again.');
        }
        $this->start_user_session($user);
    }

    public function request_password_reset()
    {
        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $email = $this->normalized_email($body['email'] ?? null);
        $genericResponse = ['message' => 'If that email belongs to a Pwesto account, a password reset link will be sent.'];
        if ($email === null) {
            $this->respond($genericResponse);
        }

        require_once APP_DIR . 'libraries' . DIRECTORY_SEPARATOR . 'Pwesto_mailer.php';
        $mailer = new Pwesto_mailer();
        $baseUrl = $this->pwesto_app_url();
        $mailConfigurationError = $mailer->configurationError();
        if ($mailConfigurationError !== null || $baseUrl === null) {
            error_log(
                'Pwesto password reset unavailable: '
                . ($mailConfigurationError ?: 'PWESTO_APP_URL is invalid or not configured.')
            );
            $this->respond([
                'error' => $mailConfigurationError
                    ? $mailConfigurationError
                    : 'Set a valid PWESTO_APP_URL in the local .env, then restart PHP.',
            ], 503);
        }

        $reset = $this->store_call(function () use ($email) {
            return $this->store->createPasswordResetRequest($email);
        }, 'Password reset is temporarily unavailable. Please try again later.');
        if ($reset['status'] !== 'created') {
            error_log('Pwesto password reset not sent: ' . $reset['status'] . '.');
            $this->respond($genericResponse);
        }

        $resetUrl = $baseUrl . '/?password_reset=' . rawurlencode($reset['token']);
        $safeName = htmlspecialchars($reset['name'], ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $html = '<p>Hello ' . $safeName . ',</p><p>We received a request to reset your Pwesto password.</p>'
            . '<p><a href="' . $safeUrl . '">Reset your password</a></p>'
            . '<p>This link expires in one hour. If you did not request this, you can ignore this email.</p>';
        $text = "Hello " . $reset['name'] . ",\n\n"
            . "Reset your Pwesto password using this one-time link (expires in one hour):\n"
            . $resetUrl . "\n\nIf you did not request this, ignore this email.";
        try {
            $mailer->send($reset['email'], $reset['name'], 'Reset your Pwesto password', $html, $text);
            error_log('Pwesto password reset message accepted by Gmail SMTP.');
        } catch (Throwable $error) {
            $this->store_call(function () use ($reset) {
                $this->store->deletePasswordReset($reset['token_hash']);
                return true;
            }, 'Password reset is temporarily unavailable. Please try again later.');
            error_log('Pwesto password reset email failed: ' . $error->getMessage());
            $this->respond([
                'error' => 'The reset email could not be sent. Check the Gmail SMTP settings and use a Google App Password, not your regular account password.',
            ], 503);
        }
        $this->respond($genericResponse);
    }

    public function reset_password()
    {
        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $token = $body['token'] ?? '';
        $password = $body['password'] ?? null;
        $confirmation = $body['confirm_password'] ?? null;
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->respond(['error' => 'This password reset link is invalid or expired. Request a new one.'], 422);
        }
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            $this->respond(['error' => 'Choose a password between 8 and 72 characters.'], 422);
        }
        if (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
            $this->respond(['error' => 'Your passwords do not match.'], 422);
        }
        $reset = $this->store_call(function () use ($token, $password) {
            return $this->store->resetPassword($token, $password);
        }, 'Your password could not be reset. Please request a new link.');
        if (!$reset) {
            $this->respond(['error' => 'This password reset link is invalid or expired. Request a new one.'], 422);
        }
        $this->respond(['success' => true, 'message' => 'Your password has been updated. You can now sign in.']);
    }

    private function start_user_session(array $user)
    {
        $this->session->regenerate_on_login(true);
        $this->session->set_userdata('pwesto_user', $user);
        $this->session->set_userdata('pwesto_csrf', bin2hex(random_bytes(32)));
        $accountData = $this->store_call(function () use ($user) {
            return $this->store->accountSessionData($user['id']);
        }, 'Your account data could not be loaded.');
        $this->respond([
            'user' => $user,
            'csrf_token' => $_SESSION['pwesto_csrf'],
            'favorites' => $accountData['favorites'],
            'appointments' => $accountData['appointments'],
        ]);
    }

    private function normalized_email($value)
    {
        if (!is_string($value) || strlen($value) > 254) {
            return null;
        }
        $email = strtolower(trim($value));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function text_length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function validated_product(array $body)
    {
        $name = isset($body['name']) && is_string($body['name']) ? trim($body['name']) : '';
        $description = isset($body['description']) && is_string($body['description'])
            ? trim($body['description'])
            : '';
        $price = $body['price'] ?? null;
        $stock = $body['stock'] ?? null;

        if ($this->text_length($name) < 2 || $this->text_length($name) > 100) {
            $this->respond(['error' => 'Enter a product name between 2 and 100 characters.'], 422);
        }
        if ($this->text_length($description) > 500) {
            $this->respond(['error' => 'Product details must be 500 characters or fewer.'], 422);
        }
        if (!is_string($price) && !is_int($price) && !is_float($price)) {
            $this->respond(['error' => 'Enter a valid product price in pesos.'], 422);
        }
        $price = (string) $price;
        if (!preg_match('/^(?:0|[1-9]\d{0,7})(?:\.\d{1,2})?$/', $price) || (float) $price <= 0) {
            $this->respond(['error' => 'Enter a price greater than ₱0 with up to 2 decimal places.'], 422);
        }
        if (is_string($stock) && preg_match('/^(?:0|[1-9]\d{0,6})$/', $stock)) {
            $stock = (int) $stock;
        }
        if (!is_int($stock) || $stock < 0 || $stock > 1000000) {
            $this->respond(['error' => 'Enter available stock as a whole number from 0 to 1,000,000.'], 422);
        }
        $parts = explode('.', $price, 2);
        $priceCents = ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');

        return [
            'name' => $name,
            'description' => $description,
            'price_cents' => $priceCents,
            'stock' => $stock,
        ];
    }

    private function validate_product_image()
    {
        if (!isset($_FILES['image']) || !is_array($_FILES['image'])
            || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $file = $_FILES['image'];
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
            || !is_uploaded_file($file['tmp_name'])) {
            if (in_array($file['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $this->respond(['error' => 'Product images must be no larger than 5 MB.'], 413);
            }
            $this->respond(['error' => 'The selected product image could not be uploaded. Choose a valid image and try again.'], 422);
        }
        $size = filesize($file['tmp_name']);
        if ($size === false || $size < 1 || $size > 5242880) {
            $this->respond(['error' => 'Product images must be no larger than 5 MB.'], 413);
        }
        if (!function_exists('finfo_open') || !function_exists('getimagesize')) {
            $this->respond(['error' => 'Image validation is unavailable on this server.'], 503);
        }
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo === false) {
            $this->respond(['error' => 'Image validation is unavailable on this server.'], 503);
        }
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);
        $allowed = [
            'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
            'image/png' => ['png', IMAGETYPE_PNG],
            'image/webp' => ['webp', IMAGETYPE_WEBP],
        ];
        if (!is_string($mimeType) || !isset($allowed[$mimeType])) {
            $this->respond(['error' => 'Product images must be JPEG, PNG, or WebP files.'], 422);
        }
        $image = @getimagesize($file['tmp_name']);
        if ($image === false || $image[2] !== $allowed[$mimeType][1]
            || $image[0] < 1 || $image[1] < 1 || $image[0] > 5000 || $image[1] > 5000
            || $image[0] * $image[1] > 20000000) {
            $this->respond(['error' => 'The product image is invalid or exceeds 5,000 × 5,000 pixels.'], 422);
        }
        return [
            'tmp_name' => $file['tmp_name'],
            'extension' => $allowed[$mimeType][0],
            'mime_type' => $mimeType,
        ];
    }

    private function persist_product_image(array $image, $vendorId)
    {
        $objectName = 'cloudinary/products/' . $vendorId . '/' . bin2hex(random_bytes(16));
        try {
            return $this->cloudinary_storage()->upload($image['tmp_name'], $objectName);
        } catch (Throwable $error) {
            error_log('Pwesto product image upload failed: ' . $error->getMessage());
            $this->respond(['error' => 'The product image could not be saved to Cloudinary. Check its server configuration.'], 503);
        }
    }

    private function product_image_directory()
    {
        return ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'pwesto-products';
    }

    private function delete_product_image($fileName)
    {
        if (Pwesto_cloudinary_storage::isProductObject($fileName)) {
            try {
                $this->cloudinary_storage()->delete($fileName);
            } catch (Throwable $error) {
                error_log('Pwesto product image cleanup failed: ' . $error->getMessage());
            }
            return;
        }
        if (!is_string($fileName) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $fileName)) {
            return;
        }
        $path = $this->product_image_directory() . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($path) && !unlink($path)) {
            error_log('Pwesto product image could not be removed: ' . $path);
        }
    }

    private function present_product_images(array $products, $ownerId = null)
    {
        foreach ($products as &$product) {
            $product['is_sample'] = !empty($product['is_sample']);
            $image = $product['image'] ?? null;
            if (Pwesto_cloudinary_storage::isProductObject($image)
                && is_string($product['id'] ?? null)
                && preg_match('/^[a-f0-9]{32}$/', $product['id'])) {
                $imagePath = $ownerId === null ? '/api/products/image' : '/api/vendors/products/image';
                $product['image_url'] = $imagePath . '?product_id=' . rawurlencode($product['id']);
            } else {
                $product['image_url'] = is_string($image) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $image)
                ? $basePath . '/uploads/pwesto-products/' . $image
                : null;
            }
        }
        unset($product);
        return $products;
    }

    public function logout()
    {
        $this->require_method('POST');
        $this->verify_csrf_token();
        $this->session->unset_userdata('pwesto_user');
        $this->session->set_userdata('pwesto_csrf', bin2hex(random_bytes(32)));
        $this->respond(['user' => null, 'csrf_token' => $_SESSION['pwesto_csrf']]);
    }

    public function favorites()
    {
        $this->require_user();
        $user = $this->current_user();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            $favorites = $this->store_call(function () use ($user) { return $this->store->favorites($user['id']); }, 'Your saved stalls could not be loaded.');
            $this->respond(['favorites' => $favorites]);
        }

        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $vendorId = $body['vendor_id'] ?? null;
        $saved = $body['saved'] ?? null;

        if (!is_string($vendorId) || $vendorId === '' || !is_bool($saved)) {
            $this->respond(['error' => 'A stall and saved status are required.'], 422);
        }
        $updated = $this->store_call(function () use ($user, $vendorId, $saved) {
            return $this->store->setFavorite($user['id'], $vendorId, $saved);
        }, 'Your saved stalls could not be updated.');
        if (!$updated) {
            $this->respond(['error' => 'That market stall is not available.'], 404);
        }
        $favorites = $this->store_call(function () use ($user) { return $this->store->favorites($user['id']); }, 'Your saved stalls could not be loaded.');
        $this->respond(['favorites' => $favorites]);
    }

    public function appointments()
    {
        $this->require_user();
        $user = $this->current_user();
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'GET') {
            $appointments = $this->store_call(function () use ($user) { return $this->store->appointments($user['id']); }, 'Your visits could not be loaded.');
            $this->respond(['appointments' => $appointments]);
        }
        if ($method === 'DELETE') {
            $this->verify_csrf_token();
            $body = $this->json_body();
            $id = $body['id'] ?? '';
            if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/', $id)) {
                $this->respond(['error' => 'Choose a valid visit to cancel.'], 422);
            }
            $cancelled = $this->store_call(function () use ($user, $id) {
                return $this->store->cancelAppointment($user['id'], $id);
            }, 'Your visit could not be cancelled. Please try again.');
            if (!$cancelled) {
                $this->respond(['error' => 'This visit could not be cancelled. It may already be cancelled.'], 404);
            }
            $appointments = $this->store_call(function () use ($user) { return $this->store->appointments($user['id']); }, 'Your visits could not be loaded.');
            $this->respond(['appointments' => $appointments]);
        }

        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $vendorId = $body['vendor_id'] ?? '';
        $visitDate = $body['visit_date'] ?? '';
        $visitTime = $body['visit_time'] ?? '';

        $vendor = is_string($vendorId)
            ? $this->store_call(function () use ($vendorId) { return $this->store->vendor($vendorId); }, 'The selected stall could not be loaded.')
            : null;
        if ($vendor === null) {
            $this->respond(['error' => 'Choose a valid market stall.'], 422);
        }
        if (!is_string($visitDate) || !$this->is_valid_visit_date($visitDate)) {
            $this->respond(['error' => 'Choose a valid visit date that is today or later.'], 422);
        }
        $validTimes = ['8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM', '1:00 PM', '2:00 PM', '3:00 PM'];
        if (!is_string($visitTime) || !in_array($visitTime, $validTimes, true)) {
            $this->respond(['error' => 'Choose an available visit time.'], 422);
        }
        $this->store_call(function () use ($user, $vendorId, $visitDate, $visitTime) {
            return $this->store->createAppointment($user['id'], $vendorId, $visitDate, $visitTime);
        }, 'Your visit could not be saved. Please try again.');
        $appointments = $this->store_call(function () use ($user) { return $this->store->appointments($user['id']); }, 'Your visits could not be loaded.');
        $this->respond(['appointments' => $appointments], 201);
    }

    public function my_vendors()
    {
        $this->require_user();
        $this->require_method('GET');
        $user = $this->current_user();
        $vendors = $this->store_call(function () use ($user) {
            return $this->store->vendorsForOwner($user['id']);
        }, 'Your stall applications could not be loaded.');
        $this->respond([
            'vendors' => $vendors,
            'rental_fee' => $this->monthly_rental_fee(),
            'payment_configured' => $this->xendit_configured(),
        ]);
    }

    public function update_profile()
    {
        $this->require_user();
        $this->require_method('POST');
        $this->verify_csrf_token();
        $user = $this->current_user();
        $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
        if ($this->text_length($name) < 2 || $this->text_length($name) > 80) {
            $this->respond(['error' => 'Enter a name between 2 and 80 characters.'], 422);
        }

        $previousPicture = $user['picture'] ?? '';
        $picture = $previousPicture;
        if (isset($_FILES['picture'])) {
            $file = $_FILES['picture'];
            if (!is_array($file) || !isset($file['error']) || !is_int($file['error'])) {
                $this->respond(['error' => 'The profile photo upload is invalid. Choose it again and retry.'], 400);
            }
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $this->respond(['error' => 'Choose a profile photo smaller than 2 MB.'], 413);
                }
                if (!isset($file['tmp_name'], $file['size'])
                    || $file['error'] !== UPLOAD_ERR_OK
                    || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                    $this->respond(['error' => 'The profile photo could not be uploaded. Choose it again and retry.'], 400);
                }
                $actualSize = filesize($file['tmp_name']);
                if ($actualSize === false || $actualSize < 1 || $actualSize > 2097152) {
                    $this->respond(['error' => 'Choose a profile photo smaller than 2 MB.'], 413);
                }

                $imageTypes = [
                    'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
                    'image/png' => ['png', IMAGETYPE_PNG],
                    'image/webp' => ['webp', IMAGETYPE_WEBP],
                ];
                if (!function_exists('finfo_open') || !function_exists('getimagesize')) {
                    $this->respond(['error' => 'Profile photo validation is unavailable on this server.'], 503);
                }
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($fileInfo === false) {
                    $this->respond(['error' => 'Profile photo validation is unavailable on this server.'], 503);
                }
                $mimeType = finfo_file($fileInfo, $file['tmp_name']);
                finfo_close($fileInfo);
                $imageInfo = @getimagesize($file['tmp_name']);
                if (!is_string($mimeType) || !isset($imageTypes[$mimeType])
                    || $imageInfo === false || $imageInfo[2] !== $imageTypes[$mimeType][1]
                    || $imageInfo[0] < 1 || $imageInfo[1] < 1
                    || $imageInfo[0] > 5000 || $imageInfo[1] > 5000
                    || $imageInfo[0] * $imageInfo[1] > 20000000) {
                    $this->respond(['error' => 'Use a valid JPEG, PNG, or WebP image up to 5,000 × 5,000 pixels.'], 422);
                }

                $uploadDirectory = ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'uploads'
                    . DIRECTORY_SEPARATOR . 'pwesto-profile';
                if (!is_dir($uploadDirectory)
                    && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $this->respond(['error' => 'The profile photo folder could not be created.'], 503);
                }
                $fileName = bin2hex(random_bytes(16)) . '.' . $imageTypes[$mimeType][0];
                if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . DIRECTORY_SEPARATOR . $fileName)) {
                    $this->respond(['error' => 'The profile photo could not be saved. Check server upload permissions.'], 503);
                }
                $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
                $basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
                $picture = $basePath . '/uploads/pwesto-profile/' . $fileName;
            }
        }

        try {
            $updatedUser = $this->store->updateUserProfile($user['id'], $name, $picture);
        } catch (Throwable $error) {
            if ($picture !== $previousPicture) {
                $this->delete_uploaded_profile_picture($picture);
            }
            error_log('Pwesto profile update failed: ' . $error->getMessage());
            $this->respond(['error' => 'Your profile could not be updated. Please try again.'], 503);
        }
        $this->session->set_userdata('pwesto_user', $updatedUser);
        if ($picture !== $previousPicture) {
            $this->delete_uploaded_profile_picture($previousPicture);
        }
        $this->respond(['user' => $updatedUser]);
    }

    public function messages()
    {
        $this->require_user();
        $user = $this->current_user();
        if (!in_array($user['role'] ?? 'customer', ['customer', 'vendor'], true)) {
            $this->respond(['error' => 'Messaging is available to customers and vendors.'], 403);
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'GET') {
            $conversations = $this->store_call(function () use ($user) {
                return $this->store->conversationsForUser($user['id']);
            }, 'Your messages could not be loaded.');
            $this->respond(['conversations' => $conversations]);
        }

        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        $message = isset($body['message']) && is_string($body['message']) ? trim($body['message']) : '';
        if ($message === '' || $this->text_length($message) > 2000) {
            $this->respond(['error' => 'Write a message between 1 and 2,000 characters.'], 422);
        }

        $conversationId = $body['conversation_id'] ?? null;
        if ($conversationId === null) {
            $vendorId = $body['vendor_id'] ?? '';
            if (($user['role'] ?? 'customer') !== 'customer'
                || !is_string($vendorId) || !preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $vendorId)) {
                $this->respond(['error' => 'Choose an available vendor to start a conversation.'], 422);
            }
            $conversationId = $this->store_call(function () use ($user, $vendorId) {
                return $this->store->startConversation($user['id'], $vendorId);
            }, 'A conversation with this stall could not be started.');
            if ($conversationId === null) {
                $this->respond(['error' => 'This stall is not available for direct messages.'], 404);
            }
        } elseif (!is_string($conversationId) || !preg_match('/^[a-f0-9]{32}$/', $conversationId)) {
            $this->respond(['error' => 'Choose a valid conversation.'], 422);
        }

        $sent = $this->store_call(function () use ($conversationId, $user, $message) {
            return $this->store->sendConversationMessage($conversationId, $user['id'], $message);
        }, 'Your message could not be sent.');
        if (!$sent) {
            $this->respond(['error' => 'This conversation is not available to your account.'], 404);
        }
        $messages = $this->store_call(function () use ($conversationId, $user) {
            return $this->store->conversationMessages($conversationId, $user['id']);
        }, 'Your conversation could not be loaded.');
        $this->respond(['conversation_id' => $conversationId, 'messages' => $messages], 201);
    }

    public function notifications()
    {
        $this->require_user();
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if (!in_array($method, ['GET', 'POST'], true)) {
            $this->require_method('GET');
        }
        $this->require_method($method);
        $user = $this->current_user();
        if ($method === 'GET') {
            $result = $this->store_call(function () use ($user) {
                return $this->store->notificationsForUser($user['id']);
            }, 'Your notifications could not be loaded.');
            $this->respond($result);
        }

        $this->verify_csrf_token();
        $body = $this->json_body();
        $action = $body['action'] ?? '';
        if ($action === 'mark_all_read') {
            $this->store_call(function () use ($user) {
                $this->store->markAllNotificationsRead($user['id']);
                return true;
            }, 'Your notifications could not be updated.');
        } elseif ($action === 'mark_read'
            && isset($body['notification_id'])
            && is_string($body['notification_id'])
            && preg_match('/^[a-f0-9]{32}$/', $body['notification_id'])) {
            $marked = $this->store_call(function () use ($user, $body) {
                return $this->store->markNotificationRead($user['id'], $body['notification_id']);
            }, 'Your notification could not be updated.');
            if (!$marked) {
                $this->respond(['error' => 'That notification is not available or has already been read.'], 404);
            }
        } else {
            $this->respond(['error' => 'Choose a valid notification action.'], 422);
        }

        $result = $this->store_call(function () use ($user) {
            return $this->store->notificationsForUser($user['id']);
        }, 'Your notifications were updated but could not be refreshed.');
        $this->respond($result);
    }

    public function message_thread()
    {
        $this->require_user();
        $user = $this->current_user();
        if (!in_array($user['role'] ?? 'customer', ['customer', 'vendor'], true)) {
            $this->respond(['error' => 'Messaging is available to customers and vendors.'], 403);
        }
        $this->require_method('GET');
        $conversationId = $_GET['conversation_id'] ?? '';
        if (!is_string($conversationId) || !preg_match('/^[a-f0-9]{32}$/', $conversationId)) {
            $this->respond(['error' => 'Choose a valid conversation.'], 422);
        }
        $messages = $this->store_call(function () use ($conversationId, $user) {
            return $this->store->conversationMessages($conversationId, $user['id']);
        }, 'This conversation could not be loaded.');
        if ($messages === null) {
            $this->respond(['error' => 'This conversation is not available to your account.'], 404);
        }
        $this->respond(['messages' => $messages]);
    }

    public function create_vendor()
    {
        $this->require_user();
        $this->require_method('POST');
        $this->verify_csrf_token();
        $this->reject_oversized_form_request();
        $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
        $category = $_POST['category'] ?? '';
        $market = $_POST['market'] ?? '';
        $specialty = isset($_POST['specialty']) && is_string($_POST['specialty']) ? trim($_POST['specialty']) : '';
        $validCategories = ['Seafood', 'Meat', 'Vegetables', 'Fruits', 'Pantry', 'Flowers'];

        if ($this->text_length($name) < 2 || $this->text_length($name) > 100) {
            $this->respond(['error' => 'Enter a stall name between 2 and 100 characters.'], 422);
        }
        if (!is_string($category) || !in_array($category, $validCategories, true)) {
            $this->respond(['error' => 'Choose a valid stall category.'], 422);
        }
        $markets = $this->store_call(function () { return $this->store->markets(); }, 'Market locations could not be loaded.');
        if (!is_string($market) || !in_array($market, $markets, true)) {
            $this->respond(['error' => 'Choose a supported public market.'], 422);
        }
        if ($this->text_length($specialty) < 5 || $this->text_length($specialty) > 240) {
            $this->respond(['error' => 'Describe your specialties in 5 to 240 characters.'], 422);
        }

        $rentalFee = $this->monthly_rental_fee();
        $paymentConfigured = $this->xendit_configured();
        $documents = [
            'application_document' => $this->validate_private_document('application_document', 'document'),
            'cedula_document' => $this->validate_private_document('cedula_document', 'document'),
            'photo_document' => $this->validate_private_document('photo_document', 'photo'),
        ];
        $vendorId = bin2hex(random_bytes(12));
        $storedDocuments = $this->persist_private_documents($documents, $vendorId);

        $user = $this->current_user();
        try {
            $vendorId = $this->store->createVendor([
                'id' => $vendorId,
                'owner_id' => $user['id'],
                'name' => $name,
                'category' => $category,
                'market' => $market,
                'specialty' => $specialty,
                'application_document' => $storedDocuments['application_document'],
                'cedula_document' => $storedDocuments['cedula_document'],
                'photo_document' => $storedDocuments['photo_document'],
            ]);
        } catch (Throwable $error) {
            $this->delete_private_documents($storedDocuments);
            error_log('Pwesto vendor application submission failed: ' . $error->getMessage());
            $this->respond(['error' => 'Your stall application could not be submitted. Please try again.'], 503);
        }
        $vendors = $this->store_call(function () use ($user) {
            return $this->store->vendorsForOwner($user['id']);
        }, 'Your stall application was submitted but could not be refreshed. Reload the dashboard.');
        $this->respond([
            'vendor_id' => $vendorId,
            'vendors' => $vendors,
            'rental_fee' => $rentalFee,
            'payment_configured' => $paymentConfigured,
        ], 201);
    }

    public function update_vendor_documents()
    {
        $this->require_user();
        $this->require_method('POST');
        $this->verify_csrf_token();
        $this->reject_oversized_form_request();
        $user = $this->current_user();
        $vendorId = $_POST['vendor_id'] ?? '';
        if (!is_string($vendorId) || !preg_match('/^[a-f0-9]{24}$/', $vendorId)) {
            $this->respond(['error' => 'Choose a valid stall application.'], 422);
        }
        $vendor = $this->store_call(function () use ($vendorId, $user) {
            return $this->store->vendorForOwner($vendorId, $user['id']);
        }, 'Your stall application could not be loaded.');
        if ($vendor === null) {
            $this->respond(['error' => 'That stall application is not available to your account.'], 404);
        }
        $documents = [];
        foreach ([
            'application_document' => 'document',
            'cedula_document' => 'document',
            'photo_document' => 'photo',
        ] as $field => $kind) {
            $document = $this->validate_private_document($field, $kind, false);
            if ($document !== null) {
                $documents[$field] = $document;
            }
        }
        if (!$documents) {
            $this->respond(['error' => 'Choose at least one document to upload.'], 422);
        }
        $storedDocuments = $this->persist_private_documents($documents, $vendorId);
        try {
            $previous = $this->store->updateVendorDocuments($vendorId, $user['id'], $storedDocuments);
        } catch (Throwable $error) {
            $this->delete_private_documents($storedDocuments);
            error_log('Pwesto vendor requirement update failed: ' . $error->getMessage());
            $this->respond(['error' => 'Your requirements could not be updated. Please try again.'], 503);
        }
        if ($previous === false) {
            $this->delete_private_documents($storedDocuments);
            $this->respond(['error' => 'That stall application is not available to your account.'], 404);
        }
        $this->delete_private_documents($previous);
        $vendors = $this->store_call(function () use ($user) {
            return $this->store->vendorsForOwner($user['id']);
        }, 'Your documents were saved but the application could not be refreshed.');
        $this->respond(['vendors' => $vendors]);
    }

    public function rental_payment()
    {
        $this->require_user();
        $this->require_method('POST');
        $this->verify_csrf_token();
        $user = $this->current_user();
        if (!in_array($user['role'] ?? '', ['vendor', 'customer'], true)) {
            $this->respond(['error' => 'This account cannot submit a stall rental payment.'], 403);
        }
        $body = $this->json_body();
        $vendorId = $body['vendor_id'] ?? '';
        if (!is_string($vendorId) || !preg_match('/^[a-f0-9]{24}$/', $vendorId)) {
            $this->respond(['error' => 'Choose a valid stall application.'], 422);
        }
        $vendor = $this->store_call(function () use ($vendorId, $user) {
            return $this->store->vendorForOwner($vendorId, $user['id']);
        }, 'Your stall application could not be loaded.');
        if ($vendor === null) {
            $this->respond(['error' => 'That stall application is not available to your account.'], 404);
        }
        if (empty($vendor['has_application']) || empty($vendor['has_cedula']) || empty($vendor['has_photo'])) {
            $this->respond(['error' => 'Submit all required documents before starting rental payment.'], 422);
        }
        if ($vendor['rental_payment_status'] === 'paid') {
            $this->respond(['payment_status' => 'paid']);
        }
        if (!empty($vendor['rental_checkout_url'])) {
            $this->respond([
                'checkout_url' => $vendor['rental_checkout_url'],
                'payment_status' => $vendor['rental_payment_status'],
            ]);
        }
        if (!$this->xendit_configured()) {
            $this->respond(['error' => 'Online payment is not configured for the selected Xendit mode. Ask the Pwesto administrator to set matching server credentials.'], 503);
        }

        $amount = $this->monthly_rental_fee();
        $externalId = 'pwesto-rent-' . $vendorId . '-' . bin2hex(random_bytes(6));
        $response = $this->xendit_request('POST', '/v2/invoices', [
            'external_id' => $externalId,
            'amount' => $amount,
            'currency' => 'PHP',
            'invoice_duration' => 86400,
            'description' => 'Pwesto stall rental — one month',
            'payment_methods' => ['GCASH'],
            'metadata' => [
                'pwesto_vendor_id' => $vendorId,
                'pwesto_payment_type' => 'rental',
            ],
        ]);
        $invoice = $response['body'];
        $checkoutId = $invoice['id'] ?? '';
        $checkoutUrl = $invoice['invoice_url'] ?? '';
        if ($response['status'] < 200 || $response['status'] >= 300
            || !is_string($checkoutId) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $checkoutId)
            || !$this->is_xendit_checkout_url($checkoutUrl)
            || ($invoice['external_id'] ?? '') !== $externalId
            || $this->xendit_amount_cents($invoice['amount'] ?? null) !== $amount * 100
            || ($invoice['currency'] ?? '') !== 'PHP') {
            error_log('Pwesto Xendit rental invoice creation failed with HTTP ' . $response['status']);
            $this->respond(['error' => 'Xendit could not create a checkout. Please try again or contact the administrator.'], 502);
        }
        $saved = $this->store_call(function () use ($vendorId, $user, $checkoutId, $checkoutUrl, $amount) {
            return $this->store->saveRentalCheckout($vendorId, $user['id'], $checkoutId, $checkoutUrl, $amount * 100);
        }, 'The rental checkout could not be saved.');
        if (!$saved) {
            $this->respond(['error' => 'The rental checkout could not be attached to this application.'], 409);
        }
        $this->respond(['checkout_url' => $checkoutUrl, 'payment_status' => 'pending']);
    }

    public function check_rental_payment()
    {
        $this->require_user();
        $this->require_method('POST');
        $this->verify_csrf_token();
        $user = $this->current_user();
        $body = $this->json_body();
        $vendorId = $body['vendor_id'] ?? '';
        if (!is_string($vendorId) || !preg_match('/^[a-f0-9]{24}$/', $vendorId)) {
            $this->respond(['error' => 'Choose a valid stall application.'], 422);
        }
        $vendor = $this->store_call(function () use ($vendorId, $user) {
            return $this->store->vendorForOwner($vendorId, $user['id']);
        }, 'Your stall application could not be loaded.');
        if ($vendor === null) {
            $this->respond(['error' => 'That stall application is not available to your account.'], 404);
        }
        if ($vendor['rental_payment_status'] === 'paid') {
            $this->respond(['payment_status' => 'paid']);
        }
        if (!$this->xendit_configured()) {
            $this->respond(['error' => 'Xendit checkout is not configured on the server.'], 503);
        }
        $checkoutId = $vendor['rental_checkout_id'] ?? '';
        $amount = $vendor['rental_checkout_amount'] ?? null;
        if (!is_string($checkoutId) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $checkoutId)
            || !is_numeric($amount) || (int) $amount < 100) {
            $this->respond(['error' => 'The Xendit invoice is not attached to this rental application.'], 409);
        }
        $response = $this->xendit_request('GET', '/v2/invoices/' . rawurlencode($checkoutId));
        if ($response['status'] < 200 || $response['status'] >= 300) {
            error_log('Pwesto Xendit rental payment check failed with HTTP ' . $response['status']);
            $this->respond(['error' => 'Xendit could not verify this payment. Try again shortly.'], 502);
        }
        $invoice = $response['body'];
        $externalIdPattern = '/^pwesto-rent-' . preg_quote($vendorId, '/') . '-[a-f0-9]{12}$/';
        if (($invoice['id'] ?? '') !== $checkoutId
            || !is_string($invoice['external_id'] ?? null)
            || !preg_match($externalIdPattern, $invoice['external_id'])
            || $this->xendit_amount_cents($invoice['amount'] ?? null) !== (int) $amount
            || ($invoice['currency'] ?? '') !== 'PHP') {
            $this->respond(['error' => 'The Xendit invoice does not match this rental application. Contact the Pwesto administrator.'], 409);
        }
        if (($invoice['status'] ?? '') !== 'PAID') {
            $this->respond(['payment_status' => 'pending']);
        }
        $recorded = $this->store_call(function () use ($vendorId, $checkoutId, $amount) {
            return $this->store->markRentalPaidByCheckout($vendorId, $checkoutId, (int) $amount);
        }, 'The confirmed stall rental payment could not be recorded.');
        if (!$recorded) {
            $alreadyRecorded = $this->store_call(function () use ($vendorId, $checkoutId, $amount) {
                return $this->store->rentalPaymentRecorded($vendorId, $checkoutId, (int) $amount);
            }, 'The Xendit rental payment could not be verified.');
            if (!$alreadyRecorded) {
                $this->respond(['error' => 'The payment no longer matches this rental application. Contact the Pwesto administrator.'], 409);
            }
        }
        $this->respond(['payment_status' => 'paid']);
    }

    public function xendit_webhook()
    {
        $this->require_method('POST');
        $callbackToken = getenv('XENDIT_WEBHOOK_TOKEN');
        $providedToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
        if (!is_string($callbackToken) || trim($callbackToken) === '') {
            error_log('Pwesto Xendit callback token is not configured.');
            $this->respond(['error' => 'Xendit webhook is not configured.'], 503);
        }
        if (!is_string($providedToken) || !hash_equals(trim($callbackToken), trim($providedToken))) {
            $this->respond(['error' => 'Xendit webhook authentication failed.'], 401);
        }
        $rawBody = file_get_contents('php://input', false, null, 0, 1048577);
        if (!is_string($rawBody) || $rawBody === '' || strlen($rawBody) > 1048576) {
            $this->respond(['error' => 'Invalid webhook request body.'], 413);
        }
        $event = json_decode($rawBody, true);
        if (!is_array($event)) {
            $this->respond(['error' => 'Invalid webhook request body.'], 400);
        }
        if (($event['status'] ?? '') !== 'PAID') {
            $this->respond(['received' => true]);
        }

        $checkoutId = $event['id'] ?? '';
        $externalId = $event['external_id'] ?? '';
        $amount = $this->xendit_amount_cents($event['amount'] ?? null);
        if (!is_string($checkoutId) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $checkoutId)
            || !is_string($externalId) || $externalId === ''
            || !is_int($amount) || $amount < 100
            || ($event['currency'] ?? '') !== 'PHP') {
            $this->respond(['error' => 'Xendit invoice details are invalid.'], 422);
        }
        if (preg_match('/^pwesto-order-([a-f0-9]{32})$/', $externalId, $matches)) {
            $orderId = $matches[1];
            $updated = $this->store_call(function () use ($orderId, $checkoutId, $amount) {
                return $this->store->markOrderPaidByCheckout($orderId, $checkoutId, $amount);
            }, 'The confirmed GCash order payment could not be recorded.');
            if (!$updated) {
                $this->respond(['error' => 'No matching unpaid GCash order was found.'], 404);
            }
            $this->sendVendorPaymentReceipt($orderId);
            $this->respond(['received' => true]);
        }
        if (!preg_match('/^pwesto-rent-([a-f0-9]{24})-[a-f0-9]{12}$/', $externalId, $matches)) {
            $this->respond(['error' => 'Xendit invoice does not match an expected Pwesto payment.'], 422);
        }
        $vendorId = $matches[1];
        $updated = $this->store_call(function () use ($vendorId, $checkoutId, $amount) {
            return $this->store->markRentalPaidByCheckout($vendorId, $checkoutId, $amount);
        }, 'The confirmed Xendit payment could not be recorded.');
        if (!$updated) {
            $alreadyRecorded = $this->store_call(function () use ($vendorId, $checkoutId, $amount) {
                return $this->store->rentalPaymentRecorded($vendorId, $checkoutId, $amount);
            }, 'The Xendit payment could not be verified.');
            if (!$alreadyRecorded) {
                $this->respond(['error' => 'No matching stall application was found for this payment.'], 404);
            }
        }
        $this->respond(['received' => true]);
    }

    public function vendor_document()
    {
        $this->require_role('admin');
        $this->require_method('GET');
        $vendorId = $_GET['vendor_id'] ?? '';
        $kind = $_GET['kind'] ?? '';
        if (!is_string($vendorId) || !preg_match('/^[a-f0-9]{24}$/', $vendorId)
            || !is_string($kind) || !in_array($kind, ['application', 'cedula', 'photo'], true)) {
            $this->respond(['error' => 'Choose a valid vendor requirement document.'], 422);
        }
        $this->stream_vendor_document($vendorId, $kind);
    }

    public function admin_storage()
    {
        $this->require_role('admin');
        $this->require_method('GET');
        $files = $this->store_call(function () {
            return $this->store->adminStorageFiles();
        }, 'Storage files could not be loaded.');
        $apiBase = '/api';

        $products = [];
        foreach ($files['products'] as $product) {
            $image = $product['image'] ?? '';
            if (Pwesto_cloudinary_storage::isProductObject($image)) {
                $imageUrl = $apiBase . '/admin/product-image?product_id=' . rawurlencode($product['id']);
            } elseif (is_string($image) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $image)) {
                $imageUrl = $basePath . '/uploads/pwesto-products/' . rawurlencode($image);
            } else {
                continue;
            }
            $products[] = [
                'product_name' => $product['product_name'],
                'vendor_name' => $product['vendor_name'],
                'vendor_status' => $product['vendor_status'],
                'is_sample' => (bool) $product['is_sample'],
                'image_url' => $imageUrl,
            ];
        }

        $stalls = [];
        foreach ($files['stalls'] as $stall) {
            $image = $stall['image'] ?? '';
            $imageUrl = is_string($image)
                && filter_var($image, FILTER_VALIDATE_URL)
                && strtolower((string) parse_url($image, PHP_URL_SCHEME)) === 'https'
                ? $image
                : '';
            $stalls[] = [
                'stall_code' => $stall['stall_code'],
                'name' => $stall['name'],
                'category' => $stall['category'],
                'status' => $stall['status'],
                'is_sample_image' => (bool) $stall['is_sample_image'],
                'image_url' => $imageUrl,
            ];
        }

        $catalog = array_map(function ($product) {
            return [
                'product_name' => $product['product_name'],
                'description' => $product['description'],
                'price_cents' => (int) $product['price_cents'],
                'stock' => (int) $product['stock'],
                'active' => (bool) $product['active'],
                'is_sample' => (bool) $product['is_sample'],
                'vendor_name' => $product['vendor_name'],
                'stall_code' => $product['stall_code'],
                'vendor_category' => $product['vendor_category'],
                'vendor_status' => $product['vendor_status'],
            ];
        }, $files['catalog']);

        $documents = [];
        $documentKinds = [
            'application_document' => ['application', 'Application form'],
            'cedula_document' => ['cedula', 'Barangay cedula'],
            'photo_document' => ['photo', '2×2 photo'],
        ];
        foreach ($files['vendors'] as $vendor) {
            foreach ($documentKinds as $column => $document) {
                $objectName = $vendor[$column] ?? '';
                if (!Pwesto_cloudinary_storage::isVendorDocumentObject($objectName)
                    && (!is_string($objectName) || !preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $objectName))) {
                    continue;
                }
                $documents[] = [
                    'vendor_name' => $vendor['name'],
                    'vendor_status' => $vendor['status'],
                    'label' => $document[1],
                    'url' => $apiBase . '/admin/vendor-document?vendor_id='
                        . rawurlencode($vendor['id']) . '&kind=' . rawurlencode($document[0]),
                ];
            }
        }

        $rentalFeeCents = $this->monthly_rental_fee() * 100;
        $applications = array_map(function ($vendor) use ($documentKinds, $apiBase, $rentalFeeCents) {
            $documents = [];
            foreach ($documentKinds as $column => $document) {
                $objectName = $vendor[$column] ?? '';
                $available = Pwesto_cloudinary_storage::isVendorDocumentObject($objectName)
                    || (is_string($objectName) && preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $objectName));
                $documents[$document[0]] = $available
                    ? $apiBase . '/admin/vendor-document?vendor_id='
                        . rawurlencode($vendor['id']) . '&kind=' . rawurlencode($document[0])
                    : null;
            }
            $amount = isset($vendor['rental_checkout_amount']) && (int) $vendor['rental_checkout_amount'] > 0
                ? (int) $vendor['rental_checkout_amount']
                : $rentalFeeCents;
            return [
                'stall_code' => $vendor['stall_code'],
                'name' => $vendor['name'],
                'category' => $vendor['category'],
                'status' => $vendor['status'],
                'owner_name' => $vendor['owner_name'],
                'owner_email' => $vendor['owner_email'],
                'rental_payment_status' => $vendor['rental_payment_status'],
                'rental_amount_cents' => $amount,
                'rental_paid_at' => $vendor['rental_paid_at'],
                'rental_paid_until' => $vendor['rental_paid_until'],
                'documents' => $documents,
            ];
        }, $files['vendors']);

        $this->respond([
            'stalls' => $stalls,
            'catalog' => $catalog,
            'products' => $products,
            'documents' => $documents,
            'applications' => $applications,
        ]);
    }

    private function stream_vendor_document($vendorId, $kind)
    {
        $objectName = $this->store_call(function () use ($vendorId, $kind) {
            return $this->store->vendorDocument($vendorId, $kind);
        }, 'The vendor requirement document could not be loaded.');
        if (Pwesto_cloudinary_storage::isVendorDocumentObject($objectName)) {
            try {
                $content = $this->cloudinary_storage()->download($objectName);
            } catch (Throwable $error) {
                error_log('Pwesto private vendor document download failed: ' . $error->getMessage());
                $this->respond(['error' => 'The vendor requirement could not be loaded from Cloudinary.'], 503);
            }
            $extension = pathinfo($objectName, PATHINFO_EXTENSION);
            $mimeType = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][$extension];
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . strlen($content));
            header('Content-Disposition: inline; filename="' . $kind . '-' . $vendorId . '.' . $extension . '"');
            header('Cache-Control: private, no-store');
            header('X-Content-Type-Options: nosniff');
            echo $content;
            exit;
        }
        if ($objectName === null || !preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $objectName)) {
            $this->respond(['error' => 'That requirement document is not available.'], 404);
        }
        $path = $this->private_document_directory() . DIRECTORY_SEPARATOR . $objectName;
        if (!is_file($path) || !is_readable($path)) {
            $this->respond(['error' => 'That requirement document is not available on the server.'], 404);
        }
        $mimeType = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][pathinfo($objectName, PATHINFO_EXTENSION)];
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $kind . '-' . $vendorId . '.' . pathinfo($objectName, PATHINFO_EXTENSION) . '"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private function validate_private_document($field, $kind, $required = true)
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            if (!$required) {
                return null;
            }
            $this->respond(['error' => 'Upload the application form, barangay cedula, and 2×2 photo to continue.'], 422);
        }
        $file = $_FILES[$field];
        if (($file['error'] ?? null) === UPLOAD_ERR_NO_FILE && !$required) {
            return null;
        }
        if (!isset($file['error'], $file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK
            || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            if (($file['error'] ?? null) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? null) === UPLOAD_ERR_FORM_SIZE) {
                $this->respond(['error' => 'Each requirement document must be no larger than 2 MB.'], 413);
            }
            $this->respond(['error' => $required
                ? 'Upload all three required files before submitting the application.'
                : 'The selected document could not be uploaded. Choose a valid file and try again.'], 422);
        }
        $size = filesize($file['tmp_name']);
        if ($size === false || $size < 1 || $size > 2097152) {
            $this->respond(['error' => 'Each requirement document must be no larger than 2 MB.'], 413);
        }
        if (!function_exists('finfo_open') || !function_exists('getimagesize')) {
            $this->respond(['error' => 'File validation is unavailable on this server.'], 503);
        }
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo === false) {
            $this->respond(['error' => 'File validation is unavailable on this server.'], 503);
        }
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);

        if ($kind === 'document') {
            $allowed = [
                'application/pdf' => ['pdf', null],
                'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
                'image/png' => ['png', IMAGETYPE_PNG],
            ];
        } else {
            $allowed = [
                'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
                'image/png' => ['png', IMAGETYPE_PNG],
            ];
        }
        if (!is_string($mimeType) || !isset($allowed[$mimeType])) {
            $this->respond(['error' => $kind === 'photo'
                ? 'The 2×2 photo must be a JPEG or PNG image.'
                : 'Application form and cedula files must be PDF, JPEG, or PNG.'], 422);
        }
        $imageType = $allowed[$mimeType][1];
        if ($imageType !== null) {
            $image = @getimagesize($file['tmp_name']);
            if ($image === false || $image[2] !== $imageType || $image[0] < 1 || $image[1] < 1
                || $image[0] > 5000 || $image[1] > 5000 || $image[0] * $image[1] > 20000000) {
                $this->respond(['error' => 'The uploaded image is invalid or exceeds 5,000 × 5,000 pixels.'], 422);
            }
            if ($kind === 'photo' && ($image[0] < 300 || $image[1] < 300
                || min($image[0], $image[1]) / max($image[0], $image[1]) < 0.85)) {
                $this->respond(['error' => 'Use a square 2×2 photo image at least 300 × 300 pixels.'], 422);
            }
        }
        return [
            'tmp_name' => $file['tmp_name'],
            'extension' => $allowed[$mimeType][0],
            'mime_type' => $mimeType,
        ];
    }

    private function persist_private_documents(array $documents, $vendorId)
    {
        $storedDocuments = [];
        foreach ($documents as $column => $document) {
            $objectName = 'cloudinary/vendor-requirements/' . $vendorId . '/' . $column . '/'
                . bin2hex(random_bytes(16)) . '.' . $document['extension'];
            try {
                $storedDocuments[$column] = $this->cloudinary_storage()->upload(
                    $document['tmp_name'],
                    $objectName
                );
            } catch (Throwable $error) {
                $this->delete_private_documents($storedDocuments);
                $labels = [
                    'application_document' => 'application form',
                    'cedula_document' => 'barangay cedula',
                    'photo_document' => '2×2 photo',
                ];
                $size = @filesize($document['tmp_name']);
                $logMessage =
                    'Pwesto vendor document upload failed for ' . ($labels[$column] ?? 'requirement')
                    . ' (' . $document['mime_type'] . ', ' . ($size === false ? 'unknown' : $size) . ' bytes): '
                    . get_class($error) . ': ' . $error->getMessage();
                error_log($logMessage);
                $logPath = ROOT_DIR . 'runtime' . DIRECTORY_SEPARATOR . 'pwesto-upload-errors.log';
                $loggedBytes = file_put_contents(
                    $logPath,
                    '[' . date('Y-m-d H:i:s') . '] ' . $logMessage . PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );
                if ($loggedBytes === false) {
                    error_log('Pwesto upload diagnostic could not be written to ' . $logPath);
                }
                $this->respond([
                    'error' => 'The ' . ($labels[$column] ?? 'requirement document')
                        . ' could not be saved. Check the server log for the upload error and try again.',
                ], 503);
            }
        }
        return $storedDocuments;
    }

    private function private_document_directory()
    {
        return ROOT_DIR . 'runtime' . DIRECTORY_SEPARATOR . 'pwesto-documents';
    }

    private function delete_private_documents(array $fileNames)
    {
        $directory = $this->private_document_directory();
        foreach ($fileNames as $fileName) {
            if (Pwesto_cloudinary_storage::isVendorDocumentObject($fileName)) {
                try {
                    $this->cloudinary_storage()->delete($fileName);
                } catch (Throwable $error) {
                    error_log('Pwesto vendor document cleanup failed: ' . $error->getMessage());
                }
                continue;
            }
            if (is_string($fileName) && preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $fileName)) {
                $path = $directory . DIRECTORY_SEPARATOR . $fileName;
                if (is_file($path) && !unlink($path)) {
                    error_log('Pwesto vendor document cleanup failed: ' . $path);
                }
            }
        }
    }

    private function cloudinary_storage()
    {
        if ($this->cloudinaryStorage === null) {
            $this->cloudinaryStorage = new Pwesto_cloudinary_storage();
        }
        return $this->cloudinaryStorage;
    }

    private function monthly_rental_fee()
    {
        $configured = getenv('PWESTO_MONTHLY_RENT_PHP');
        if ($configured === false || $configured === '') {
            return 7000;
        }
        if (!ctype_digit($configured) || (int) $configured < 1 || (int) $configured > 1000000) {
            $this->respond(['error' => 'The configured monthly stall rent is invalid. Contact the Pwesto administrator.'], 503);
        }
        return (int) $configured;
    }

    private function xendit_secret_key()
    {
        $key = getenv('XENDIT_SECRET_KEY');
        return is_string($key) ? trim($key) : '';
    }

    private function xendit_configured()
    {
        $mode = getenv('XENDIT_MODE') ?: 'test';
        if (!in_array($mode, ['test', 'live'], true)) {
            return false;
        }
        $key = $this->xendit_secret_key();
        $expectedPrefixes = $mode === 'live'
            ? ['xnd_production-', 'xnd_production_']
            : ['xnd_development-', 'xnd_development_'];
        $keyMatchesMode = false;
        foreach ($expectedPrefixes as $expectedPrefix) {
            if (strpos($key, $expectedPrefix) === 0) {
                $keyMatchesMode = true;
                break;
            }
        }
        if (!$keyMatchesMode) {
            return false;
        }
        $webhookToken = getenv('XENDIT_WEBHOOK_TOKEN');
        return is_string($webhookToken) && trim($webhookToken) !== '';
    }

    private function xendit_request($method, $path, ?array $body = null)
    {
        $secret = $this->xendit_secret_key();
        $headers = [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($secret . ':'),
        ];
        $options = [
            'method' => $method,
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers) . "\r\n",
        ];
        if ($body !== null) {
            $options['header'] .= "Content-Type: application/json\r\n";
            $options['content'] = json_encode($body, JSON_UNESCAPED_SLASHES);
        }
        $context = stream_context_create([
            'http' => $options,
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $responseBody = @file_get_contents('https://api.xendit.co' . $path, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        $status = preg_match('/\s(\d{3})\s/', $statusLine, $matches) ? (int) $matches[1] : 0;
        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        if (!is_array($decoded)) {
            $decoded = [];
        }
        return ['status' => $status, 'body' => $decoded];
    }

    private function is_xendit_checkout_url($url)
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)
            || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return false;
        }
        return in_array(parse_url($url, PHP_URL_HOST), [
            'checkout.xendit.co',
            'checkout-staging.xendit.co',
        ], true);
    }

    private function xendit_amount_cents($amount)
    {
        if (!is_numeric($amount) || !is_finite((float) $amount) || (float) $amount < 0) {
            return null;
        }
        $amountCents = round((float) $amount * 100);
        return $amountCents <= PHP_INT_MAX ? (int) $amountCents : null;
    }

    public function update_vendor()
    {
        $this->require_user();
        $this->require_method('POST');
        $this->verify_csrf_token();
        $user = $this->current_user();
        if (($user['role'] ?? '') !== 'vendor') {
            $this->respond(['error' => 'Only vendor accounts can edit stall listings.'], 403);
        }

        $body = $this->json_body();
        $vendorId = $body['vendor_id'] ?? '';
        $name = isset($body['name']) && is_string($body['name']) ? trim($body['name']) : '';
        $category = $body['category'] ?? '';
        $market = $body['market'] ?? '';
        $specialty = isset($body['specialty']) && is_string($body['specialty']) ? trim($body['specialty']) : '';
        $validCategories = ['Produce', 'Seafood', 'Meat', 'Vegetables', 'Fruits', 'Pantry', 'Flowers'];

        if (!is_string($vendorId) || !preg_match('/^[a-f0-9]{24}$/', $vendorId)) {
            $this->respond(['error' => 'Choose a valid stall listing to update.'], 422);
        }
        if ($this->text_length($name) < 2 || $this->text_length($name) > 100) {
            $this->respond(['error' => 'Enter a stall name between 2 and 100 characters.'], 422);
        }
        if (!is_string($category) || !in_array($category, $validCategories, true)) {
            $this->respond(['error' => 'Choose a valid stall category.'], 422);
        }
        $markets = $this->store_call(function () { return $this->store->markets(); }, 'Market locations could not be loaded.');
        if (!is_string($market) || !in_array($market, $markets, true)) {
            $this->respond(['error' => 'Choose a supported public market.'], 422);
        }
        if ($this->text_length($specialty) < 5 || $this->text_length($specialty) > 240) {
            $this->respond(['error' => 'Describe your specialties in 5 to 240 characters.'], 422);
        }

        $updated = $this->store_call(function () use ($vendorId, $user, $name, $category, $market, $specialty) {
            return $this->store->updateVendor($vendorId, $user['id'], [
                'name' => $name,
                'category' => $category,
                'market' => $market,
                'specialty' => $specialty,
            ]);
        }, 'Your stall profile could not be updated. Please try again.');
        if (!$updated) {
            $this->respond(['error' => 'That stall listing is not available to your account.'], 404);
        }
        $vendors = $this->store_call(function () use ($user) {
            return $this->store->vendorsForOwner($user['id']);
        }, 'Your stall applications could not be loaded.');
        $this->respond(['vendors' => $vendors]);
    }

    public function admin_vendors()
    {
        $this->require_role('admin');
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'GET') {
            $summary = $this->store_call(function () { return $this->store->adminDashboard(); }, 'Admin dashboard data could not be loaded.');
            $vendors = $this->store_call(function () { return $this->store->pendingVendors(); }, 'Vendor applications could not be loaded.');
            $unassigned = $this->store_call(function () { return $this->store->unassignedVendors(); }, 'Unassigned stalls could not be loaded.');
            $this->respond(['summary' => $summary, 'applications' => $vendors, 'unassigned_stalls' => $unassigned]);
        }
        $this->require_method('POST');
        $this->verify_csrf_token();
        $body = $this->json_body();
        if (($body['action'] ?? '') === 'assign_account') {
            $vendorId = $body['vendor_id'] ?? '';
            $email = $this->normalized_email($body['email'] ?? null);
            $name = $body['name'] ?? '';
            if (!is_string($vendorId) || !preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/i', $vendorId)
                || $email === null || !is_string($name) || strlen(trim($name)) < 2 || strlen($name) > 120) {
                $this->respond(['error' => 'Enter the vendor’s real name and a valid email address for this stall.'], 422);
            }

            $assignment = $this->store_call(function () use ($vendorId, $email, $name) {
                return $this->store->assignVendorAccount($vendorId, $email, trim($name));
            }, 'The vendor account could not be linked to this stall.');
            if ($assignment['status'] !== 'assigned') {
                $errors = [
                    'not_found' => ['This stall no longer exists.', 404],
                    'already_assigned' => ['This stall already has a vendor account. Refresh the admin page.', 409],
                    'role_conflict' => ['That email belongs to a non-vendor account. Use a vendor account email.', 409],
                    'account_in_use' => ['That vendor account is already linked to another stall. Use a separate account email.', 409],
                ];
                [$message, $status] = $errors[$assignment['status']] ?? ['The vendor account could not be linked.', 409];
                $this->respond(['error' => $message], $status);
            }

            $summary = $this->store_call(function () { return $this->store->adminDashboard(); }, 'Admin dashboard data could not be loaded.');
            $vendors = $this->store_call(function () { return $this->store->pendingVendors(); }, 'Vendor applications could not be loaded.');
            $unassigned = $this->store_call(function () { return $this->store->unassignedVendors(); }, 'Unassigned stalls could not be loaded.');
            $this->respond([
                'summary' => $summary,
                'applications' => $vendors,
                'unassigned_stalls' => $unassigned,
                'account_created' => $assignment['account_created'],
            ]);
        }

        $vendorId = $body['vendor_id'] ?? '';
        $decision = $body['decision'] ?? '';
        if (!is_string($vendorId) || !preg_match('/^[a-f0-9]{24}$/', $vendorId)
            || !is_string($decision) || !in_array($decision, ['approved', 'rejected'], true)) {
            $this->respond(['error' => 'Choose an application and a valid review decision.'], 422);
        }

        $reviewed = $this->store_call(function () use ($vendorId, $decision) {
            return $this->store->reviewVendor($vendorId, $decision);
        }, 'This application could not be reviewed. Please try again.');
        if (!$reviewed) {
            $this->respond(['error' => 'This application has already been reviewed or no longer exists.'], 404);
        }
        $summary = $this->store_call(function () { return $this->store->adminDashboard(); }, 'Admin dashboard data could not be loaded.');
        $vendors = $this->store_call(function () { return $this->store->pendingVendors(); }, 'Vendor applications could not be loaded.');
        $unassigned = $this->store_call(function () { return $this->store->unassignedVendors(); }, 'Unassigned stalls could not be loaded.');
        $this->respond(['summary' => $summary, 'applications' => $vendors, 'unassigned_stalls' => $unassigned]);
    }

    public function check_order_payment()
    {
        $this->require_role('customer');
        $this->require_method('POST');
        $this->verify_csrf_token();
        if (!$this->xendit_configured()) {
            $this->respond(['error' => 'Xendit checkout is not configured on the server.'], 503);
        }
        $user = $this->current_user();
        $body = $this->json_body();
        $orderId = $body['order_id'] ?? '';
        if (!is_string($orderId) || !preg_match('/^[a-f0-9]{32}$/', $orderId)) {
            $this->respond(['error' => 'Choose a valid order to check.'], 422);
        }
        $order = $this->store_call(function () use ($orderId, $user) {
            return $this->store->orderCheckoutForCustomer($orderId, $user['id']);
        }, 'The GCash order could not be loaded.');
        if ($order === null) {
            $this->respond(['error' => 'That GCash order is not available to your account.'], 404);
        }
        if ($order['payment_status'] === 'paid') {
            $this->sendVendorPaymentReceipt($orderId);
            $this->respond(['payment_status' => 'paid']);
        }
        $checkoutId = $order['payment_checkout_id'];
        if (!is_string($checkoutId) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $checkoutId)) {
            $this->respond(['error' => 'The Xendit invoice is not attached to this order.'], 409);
        }
        $response = $this->xendit_request('GET', '/v2/invoices/' . rawurlencode($checkoutId));
        if ($response['status'] < 200 || $response['status'] >= 300) {
            error_log('Pwesto Xendit order payment check failed with HTTP ' . $response['status']);
            $this->respond(['error' => 'Xendit could not verify this payment. Try again shortly.'], 502);
        }
        $invoice = $response['body'];
        $expectedExternalId = 'pwesto-order-' . $orderId;
        if (($invoice['id'] ?? '') !== $checkoutId
            || ($invoice['external_id'] ?? '') !== $expectedExternalId
            || $this->xendit_amount_cents($invoice['amount'] ?? null) !== $order['total_cents']
            || ($invoice['currency'] ?? '') !== 'PHP') {
            $this->respond(['error' => 'The Xendit invoice does not match this order. Contact the Pwesto administrator.'], 409);
        }
        if (($invoice['status'] ?? '') !== 'PAID') {
            $this->respond(['payment_status' => 'pending']);
        }
        $recorded = $this->store_call(function () use ($orderId, $checkoutId, $order) {
            return $this->store->markOrderPaidByCheckout($orderId, $checkoutId, $order['total_cents']);
        }, 'The confirmed GCash payment could not be recorded.');
        if (!$recorded) {
            $this->respond(['error' => 'The payment no longer matches this order. Contact the Pwesto administrator.'], 409);
        }
        $this->sendVendorPaymentReceipt($orderId);
        $this->respond(['payment_status' => 'paid']);
    }

    private function verify_google_credential($credential)
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credential);
        $responseBody = false;

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $responseBody = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            if ($responseBody === false) {
                error_log('Pwesto Google sign-in verification failed: ' . $curlError);
                $this->respond(['error' => 'Google sign-in could not be reached. Please try again shortly.'], 502);
            }
            if ($status !== 200) {
                return null;
            }
        } else {
            $context = stream_context_create([
                'http' => ['timeout' => 8, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n"],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $responseBody = @file_get_contents($url, false, $context);
            $statusLine = $http_response_header[0] ?? '';
            if ($responseBody === false) {
                error_log('Pwesto Google sign-in verification failed: HTTPS request unavailable.');
                $this->respond(['error' => 'Google sign-in could not be reached. Please try again shortly.'], 502);
            }
            if (strpos($statusLine, ' 200 ') === false) {
                return null;
            }
        }

        $claims = json_decode($responseBody, true);
        if (!is_array($claims)) {
            return null;
        }

        $issuer = $claims['iss'] ?? '';
        $emailVerified = $claims['email_verified'] ?? false;
        $validIssuer = in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true);
        $validEmail = in_array($emailVerified, [true, 'true', 1, '1'], true);
        $validExpiry = isset($claims['exp']) && ctype_digit((string) $claims['exp']) && (int) $claims['exp'] > time();
        $validIdentity = !empty($claims['sub']) && !empty($claims['email']) && !empty($claims['name']);

        if (($claims['aud'] ?? '') !== (config_item('google_client_id') ?: '')
            || !$validIssuer || !$validEmail || !$validExpiry || !$validIdentity) {
            return null;
        }

        return [
            'sub' => substr((string) $claims['sub'], 0, 255),
            'email' => substr((string) $claims['email'], 0, 254),
            'name' => substr((string) $claims['name'], 0, 120),
            'picture' => isset($claims['picture'])
                && filter_var($claims['picture'], FILTER_VALIDATE_URL)
                && parse_url($claims['picture'], PHP_URL_SCHEME) === 'https'
                ? substr((string) $claims['picture'], 0, 2048)
                : '',
        ];
    }

    private function is_valid_visit_date($date)
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        $errors = DateTime::getLastErrors();
        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $date
            && $date >= date('Y-m-d');
    }

    private function current_user()
    {
        $user = $this->session->userdata('pwesto_user');
        return is_array($user) ? $user : null;
    }

    private function delete_uploaded_profile_picture($picture)
    {
        $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
        $urlPrefix = $basePath . '/uploads/pwesto-profile/';
        if (!is_string($picture) || strpos($picture, $urlPrefix) !== 0) {
            return;
        }
        $fileName = substr($picture, strlen($urlPrefix));
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $fileName)) {
            return;
        }
        $filePath = ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'pwesto-profile' . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($filePath) && !unlink($filePath)) {
            error_log('Pwesto old profile photo could not be removed: ' . $filePath);
        }
    }

    private function require_user()
    {
        if ($this->current_user() === null) {
            $this->respond(['error' => 'Sign in to continue.'], 401);
        }
    }

    private function require_role($role)
    {
        $this->require_user();
        $user = $this->current_user();
        if (($user['role'] ?? 'customer') !== $role
            || ($role === 'admin' && !$this->isAdminEmail($user['email'] ?? ''))) {
            $this->respond(['error' => 'You do not have permission to access this page.'], 403);
        }
    }

    private function require_vendor_workspace()
    {
        $this->require_user();
        $user = $this->current_user();
        if (($user['role'] ?? 'customer') === 'admin'
            || $this->isAdminEmail($user['email'] ?? '')) {
            $this->respond(['error' => 'Only a stall owner can manage its products.'], 403);
        }
        $vendors = $this->store_call(function () use ($user) {
            return $this->store->vendorsForOwner($user['id']);
        }, 'Your stall applications could not be loaded.');
        if (!$vendors) {
            $this->respond(['error' => 'Submit a stall application before managing products.'], 403);
        }
        return $user;
    }

    private function isAdminEmail($email)
    {
        $configured = getenv('PWESTO_ADMIN_EMAILS') ?: '';
        $allowed = array_filter(array_map('trim', explode(',', $configured)));
        foreach ($allowed as $adminEmail) {
            if (strcasecmp($adminEmail, $email) === 0) {
                return true;
            }
        }
        return false;
    }

    private function pwesto_app_url()
    {
        $url = trim((string) (getenv('PWESTO_APP_URL') ?: ''));
        $parts = $url === '' ? false : parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }
        $isLocalHttp = $parts['scheme'] === 'http'
            && in_array(strtolower($parts['host']), ['localhost', '127.0.0.1', '::1'], true);
        if ($parts['scheme'] !== 'https' && !$isLocalHttp) {
            return null;
        }
        return rtrim($url, '/');
    }

    private function sendDeliveryStatusEmail($email, $name, $vendorName, $orderId, $status, $address)
    {
        require_once APP_DIR . 'libraries' . DIRECTORY_SEPARATOR . 'Pwesto_mailer.php';
        $labels = [
            'pending' => 'Order received',
            'accepted' => 'Order accepted',
            'preparing' => 'Order is being prepared',
            'out_for_delivery' => 'Order is out for delivery',
            'delivered' => 'Order delivered',
            'cancelled' => 'Order cancelled',
        ];
        $label = $labels[$status] ?? 'Order updated';
        $safeName = htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8');
        $safeVendor = htmlspecialchars((string) $vendorName, ENT_QUOTES, 'UTF-8');
        $safeOrderId = htmlspecialchars(substr((string) $orderId, 0, 8), ENT_QUOTES, 'UTF-8');
        $safeAddress = htmlspecialchars((string) $address, ENT_QUOTES, 'UTF-8');
        $addressHtml = $safeAddress === '' ? '' : '<p>Delivery address: ' . $safeAddress . '</p>';
        $addressText = $address === null || $address === '' ? '' : "Delivery address: " . $address . "\n";
        $html = '<p>Hello ' . $safeName . ',</p><p><strong>' . $label . '</strong> from '
            . $safeVendor . ' (order #' . $safeOrderId . ').</p>' . $addressHtml;
        $text = "Hello " . $name . ",\n\n" . $label . " from " . $vendorName
            . " (order #" . substr((string) $orderId, 0, 8) . ").\n" . $addressText;
        try {
            $mailer = new Pwesto_mailer();
            $mailer->send($email, $name, 'Pwesto delivery update: ' . $label, $html, $text);
            return true;
        } catch (Throwable $error) {
            error_log('Pwesto delivery email failed for order #' . substr((string) $orderId, 0, 8) . ': ' . $error->getMessage());
            return false;
        }
    }

    private function sendVendorPaymentReceipt($orderId)
    {
        $receipt = $this->store_call(function () use ($orderId) {
            return $this->store->claimVendorPaymentEmail($orderId);
        }, 'The vendor payment email could not be prepared.');
        if ($receipt === null) {
            return true;
        }

        $sent = false;
        try {
            require_once APP_DIR . 'libraries' . DIRECTORY_SEPARATOR . 'Pwesto_mailer.php';
            $mailer = new Pwesto_mailer();
            $safeVendor = htmlspecialchars($receipt['vendor_name'], ENT_QUOTES, 'UTF-8');
            $safeCustomer = htmlspecialchars($receipt['customer_name'], ENT_QUOTES, 'UTF-8');
            $orderReference = substr((string) $receipt['id'], 0, 8);
            $safeOrderReference = htmlspecialchars($orderReference, ENT_QUOTES, 'UTF-8');
            $itemLines = [];
            $itemHtml = '';
            foreach ($receipt['items'] as $item) {
                $lineTotal = $item['unit_price_cents'] * $item['quantity'];
                $itemLines[] = $item['quantity'] . ' × ' . $item['product_name']
                    . ' — ₱' . number_format($lineTotal / 100, 2);
                $itemHtml .= '<li>' . (int) $item['quantity'] . ' × '
                    . htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8')
                    . ' — ₱' . number_format($lineTotal / 100, 2) . '</li>';
            }
            $fulfillment = $receipt['delivery_method'] === 'delivery'
                ? 'Delivery to: ' . (string) $receipt['delivery_address']
                : 'Pickup' . ($receipt['pickup_at'] ? ' at ' . $receipt['pickup_at'] : '');
            $safeFulfillment = htmlspecialchars($fulfillment, ENT_QUOTES, 'UTF-8');
            $total = '₱' . number_format($receipt['total_cents'] / 100, 2);
            $html = '<p>Hello ' . $safeVendor . ',</p>'
                . '<p>GCash payment has been confirmed for order <strong>#' . $safeOrderReference . '</strong>.</p>'
                . '<p>Customer: ' . $safeCustomer . '<br>Fulfillment: ' . $safeFulfillment . '</p>'
                . '<ul>' . $itemHtml . '</ul>'
                . '<p><strong>Total paid: ' . $total . '</strong></p>'
                . '<p>This is a Pwesto payment confirmation for your order records.</p>';
            $text = "Hello " . $receipt['vendor_name'] . ",\n\n"
                . "GCash payment has been confirmed for order #" . $orderReference . ".\n"
                . "Customer: " . $receipt['customer_name'] . "\n"
                . $fulfillment . "\n\n"
                . implode("\n", $itemLines) . "\n\n"
                . "Total paid: " . $total . "\n\n"
                . "This is a Pwesto payment confirmation for your order records.";
            $mailer->send(
                $receipt['vendor_email'],
                $receipt['vendor_name'],
                'Pwesto GCash payment confirmed · Order #' . $orderReference,
                $html,
                $text
            );
            $sent = true;
        } catch (Throwable $error) {
            error_log(
                'Pwesto vendor GCash email failed for order #'
                . substr((string) $orderId, 0, 8) . ': ' . $error->getMessage()
            );
        }

        $completed = $this->store_call(function () use ($orderId, $sent) {
            return $this->store->completeVendorPaymentEmail($orderId, $sent);
        }, 'The vendor payment email status could not be saved.');
        if (!$completed) {
            error_log('Pwesto vendor GCash email status could not be updated for order #' . substr((string) $orderId, 0, 8));
        }
        return $sent;
    }

    private function store_call(callable $operation, $message)
    {
        try {
            return $operation();
        } catch (Throwable $error) {
            error_log('Pwesto database operation failed: ' . $error->getMessage());
            $this->respond(['error' => $message], 503);
        }
    }

    private function ensure_csrf_token()
    {
        if (!$this->session->has_userdata('pwesto_csrf')) {
            $this->session->set_userdata('pwesto_csrf', bin2hex(random_bytes(32)));
        }
    }

    private function verify_csrf_token()
    {
        $expected = $this->session->userdata('pwesto_csrf');
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($expected) || !is_string($provided) || $provided === '' || !hash_equals($expected, $provided)) {
            $this->respond(['error' => 'Your session has expired. Refresh the page and try again.'], 403);
        }
    }

    private function json_body()
    {
        $raw = file_get_contents('php://input', false, null, 0, 16385);
        if ($raw !== false && strlen($raw) > 16384) {
            $this->respond(['error' => 'Request body is too large.'], 413);
        }
        $body = json_decode($raw === false ? '' : $raw, true);
        if (!is_array($body)) {
            $this->respond(['error' => 'A valid JSON request body is required.'], 400);
        }
        return $body;
    }

    private function reject_oversized_form_request()
    {
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? '';
        if (!is_string($contentLength) || !ctype_digit($contentLength)) {
            return;
        }
        $maxBytes = $this->ini_size_bytes(ini_get('post_max_size'));
        if ($maxBytes > 0 && (int) $contentLength > $maxBytes) {
            $this->respond([
                'error' => 'The combined upload is too large. Keep each requirement file under 2 MB and try again.',
            ], 413);
        }
    }

    private function ini_size_bytes($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return 0;
        }
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        if ($number <= 0) {
            return 0;
        }
        if ($unit === 'g') {
            $number *= 1024;
            $unit = 'm';
        }
        if ($unit === 'm') {
            $number *= 1024;
            $unit = 'k';
        }
        if ($unit === 'k') {
            $number *= 1024;
        }
        return (int) $number;
    }

    private function require_method($method)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
            header('Allow: ' . $method);
            $this->respond(['error' => 'Method not allowed.'], 405);
        }
    }

    private function respond($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
