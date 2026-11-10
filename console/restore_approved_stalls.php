<?php

define('PREVENT_DIRECT_ACCESS', true);
define('ROOT_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_DIR', ROOT_DIR);

require_once ROOT_DIR . 'libraries' . DIRECTORY_SEPARATOR . 'Pwesto_store.php';

$store = new Pwesto_store();
$pdo = $store->connection();
$existingCount = (int) $pdo->query('SELECT COUNT(*) FROM pwesto_vendors')->fetchColumn();

if ($existingCount > 0) {
    echo "Stall restore skipped: the destination already contains vendor records.\n";
    exit(0);
}

$stalls = [
    [
        'id' => 'bukid-meats',
        'stall_code' => 'MEAT-001',
        'name' => 'Bukid Farm Meats',
        'category' => 'Meat',
        'market' => 'Makati Central Market',
        'specialty' => 'Local farm-raised cuts',
        'rating' => 4.80,
        'latitude' => 14.5495,
        'longitude' => 121.0194,
        'icon' => '⌂',
        'image' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&w=220&h=220&q=80',
    ],
    [
        'id' => 'fresh-harvest',
        'stall_code' => 'PRO-001',
        'name' => 'Fresh Harvest Produce',
        'category' => 'Produce',
        'market' => 'Makati Central Market',
        'specialty' => 'Seasonal fruit, leafy greens',
        'rating' => 4.90,
        'latitude' => 14.5562,
        'longitude' => 121.0221,
        'icon' => '✳',
        'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=220&h=220&q=80',
    ],
];

$insert = $pdo->prepare(
    'INSERT INTO pwesto_vendors
        (id, owner_id, name, category, market, specialty, rating, latitude, longitude,
         icon, image, status, created_at, stall_code)
     VALUES
        (:id, NULL, :name, :category, :market, :specialty, :rating, :latitude, :longitude,
         :icon, :image, :status, CURRENT_TIMESTAMP, :stall_code)'
);

$pdo->beginTransaction();
try {
    foreach ($stalls as $stall) {
        $stall['status'] = 'approved';
        $insert->execute($stall);
    }
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}

echo "Restored 2 approved, unowned stall listings. No users or credentials were copied.\n";
