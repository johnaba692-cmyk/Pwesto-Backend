<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Pwesto_store
{
    private const CURRENT_SCHEMA_VERSION = 5;

    private $pdo;
    private $driver;

    public function __construct()
    {
        if (!class_exists('PDO')) {
            throw new RuntimeException('PDO is required for the Pwesto database.');
        }
        $this->driver = strtolower(getenv('PWESTO_DB_DRIVER') ?: 'sqlite');
        if (!in_array($this->driver, ['sqlite', 'mysql'], true)) {
            throw new RuntimeException('PWESTO_DB_DRIVER must be either sqlite or mysql.');
        }
        if (!in_array($this->driver, PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('The PDO ' . ucfirst($this->driver) . ' extension is required for the Pwesto database.');
        }

        if ($this->driver === 'mysql') {
            $this->connectMysql();
        } else {
            $this->connectSqlite();
        }
        if (!$this->schemaIsCurrent()) {
            $this->createSchema();
            $this->retireBuiltInDemoStalls();
            $this->retireMisnamedDuplicateStall();
            $this->assignMissingStallCodes();
            $this->markSchemaCurrent();
        }
        $this->configureConfirmedStalls();
    }

    public function connection()
    {
        return $this->pdo;
    }

    private function connectSqlite()
    {
        $path = getenv('PWESTO_DB_PATH') ?: ROOT_DIR . 'runtime' . DIRECTORY_SEPARATOR . 'pwesto.sqlite';
        if ($path !== ':memory:') {
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new RuntimeException('The Pwesto database directory could not be created.');
            }
            if (!is_writable($directory)) {
                throw new RuntimeException('The Pwesto database directory is not writable.');
            }
        }

        $this->pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA busy_timeout = 5000');
    }

    private function connectMysql()
    {
        $host = getenv('DB_HOST') ?: '';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_NAME') ?: '';
        $username = getenv('DB_USER') ?: '';
        $password = getenv('DB_PASSWORD');
        $isLocalHost = in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
        $caFile = getenv('PWESTO_DB_SSL_CA') ?: '';
        if ($caFile !== '' && !preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/', $caFile)) {
            $caFile = ROOT_DIR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caFile);
        }
        if ($host === '' || $database === '' || $username === '' || $password === false
            || (!$isLocalHost && $password === '')) {
            throw new RuntimeException('Set DB_HOST, DB_NAME, DB_USER, and DB_PASSWORD in the local environment to connect Pwesto to MySQL.');
        }
        if (!ctype_digit((string) $port) || (int) $port < 1 || (int) $port > 65535) {
            throw new RuntimeException('DB_PORT must be a valid TCP port.');
        }
        if (!$isLocalHost && ($caFile === '' || !is_file($caFile) || !is_readable($caFile))) {
            throw new RuntimeException('Set PWESTO_DB_SSL_CA to the readable Aiven CA certificate file to enable verified TLS.');
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];
        if (!$isLocalHost) {
            if (!defined('PDO::MYSQL_ATTR_SSL_CA')) {
                throw new RuntimeException('The PDO MySQL TLS options are unavailable. Enable the PDO MySQL extension.');
            }
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }
        }

        $dsn = 'mysql:host=' . $host . ';port=' . (int) $port
            . ';dbname=' . $database . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, $username, $password, $options);
    }

    private function isMysql()
    {
        return $this->driver === 'mysql';
    }

    private function schemaIsCurrent()
    {
        if ($this->isMysql()) {
            $tableExists = $this->pdo->query(
                "SELECT 1 FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'pwesto_schema_version'
                 LIMIT 1"
            )->fetchColumn();
        } else {
            $query = $this->pdo->query(
                "SELECT 1 FROM sqlite_master
                 WHERE type = 'table' AND name = 'pwesto_schema_version' LIMIT 1"
            );
            $tableExists = $query->fetchColumn();
        }
        if (!$tableExists) {
            return false;
        }
        $version = $this->pdo->query(
            'SELECT version FROM pwesto_schema_version ORDER BY version DESC LIMIT 1'
        )->fetchColumn();
        return $version !== false && (int) $version >= self::CURRENT_SCHEMA_VERSION;
    }

    private function markSchemaCurrent()
    {
        if ($this->isMysql()) {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS pwesto_schema_version (
                    version INT NOT NULL PRIMARY KEY,
                    applied_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } else {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS pwesto_schema_version (
                    version INTEGER NOT NULL PRIMARY KEY,
                    applied_at TEXT NOT NULL
                )'
            );
        }
        $this->pdo->exec('DELETE FROM pwesto_schema_version');
        $query = $this->pdo->prepare(
            'INSERT INTO pwesto_schema_version (version, applied_at)
             VALUES (:version, CURRENT_TIMESTAMP)'
        );
        $query->execute(['version' => self::CURRENT_SCHEMA_VERSION]);
    }

    public function vendors()
    {
        $query = $this->pdo->query(
            "SELECT v.id, v.stall_code, v.name, v.category, v.market, v.specialty, v.rating,
                    v.latitude AS lat, v.longitude AS lng, v.icon, v.image,
                    COALESCE((
                        SELECT GROUP_CONCAT(p.name, ' ')
                        FROM pwesto_products p
                        WHERE p.vendor_id = v.id AND p.active = 1 AND p.stock > 0
                          AND p.description NOT LIKE '%Sample dataset:%'
                    ), '') AS product_names,
                    CASE WHEN v.owner_id IS NULL THEN 0 ELSE 1 END AS can_message,
                    CASE WHEN v.image LIKE 'https://images.unsplash.com/%' THEN 1 ELSE 0 END AS is_sample_image,
                    CASE WHEN " . $this->orderableVendorCondition('v') . " THEN 1 ELSE 0 END AS can_order
             FROM pwesto_vendors v
             WHERE v.status = 'approved'
               AND " . $this->orderableVendorCondition('v') . "
             ORDER BY v.category, v.stall_code, v.name"
        );
        return array_map([$this, 'formatVendor'], $query->fetchAll());
    }

    private function orderableVendorCondition($vendorAlias)
    {
        return "EXISTS (
                    SELECT 1 FROM pwesto_users orderable_owner
                    WHERE orderable_owner.id = {$vendorAlias}.owner_id
                      AND orderable_owner.role = 'vendor'
                )
                AND EXISTS (
                    SELECT 1 FROM pwesto_products orderable_product
                    WHERE orderable_product.vendor_id = {$vendorAlias}.id
                      AND orderable_product.active = 1
                      AND orderable_product.stock > 0
                      AND orderable_product.description NOT LIKE '%Sample dataset:%'
                )";
    }

    public function assistantCatalog()
    {
        $query = $this->pdo->query(
            "SELECT v.id AS vendor_id, v.name AS vendor_name, v.stall_code, v.category, v.market, v.specialty,
                    CASE WHEN v.owner_id IS NULL THEN 0 ELSE 1 END AS has_owner,
                    p.name AS product_name, p.description AS product_description,
                    p.price_cents, p.stock
             FROM pwesto_vendors v
             LEFT JOIN pwesto_products p ON p.vendor_id = v.id AND p.active = 1
                    AND p.stock > 0 AND p.description NOT LIKE '%Sample dataset:%'
             WHERE v.status = 'approved'
               AND " . $this->orderableVendorCondition('v') . "
             ORDER BY v.name, p.name"
        );
        return $query->fetchAll();
    }

    public function vendor($id)
    {
        $query = $this->pdo->prepare(
            "SELECT id, stall_code, name, category, market, specialty, rating, latitude AS lat,
                    longitude AS lng, icon, image,
                    CASE WHEN v.owner_id IS NULL THEN 0 ELSE 1 END AS can_message,
                    CASE WHEN v.image LIKE 'https://images.unsplash.com/%' THEN 1 ELSE 0 END AS is_sample_image,
                    CASE WHEN " . $this->orderableVendorCondition('v') . " THEN 1 ELSE 0 END AS can_order
             FROM pwesto_vendors v
             WHERE v.id = :id AND v.status = 'approved'
               AND " . $this->orderableVendorCondition('v') . " LIMIT 1"
        );
        $query->execute(['id' => $id]);
        $vendor = $query->fetch();
        return $vendor ? $this->formatVendor($vendor) : null;
    }

    public function products($vendorId)
    {
        $query = $this->pdo->prepare(
            "SELECT p.id, p.vendor_id, p.name, p.description, p.price_cents, p.stock, p.image,
                    CASE WHEN p.description LIKE '%Sample dataset:%' THEN 1 ELSE 0 END AS is_sample
             FROM pwesto_products p
             INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
             WHERE p.vendor_id = :vendor_id AND v.status = 'approved' AND p.active = 1
               AND p.stock > 0 AND p.description NOT LIKE '%Sample dataset:%'
               AND " . $this->orderableVendorCondition('v') . "
             ORDER BY p.name"
        );
        $query->execute(['vendor_id' => $vendorId]);
        return $query->fetchAll();
    }

    public function publicProductImage($productId)
    {
        $query = $this->pdo->prepare(
            "SELECT p.image FROM pwesto_products p
             INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
            WHERE p.id = :id AND p.active = 1 AND p.stock > 0
              AND p.description NOT LIKE '%Sample dataset:%'
              AND v.status = 'approved'
              AND " . $this->orderableVendorCondition('v') . "
            LIMIT 1"
        );
        $query->execute(['id' => $productId]);
        $image = $query->fetchColumn();
        return is_string($image) && $image !== '' ? $image : null;
    }

    public function productImageForOwner($productId, $ownerId)
    {
        $query = $this->pdo->prepare(
            'SELECT p.image FROM pwesto_products p
             INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
             WHERE p.id = :id AND v.owner_id = :owner_id
             LIMIT 1'
        );
        $query->execute(['id' => $productId, 'owner_id' => $ownerId]);
        $image = $query->fetchColumn();
        return is_string($image) && $image !== '' ? $image : null;
    }

    public function productImageForAdmin($productId)
    {
        $query = $this->pdo->prepare(
            'SELECT image FROM pwesto_products WHERE id = :id LIMIT 1'
        );
        $query->execute(['id' => $productId]);
        $image = $query->fetchColumn();
        return is_string($image) && $image !== '' ? $image : null;
    }

    public function productsForOwner($vendorId, $ownerId)
    {
        $query = $this->pdo->prepare(
            'SELECT p.id, p.vendor_id, p.name, p.description, p.price_cents, p.stock, p.active, p.image
             FROM pwesto_products p
             INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
             WHERE p.vendor_id = :vendor_id AND v.owner_id = :owner_id
             ORDER BY p.name'
        );
        $query->execute(['vendor_id' => $vendorId, 'owner_id' => $ownerId]);
        return $query->fetchAll();
    }

    public function createProduct($vendorId, $ownerId, array $product, $image = null)
    {
        $ownsVendor = $this->pdo->prepare(
            'SELECT 1 FROM pwesto_vendors WHERE id = :vendor_id AND owner_id = :owner_id LIMIT 1'
        );
        $ownsVendor->execute(['vendor_id' => $vendorId, 'owner_id' => $ownerId]);
        if (!$ownsVendor->fetchColumn()) {
            return false;
        }

        $id = bin2hex(random_bytes(16));
        $query = $this->pdo->prepare(
            'INSERT INTO pwesto_products
                (id, vendor_id, name, description, price_cents, stock, image, created_at, updated_at)
             VALUES
                (:id, :vendor_id, :name, :description, :price_cents, :stock, :image, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $query->execute([
            'id' => $id,
            'vendor_id' => $vendorId,
            'name' => $product['name'],
            'description' => $product['description'],
            'price_cents' => $product['price_cents'],
            'stock' => $product['stock'],
            'image' => $image,
        ]);
        return $id;
    }

    public function updateProduct($productId, $vendorId, $ownerId, array $product, $image = null)
    {
        $existing = $this->pdo->prepare(
            'SELECT p.image FROM pwesto_products p
             INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
             WHERE p.id = :id AND p.vendor_id = :vendor_id AND v.owner_id = :owner_id LIMIT 1'
        );
        $existing->execute(['id' => $productId, 'vendor_id' => $vendorId, 'owner_id' => $ownerId]);
        $previousImage = $existing->fetchColumn();
        if ($previousImage === false) {
            return false;
        }

        $query = $this->pdo->prepare(
            'UPDATE pwesto_products
             SET name = :name, description = :description, price_cents = :price_cents,
                 stock = :stock, image = COALESCE(:image, image), updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND vendor_id = :vendor_id
               AND EXISTS (
                   SELECT 1 FROM pwesto_vendors
                   WHERE pwesto_vendors.id = pwesto_products.vendor_id AND owner_id = :owner_id
               )'
        );
        $query->execute([
            'name' => $product['name'],
            'description' => $product['description'],
            'price_cents' => $product['price_cents'],
            'stock' => $product['stock'],
            'image' => $image,
            'id' => $productId,
            'vendor_id' => $vendorId,
            'owner_id' => $ownerId,
        ]);
        return $query->rowCount() > 0 ? ['previous_image' => $previousImage] : false;
    }

    public function deleteProduct($productId, $ownerId)
    {
        $this->pdo->beginTransaction();
        try {
            $existing = $this->pdo->prepare(
                'SELECT p.image FROM pwesto_products p
                 INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
                 WHERE p.id = :id AND v.owner_id = :owner_id LIMIT 1'
            );
            $existing->execute(['id' => $productId, 'owner_id' => $ownerId]);
            $image = $existing->fetchColumn();
            if ($image === false) {
                $this->pdo->rollBack();
                return false;
            }
            $query = $this->pdo->prepare(
                'DELETE FROM pwesto_products WHERE id = :id'
            );
            $query->execute(['id' => $productId]);
            $this->pdo->commit();
            return ['image' => $image];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function createOrder($customerId, $vendorId, array $items, $deliveryMethod = 'pickup', $deliveryAddress = null, $pickupAt = null, $paymentMethod = 'cash_on_pickup')
    {
        if (!in_array($paymentMethod, ['cash_on_pickup', 'cash_to_vendor', 'gcash'], true)) {
            return ['error' => 'Choose a supported payment method.', 'status' => 422];
        }
        $this->pdo->beginTransaction();
        try {
            $vendorQuery = $this->pdo->prepare(
                "SELECT v.name, v.owner_id
                 FROM pwesto_vendors v
                 INNER JOIN pwesto_users owner ON owner.id = v.owner_id AND owner.role = 'vendor'
                 WHERE v.id = :id AND v.status = 'approved' LIMIT 1"
            );
            $vendorQuery->execute(['id' => $vendorId]);
            $vendor = $vendorQuery->fetch(PDO::FETCH_ASSOC);
            if (!$vendor) {
                $this->pdo->rollBack();
                return ['error' => 'This vendor is not available for orders.', 'status' => 404];
            }
            if ($vendor['owner_id'] === null) {
                $this->pdo->rollBack();
                return ['error' => 'This stall only has sample catalog data and is not accepting orders.', 'status' => 409];
            }
            $vendorName = $vendor['name'];

            $productQuery = $this->pdo->prepare(
                "SELECT id, name, description, price_cents, stock
                 FROM pwesto_products
                 WHERE id = :id AND vendor_id = :vendor_id AND active = 1 LIMIT 1"
            );
            $orderItems = [];
            $totalCents = 0;
            foreach ($items as $item) {
                $productQuery->execute(['id' => $item['product_id'], 'vendor_id' => $vendorId]);
                $product = $productQuery->fetch();
                if (!$product || (int) $product['stock'] < $item['quantity']) {
                    $this->pdo->rollBack();
                    return ['error' => 'One or more products are unavailable or have insufficient stock. Refresh the products and try again.', 'status' => 409];
                }
                if (strpos((string) $product['description'], 'Sample dataset:') !== false) {
                    $this->pdo->rollBack();
                    return ['error' => 'This product has sample pricing and stock. The vendor must confirm its listing before it can be ordered.', 'status' => 409];
                }
                $lineTotal = (int) $product['price_cents'] * $item['quantity'];
                if ($lineTotal > PHP_INT_MAX - $totalCents) {
                    $this->pdo->rollBack();
                    return ['error' => 'The order total is too large.', 'status' => 422];
                }
                $totalCents += $lineTotal;
                $orderItems[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price_cents' => (int) $product['price_cents'],
                    'quantity' => $item['quantity'],
                ];
            }

            $orderId = bin2hex(random_bytes(16));
            $insertOrder = $this->pdo->prepare(
                "INSERT INTO pwesto_orders
                    (id, customer_id, vendor_id, status, delivery_method, delivery_address, pickup_at,
                     payment_method, payment_status, total_cents, created_at, updated_at)
                 VALUES (:id, :customer_id, :vendor_id, 'pending', :delivery_method, :delivery_address,
                         :pickup_at, :payment_method, :payment_status, :total_cents, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
            );
            $insertOrder->execute([
                'id' => $orderId,
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'pickup_at' => $pickupAt,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'gcash' ? 'pending' : 'due',
                'total_cents' => $totalCents,
            ]);

            $insertItem = $this->pdo->prepare(
                'INSERT INTO pwesto_order_items
                    (id, order_id, product_id, product_name, unit_price_cents, quantity)
                 VALUES (:id, :order_id, :product_id, :product_name, :unit_price_cents, :quantity)'
            );
            $decrementStock = $this->pdo->prepare(
                'UPDATE pwesto_products SET stock = stock - :quantity, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND stock >= :quantity'
            );
            foreach ($orderItems as $item) {
                $decrementStock->execute(['quantity' => $item['quantity'], 'id' => $item['id']]);
                if ($decrementStock->rowCount() !== 1) {
                    $this->pdo->rollBack();
                    return ['error' => 'Product stock changed while placing your order. Refresh and try again.', 'status' => 409];
                }
                $insertItem->execute([
                    'id' => bin2hex(random_bytes(16)),
                    'order_id' => $orderId,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'unit_price_cents' => $item['price_cents'],
                    'quantity' => $item['quantity'],
                ]);
            }
            if ($paymentMethod !== 'gcash') {
                $this->createNotification(
                    $this->vendorOwnerId($vendorId),
                    'product_order',
                    'New product order',
                    'A customer placed order #' . substr($orderId, 0, 8) . ' at ' . $vendorName . '.'
                );
            }
            $this->pdo->commit();
            $customerQuery = $this->pdo->prepare(
                'SELECT email, name FROM pwesto_users WHERE id = :id LIMIT 1'
            );
            $customerQuery->execute(['id' => $customerId]);
            $customer = $customerQuery->fetch();
            return [
                'order_id' => $orderId,
                'customer_email' => $customer['email'] ?? '',
                'customer_name' => $customer['name'] ?? '',
                'vendor_name' => $vendorName,
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'pickup_at' => $pickupAt,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'gcash' ? 'pending' : 'due',
                'total_cents' => $totalCents,
            ];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function saveOrderCheckout($orderId, $customerId, $checkoutId, $checkoutUrl, $amount)
    {
        $query = $this->pdo->prepare(
            "UPDATE pwesto_orders
             SET payment_checkout_id = :checkout_id, payment_checkout_url = :checkout_url,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND customer_id = :customer_id
               AND payment_method = 'gcash' AND payment_status = 'pending'
               AND total_cents = :amount"
        );
        $query->execute([
            'checkout_id' => $checkoutId,
            'checkout_url' => $checkoutUrl,
            'id' => $orderId,
            'customer_id' => $customerId,
            'amount' => $amount,
        ]);
        return $query->rowCount() === 1;
    }

    public function discardUnpaidGcashOrder($orderId, $customerId)
    {
        $this->pdo->beginTransaction();
        try {
            $order = $this->pdo->prepare(
                "SELECT id FROM pwesto_orders
                 WHERE id = :id AND customer_id = :customer_id
                   AND payment_method = 'gcash' AND payment_status = 'pending'
                 LIMIT 1"
            );
            $order->execute(['id' => $orderId, 'customer_id' => $customerId]);
            if (!$order->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }
            $items = $this->pdo->prepare(
                'SELECT product_id, quantity FROM pwesto_order_items
                 WHERE order_id = :order_id AND product_id IS NOT NULL'
            );
            $items->execute(['order_id' => $orderId]);
            $restore = $this->pdo->prepare(
                'UPDATE pwesto_products SET stock = stock + :quantity, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :product_id'
            );
            foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $restore->execute(['quantity' => $item['quantity'], 'product_id' => $item['product_id']]);
            }
            $delete = $this->pdo->prepare('DELETE FROM pwesto_orders WHERE id = :id');
            $delete->execute(['id' => $orderId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function markOrderPaidByCheckout($orderId, $checkoutId, $amount)
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare(
                "SELECT o.payment_status, o.payment_checkout_id, o.total_cents,
                        v.owner_id, v.name AS vendor_name
                 FROM pwesto_orders o
                 INNER JOIN pwesto_vendors v ON v.id = o.vendor_id
                 WHERE o.id = :id AND o.payment_method = 'gcash' LIMIT 1"
            );
            $query->execute(['id' => $orderId]);
            $order = $query->fetch(PDO::FETCH_ASSOC);
            if (!$order
                || $order['payment_checkout_id'] !== $checkoutId
                || (int) $order['total_cents'] !== $amount) {
                $this->pdo->rollBack();
                return false;
            }
            if ($order['payment_status'] === 'paid') {
                $this->pdo->commit();
                return true;
            }
            if ($order['payment_status'] !== 'pending') {
                $this->pdo->rollBack();
                return false;
            }
            $update = $this->pdo->prepare(
                "UPDATE pwesto_orders SET payment_status = 'paid', updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND payment_status = 'pending'"
            );
            $update->execute(['id' => $orderId]);
            if ($update->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }
            $this->createNotification(
                $order['owner_id'],
                'product_order',
                'Paid GCash order',
                'GCash payment confirmed for order #' . substr($orderId, 0, 8) . ' at ' . $order['vendor_name'] . '.'
            );
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function claimVendorPaymentEmail($orderId)
    {
        $this->pdo->beginTransaction();
        try {
            $claim = $this->pdo->prepare(
                "UPDATE pwesto_orders
                 SET vendor_receipt_email_status = 'sending',
                     vendor_receipt_email_attempted_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND payment_method = 'gcash' AND payment_status = 'paid'
                   AND (
                       vendor_receipt_email_status = 'pending'
                       OR (
                           vendor_receipt_email_status = 'sending'
                           AND vendor_receipt_email_attempted_at < :stale_before
                       )
                   )"
            );
            $claim->execute([
                'id' => $orderId,
                'stale_before' => gmdate('Y-m-d H:i:s', time() - 600),
            ]);
            if ($claim->rowCount() !== 1) {
                $this->pdo->commit();
                return null;
            }

            $query = $this->pdo->prepare(
                "SELECT o.id, o.total_cents, o.delivery_method, o.delivery_address, o.pickup_at,
                        o.payment_checkout_id, v.name AS vendor_name, owner.email AS vendor_email,
                        customer.name AS customer_name
                 FROM pwesto_orders o
                 INNER JOIN pwesto_vendors v ON v.id = o.vendor_id
                 INNER JOIN pwesto_users owner ON owner.id = v.owner_id AND owner.role = 'vendor'
                 INNER JOIN pwesto_users customer ON customer.id = o.customer_id
                 WHERE o.id = :id AND o.payment_method = 'gcash' AND o.payment_status = 'paid'
                 LIMIT 1"
            );
            $query->execute(['id' => $orderId]);
            $order = $query->fetch(PDO::FETCH_ASSOC);
            if (!$order) {
                $this->pdo->rollBack();
                return null;
            }
            $itemsQuery = $this->pdo->prepare(
                'SELECT product_name, unit_price_cents, quantity
                 FROM pwesto_order_items WHERE order_id = :order_id ORDER BY id'
            );
            $itemsQuery->execute(['order_id' => $orderId]);
            $order['items'] = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);
            $order['total_cents'] = (int) $order['total_cents'];
            foreach ($order['items'] as &$item) {
                $item['unit_price_cents'] = (int) $item['unit_price_cents'];
                $item['quantity'] = (int) $item['quantity'];
            }
            unset($item);
            $this->pdo->commit();
            return $order;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function completeVendorPaymentEmail($orderId, $sent)
    {
        if ($sent) {
            $query = $this->pdo->prepare(
                "UPDATE pwesto_orders SET vendor_receipt_email_status = 'sent'
                 WHERE id = :id AND vendor_receipt_email_status = 'sending'"
            );
            $query->execute(['id' => $orderId]);
            return $query->rowCount() === 1;
        }
        $query = $this->pdo->prepare(
            "UPDATE pwesto_orders
             SET vendor_receipt_email_status = 'pending',
                 vendor_receipt_email_attempted_at = NULL
             WHERE id = :id AND vendor_receipt_email_status = 'sending'"
        );
        $query->execute(['id' => $orderId]);
        return $query->rowCount() === 1;
    }

    public function pendingVendorPaymentEmails($ownerId)
    {
        $query = $this->pdo->prepare(
            "SELECT o.id
             FROM pwesto_orders o
             INNER JOIN pwesto_vendors v ON v.id = o.vendor_id
             WHERE v.owner_id = :owner_id AND o.payment_method = 'gcash'
               AND o.payment_status = 'paid'
               AND (
                   o.vendor_receipt_email_status = 'pending'
                   OR (
                       o.vendor_receipt_email_status = 'sending'
                       AND o.vendor_receipt_email_attempted_at < :stale_before
                   )
               )
             ORDER BY o.updated_at DESC
             LIMIT 10"
        );
        $query->execute([
            'owner_id' => $ownerId,
            'stale_before' => gmdate('Y-m-d H:i:s', time() - 600),
        ]);
        return array_column($query->fetchAll(PDO::FETCH_ASSOC), 'id');
    }

    public function orderCheckoutForCustomer($orderId, $customerId)
    {
        $query = $this->pdo->prepare(
            'SELECT payment_checkout_id, payment_status, total_cents
             FROM pwesto_orders
             WHERE id = :id AND customer_id = :customer_id AND payment_method = \'gcash\'
             LIMIT 1'
        );
        $query->execute(['id' => $orderId, 'customer_id' => $customerId]);
        $order = $query->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return null;
        }
        $order['total_cents'] = (int) $order['total_cents'];
        return $order;
    }

    public function ordersForCustomer($customerId)
    {
        return $this->ordersFor('customer_id', $customerId);
    }

    public function ordersForOwner($ownerId)
    {
        return $this->ordersFor('owner_id', $ownerId);
    }

    public function updateOrderStatus($orderId, $ownerId, $status)
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare(
                'SELECT o.status, o.customer_id, o.delivery_method, o.delivery_address,
                        u.email AS customer_email, u.name AS customer_name, v.name AS vendor_name
                 FROM pwesto_orders o
                 INNER JOIN pwesto_vendors v ON v.id = o.vendor_id
                 INNER JOIN pwesto_users u ON u.id = o.customer_id
                 WHERE o.id = :order_id AND v.owner_id = :owner_id LIMIT 1'
            );
            $query->execute(['order_id' => $orderId, 'owner_id' => $ownerId]);
            $order = $query->fetch();
            $transitions = $order && $order['delivery_method'] === 'delivery'
                ? [
                    'pending' => ['accepted', 'cancelled'],
                    'accepted' => ['preparing', 'cancelled'],
                    'preparing' => ['out_for_delivery', 'cancelled'],
                    'out_for_delivery' => ['delivered'],
                    'delivered' => [],
                    'ready' => [],
                    'completed' => [],
                    'cancelled' => [],
                ]
                : [
                    'pending' => ['accepted', 'cancelled'],
                    'accepted' => ['preparing', 'cancelled'],
                    'preparing' => ['ready', 'cancelled'],
                    'ready' => ['completed'],
                    'completed' => [],
                    'out_for_delivery' => [],
                    'delivered' => [],
                    'cancelled' => [],
                ];
            if (!$order || !in_array($status, $transitions[$order['status']] ?? [], true)) {
                $this->pdo->rollBack();
                return false;
            }

            $update = $this->pdo->prepare(
                'UPDATE pwesto_orders SET status = :status, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND status = :previous_status'
            );
            $update->execute([
                'status' => $status,
                'id' => $orderId,
                'previous_status' => $order['status'],
            ]);
            if ($update->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            if ($status === 'cancelled') {
                $restoreStock = $this->pdo->prepare(
                    'UPDATE pwesto_products SET stock = stock + (
                        SELECT quantity FROM pwesto_order_items
                        WHERE order_id = :order_id AND product_id = pwesto_products.id
                    ), updated_at = CURRENT_TIMESTAMP
                    WHERE id IN (SELECT product_id FROM pwesto_order_items WHERE order_id = :order_id)'
                );
                $restoreStock->execute(['order_id' => $orderId]);
            }
            $this->createNotification(
                $order['customer_id'],
                'product_order_update',
                'Order ' . $status,
                $order['vendor_name'] . ' updated your order to ' . $status . '.'
            );
            $this->pdo->commit();
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
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function ordersFor($identityColumn, $identity)
    {
        if (!in_array($identityColumn, ['customer_id', 'owner_id'], true)) {
            throw new InvalidArgumentException('Unsupported order owner column.');
        }
        $query = $this->pdo->prepare(
            "SELECT o.id, o.customer_id, o.vendor_id, o.status, o.delivery_method,
                    o.delivery_address, o.pickup_at, o.payment_method, o.payment_status,
                    o.payment_checkout_url, o.total_cents, o.created_at,
                    v.name AS vendor_name, u.name AS customer_name
             FROM pwesto_orders o
             INNER JOIN pwesto_vendors v ON v.id = o.vendor_id
             INNER JOIN pwesto_users u ON u.id = o.customer_id
             WHERE " . ($identityColumn === 'owner_id'
                 ? "v.owner_id = :identity AND (o.payment_method != 'gcash' OR o.payment_status = 'paid')"
                 : 'o.customer_id = :identity') . "
             ORDER BY o.created_at DESC"
        );
        $query->execute(['identity' => $identity]);
        $orders = $query->fetchAll();
        if (!$orders) {
            return [];
        }
        $itemsQuery = $this->pdo->prepare(
            'SELECT product_name, unit_price_cents, quantity
             FROM pwesto_order_items WHERE order_id = :order_id ORDER BY product_name, id'
        );
        foreach ($orders as &$order) {
            $itemsQuery->execute(['order_id' => $order['id']]);
            $order['items'] = $itemsQuery->fetchAll();
            $order['total_cents'] = (int) $order['total_cents'];
        }
        unset($order);
        return $orders;
    }

    private function vendorOwnerId($vendorId)
    {
        $query = $this->pdo->prepare('SELECT owner_id FROM pwesto_vendors WHERE id = :id');
        $query->execute(['id' => $vendorId]);
        return $query->fetchColumn();
    }

    public function startConversation($customerId, $vendorId)
    {
        $vendorQuery = $this->pdo->prepare(
            "SELECT owner_id FROM pwesto_vendors WHERE id = :vendor_id AND status = 'approved' LIMIT 1"
        );
        $vendorQuery->execute(['vendor_id' => $vendorId]);
        $ownerId = $vendorQuery->fetchColumn();
        if ($ownerId === false || $ownerId === null || $ownerId === $customerId) {
            return null;
        }

        $conversationId = bin2hex(random_bytes(16));
        $sql = 'INSERT INTO pwesto_conversations (id, vendor_id, customer_id, created_at, updated_at)
                VALUES (:id, :vendor_id, :customer_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
        $sql .= $this->isMysql()
            ? ' ON DUPLICATE KEY UPDATE id = id'
            : ' ON CONFLICT(vendor_id, customer_id) DO NOTHING';
        $insert = $this->pdo->prepare($sql);
        $insert->execute([
            'id' => $conversationId,
            'vendor_id' => $vendorId,
            'customer_id' => $customerId,
        ]);

        $query = $this->pdo->prepare(
            'SELECT id FROM pwesto_conversations WHERE vendor_id = :vendor_id AND customer_id = :customer_id LIMIT 1'
        );
        $query->execute(['vendor_id' => $vendorId, 'customer_id' => $customerId]);
        return $query->fetchColumn() ?: null;
    }

    public function conversationsForUser($userId)
    {
        $query = $this->pdo->prepare(
            'SELECT c.id, c.vendor_id, c.customer_id, v.name AS stall_name, v.market,
                    CASE WHEN c.customer_id = :customer_case THEN owner.name ELSE customer.name END AS other_name,
                    (SELECT m.body FROM pwesto_messages m
                     WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message,
                    (SELECT m.created_at FROM pwesto_messages m
                     WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message_at,
                    (SELECT COUNT(*) FROM pwesto_messages unread
                     WHERE unread.conversation_id = c.id AND unread.sender_id != :unread_sender
                       AND unread.read_at IS NULL) AS unread_count
             FROM pwesto_conversations c
             INNER JOIN pwesto_vendors v ON v.id = c.vendor_id
             INNER JOIN pwesto_users customer ON customer.id = c.customer_id
             LEFT JOIN pwesto_users owner ON owner.id = v.owner_id
             WHERE c.customer_id = :customer_id
                OR EXISTS (
                    SELECT 1 FROM pwesto_vendors owned
                    WHERE owned.id = c.vendor_id AND owned.owner_id = :owner_id
                )
             ORDER BY COALESCE(c.updated_at, c.created_at) DESC'
        );
        $query->execute([
            'customer_case' => $userId,
            'unread_sender' => $userId,
            'customer_id' => $userId,
            'owner_id' => $userId,
        ]);
        return $query->fetchAll();
    }

    public function conversationMessages($conversationId, $userId)
    {
        if (!$this->isConversationParticipant($conversationId, $userId)) {
            return null;
        }
        $read = $this->pdo->prepare(
            'UPDATE pwesto_messages SET read_at = CURRENT_TIMESTAMP
             WHERE conversation_id = :conversation_id AND sender_id != :reader_id AND read_at IS NULL'
        );
        $read->execute(['conversation_id' => $conversationId, 'reader_id' => $userId]);

        $query = $this->pdo->prepare(
            'SELECT m.id, m.sender_id, u.name AS sender_name, m.body, m.created_at, m.read_at
             FROM pwesto_messages m
             INNER JOIN pwesto_users u ON u.id = m.sender_id
             WHERE m.conversation_id = :conversation_id
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $query->execute(['conversation_id' => $conversationId]);
        return $query->fetchAll();
    }

    public function sendConversationMessage($conversationId, $senderId, $body)
    {
        if (!$this->isConversationParticipant($conversationId, $senderId)) {
            return false;
        }
        $conversationQuery = $this->pdo->prepare(
            'SELECT c.customer_id, v.owner_id, v.name AS stall_name
             FROM pwesto_conversations c
             INNER JOIN pwesto_vendors v ON v.id = c.vendor_id
             WHERE c.id = :conversation_id LIMIT 1'
        );
        $conversationQuery->execute(['conversation_id' => $conversationId]);
        $conversation = $conversationQuery->fetch();
        if (!$conversation) {
            return false;
        }
        $recipientId = $conversation['customer_id'] === $senderId
            ? $conversation['owner_id']
            : $conversation['customer_id'];
        $senderQuery = $this->pdo->prepare('SELECT name FROM pwesto_users WHERE id = :id LIMIT 1');
        $senderQuery->execute(['id' => $senderId]);
        $senderName = $senderQuery->fetchColumn();
        $messageId = bin2hex(random_bytes(16));
        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO pwesto_messages (id, conversation_id, sender_id, body, created_at)
                 VALUES (:id, :conversation_id, :sender_id, :body, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'id' => $messageId,
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'body' => $body,
            ]);
            $update = $this->pdo->prepare(
                'UPDATE pwesto_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $update->execute(['id' => $conversationId]);
            if ($recipientId !== null && $senderName !== false) {
                $this->createNotification(
                    $recipientId,
                    'message',
                    $senderName . ' sent you a message',
                    'New message in your conversation about ' . $conversation['stall_name'] . '.'
                );
            }
            $this->pdo->commit();
            return $messageId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function notificationsForUser($userId)
    {
        $query = $this->pdo->prepare(
            'SELECT id, type, title, message, created_at, read_at
             FROM pwesto_notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT 50'
        );
        $query->execute(['user_id' => $userId]);
        $notifications = $query->fetchAll();
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM pwesto_notifications WHERE user_id = :user_id AND read_at IS NULL'
        );
        $count->execute(['user_id' => $userId]);
        return ['notifications' => $notifications, 'unread_count' => (int) $count->fetchColumn()];
    }

    public function markNotificationRead($userId, $notificationId)
    {
        $query = $this->pdo->prepare(
            'UPDATE pwesto_notifications SET read_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id AND read_at IS NULL'
        );
        $query->execute(['id' => $notificationId, 'user_id' => $userId]);
        if ($query->rowCount() === 1) {
            return true;
        }
        $exists = $this->pdo->prepare(
            'SELECT 1 FROM pwesto_notifications WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $exists->execute(['id' => $notificationId, 'user_id' => $userId]);
        return $exists->fetchColumn() !== false;
    }

    public function markAllNotificationsRead($userId)
    {
        $query = $this->pdo->prepare(
            'UPDATE pwesto_notifications SET read_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND read_at IS NULL'
        );
        $query->execute(['user_id' => $userId]);
    }

    private function createNotification($userId, $type, $title, $message)
    {
        $query = $this->pdo->prepare(
            'INSERT INTO pwesto_notifications (id, user_id, type, title, message, created_at)
             VALUES (:id, :user_id, :type, :title, :message, CURRENT_TIMESTAMP)'
        );
        $query->execute([
            'id' => bin2hex(random_bytes(16)),
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function isConversationParticipant($conversationId, $userId)
    {
        $query = $this->pdo->prepare(
            'SELECT 1 FROM pwesto_conversations c
             INNER JOIN pwesto_vendors v ON v.id = c.vendor_id
             WHERE c.id = :conversation_id AND (c.customer_id = :customer_id OR v.owner_id = :owner_id)
             LIMIT 1'
        );
        $query->execute([
            'conversation_id' => $conversationId,
            'customer_id' => $userId,
            'owner_id' => $userId,
        ]);
        return $query->fetchColumn() !== false;
    }

    public function saveUser(array $user)
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('SELECT id FROM pwesto_users WHERE google_sub = :google_sub LIMIT 1');
            $query->execute(['google_sub' => $user['id']]);
            $userId = $query->fetchColumn();

            if ($userId === false) {
                $query = $this->pdo->prepare('SELECT id FROM pwesto_users WHERE email = :email LIMIT 1');
                $query->execute(['email' => $user['email']]);
                $userId = $query->fetchColumn();
            }

            if ($userId === false) {
                $userId = $user['id'];
                $insert = $this->pdo->prepare(
                    'INSERT INTO pwesto_users
                        (id, google_sub, email, name, picture, password_hash, role, created_at, updated_at)
                     VALUES (:id, :google_sub, :email, :name, :picture, NULL, :role, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $insert->execute([
                    'id' => $userId,
                    'google_sub' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'picture' => $user['picture'],
                    'role' => $user['role'] ?? 'customer',
                ]);
            } else {
                $update = $this->pdo->prepare(
                    'UPDATE pwesto_users SET google_sub = :google_sub,
                        name = CASE WHEN profile_customized = 1 THEN name ELSE :name END,
                        picture = CASE WHEN profile_customized = 1 THEN picture ELSE :picture END,
                        role = CASE WHEN :role = \'admin\' THEN \'admin\' ELSE role END,
                        updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $update->execute([
                    'google_sub' => $user['id'],
                    'name' => $user['name'],
                    'picture' => $user['picture'],
                    'role' => $user['role'] ?? 'customer',
                    'id' => $userId,
                ]);
            }
            $this->pdo->commit();
            return $this->publicUserById($userId);
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function registerEmailUser($email, $name, $password, $role)
    {
        $id = bin2hex(random_bytes(16));
        $query = $this->pdo->prepare(
            'INSERT INTO pwesto_users
                (id, google_sub, email, name, picture, password_hash, role, created_at, updated_at)
             VALUES (:id, NULL, :email, :name, \'\', :password_hash, :role, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $query->execute([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
        return $this->publicUserById($id);
    }

    public function grantAdminRole($userId)
    {
        $query = $this->pdo->prepare(
            "UPDATE pwesto_users SET role = 'admin', updated_at = CURRENT_TIMESTAMP WHERE id = :id"
        );
        $query->execute(['id' => $userId]);
        return $this->publicUserById($userId);
    }

    public function authenticateEmailUser($email, $password)
    {
        $query = $this->pdo->prepare(
            'SELECT id, email, name, picture, password_hash, role
             FROM pwesto_users WHERE email = :email LIMIT 1'
        );
        $query->execute(['email' => $email]);
        $user = $query->fetch();
        if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $update = $this->pdo->prepare(
                'UPDATE pwesto_users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
        }

        unset($user['password_hash']);
        return $user;
    }

    public function createPasswordResetRequest($email)
    {
        $query = $this->pdo->prepare(
            'SELECT id, email, name, password_hash FROM pwesto_users WHERE email = :email LIMIT 1'
        );
        $query->execute(['email' => $email]);
        $user = $query->fetch();
        if (!$user) {
            return ['status' => 'not_found'];
        }

        $cutoff = $this->isMysql()
            ? 'DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 60 SECOND)'
            : "datetime('now', '-60 seconds')";
        $recent = $this->pdo->prepare(
            'SELECT 1 FROM pwesto_password_resets WHERE user_id = :user_id AND created_at >= ' . $cutoff . ' LIMIT 1'
        );
        $recent->execute(['user_id' => $user['id']]);
        if ($recent->fetchColumn()) {
            return ['status' => 'rate_limited'];
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = $this->isMysql()
            ? 'DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)'
            : "datetime('now', '+1 hour')";
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM pwesto_password_resets WHERE user_id = :user_id');
            $delete->execute(['user_id' => $user['id']]);
            $insert = $this->pdo->prepare(
                'INSERT INTO pwesto_password_resets (token_hash, user_id, expires_at, created_at)
                 VALUES (:token_hash, :user_id, ' . $expiresAt . ', CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'token_hash' => $tokenHash,
                'user_id' => $user['id'],
            ]);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return [
            'status' => 'created',
            'token' => $token,
            'token_hash' => $tokenHash,
            'email' => $user['email'],
            'name' => $user['name'],
        ];
    }

    public function deletePasswordReset($tokenHash)
    {
        $query = $this->pdo->prepare('DELETE FROM pwesto_password_resets WHERE token_hash = :token_hash');
        $query->execute(['token_hash' => $tokenHash]);
    }

    public function resetPassword($token, $password)
    {
        $tokenHash = hash('sha256', $token);
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare(
                'SELECT user_id FROM pwesto_password_resets
                 WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP LIMIT 1'
            );
            $query->execute(['token_hash' => $tokenHash]);
            $userId = $query->fetchColumn();
            if ($userId === false) {
                $this->pdo->rollBack();
                return false;
            }

            $update = $this->pdo->prepare(
                'UPDATE pwesto_users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $userId,
            ]);
            if ($update->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }
            $delete = $this->pdo->prepare('DELETE FROM pwesto_password_resets WHERE user_id = :user_id');
            $delete->execute(['user_id' => $userId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function updateUserProfile($userId, $name, $picture)
    {
        $query = $this->pdo->prepare(
            'UPDATE pwesto_users SET name = :name, picture = :picture,
                 profile_customized = 1, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $query->execute(['name' => $name, 'picture' => $picture, 'id' => $userId]);
        return $this->publicUserById($userId);
    }

    public function emailAttemptAllowed($email, $ip)
    {
        $identities = $this->authIdentityHashes($email, $ip);
        $query = $this->pdo->prepare(
            'SELECT attempts, window_started FROM pwesto_auth_attempts
             WHERE identity_hash IN (:email_identity, :ip_identity)'
        );
        $query->execute([
            'email_identity' => $identities[0],
            'ip_identity' => $identities[1],
        ]);
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $record) {
            if ((int) $record['window_started'] > time() - 900 && (int) $record['attempts'] >= 8) {
                return false;
            }
        }
        return true;
    }

    public function recordFailedEmailAttempt($email, $ip)
    {
        $sql = 'INSERT INTO pwesto_auth_attempts (identity_hash, attempts, window_started)
                VALUES (:email_identity, 1, :now), (:ip_identity, 1, :now)';
        $sql .= $this->isMysql()
            ? ' ON DUPLICATE KEY UPDATE
                    attempts = CASE WHEN window_started <= :cutoff_attempt THEN 1 ELSE attempts + 1 END,
                    window_started = CASE WHEN window_started <= :cutoff_window THEN :now_window ELSE window_started END'
            : ' ON CONFLICT(identity_hash) DO UPDATE SET
                    attempts = CASE WHEN window_started <= :cutoff_attempt THEN 1 ELSE attempts + 1 END,
                    window_started = CASE WHEN window_started <= :cutoff_window THEN :now_window ELSE window_started END';
        $query = $this->pdo->prepare($sql);
        $now = time();
        $identities = $this->authIdentityHashes($email, $ip);
        $query->execute([
            'email_identity' => $identities[0],
            'ip_identity' => $identities[1],
            'now' => $now,
            'cutoff_attempt' => $now - 900,
            'cutoff_window' => $now - 900,
            'now_window' => $now,
        ]);
    }

    public function clearEmailAttempts($email, $ip)
    {
        $identities = $this->authIdentityHashes($email, $ip);
        $query = $this->pdo->prepare(
            'DELETE FROM pwesto_auth_attempts
             WHERE identity_hash IN (:email_identity, :ip_identity)'
        );
        $query->execute([
            'email_identity' => $identities[0],
            'ip_identity' => $identities[1],
        ]);
    }

    private function authIdentityHashes($email, $ip)
    {
        return [
            hash('sha256', 'email:' . strtolower($email)),
            hash('sha256', 'ip:' . $ip),
        ];
    }

    private function publicUserById($id)
    {
        $query = $this->pdo->prepare('SELECT id, email, name, picture, role FROM pwesto_users WHERE id = :id LIMIT 1');
        $query->execute(['id' => $id]);
        $user = $query->fetch();
        if (!$user) {
            throw new RuntimeException('Pwesto account was not available after saving.');
        }
        return $user;
    }

    public function favorites($userId)
    {
        $query = $this->pdo->prepare(
            'SELECT vendor_id FROM pwesto_favorites WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $query->execute(['user_id' => $userId]);
        return array_map('strval', $query->fetchAll(PDO::FETCH_COLUMN));
    }

    public function setFavorite($userId, $vendorId, $saved)
    {
        if ($this->vendor($vendorId) === null) {
            return false;
        }

        if ($saved) {
            $sql = 'INSERT INTO pwesto_favorites (user_id, vendor_id, created_at)
                    VALUES (:user_id, :vendor_id, CURRENT_TIMESTAMP)';
            $sql .= $this->isMysql()
                ? ' ON DUPLICATE KEY UPDATE user_id = user_id'
                : ' ON CONFLICT(user_id, vendor_id) DO NOTHING';
            $query = $this->pdo->prepare($sql);
            $query->execute(['user_id' => $userId, 'vendor_id' => $vendorId]);
        } else {
            $query = $this->pdo->prepare(
                'DELETE FROM pwesto_favorites WHERE user_id = :user_id AND vendor_id = :vendor_id'
            );
            $query->execute(['user_id' => $userId, 'vendor_id' => $vendorId]);
        }
        return true;
    }

    public function appointments($userId)
    {
        $query = $this->pdo->prepare(
            'SELECT a.id, a.vendor_id, v.name AS vendor_name, a.visit_date, a.visit_time,
                    a.status, a.created_at
             FROM pwesto_appointments a
             INNER JOIN pwesto_vendors v ON v.id = a.vendor_id
             WHERE a.user_id = :user_id
             ORDER BY a.visit_date ASC, a.visit_time ASC, a.created_at DESC'
        );
        $query->execute(['user_id' => $userId]);
        return $query->fetchAll();
    }

    public function accountSessionData($userId)
    {
        $query = $this->pdo->prepare(
            "SELECT 'favorite' AS item_type, f.vendor_id, NULL AS appointment_id,
                    NULL AS vendor_name, NULL AS visit_date, NULL AS visit_time,
                   NULL AS status, f.created_at
             FROM pwesto_favorites f
             WHERE f.user_id = :favorite_user
             UNION ALL
             SELECT 'appointment' AS item_type, a.vendor_id, a.id AS appointment_id,
                    v.name AS vendor_name, a.visit_date, a.visit_time,
                    a.status, a.created_at
             FROM pwesto_appointments a
             INNER JOIN pwesto_vendors v ON v.id = a.vendor_id
             WHERE a.user_id = :appointment_user
             ORDER BY item_type, visit_date, visit_time, created_at DESC"
        );
        $query->execute([
            'favorite_user' => $userId,
            'appointment_user' => $userId,
        ]);
        $favorites = [];
        $appointments = [];
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['item_type'] === 'favorite') {
                $favorites[] = (string) $row['vendor_id'];
                continue;
            }
            $appointments[] = [
                'id' => $row['appointment_id'],
                'vendor_id' => $row['vendor_id'],
                'vendor_name' => $row['vendor_name'],
                'visit_date' => $row['visit_date'],
                'visit_time' => $row['visit_time'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
            ];
        }
        return ['favorites' => $favorites, 'appointments' => $appointments];
    }

    public function pendingVendors()
    {
        $query = $this->pdo->query(
            "SELECT v.id, v.stall_code, v.name, v.category, v.market, v.specialty, v.status, v.created_at,
                    CASE WHEN v.rental_paid_until >= CURRENT_DATE THEN 'paid'
                         WHEN v.rental_checkout_id IS NOT NULL AND v.rental_payment_status = 'pending' THEN 'pending'
                         ELSE 'unpaid' END AS rental_payment_status,
                    CASE WHEN v.application_document IS NULL THEN 0 ELSE 1 END AS has_application,
                    CASE WHEN v.cedula_document IS NULL THEN 0 ELSE 1 END AS has_cedula,
                    CASE WHEN v.photo_document IS NULL THEN 0 ELSE 1 END AS has_photo,
                    u.name AS owner_name, u.email AS owner_email
             FROM pwesto_vendors v
             LEFT JOIN pwesto_users u ON u.id = v.owner_id
             WHERE v.status = 'pending'
             ORDER BY v.created_at ASC"
        );
        return $query->fetchAll();
    }

    public function unassignedVendors()
    {
        $query = $this->pdo->query(
            "SELECT id, stall_code, name, category, market, status
             FROM pwesto_vendors
             WHERE owner_id IS NULL
             ORDER BY category, stall_code, name"
        );
        return $query->fetchAll();
    }

    public function assignVendorAccount($vendorId, $email, $name)
    {
        $this->pdo->beginTransaction();
        try {
            $lock = $this->isMysql() ? ' FOR UPDATE' : '';
            $vendorQuery = $this->pdo->prepare(
                'SELECT id, owner_id FROM pwesto_vendors WHERE id = :id LIMIT 1' . $lock
            );
            $vendorQuery->execute(['id' => $vendorId]);
            $vendor = $vendorQuery->fetch();
            if (!$vendor) {
                $this->pdo->rollBack();
                return ['status' => 'not_found'];
            }
            if ($vendor['owner_id'] !== null) {
                $this->pdo->rollBack();
                return ['status' => 'already_assigned'];
            }

            $userQuery = $this->pdo->prepare(
                'SELECT id, name, email, role FROM pwesto_users WHERE email = :email LIMIT 1' . $lock
            );
            $userQuery->execute(['email' => $email]);
            $user = $userQuery->fetch();
            $created = false;
            if ($user) {
                if ($user['role'] !== 'vendor') {
                    $this->pdo->rollBack();
                    return ['status' => 'role_conflict'];
                }
                $ownedQuery = $this->pdo->prepare(
                    'SELECT id FROM pwesto_vendors WHERE owner_id = :owner_id LIMIT 1'
                );
                $ownedQuery->execute(['owner_id' => $user['id']]);
                if ($ownedQuery->fetchColumn()) {
                    $this->pdo->rollBack();
                    return ['status' => 'account_in_use'];
                }
            } else {
                $userId = bin2hex(random_bytes(16));
                $createUser = $this->pdo->prepare(
                    "INSERT INTO pwesto_users
                        (id, google_sub, email, name, picture, password_hash, role, created_at, updated_at)
                     VALUES (:id, NULL, :email, :name, '', NULL, 'vendor', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
                );
                $createUser->execute(['id' => $userId, 'email' => $email, 'name' => $name]);
                $user = ['id' => $userId, 'email' => $email, 'name' => $name, 'role' => 'vendor'];
                $created = true;
            }

            $assign = $this->pdo->prepare(
                'UPDATE pwesto_vendors SET owner_id = :owner_id WHERE id = :id AND owner_id IS NULL'
            );
            $assign->execute(['owner_id' => $user['id'], 'id' => $vendorId]);
            if ($assign->rowCount() !== 1) {
                $this->pdo->rollBack();
                return ['status' => 'already_assigned'];
            }
            $this->pdo->commit();
            return ['status' => 'assigned', 'account_created' => $created];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function adminStorageFiles()
    {
        $products = $this->pdo->query(
            "SELECT p.id, p.vendor_id, p.name AS product_name, p.image,
                    CASE WHEN p.description LIKE '%Sample dataset:%' THEN 1 ELSE 0 END AS is_sample,
                    v.name AS vendor_name, v.status AS vendor_status
             FROM pwesto_products p
             INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
             WHERE p.image IS NOT NULL AND p.image != ''
             ORDER BY v.name, p.name"
        )->fetchAll();
        $catalog = $this->pdo->query(
            "SELECT p.name AS product_name, p.description, p.price_cents, p.stock, p.active,
                    CASE WHEN p.description LIKE '%Sample dataset:%' THEN 1 ELSE 0 END AS is_sample,
                    v.name AS vendor_name, v.stall_code, v.category AS vendor_category,
                    v.status AS vendor_status
             FROM pwesto_products p
             INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
             ORDER BY v.category, v.stall_code, p.name"
        )->fetchAll();
        $stalls = $this->pdo->query(
            "SELECT stall_code, name, category, status, image,
                    CASE WHEN image LIKE 'https://images.unsplash.com/%' THEN 1 ELSE 0 END AS is_sample_image
             FROM pwesto_vendors
             ORDER BY category, stall_code, name"
        )->fetchAll();
        $vendors = $this->pdo->query(
            "SELECT v.id, v.stall_code, v.name, v.category, v.status,
                    v.rental_payment_status AS stored_rental_payment_status,
                    CASE WHEN v.rental_paid_until >= CURRENT_DATE THEN 'paid'
                         WHEN v.rental_checkout_id IS NOT NULL AND v.rental_payment_status = 'pending' THEN 'pending'
                         ELSE 'unpaid' END AS rental_payment_status,
                    v.rental_checkout_amount, v.rental_paid_at, v.rental_paid_until,
                    v.application_document, v.cedula_document, v.photo_document,
                    u.name AS owner_name, u.email AS owner_email
             FROM pwesto_vendors v
             LEFT JOIN pwesto_users u ON u.id = v.owner_id
             ORDER BY v.category, v.stall_code, v.name"
        )->fetchAll();
        return [
            'products' => $products,
            'catalog' => $catalog,
            'stalls' => $stalls,
            'vendors' => $vendors,
        ];
    }

    public function reviewVendor($vendorId, $decision)
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            if ($decision === 'approved') {
                $requirements = $this->pdo->prepare(
                    "SELECT 1 FROM pwesto_vendors
                     WHERE id = :id AND status = 'pending'
                       AND application_document IS NOT NULL
                       AND cedula_document IS NOT NULL
                       AND photo_document IS NOT NULL
                       AND rental_paid_until >= CURRENT_DATE
                     LIMIT 1"
                );
                $requirements->execute(['id' => $vendorId]);
                if (!$requirements->fetchColumn()) {
                    $this->pdo->rollBack();
                    return false;
                }
            }
            $query = $this->pdo->prepare(
                "UPDATE pwesto_vendors SET status = :status
                 WHERE id = :id AND status = 'pending'"
            );
            $query->execute(['status' => $decision, 'id' => $vendorId]);
            if ($query->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            if ($decision === 'approved') {
                $owner = $this->pdo->prepare(
                    "UPDATE pwesto_users SET role = 'vendor', updated_at = CURRENT_TIMESTAMP
                     WHERE id = (SELECT owner_id FROM pwesto_vendors WHERE id = :id)
                       AND role != 'admin'"
                );
                $owner->execute(['id' => $vendorId]);
            }
            $details = $this->pdo->prepare(
                'SELECT owner_id, name FROM pwesto_vendors WHERE id = :id LIMIT 1'
            );
            $details->execute(['id' => $vendorId]);
            $vendor = $details->fetch();
            if ($vendor && $vendor['owner_id'] !== null) {
                $approved = $decision === 'approved';
                $this->createNotification(
                    $vendor['owner_id'],
                    'rental_review',
                    $approved ? 'Your stall rental was approved' : 'Your stall rental was declined',
                    $approved
                        ? $vendor['name'] . ' is approved and now visible to customers.'
                        : $vendor['name'] . ' was not approved. Check your stall dashboard for next steps.'
                );
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function adminDashboard()
    {
        $summary = $this->pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM pwesto_users) AS users,
                (SELECT COUNT(*) FROM pwesto_users WHERE role = 'customer') AS customers,
                (SELECT COUNT(*) FROM pwesto_users WHERE role = 'vendor') AS vendors,
                (SELECT COUNT(*) FROM pwesto_vendors WHERE status = 'approved') AS approved_stalls,
                (SELECT COUNT(*) FROM pwesto_vendors WHERE status = 'pending') AS pending_applications,
                (SELECT COUNT(*) FROM pwesto_appointments) AS visits,
                (SELECT COUNT(*) FROM pwesto_products p
                    INNER JOIN pwesto_vendors v ON v.id = p.vendor_id
                    WHERE p.active = 1 AND v.status = 'approved') AS active_products,
                (SELECT COUNT(*) FROM pwesto_orders
                    WHERE status NOT IN ('completed', 'delivered', 'cancelled')) AS open_orders"
        )->fetch();
        $summary['category_breakdown'] = $this->pdo->query(
            "SELECT category, COUNT(*) AS stalls
             FROM pwesto_vendors
             WHERE status = 'approved'
             GROUP BY category
             ORDER BY category"
        )->fetchAll();
        return $summary;
    }

    public function createAppointment($userId, $vendorId, $visitDate, $visitTime)
    {
        if ($this->vendor($vendorId) === null) {
            return false;
        }

        $id = bin2hex(random_bytes(16));
        $query = $this->pdo->prepare(
            "INSERT INTO pwesto_appointments
                (id, user_id, vendor_id, visit_date, visit_time, status, created_at)
             VALUES (:id, :user_id, :vendor_id, :visit_date, :visit_time, 'Planned', CURRENT_TIMESTAMP)"
        );
        $query->execute([
            'id' => $id,
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'visit_date' => $visitDate,
            'visit_time' => $visitTime,
        ]);
        return $id;
    }

    public function cancelAppointment($userId, $appointmentId)
    {
        $query = $this->pdo->prepare(
            "UPDATE pwesto_appointments SET status = 'Cancelled'
             WHERE id = :id AND user_id = :user_id AND status = 'Planned'"
        );
        $query->execute(['id' => $appointmentId, 'user_id' => $userId]);
        return $query->rowCount() === 1;
    }

    public function createVendor(array $vendor)
    {
        $markets = $this->marketLocations();
        $location = $markets[$vendor['market']];
        $id = $vendor['id'] ?? bin2hex(random_bytes(12));
        $this->pdo->beginTransaction();
        try {
            $stallCode = $this->nextStallCode($vendor['category']);
            $query = $this->pdo->prepare(
                "INSERT INTO pwesto_vendors
                    (id, owner_id, stall_code, name, category, market, specialty, rating, latitude,
                     longitude, icon, image, application_document, cedula_document,
                     photo_document, status, rental_payment_status, created_at)
                 VALUES (:id, :owner_id, :stall_code, :name, :category, :market, :specialty, 0,
                         :latitude, :longitude, :icon, :image, :application_document,
                         :cedula_document, :photo_document, 'pending', 'unpaid', CURRENT_TIMESTAMP)"
            );
            $query->execute([
                'id' => $id,
                'owner_id' => $vendor['owner_id'],
                'stall_code' => $stallCode,
                'name' => $vendor['name'],
                'category' => $vendor['category'],
                'market' => $vendor['market'],
                'specialty' => $vendor['specialty'],
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'icon' => '⌂',
                'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=220&h=220&q=80',
                'application_document' => $vendor['application_document'],
                'cedula_document' => $vendor['cedula_document'],
                'photo_document' => $vendor['photo_document'],
            ]);
            $this->notifyAdmins(
                'rental_request',
                'New stall rental request',
                $vendor['name'] . ' submitted a rental request for ' . $vendor['market'] . '.'
            );
            $this->pdo->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function notifyAdmins($type, $title, $message)
    {
        $admins = $this->pdo->query("SELECT id FROM pwesto_users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $adminId) {
            $this->createNotification($adminId, $type, $title, $message);
        }
    }

    public function vendorsForOwner($userId)
    {
        $query = $this->pdo->prepare(
            "SELECT id, stall_code, name, category, market, specialty, status, created_at,
                    CASE WHEN rental_paid_until >= CURRENT_DATE THEN 'paid'
                         WHEN rental_checkout_id IS NOT NULL AND rental_payment_status = 'pending' THEN 'pending'
                         ELSE 'unpaid' END AS rental_payment_status,
                    CASE WHEN rental_payment_status = 'pending' THEN rental_checkout_url ELSE NULL END AS rental_checkout_url,
                    rental_paid_until,
                    CASE WHEN application_document IS NULL THEN 0 ELSE 1 END AS has_application,
                    CASE WHEN cedula_document IS NULL THEN 0 ELSE 1 END AS has_cedula,
                    CASE WHEN photo_document IS NULL THEN 0 ELSE 1 END AS has_photo
             FROM pwesto_vendors WHERE owner_id = :owner_id ORDER BY created_at DESC"
        );
        $query->execute(['owner_id' => $userId]);
        return $query->fetchAll();
    }

    public function vendorDocument($vendorId, $kind)
    {
        $columns = [
            'application' => 'application_document',
            'cedula' => 'cedula_document',
            'photo' => 'photo_document',
        ];
        if (!isset($columns[$kind])) {
            return null;
        }
        $query = $this->pdo->prepare(
            'SELECT ' . $columns[$kind] . ' FROM pwesto_vendors WHERE id = :id LIMIT 1'
        );
        $query->execute(['id' => $vendorId]);
        $path = $query->fetchColumn();
        return is_string($path) && $path !== '' ? $path : null;
    }

    public function updateVendorDocuments($vendorId, $ownerId, array $documents)
    {
        $allowed = ['application_document', 'cedula_document', 'photo_document'];
        $documents = array_intersect_key($documents, array_flip($allowed));
        if (!$documents) {
            return false;
        }
        $current = $this->pdo->prepare(
            'SELECT application_document, cedula_document, photo_document
             FROM pwesto_vendors WHERE id = :id AND owner_id = :owner_id LIMIT 1'
        );
        $current->execute(['id' => $vendorId, 'owner_id' => $ownerId]);
        $previous = $current->fetch();
        if (!$previous) {
            return false;
        }
        $assignments = [];
        foreach ($documents as $column => $fileName) {
            $assignments[] = $column . ' = :' . $column;
        }
        $query = $this->pdo->prepare(
            'UPDATE pwesto_vendors SET ' . implode(', ', $assignments)
            . ' WHERE id = :id AND owner_id = :owner_id'
        );
        $documents['id'] = $vendorId;
        $documents['owner_id'] = $ownerId;
        $query->execute($documents);
        return array_intersect_key($previous, array_flip(array_keys($documents)));
    }

    public function vendorForOwner($vendorId, $ownerId)
    {
        $query = $this->pdo->prepare(
            "SELECT id,
                    CASE WHEN rental_paid_until >= CURRENT_DATE THEN 'paid'
                         WHEN rental_checkout_id IS NOT NULL AND rental_payment_status = 'pending' THEN 'pending'
                         ELSE 'unpaid' END AS rental_payment_status,
                    CASE WHEN rental_payment_status = 'pending' THEN rental_checkout_id ELSE NULL END AS rental_checkout_id,
                    CASE WHEN rental_payment_status = 'pending' THEN rental_checkout_url ELSE NULL END AS rental_checkout_url,
                    CASE WHEN rental_payment_status = 'pending' THEN rental_checkout_amount ELSE NULL END AS rental_checkout_amount,
                    rental_paid_until,
                    CASE WHEN application_document IS NULL THEN 0 ELSE 1 END AS has_application,
                    CASE WHEN cedula_document IS NULL THEN 0 ELSE 1 END AS has_cedula,
                    CASE WHEN photo_document IS NULL THEN 0 ELSE 1 END AS has_photo
             FROM pwesto_vendors WHERE id = :id AND owner_id = :owner_id LIMIT 1"
        );
        $query->execute(['id' => $vendorId, 'owner_id' => $ownerId]);
        return $query->fetch() ?: null;
    }

    public function saveRentalCheckout($vendorId, $ownerId, $checkoutId, $checkoutUrl, $amount)
    {
        $query = $this->pdo->prepare(
            "UPDATE pwesto_vendors
             SET rental_checkout_id = :checkout_id, rental_checkout_url = :checkout_url,
                 rental_checkout_amount = :amount, rental_payment_status = 'pending',
                 rental_paid_until = NULL
             WHERE id = :id AND owner_id = :owner_id
               AND rental_payment_status != 'pending'
               AND (rental_paid_until IS NULL OR rental_paid_until < CURRENT_DATE)"
        );
        $query->execute([
            'checkout_id' => $checkoutId,
            'checkout_url' => $checkoutUrl,
            'amount' => $amount,
            'id' => $vendorId,
            'owner_id' => $ownerId,
        ]);
        return $query->rowCount() === 1;
    }

    public function markRentalPaidByCheckout($vendorId, $checkoutId, $amount)
    {
        $this->pdo->beginTransaction();
        try {
            $paidUntil = $this->isMysql()
                ? 'DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH)'
                : "date('now', '+1 month')";
            $query = $this->pdo->prepare(
                "UPDATE pwesto_vendors SET rental_payment_status = 'paid',
                     rental_paid_at = CURRENT_TIMESTAMP, rental_paid_until = " . $paidUntil . "
                 WHERE id = :id AND rental_checkout_id = :checkout_id
                   AND rental_checkout_amount = :amount
                   AND rental_payment_status = 'pending'
                   AND (rental_paid_until IS NULL OR rental_paid_until < CURRENT_DATE)"
            );
            $query->execute(['id' => $vendorId, 'checkout_id' => $checkoutId, 'amount' => $amount]);
            if ($query->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }
            $vendorQuery = $this->pdo->prepare('SELECT name, market FROM pwesto_vendors WHERE id = :id');
            $vendorQuery->execute(['id' => $vendorId]);
            $vendor = $vendorQuery->fetch();
            $this->notifyAdmins(
                'rental_payment',
                'Stall rental payment confirmed',
                $vendor['name'] . ' in ' . $vendor['market'] . ' is ready for review.'
            );
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function rentalPaymentRecorded($vendorId, $checkoutId, $amount)
    {
        $query = $this->pdo->prepare(
            "SELECT 1 FROM pwesto_vendors
             WHERE id = :id AND rental_checkout_id = :checkout_id
               AND rental_checkout_amount = :amount
               AND rental_payment_status = 'paid' AND rental_paid_until >= CURRENT_DATE
             LIMIT 1"
        );
        $query->execute(['id' => $vendorId, 'checkout_id' => $checkoutId, 'amount' => $amount]);
        return $query->fetchColumn() !== false;
    }

    public function updateVendor($vendorId, $ownerId, array $vendor)
    {
        $location = $this->marketLocations()[$vendor['market']];
        $this->pdo->beginTransaction();
        try {
            $current = $this->pdo->prepare(
                'SELECT category FROM pwesto_vendors WHERE id = :id AND owner_id = :owner_id LIMIT 1'
            );
            $current->execute(['id' => $vendorId, 'owner_id' => $ownerId]);
            $currentCategory = $current->fetchColumn();
            if ($currentCategory === false) {
                $this->pdo->rollBack();
                return false;
            }

            $stallCode = $this->categoryStallPrefix($vendor['category']) === $this->categoryStallPrefix($currentCategory)
                ? null
                : $this->nextStallCode($vendor['category']);
            $query = $this->pdo->prepare(
                "UPDATE pwesto_vendors
                 SET name = :name, category = :category, market = :market, specialty = :specialty,
                     latitude = :latitude, longitude = :longitude,
                     stall_code = COALESCE(:stall_code, stall_code),
                     status = CASE WHEN status = 'rejected' THEN 'pending' ELSE status END
                 WHERE id = :id AND owner_id = :owner_id"
            );
            $query->execute([
                'name' => $vendor['name'],
                'category' => $vendor['category'],
                'market' => $vendor['market'],
                'specialty' => $vendor['specialty'],
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'stall_code' => $stallCode,
                'id' => $vendorId,
                'owner_id' => $ownerId,
            ]);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
        return true;
    }

    public function markets()
    {
        return ['Calapan City Public Market'];
    }

    private function createSchema()
    {
        if ($this->isMysql()) {
            $this->createMysqlSchema();
        } else {
            $this->createSqliteSchema();
            $this->ensureSqliteOrderDeliverySchema();
            $this->ensureSqliteOrderPaymentSchema();
        }
        $this->ensureStallCodeSchema();
    }

    private function createMysqlSchema()
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS pwesto_users (
                id VARCHAR(255) NOT NULL PRIMARY KEY,
                google_sub VARCHAR(255) NULL UNIQUE,
                email VARCHAR(254) NOT NULL UNIQUE,
                name VARCHAR(120) NOT NULL,
                picture TEXT NOT NULL,
                profile_customized TINYINT NOT NULL DEFAULT 0,
                password_hash VARCHAR(255) NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'customer',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_vendors (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                owner_id VARCHAR(255) NULL,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(60) NOT NULL,
                market VARCHAR(100) NOT NULL,
                specialty VARCHAR(240) NOT NULL,
                rating DECIMAL(4,2) NOT NULL DEFAULT 0,
                latitude DECIMAL(10,7) NOT NULL,
                longitude DECIMAL(10,7) NOT NULL,
                icon VARCHAR(32) NOT NULL DEFAULT '⌂',
                image TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'approved',
                application_document TEXT NULL,
                cedula_document TEXT NULL,
                photo_document TEXT NULL,
                rental_payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
                rental_checkout_id VARCHAR(255) NULL,
                rental_checkout_url TEXT NULL,
                rental_checkout_amount BIGINT NULL,
                rental_paid_at DATETIME NULL,
                rental_paid_until DATE NULL,
                created_at DATETIME NOT NULL,
                INDEX pwesto_vendors_market_idx (status, market),
                CONSTRAINT pwesto_vendors_owner_fk FOREIGN KEY (owner_id) REFERENCES pwesto_users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_favorites (
                user_id VARCHAR(255) NOT NULL,
                vendor_id VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (user_id, vendor_id),
                CONSTRAINT pwesto_favorites_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_favorites_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_appointments (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                vendor_id VARCHAR(64) NOT NULL,
                visit_date DATE NOT NULL,
                visit_time VARCHAR(32) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Planned',
                created_at DATETIME NOT NULL,
                INDEX pwesto_appointments_user_idx (user_id, visit_date),
                CONSTRAINT pwesto_appointments_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_appointments_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_auth_attempts (
                identity_hash CHAR(64) NOT NULL PRIMARY KEY,
                attempts INT NOT NULL,
                window_started BIGINT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_password_resets (
                token_hash CHAR(64) NOT NULL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX pwesto_password_resets_user_idx (user_id),
                CONSTRAINT pwesto_password_resets_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_conversations (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                vendor_id VARCHAR(64) NOT NULL,
                customer_id VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY pwesto_conversations_vendor_customer_idx (vendor_id, customer_id),
                INDEX pwesto_conversations_customer_idx (customer_id, updated_at),
                INDEX pwesto_conversations_vendor_idx (vendor_id, updated_at),
                CONSTRAINT pwesto_conversations_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_conversations_customer_fk FOREIGN KEY (customer_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_messages (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                conversation_id VARCHAR(64) NOT NULL,
                sender_id VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                read_at DATETIME NULL,
                INDEX pwesto_messages_conversation_idx (conversation_id, created_at),
                CONSTRAINT pwesto_messages_conversation_fk FOREIGN KEY (conversation_id) REFERENCES pwesto_conversations(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_messages_sender_fk FOREIGN KEY (sender_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_notifications (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                type VARCHAR(60) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                read_at DATETIME NULL,
                INDEX pwesto_notifications_user_idx (user_id, read_at, created_at),
                CONSTRAINT pwesto_notifications_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_products (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                vendor_id VARCHAR(64) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(500) NOT NULL DEFAULT '',
                price_cents BIGINT NOT NULL,
                stock INT NOT NULL DEFAULT 0,
                image TEXT NULL,
                active TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX pwesto_products_vendor_idx (vendor_id, active, name),
                CONSTRAINT pwesto_products_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_orders (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                customer_id VARCHAR(255) NOT NULL,
                vendor_id VARCHAR(64) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                delivery_method VARCHAR(20) NOT NULL DEFAULT 'pickup',
                delivery_address TEXT NULL,
                pickup_at DATETIME NULL,
                payment_method VARCHAR(32) NOT NULL DEFAULT 'cash_on_pickup',
                payment_status VARCHAR(20) NOT NULL DEFAULT 'due',
                payment_checkout_id VARCHAR(255) NULL,
                payment_checkout_url TEXT NULL,
                vendor_receipt_email_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                vendor_receipt_email_attempted_at DATETIME NULL,
                total_cents BIGINT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX pwesto_orders_customer_idx (customer_id, created_at),
                INDEX pwesto_orders_vendor_idx (vendor_id, status, created_at),
                CONSTRAINT pwesto_orders_customer_fk FOREIGN KEY (customer_id) REFERENCES pwesto_users(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_orders_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS pwesto_order_items (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                order_id VARCHAR(64) NOT NULL,
                product_id VARCHAR(64) NULL,
                product_name VARCHAR(100) NOT NULL,
                unit_price_cents BIGINT NOT NULL,
                quantity INT NOT NULL,
                INDEX pwesto_order_items_order_idx (order_id),
                CONSTRAINT pwesto_order_items_order_fk FOREIGN KEY (order_id) REFERENCES pwesto_orders(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_order_items_product_fk FOREIGN KEY (product_id) REFERENCES pwesto_products(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($tables as $sql) {
            $this->pdo->exec($sql);
        }
        $this->ensureMysqlTextColumn('pwesto_products', 'image');
        $this->ensureMysqlColumn('pwesto_users', 'google_sub', 'VARCHAR(255) NULL');
        $this->ensureMysqlColumn('pwesto_users', 'password_hash', 'VARCHAR(255) NULL');
        $this->ensureMysqlColumn('pwesto_users', 'role', "VARCHAR(20) NOT NULL DEFAULT 'customer'");
        $this->ensureMysqlColumn('pwesto_users', 'profile_customized', 'TINYINT NOT NULL DEFAULT 0');
        $this->ensureMysqlColumn('pwesto_orders', 'delivery_method', "VARCHAR(20) NOT NULL DEFAULT 'pickup'");
        $this->ensureMysqlColumn('pwesto_orders', 'delivery_address', 'TEXT NULL');
        $this->ensureMysqlColumn('pwesto_orders', 'pickup_at', 'DATETIME NULL');
        $this->ensureMysqlColumn('pwesto_orders', 'payment_method', "VARCHAR(32) NOT NULL DEFAULT 'cash_on_pickup'");
        $this->ensureMysqlColumn('pwesto_orders', 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'due'");
        $this->ensureMysqlColumn('pwesto_orders', 'payment_checkout_id', 'VARCHAR(255) NULL');
        $this->ensureMysqlColumn('pwesto_orders', 'payment_checkout_url', 'TEXT NULL');
        $this->ensureMysqlColumn('pwesto_orders', 'vendor_receipt_email_status', "VARCHAR(20) NOT NULL DEFAULT 'pending'");
        $this->ensureMysqlColumn('pwesto_orders', 'vendor_receipt_email_attempted_at', 'DATETIME NULL');
        $this->pdo->exec(
            "UPDATE pwesto_orders SET vendor_receipt_email_status = 'sent'
             WHERE payment_method = 'gcash' AND payment_status = 'paid'
               AND vendor_receipt_email_status = 'pending'"
        );
        foreach ([
            'rental_payment_status' => "VARCHAR(20) NOT NULL DEFAULT 'unpaid'",
            'rental_checkout_id' => 'VARCHAR(255) NULL',
            'rental_checkout_url' => 'TEXT NULL',
            'rental_checkout_amount' => 'BIGINT NULL',
            'rental_paid_at' => 'DATETIME NULL',
            'rental_paid_until' => 'DATE NULL',
        ] as $column => $definition) {
            $this->ensureMysqlColumn('pwesto_vendors', $column, $definition);
        }
        foreach (['application_document', 'cedula_document', 'photo_document'] as $column) {
            $this->ensureMysqlTextColumn('pwesto_vendors', $column);
        }
    }

    private function ensureMysqlColumn($table, $column, $definition)
    {
        $query = $this->pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        $columns = array_column($query->fetchAll(), 'Field');
        if (!in_array($column, $columns, true)) {
            $this->pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        }
    }

    private function ensureStallCodeSchema()
    {
        if ($this->isMysql()) {
            $this->ensureMysqlColumn('pwesto_vendors', 'stall_code', 'VARCHAR(24) NULL');
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS pwesto_stall_sequences (
                    prefix VARCHAR(12) NOT NULL PRIMARY KEY,
                    next_number INT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $indexes = $this->pdo->query('SHOW INDEX FROM `pwesto_vendors`')->fetchAll(PDO::FETCH_ASSOC);
            $hasUniqueCodeIndex = false;
            foreach ($indexes as $index) {
                if (($index['Key_name'] ?? '') === 'pwesto_vendors_stall_code_uq') {
                    $hasUniqueCodeIndex = true;
                    break;
                }
            }
            if (!$hasUniqueCodeIndex) {
                $this->pdo->exec(
                    'CREATE UNIQUE INDEX pwesto_vendors_stall_code_uq ON pwesto_vendors (stall_code)'
                );
            }
            return;
        }

        $columns = $this->pdo->query('PRAGMA table_info(pwesto_vendors)')->fetchAll(PDO::FETCH_ASSOC);
        $hasStallCode = false;
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'stall_code') {
                $hasStallCode = true;
                break;
            }
        }
        if (!$hasStallCode) {
            $this->pdo->exec('ALTER TABLE pwesto_vendors ADD COLUMN stall_code TEXT NULL');
        }
        $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS pwesto_vendors_stall_code_uq ON pwesto_vendors(stall_code)');
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS pwesto_stall_sequences (
                prefix TEXT PRIMARY KEY,
                next_number INTEGER NOT NULL
            )'
        );
    }

    private function ensureMysqlTextColumn($table, $column)
    {
        $query = $this->pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        $columns = $query->fetchAll(PDO::FETCH_ASSOC);
        $columnInfo = null;
        foreach ($columns as $item) {
            if (($item['Field'] ?? null) === $column) {
                $columnInfo = $item;
                break;
            }
        }
        if ($columnInfo === null) {
            $this->pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` TEXT NULL');
            return;
        }
        if (!preg_match('/^(tinytext|text|mediumtext|longtext)$/i', (string) ($columnInfo['Type'] ?? ''))) {
            $this->pdo->exec('ALTER TABLE `' . $table . '` MODIFY COLUMN `' . $column . '` TEXT NULL');
        }
    }

    private function createSqliteSchema()
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS pwesto_users (
                id TEXT PRIMARY KEY,
                google_sub TEXT NULL UNIQUE,
                email TEXT NOT NULL,
                name TEXT NOT NULL,
                picture TEXT NOT NULL DEFAULT '',
                profile_customized INTEGER NOT NULL DEFAULT 0,
                password_hash TEXT NULL,
                role TEXT NOT NULL DEFAULT 'customer'
                    CHECK(role IN ('customer', 'vendor', 'admin')),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE UNIQUE INDEX IF NOT EXISTS pwesto_users_email_idx
                ON pwesto_users(email);
            CREATE TABLE IF NOT EXISTS pwesto_vendors (
                id TEXT PRIMARY KEY,
                owner_id TEXT NULL REFERENCES pwesto_users(id) ON DELETE SET NULL,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                market TEXT NOT NULL,
                specialty TEXT NOT NULL,
                rating REAL NOT NULL DEFAULT 0,
                latitude REAL NOT NULL,
                longitude REAL NOT NULL,
                icon TEXT NOT NULL DEFAULT '⌂',
                image TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'approved'
                    CHECK(status IN ('pending', 'approved', 'rejected')),
                application_document TEXT NULL,
                cedula_document TEXT NULL,
                photo_document TEXT NULL,
                rental_payment_status TEXT NOT NULL DEFAULT 'unpaid',
                rental_checkout_id TEXT NULL,
                rental_checkout_url TEXT NULL,
                rental_checkout_amount INTEGER NULL,
                rental_paid_at TEXT NULL,
                rental_paid_until TEXT NULL,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS pwesto_vendors_market_idx
                ON pwesto_vendors(status, market);
            CREATE TABLE IF NOT EXISTS pwesto_favorites (
                user_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                vendor_id TEXT NOT NULL REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                created_at TEXT NOT NULL,
                PRIMARY KEY(user_id, vendor_id)
            );
            CREATE TABLE IF NOT EXISTS pwesto_appointments (
                id TEXT PRIMARY KEY,
                user_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                vendor_id TEXT NOT NULL REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                visit_date TEXT NOT NULL,
                visit_time TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'Planned'
                    CHECK(status IN ('Planned', 'Cancelled')),
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS pwesto_appointments_user_idx
                ON pwesto_appointments(user_id, visit_date);
            CREATE TABLE IF NOT EXISTS pwesto_auth_attempts (
                identity_hash TEXT PRIMARY KEY,
                attempts INTEGER NOT NULL,
                window_started INTEGER NOT NULL
            );
            CREATE TABLE IF NOT EXISTS pwesto_password_resets (
                token_hash TEXT PRIMARY KEY,
                user_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS pwesto_password_resets_user_idx
                ON pwesto_password_resets(user_id);
            CREATE TABLE IF NOT EXISTS pwesto_conversations (
                id TEXT PRIMARY KEY,
                vendor_id TEXT NOT NULL REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                customer_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                UNIQUE(vendor_id, customer_id)
            );
            CREATE INDEX IF NOT EXISTS pwesto_conversations_customer_idx
                ON pwesto_conversations(customer_id, updated_at);
            CREATE INDEX IF NOT EXISTS pwesto_conversations_vendor_idx
                ON pwesto_conversations(vendor_id, updated_at);
            CREATE TABLE IF NOT EXISTS pwesto_messages (
                id TEXT PRIMARY KEY,
                conversation_id TEXT NOT NULL REFERENCES pwesto_conversations(id) ON DELETE CASCADE,
                sender_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                body TEXT NOT NULL,
                created_at TEXT NOT NULL,
                read_at TEXT NULL
            );
            CREATE INDEX IF NOT EXISTS pwesto_messages_conversation_idx
                ON pwesto_messages(conversation_id, created_at);
            CREATE TABLE IF NOT EXISTS pwesto_notifications (
                id TEXT PRIMARY KEY,
                user_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                type TEXT NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                created_at TEXT NOT NULL,
                read_at TEXT NULL
            );
            CREATE INDEX IF NOT EXISTS pwesto_notifications_user_idx
                ON pwesto_notifications(user_id, read_at, created_at);
            CREATE TABLE IF NOT EXISTS pwesto_products (
                id TEXT PRIMARY KEY,
                vendor_id TEXT NOT NULL REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                price_cents INTEGER NOT NULL CHECK(price_cents > 0),
                stock INTEGER NOT NULL DEFAULT 0 CHECK(stock >= 0),
                image TEXT NULL,
                active INTEGER NOT NULL DEFAULT 1 CHECK(active IN (0, 1)),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS pwesto_products_vendor_idx
                ON pwesto_products(vendor_id, active, name);
            CREATE TABLE IF NOT EXISTS pwesto_orders (
                id TEXT PRIMARY KEY,
                customer_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                vendor_id TEXT NOT NULL REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                status TEXT NOT NULL DEFAULT 'pending'
                    CHECK(status IN ('pending', 'accepted', 'preparing', 'ready', 'out_for_delivery', 'completed', 'delivered', 'cancelled')),
                delivery_method TEXT NOT NULL DEFAULT 'pickup'
                    CHECK(delivery_method IN ('pickup', 'delivery')),
                delivery_address TEXT NULL,
                pickup_at TEXT NULL,
                payment_method TEXT NOT NULL DEFAULT 'cash_on_pickup',
                payment_status TEXT NOT NULL DEFAULT 'due',
                payment_checkout_id TEXT NULL,
                payment_checkout_url TEXT NULL,
                vendor_receipt_email_status TEXT NOT NULL DEFAULT 'pending',
                vendor_receipt_email_attempted_at TEXT NULL,
                total_cents INTEGER NOT NULL CHECK(total_cents > 0),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS pwesto_orders_customer_idx
                ON pwesto_orders(customer_id, created_at);
            CREATE INDEX IF NOT EXISTS pwesto_orders_vendor_idx
                ON pwesto_orders(vendor_id, status, created_at);
            CREATE TABLE IF NOT EXISTS pwesto_order_items (
                id TEXT PRIMARY KEY,
                order_id TEXT NOT NULL REFERENCES pwesto_orders(id) ON DELETE CASCADE,
                product_id TEXT NULL REFERENCES pwesto_products(id) ON DELETE SET NULL,
                product_name TEXT NOT NULL,
                unit_price_cents INTEGER NOT NULL CHECK(unit_price_cents > 0),
                quantity INTEGER NOT NULL CHECK(quantity > 0)
            );
            CREATE INDEX IF NOT EXISTS pwesto_order_items_order_idx
                ON pwesto_order_items(order_id)"
        );
        $productColumns = $this->pdo->query('PRAGMA table_info(pwesto_products)')->fetchAll();
        if (!in_array('image', array_column($productColumns, 'name'), true)) {
            $this->pdo->exec('ALTER TABLE pwesto_products ADD COLUMN image TEXT NULL');
        }
        $columns = $this->pdo->query('PRAGMA table_info(pwesto_users)')->fetchAll();
        $columnNames = array_column($columns, 'name');
        if (!in_array('google_sub', $columnNames, true)) {
            $this->pdo->exec('ALTER TABLE pwesto_users ADD COLUMN google_sub TEXT NULL');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS pwesto_users_google_sub_idx ON pwesto_users(google_sub)');
        }
        if (!in_array('password_hash', $columnNames, true)) {
            $this->pdo->exec('ALTER TABLE pwesto_users ADD COLUMN password_hash TEXT NULL');
        }
        if (!in_array('role', $columnNames, true)) {
            $this->pdo->exec("ALTER TABLE pwesto_users ADD COLUMN role TEXT NOT NULL DEFAULT 'customer'");
        }
        if (!in_array('profile_customized', $columnNames, true)) {
            $this->pdo->exec('ALTER TABLE pwesto_users ADD COLUMN profile_customized INTEGER NOT NULL DEFAULT 0');
        }
        $vendorColumns = $this->pdo->query('PRAGMA table_info(pwesto_vendors)')->fetchAll();
        $vendorColumnNames = array_column($vendorColumns, 'name');
        $vendorMigrations = [
            'application_document' => 'TEXT NULL',
            'cedula_document' => 'TEXT NULL',
            'photo_document' => 'TEXT NULL',
            'rental_payment_status' => "TEXT NOT NULL DEFAULT 'unpaid'",
            'rental_checkout_id' => 'TEXT NULL',
            'rental_checkout_url' => 'TEXT NULL',
            'rental_checkout_amount' => 'INTEGER NULL',
            'rental_paid_at' => 'TEXT NULL',
            'rental_paid_until' => 'TEXT NULL',
        ];
        foreach ($vendorMigrations as $column => $definition) {
            if (!in_array($column, $vendorColumnNames, true)) {
                $this->pdo->exec('ALTER TABLE pwesto_vendors ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    private function ensureSqliteOrderDeliverySchema()
    {
        $columns = $this->pdo->query('PRAGMA table_info(pwesto_orders)')->fetchAll();
        $columnNames = array_column($columns, 'name');
        $definition = $this->pdo->query(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'pwesto_orders'"
        )->fetchColumn();
        if (in_array('delivery_method', $columnNames, true)
            && in_array('delivery_address', $columnNames, true)
            && in_array('pickup_at', $columnNames, true)
            && strpos($definition, "'out_for_delivery'") !== false
            && strpos($definition, "'delivered'") !== false) {
            return;
        }

        $deliveryMethod = in_array('delivery_method', $columnNames, true) ? 'delivery_method' : "'pickup'";
        $deliveryAddress = in_array('delivery_address', $columnNames, true) ? 'delivery_address' : 'NULL';
        $pickupAt = in_array('pickup_at', $columnNames, true) ? 'pickup_at' : 'NULL';
        $this->pdo->exec('PRAGMA foreign_keys = OFF');
        try {
            $this->pdo->beginTransaction();
            $this->pdo->exec(
                "CREATE TABLE pwesto_orders_delivery_new (
                    id TEXT PRIMARY KEY,
                    customer_id TEXT NOT NULL REFERENCES pwesto_users(id) ON DELETE CASCADE,
                    vendor_id TEXT NOT NULL REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                    status TEXT NOT NULL DEFAULT 'pending',
                    delivery_method TEXT NOT NULL DEFAULT 'pickup'
                        CHECK(delivery_method IN ('pickup', 'delivery')),
                    delivery_address TEXT NULL,
                    pickup_at TEXT NULL,
                    total_cents INTEGER NOT NULL CHECK(total_cents > 0),
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )"
            );
            $this->pdo->exec(
                'INSERT INTO pwesto_orders_delivery_new
                    (id, customer_id, vendor_id, status, delivery_method, delivery_address, pickup_at, total_cents, created_at, updated_at)
                 SELECT id, customer_id, vendor_id, status, ' . $deliveryMethod . ', ' . $deliveryAddress . ', ' . $pickupAt
                    . ', total_cents, created_at, updated_at FROM pwesto_orders'
            );
            $this->pdo->exec('DROP TABLE pwesto_orders');
            $this->pdo->exec('ALTER TABLE pwesto_orders_delivery_new RENAME TO pwesto_orders');
            $this->pdo->exec(
                'CREATE INDEX pwesto_orders_customer_idx ON pwesto_orders(customer_id, created_at)'
            );
            $this->pdo->exec(
                'CREATE INDEX pwesto_orders_vendor_idx ON pwesto_orders(vendor_id, status, created_at)'
            );
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        } finally {
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    private function ensureSqliteOrderPaymentSchema()
    {
        $columns = $this->pdo->query('PRAGMA table_info(pwesto_orders)')->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
        $migrations = [
            'payment_method' => "TEXT NOT NULL DEFAULT 'cash_on_pickup'",
            'payment_status' => "TEXT NOT NULL DEFAULT 'due'",
            'payment_checkout_id' => 'TEXT NULL',
            'payment_checkout_url' => 'TEXT NULL',
            'vendor_receipt_email_status' => "TEXT NOT NULL DEFAULT 'pending'",
            'vendor_receipt_email_attempted_at' => 'TEXT NULL',
        ];
        foreach ($migrations as $column => $definition) {
            if (!in_array($column, $columnNames, true)) {
                $this->pdo->exec('ALTER TABLE pwesto_orders ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
        $this->pdo->exec(
            "UPDATE pwesto_orders SET vendor_receipt_email_status = 'sent'
             WHERE payment_method = 'gcash' AND payment_status = 'paid'
               AND vendor_receipt_email_status = 'pending'"
        );
    }

    private function retireBuiltInDemoStalls()
    {
        $demoIds = ['isla-seafood', 'kusina-gulay', 'lola-marias', 'petal-street'];
        $placeholders = implode(', ', array_fill(0, count($demoIds), '?'));

        $products = $this->pdo->prepare(
            "UPDATE pwesto_products SET active = 0
             WHERE vendor_id IN ($placeholders)
               AND EXISTS (
                   SELECT 1 FROM pwesto_vendors
                   WHERE pwesto_vendors.id = pwesto_products.vendor_id AND pwesto_vendors.owner_id IS NULL
               )"
        );
        $products->execute($demoIds);

        $vendors = $this->pdo->prepare(
            "UPDATE pwesto_vendors SET status = 'rejected'
             WHERE id IN ($placeholders) AND owner_id IS NULL AND status = 'approved'"
        );
        $vendors->execute($demoIds);
    }

    private function configureConfirmedStalls()
    {
        $stalls = [
            [
                'id' => 'bukid-meats',
                'name' => 'Meat 001',
                'category' => 'Meat',
                'stall_code' => 'MEAT-001',
                'email' => trim((string) (getenv('PWESTO_MEAT_VENDOR_EMAIL') ?: '')),
            ],
            [
                'id' => 'fresh-harvest',
                'name' => 'Fruit 001',
                'category' => 'Fruit',
                'stall_code' => 'FRU-001',
                'email' => trim((string) (getenv('PWESTO_FRUIT_VENDOR_EMAIL') ?: '')),
            ],
        ];

        $findStall = $this->pdo->prepare(
            'SELECT owner_id, name, category, stall_code, status
             FROM pwesto_vendors WHERE id = :id LIMIT 1'
        );
        $findAccount = $this->pdo->prepare(
            'SELECT id, role FROM pwesto_users
             WHERE LOWER(email) = LOWER(:email) LIMIT 1'
        );
        $findOtherStall = $this->pdo->prepare(
            'SELECT id FROM pwesto_vendors WHERE owner_id = :owner_id AND id != :stall_id LIMIT 1'
        );
        $updateStall = $this->pdo->prepare(
            "UPDATE pwesto_vendors
             SET owner_id = :owner_id, name = :name, category = :category,
                 stall_code = :stall_code, status = :status
             WHERE id = :id"
        );

        foreach ($stalls as $stall) {
            $findStall->execute(['id' => $stall['id']]);
            $existing = $findStall->fetch();
            if (!$existing) {
                error_log('Pwesto confirmed stall is missing: ' . $stall['id'] . '.');
                continue;
            }

            if ($stall['email'] === '' || !filter_var($stall['email'], FILTER_VALIDATE_EMAIL)) {
                error_log('Set the confirmed vendor email environment variable for stall ' . $stall['id'] . '.');
                continue;
            }

            $findAccount->execute(['email' => $stall['email']]);
            $account = $findAccount->fetch();
            if (!$account || $account['role'] !== 'vendor') {
                error_log('Pwesto confirmed vendor account is missing or is not a vendor: ' . $stall['id'] . '.');
                continue;
            }

            $findOtherStall->execute([
                'owner_id' => $account['id'],
                'stall_id' => $stall['id'],
            ]);
            if ($findOtherStall->fetchColumn()) {
                error_log('Pwesto confirmed vendor account is linked to another stall: ' . $stall['id'] . '.');
                continue;
            }

            if ($existing['owner_id'] !== $account['id'] || $existing['name'] !== $stall['name']
                || $existing['category'] !== $stall['category'] || $existing['stall_code'] !== $stall['stall_code']
                || $existing['status'] !== 'approved') {
                $updateStall->execute([
                    'owner_id' => $account['id'],
                    'name' => $stall['name'],
                    'category' => $stall['category'],
                    'stall_code' => $stall['stall_code'],
                    'status' => 'approved',
                    'id' => $stall['id'],
                ]);
            }
        }
    }

    private function retireMisnamedDuplicateStall()
    {
        $query = $this->pdo->prepare(
            "UPDATE pwesto_vendors SET status = 'rejected'
             WHERE stall_code = :stall_code
               AND name = :name
               AND category = :category
               AND status = 'approved'"
        );
        $query->execute([
            'stall_code' => 'MEAT-006',
            'name' => 'Fruits Stall 001',
            'category' => 'Meat',
        ]);
    }

    private function assignMissingStallCodes()
    {
        $rows = $this->pdo->query(
            "SELECT id, category FROM pwesto_vendors
             WHERE stall_code IS NULL
             ORDER BY category, created_at, id"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $update = $this->pdo->prepare('UPDATE pwesto_vendors SET stall_code = :stall_code WHERE id = :id AND stall_code IS NULL');
            foreach ($rows as $row) {
                $update->execute([
                    'stall_code' => $this->nextStallCode($row['category']),
                    'id' => $row['id'],
                ]);
            }
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function nextStallCode($category)
    {
        $prefix = $this->categoryStallPrefix($category);
        $sql = $this->isMysql()
            ? 'INSERT INTO pwesto_stall_sequences (prefix, next_number) VALUES (:prefix, 1)
               ON DUPLICATE KEY UPDATE next_number = next_number + 1'
            : 'INSERT INTO pwesto_stall_sequences (prefix, next_number) VALUES (:prefix, 1)
               ON CONFLICT(prefix) DO UPDATE SET next_number = next_number + 1';
        $insert = $this->pdo->prepare($sql);
        $insert->execute(['prefix' => $prefix]);

        $query = $this->pdo->prepare(
            'SELECT next_number FROM pwesto_stall_sequences WHERE prefix = :prefix'
            . ($this->isMysql() ? ' FOR UPDATE' : '')
        );
        $query->execute(['prefix' => $prefix]);
        $number = (int) $query->fetchColumn();
        return $prefix . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    private function categoryStallPrefix($category)
    {
        $prefixes = [
            'Meat' => 'MEAT',
            'Vegetables' => 'VEG',
            'Fruits' => 'FRU',
            'Seafood' => 'SEA',
            'Pantry' => 'PAN',
            'Flowers' => 'FLW',
            'Produce' => 'PRO',
        ];
        return $prefixes[$category] ?? 'STL';
    }

    private function formatVendor(array $vendor)
    {
        $marketCenter = ['lat' => 13.4118, 'lng' => 121.1809];
        $lat1 = deg2rad($marketCenter['lat']);
        $lat2 = deg2rad((float) $vendor['lat']);
        $latDelta = deg2rad((float) $vendor['lat'] - $marketCenter['lat']);
        $lngDelta = deg2rad((float) $vendor['lng'] - $marketCenter['lng']);
        $distance = 6371 * 2 * asin(sqrt(
            sin($latDelta / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($lngDelta / 2) ** 2
        ));
        $vendor['rating'] = number_format((float) $vendor['rating'], 1);
        $vendor['lat'] = (float) $vendor['lat'];
        $vendor['lng'] = (float) $vendor['lng'];
        if (array_key_exists('can_message', $vendor)) {
            $vendor['can_message'] = (bool) $vendor['can_message'];
        }
        if (array_key_exists('can_order', $vendor)) {
            $vendor['can_order'] = (bool) $vendor['can_order'];
        }
        if (array_key_exists('is_sample_image', $vendor)) {
            $vendor['is_sample_image'] = (bool) $vendor['is_sample_image'];
        }
        $vendor['distance'] = number_format($distance, 1) . ' km';
        return $vendor;
    }

    private function marketLocations()
    {
        return [
            'Calapan City Public Market' => ['lat' => 13.4118, 'lng' => 121.1809],
            'Makati Central Market' => ['lat' => 14.5547, 'lng' => 121.0244],
            'Poblacion Community Market' => ['lat' => 14.5654, 'lng' => 121.0311],
            'Legazpi Sunday Market' => ['lat' => 14.5531, 'lng' => 121.0148],
        ];
    }
}
