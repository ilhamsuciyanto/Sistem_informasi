<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/actions/handle_post.php';

try {
    $pdo = db();
    ensure_demo_seasonings($pdo);
} catch (Throwable $exception) {
    http_response_code(503);
    $databaseError = $exception->getMessage();
    require __DIR__ . '/includes/database_error.php';
    exit;
}

$page = preg_replace('/[^a-z_]/', '', (string) ($_GET['page'] ?? 'dashboard')) ?: 'dashboard';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_post($pdo);
}

if (isset($_GET['export']) && $_GET['export'] === 'sales') {
    $from = (string) ($_GET['from'] ?? '');
    $to = (string) ($_GET['to'] ?? '');
    $query = 'SELECT sale_number, created_at, payment_method, total, gross_profit FROM sales WHERE 1=1';
    $params = [];
    if ($from !== '') {
        $query .= ' AND DATE(created_at) >= ?';
        $params[] = $from;
    }
    if ($to !== '') {
        $query .= ' AND DATE(created_at) <= ?';
        $params[] = $to;
    }
    $statement = $pdo->prepare($query . ' ORDER BY created_at DESC');
    $statement->execute($params);
    $rows = [];
    foreach ($statement->fetchAll() as $sale) {
        $rows[] = [$sale['sale_number'], $sale['created_at'], $sale['payment_method'], $sale['total'], $sale['gross_profit']];
    }
    export_csv('laporan-penjualan.csv', ['Nomor transaksi', 'Tanggal', 'Pembayaran', 'Total', 'Laba kotor'], $rows);
}

$allowedPages = ['dashboard', 'pos', 'products', 'inventory', 'purchases', 'hpp', 'alerts', 'reports', 'settings'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}
require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';
