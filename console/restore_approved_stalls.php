<?php

define('PREVENT_DIRECT_ACCESS', true);
define('ROOT_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_DIR', ROOT_DIR);

require_once ROOT_DIR . 'libraries' . DIRECTORY_SEPARATOR . 'Pwesto_store.php';

$store = new Pwesto_store();
$pdo = $store->connection();

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
    [
        'id' => 'isla-seafood',
        'stall_code' => 'SEA-001',
        'name' => 'Isla Fresh Seafood',
        'category' => 'Seafood',
        'market' => 'Makati Central Market',
        'specialty' => 'Daily catch, cleaned to order',
        'rating' => 4.80,
        'latitude' => 14.5519,
        'longitude' => 121.0275,
        'icon' => '≈',
        'image' => 'https://images.unsplash.com/photo-1510130387422-82bed34b37e9?auto=format&fit=crop&w=220&h=220&q=80',
    ],
    [
        'id' => 'kusina-gulay',
        'stall_code' => 'PRO-002',
        'name' => 'Kusina Gulay & Herbs',
        'category' => 'Produce',
        'market' => 'Makati Central Market',
        'specialty' => 'Local vegetables, fresh herbs',
        'rating' => 4.70,
        'latitude' => 14.5583,
        'longitude' => 121.0294,
        'icon' => '✿',
        'image' => 'https://images.unsplash.com/photo-1566385101042-1a0aa0c1268c?auto=format&fit=crop&w=220&h=220&q=80',
    ],
    [
        'id' => 'lola-marias',
        'stall_code' => 'PAN-001',
        'name' => 'Lola Maria’s Pantry',
        'category' => 'Pantry',
        'market' => 'Poblacion Community Market',
        'specialty' => 'Homemade atsara, local pantry staples',
        'rating' => 5.00,
        'latitude' => 14.5654,
        'longitude' => 121.0311,
        'icon' => '♡',
        'image' => 'https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&w=220&h=220&q=80',
    ],
    [
        'id' => 'petal-street',
        'stall_code' => 'FLW-001',
        'name' => 'Petal Street Flowers',
        'category' => 'Flowers',
        'market' => 'Legazpi Sunday Market',
        'specialty' => 'Fresh-cut blooms and bouquets',
        'rating' => 4.90,
        'latitude' => 14.5531,
        'longitude' => 121.0148,
        'icon' => '✿',
        'image' => 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=220&h=220&q=80',
    ],
];

$findStall = $pdo->prepare('SELECT id FROM pwesto_vendors WHERE id = :id LIMIT 1');
$approveStall = $pdo->prepare(
    "UPDATE pwesto_vendors SET status = 'approved' WHERE id = :id"
);
$insert = $pdo->prepare(
    'INSERT INTO pwesto_vendors
        (id, owner_id, name, category, market, specialty, rating, latitude, longitude,
         icon, image, status, created_at, stall_code)
     VALUES
        (:id, NULL, :name, :category, :market, :specialty, :rating, :latitude, :longitude,
         :icon, :image, :status, CURRENT_TIMESTAMP, :stall_code)'
);

$pdo->beginTransaction();
$restored = 0;
$alreadyPresent = 0;
try {
    foreach ($stalls as $stall) {
        $stall['status'] = 'approved';
        $findStall->execute(['id' => $stall['id']]);
        if ($findStall->fetchColumn()) {
            $approveStall->execute(['id' => $stall['id']]);
            $alreadyPresent++;
            continue;
        }
        $insert->execute($stall);
        $restored++;
    }
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}

echo "Restored {$restored} approved stall listings; approved {$alreadyPresent} existing listings. No users or credentials were copied.\n";
