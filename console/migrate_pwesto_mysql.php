<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_DIR', ROOT_DIR);
define('PREVENT_DIRECT_ACCESS', true);

$envPath = ROOT_DIR . '.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            continue;
        }
        if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
    }
}

require_once APP_DIR . 'libraries' . DIRECTORY_SEPARATOR . 'Pwesto_store.php';

$tables = [
    'pwesto_users',
    'pwesto_vendors',
    'pwesto_favorites',
    'pwesto_appointments',
    'pwesto_auth_attempts',
    'pwesto_password_resets',
    'pwesto_conversations',
    'pwesto_messages',
    'pwesto_notifications',
    'pwesto_products',
    'pwesto_orders',
    'pwesto_order_items',
];

$sourcePath = getenv('PWESTO_DB_PATH') ?: ROOT_DIR . 'runtime' . DIRECTORY_SEPARATOR . 'pwesto.sqlite';
if (!is_file($sourcePath)) {
    fwrite(STDERR, "SQLite source database not found: " . $sourcePath . PHP_EOL);
    exit(1);
}
if (strtolower(getenv('PWESTO_DB_DRIVER') ?: 'sqlite') !== 'mysql') {
    fwrite(STDERR, "Set PWESTO_DB_DRIVER=mysql in the local .env before running this migration." . PHP_EOL);
    exit(1);
}

try {
    $targetStore = new Pwesto_store();
    $target = $targetStore->connection();
    $source = new PDO('sqlite:' . $sourcePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    foreach ($tables as $table) {
        $source->query('SELECT 1 FROM "' . $table . '" LIMIT 1');
        $count = (int) $target->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        if ($count !== 0) {
            throw new RuntimeException(
                'The MySQL table ' . $table . ' already contains data. No rows were imported; use an empty Aiven database.'
            );
        }
    }

    $target->beginTransaction();
    $counts = [];
    foreach ($tables as $table) {
        $sourceQuery = $source->query('SELECT * FROM "' . $table . '"');
        $firstRow = $sourceQuery->fetch();
        if ($firstRow === false) {
            $counts[$table] = 0;
            continue;
        }

        $columns = array_keys($firstRow);
        $quotedColumns = array_map(function ($column) {
            return '`' . str_replace('`', '``', $column) . '`';
        }, $columns);
        $placeholders = array_fill(0, count($columns), '?');
        $insert = $target->prepare(
            'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')'
        );

        $count = 0;
        do {
            $insert->execute(array_values($firstRow));
            $count++;
            $firstRow = $sourceQuery->fetch();
        } while ($firstRow !== false);
        $counts[$table] = $count;
    }

    foreach ($counts as $table => $expected) {
        $actual = (int) $target->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        if ($actual !== $expected) {
            throw new RuntimeException('Row count verification failed for ' . $table . '.');
        }
    }
    $target->commit();

    echo "Pwesto data migration completed. Rows copied:" . PHP_EOL;
    foreach ($counts as $table => $count) {
        echo '  ' . $table . ': ' . $count . PHP_EOL;
    }
    echo "Uploaded photos and private documents remain in the local public/uploads and runtime folders." . PHP_EOL;
} catch (Throwable $error) {
    if (isset($target) && $target instanceof PDO && $target->inTransaction()) {
        $target->rollBack();
    }
    fwrite(STDERR, 'Migration failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
