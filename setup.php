<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $server = db(true);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = db();
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('File struktur database tidak ditemukan.');
        }
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
            $pdo->exec($statement);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM inventory_items')->fetchColumn() === 0) {
            $pdo->beginTransaction();
            try {
                $insertInventory = $pdo->prepare(
                    'INSERT INTO inventory_items (name, sku, category, unit, stock, average_cost, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $inventory = [
                    ['Biji kopi arabika', 'BHN-001', 'Bahan baku', 'gram', 1800, 120, 500],
                    ['Susu full cream', 'BHN-002', 'Bahan baku', 'ml', 2400, 25, 1000],
                    ['Gula aren', 'BHN-003', 'Bahan baku', 'gram', 900, 45, 300],
                    ['Gelas 16 oz', 'BHN-004', 'Kemasan', 'pcs', 48, 650, 50],
                    ['Beras premium', 'RTL-001', 'Barang ritel', 'gram', 12000, 18, 3000],
                    ['Air mineral botol', 'RTL-002', 'Barang ritel', 'pcs', 36, 2500, 12],
                ];
                foreach ($inventory as $row) {
                    $insertInventory->execute($row);
                }
                $ids = $pdo->query('SELECT sku, id FROM inventory_items')->fetchAll(PDO::FETCH_KEY_PAIR);
                $insertProduct = $pdo->prepare(
                    'INSERT INTO products (name, sku, category, product_type, selling_price, target_margin, inventory_id, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertProduct->execute(['Es kopi susu', 'MENU-001', 'Minuman', 'menu', 22000, 55, null, 1]);
                $latteId = (int) $pdo->lastInsertId();
                $insertProduct->execute(['Americano', 'MENU-002', 'Minuman', 'menu', 18000, 60, null, 1]);
                $americanoId = (int) $pdo->lastInsertId();
                $insertProduct->execute(['Beras premium 1 kg', 'RTL-101', 'Sembako', 'retail', 26000, 40, $ids['RTL-001'], 1000]);
                $insertProduct->execute(['Air mineral 600 ml', 'RTL-102', 'Minuman', 'retail', 5000, 30, $ids['RTL-002'], 1]);
                $recipe = $pdo->prepare('INSERT INTO product_recipes (product_id, inventory_id, quantity) VALUES (?, ?, ?)');
                foreach ([[$latteId, $ids['BHN-001'], 18], [$latteId, $ids['BHN-002'], 150], [$latteId, $ids['BHN-003'], 20], [$latteId, $ids['BHN-004'], 1], [$americanoId, $ids['BHN-001'], 18], [$americanoId, $ids['BHN-004'], 1]] as $line) {
                    $recipe->execute($line);
                }
                $supplier = $pdo->prepare('INSERT INTO suppliers (name) VALUES (?)');
                $supplier->execute(['PT Sumber Pangan']);
                $supplier->execute(['Koperasi Kopi Nusantara']);
                $setting = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)');
                foreach (['business_name' => 'Racik Coffee & Mart', 'margin_alert' => '35', 'price_rounding' => '500', 'notifications' => '1'] as $key => $value) {
                    $setting->execute([$key, $value]);
                }
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }

        $seasonings = [
            ['Garam halus', 'BHN-007', 'Bahan baku', 'gram', 500, 10, 100],
            ['Lada bubuk', 'BHN-008', 'Bahan baku', 'gram', 50, 240, 10],
            ['Kaldu bubuk', 'BHN-009', 'Bahan baku', 'gram', 100, 100, 20],
            ['Bawang bombay', 'BHN-010', 'Bahan baku', 'pcs', 10, 5000, 3],
        ];
        $addSeasoning = $pdo->prepare(
            'INSERT INTO inventory_items (name, sku, category, unit, stock, average_cost, min_stock)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE sku = VALUES(sku)'
        );
        foreach ($seasonings as $seasoning) {
            $addSeasoning->execute($seasoning);
        }
        $message = 'Database dan data demo berhasil disiapkan. Anda dapat membuka aplikasi.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup Racik POS</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="setup-body">
  <main class="setup-card">
    <div class="brand-mark">R</div>
    <span class="eyebrow">PENYIAPAN APLIKASI</span>
    <h1>Hubungkan database</h1>
    <p>Setup akan membuat database <strong><?= htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') ?></strong>, tabel, dan data demo di MySQL/MariaDB lokal.</p>
    <div class="setup-info"><strong>Konfigurasi koneksi</strong><span>Host: <?= htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8') ?>:<?= htmlspecialchars(DB_PORT, ENT_QUOTES, 'UTF-8') ?></span><span>User: <?= htmlspecialchars(DB_USER, ENT_QUOTES, 'UTF-8') ?></span><span>Password: <?= DB_PASS === '' ? 'kosong' : 'tersimpan' ?></span></div>
    <?php if ($message): ?><div class="notice success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?> <a href="index.php">Buka aplikasi →</a></div><?php endif; ?>
    <?php if ($error): ?><div class="notice error"><strong>Setup gagal:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?><br>Pastikan MySQL aktif di Laragon dan konfigurasi di <code>config/database.php</code> sesuai.</div><?php endif; ?>
    <form method="post"><button class="button button-primary full-button" type="submit">Buat database dan data demo</button></form>
    <p class="setup-footnote">Setup aman dijalankan ulang; tidak menghapus data yang sudah ada.</p>
  </main>
</body>
</html>
