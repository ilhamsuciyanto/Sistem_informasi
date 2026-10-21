<?php
declare(strict_types=1);

function handle_post(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        switch ($action) {
            case 'product_save':
                save_product($pdo);
                break;
            case 'product_toggle':
                $id = (int) ($_POST['product_id'] ?? 0);
                $statement = $pdo->prepare('UPDATE products SET active = IF(active = 1, 0, 1) WHERE id = ?');
                $statement->execute([$id]);
                flash('Status produk diperbarui.');
                redirect('?page=products');
            case 'product_remove':
                remove_product($pdo);
                break;
            case 'inventory_save':
                save_inventory($pdo);
                break;
            case 'purchase_save':
                save_purchase($pdo);
                break;
            case 'cart_add':
                $productId = (int) ($_POST['product_id'] ?? 0);
                $quantity = (int) ($_SESSION['cart'][$productId] ?? 0) + 1;
                if ($productId <= 0 || $quantity > product_stock($pdo, $productId)) {
                    throw new RuntimeException('Stok produk tidak mencukupi.');
                }
                $_SESSION['cart'][$productId] = $quantity;
                redirect('?page=pos');
            case 'cart_update':
                $productId = (int) ($_POST['product_id'] ?? 0);
                $quantity = (int) ($_POST['quantity'] ?? 0);
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$productId]);
                } elseif ($quantity > product_stock($pdo, $productId)) {
                    throw new RuntimeException('Jumlah melebihi stok yang tersedia.');
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }
                redirect('?page=pos');
            case 'cart_clear':
                $_SESSION['cart'] = [];
                redirect('?page=pos');
            case 'checkout':
                checkout($pdo);
                break;
            case 'recommendation_review':
                review_recommendation($pdo);
                break;
            case 'recommendation_apply':
                apply_recommendation($pdo);
                break;
            case 'settings_save':
                save_settings($pdo);
                break;
            case 'demo_reset':
                reset_demo($pdo);
                break;
            default:
                throw new RuntimeException('Aksi tidak dikenal.');
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Aksi aplikasi gagal: ' . $exception->getMessage());
        flash($exception->getMessage(), 'error');
        $returnPage = preg_replace('/[^a-z_]/', '', (string) ($_POST['return_page'] ?? $_GET['page'] ?? 'dashboard'));
        redirect('?page=' . ($returnPage ?: 'dashboard'));
    }
}

function remove_product(PDO $pdo): never
{
    $id = (int) ($_POST['product_id'] ?? 0);
    $usedInSales = $pdo->prepare('SELECT COUNT(*) FROM sale_items WHERE product_id=?');
    $usedInSales->execute([$id]);
    if ((int) $usedInSales->fetchColumn() > 0) {
        $pdo->prepare('UPDATE products SET active=0 WHERE id=?')->execute([$id]);
        flash('Produk memiliki riwayat penjualan, jadi produk dinonaktifkan agar laporan tetap utuh.');
    } else {
        $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
        flash('Produk berhasil dihapus.');
    }
    redirect('?page=products');
}

function save_product(PDO $pdo): never
{
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $sku = trim((string) ($_POST['sku'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $type = (string) ($_POST['product_type'] ?? 'menu');
    $price = (float) ($_POST['selling_price'] ?? 0);
    $target = (float) ($_POST['target_margin'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $inventoryId = $type === 'retail' ? (int) ($_POST['inventory_id'] ?? 0) : null;
    $stockQuantity = (float) ($_POST['stock_quantity'] ?? 1);
    if ($name === '' || $sku === '' || $category === '' || !in_array($type, ['menu', 'retail'], true) || $price <= 0 || $target <= 0 || $target >= 100) {
        throw new RuntimeException('Periksa nama, SKU, kategori, harga, dan target margin.');
    }
    if ($type === 'retail' && ($inventoryId <= 0 || $stockQuantity <= 0)) {
        throw new RuntimeException('Produk ritel harus memiliki barang persediaan dan takaran yang valid.');
    }

    $pdo->beginTransaction();
    if ($id > 0) {
        $statement = $pdo->prepare(
            'UPDATE products SET name=?, sku=?, category=?, product_type=?, selling_price=?, target_margin=?, inventory_id=?, stock_quantity=?, active=? WHERE id=?'
        );
        $statement->execute([$name, $sku, $category, $type, $price, $target, $inventoryId, $stockQuantity, $active, $id]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO products (name, sku, category, product_type, selling_price, target_margin, inventory_id, stock_quantity, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$name, $sku, $category, $type, $price, $target, $inventoryId, $stockQuantity, $active]);
        $id = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM product_recipes WHERE product_id = ?')->execute([$id]);
    if ($type === 'menu') {
        $inventoryIds = $_POST['recipe_inventory_id'] ?? [];
        $quantities = $_POST['recipe_quantity'] ?? [];
        $insertRecipe = $pdo->prepare('INSERT INTO product_recipes (product_id, inventory_id, quantity) VALUES (?, ?, ?)');
        $count = 0;
        foreach ($inventoryIds as $index => $rawInventoryId) {
            $recipeInventoryId = (int) $rawInventoryId;
            $quantity = (float) ($quantities[$index] ?? 0);
            if ($recipeInventoryId <= 0 || $quantity <= 0) {
                continue;
            }
            $insertRecipe->execute([$id, $recipeInventoryId, $quantity]);
            $count++;
        }
        if ($count === 0) {
            throw new RuntimeException('Menu F&B harus memiliki minimal satu bahan resep dengan takaran valid.');
        }
    }
    $pdo->commit();
    flash('Produk berhasil disimpan.');
    redirect('?page=products');
}

function save_inventory(PDO $pdo): never
{
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $sku = trim((string) ($_POST['sku'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $unit = trim((string) ($_POST['unit'] ?? ''));
    $stock = (float) ($_POST['stock'] ?? -1);
    $cost = (float) ($_POST['average_cost'] ?? -1);
    $minimum = (float) ($_POST['min_stock'] ?? -1);
    if ($name === '' || $sku === '' || $category === '' || $unit === '' || $stock < 0 || $cost < 0 || $minimum < 0) {
        throw new RuntimeException('Nama, SKU, kategori, satuan, stok, dan biaya harus valid.');
    }

    $pdo->beginTransaction();
    if ($id > 0) {
        $before = [];
        $products = $pdo->prepare('SELECT DISTINCT p.id FROM products p LEFT JOIN product_recipes r ON r.product_id=p.id WHERE p.inventory_id=? OR r.inventory_id=?');
        $products->execute([$id, $id]);
        foreach ($products->fetchAll(PDO::FETCH_COLUMN) as $productId) {
            $before[(int) $productId] = product_hpp($pdo, (int) $productId);
        }
        $current = $pdo->prepare('SELECT stock FROM inventory_items WHERE id=? FOR UPDATE');
        $current->execute([$id]);
        $oldStock = $current->fetchColumn();
        $pdo->prepare('UPDATE inventory_items SET name=?, sku=?, category=?, unit=?, stock=?, average_cost=?, min_stock=? WHERE id=?')
            ->execute([$name, $sku, $category, $unit, $stock, $cost, $minimum, $id]);
        $difference = $stock - (float) $oldStock;
        if ($difference !== 0.0) {
            store_movement($pdo, $id, 'Penyesuaian', $difference, 'Penyesuaian stok manual');
        }
        capture_product_cost_history($pdo, $id, $before);
    } else {
        $pdo->prepare('INSERT INTO inventory_items (name, sku, category, unit, stock, average_cost, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$name, $sku, $category, $unit, $stock, $cost, $minimum]);
        $id = (int) $pdo->lastInsertId();
        if ($stock > 0) {
            store_movement($pdo, $id, 'Penyesuaian', $stock, 'Stok awal');
        }
    }
    $pdo->commit();
    flash('Barang persediaan berhasil disimpan.');
    redirect('?page=inventory');
}

function save_purchase(PDO $pdo): never
{
    $supplier = trim((string) ($_POST['supplier'] ?? ''));
    $invoiceNumber = trim((string) ($_POST['invoice_number'] ?? ''));
    $date = (string) ($_POST['purchase_date'] ?? '');
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $inventoryIds = $_POST['inventory_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $costs = $_POST['unit_cost'] ?? [];
    if ($supplier === '' || $invoiceNumber === '' || $date === '' || count($inventoryIds) === 0) {
        throw new RuntimeException('Supplier, nomor nota, tanggal, dan minimal satu barang wajib diisi.');
    }
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO suppliers (name) VALUES (?) ON DUPLICATE KEY UPDATE name=VALUES(name)')->execute([$supplier]);
    $supplierStmt = $pdo->prepare('SELECT id FROM suppliers WHERE name=?');
    $supplierStmt->execute([$supplier]);
    $supplierId = (int) $supplierStmt->fetchColumn();
    $lines = [];
    $total = 0.0;
    $before = [];
    $purchaseUpdates = [];
    $seenInventory = [];
    foreach ($inventoryIds as $index => $rawId) {
        $inventoryId = (int) $rawId;
        $quantity = (float) ($quantities[$index] ?? 0);
        $unitCost = (float) ($costs[$index] ?? 0);
        if ($inventoryId <= 0 || $quantity <= 0 || $unitCost <= 0) {
            throw new RuntimeException('Setiap barang harus memiliki kuantitas dan harga beli lebih dari nol.');
        }
        $itemStmt = $pdo->prepare('SELECT id, stock, average_cost FROM inventory_items WHERE id=? FOR UPDATE');
        $itemStmt->execute([$inventoryId]);
        $item = $itemStmt->fetch();
        if (!$item) {
            throw new RuntimeException('Barang persediaan tidak ditemukan.');
        }
        if (!isset($seenInventory[$inventoryId])) {
            $seenInventory[$inventoryId] = true;
            $productStmt = $pdo->prepare('SELECT DISTINCT p.id FROM products p LEFT JOIN product_recipes r ON r.product_id=p.id WHERE p.inventory_id=? OR r.inventory_id=?');
            $productStmt->execute([$inventoryId, $inventoryId]);
            foreach ($productStmt->fetchAll(PDO::FETCH_COLUMN) as $productId) {
                $before[(int) $productId] ??= product_hpp($pdo, (int) $productId);
            }
        }
        $oldValues = $purchaseUpdates[$inventoryId] ?? ['stock' => (float) $item['stock'], 'average_cost' => (float) $item['average_cost']];
        $newStock = $oldValues['stock'] + $quantity;
        $newAverage = (($oldValues['stock'] * $oldValues['average_cost']) + ($quantity * $unitCost)) / $newStock;
        $purchaseUpdates[$inventoryId] = ['stock' => $newStock, 'average_cost' => $newAverage];
        $lines[] = ['inventory_id' => $inventoryId, 'quantity' => $quantity, 'unit_cost' => $unitCost];
        $total += $quantity * $unitCost;
    }
    $pdo->prepare('INSERT INTO purchase_invoices (supplier_id, invoice_number, purchase_date, total, notes) VALUES (?, ?, ?, ?, ?)')
        ->execute([$supplierId, $invoiceNumber, $date, $total, $notes]);
    $invoiceId = (int) $pdo->lastInsertId();
    $purchaseItem = $pdo->prepare('INSERT INTO purchase_items (invoice_id, inventory_id, quantity, unit_cost, line_total) VALUES (?, ?, ?, ?, ?)');
    foreach ($lines as $line) {
        $purchaseItem->execute([$invoiceId, $line['inventory_id'], $line['quantity'], $line['unit_cost'], $line['quantity'] * $line['unit_cost']]);
        store_movement($pdo, $line['inventory_id'], 'Pembelian', $line['quantity'], $invoiceNumber);
    }
    $updateInventory = $pdo->prepare('UPDATE inventory_items SET stock=?, average_cost=? WHERE id=?');
    foreach ($purchaseUpdates as $inventoryId => $values) {
        $updateInventory->execute([$values['stock'], $values['average_cost'], $inventoryId]);
    }
    $insertCost = $pdo->prepare('INSERT INTO cost_history (product_id, previous_hpp, new_hpp, cost_change) VALUES (?, ?, ?, ?)');
    foreach ($before as $productId => $previousHpp) {
        $newHpp = product_hpp($pdo, (int) $productId);
        $insertCost->execute([$productId, $previousHpp, $newHpp, $newHpp - $previousHpp]);
    }
    $pdo->commit();
    flash('Pembelian tersimpan. Stok dan biaya rata-rata berhasil diperbarui.');
    redirect('?page=purchases');
}

function checkout(PDO $pdo): never
{
    $cart = $_SESSION['cart'] ?? [];
    $method = (string) ($_POST['payment_method'] ?? 'Tunai');
    $cash = (float) ($_POST['cash_received'] ?? 0);
    if (!$cart || !in_array($method, ['Tunai', 'QRIS', 'Debit'], true)) {
        throw new RuntimeException('Keranjang kosong atau metode pembayaran tidak valid.');
    }
    $pdo->beginTransaction();
    $items = [];
    $total = 0.0;
    $grossProfit = 0.0;
    $deductions = [];
    foreach ($cart as $productId => $quantity) {
        $productStmt = $pdo->prepare('SELECT * FROM products WHERE id=? AND active=1 FOR UPDATE');
        $productStmt->execute([(int) $productId]);
        $product = $productStmt->fetch();
        if (!$product || (int) $quantity < 1) {
            throw new RuntimeException('Stok produk berubah atau tidak cukup. Periksa kembali keranjang.');
        }
        $hpp = product_hpp($pdo, (int) $productId);
        $lineTotal = (float) $product['selling_price'] * (int) $quantity;
        $total += $lineTotal;
        $grossProfit += ((float) $product['selling_price'] - $hpp) * (int) $quantity;
        $items[] = ['product' => $product, 'quantity' => (int) $quantity, 'hpp' => $hpp, 'total' => $lineTotal];
        if ($product['product_type'] === 'retail') {
            $deductions[(int) $product['inventory_id']] = ($deductions[(int) $product['inventory_id']] ?? 0) + (float) $product['stock_quantity'] * (int) $quantity;
        } else {
            $recipe = $pdo->prepare('SELECT inventory_id, quantity FROM product_recipes WHERE product_id=?');
            $recipe->execute([$product['id']]);
            foreach ($recipe->fetchAll() as $part) {
                $inventoryId = (int) $part['inventory_id'];
                $deductions[$inventoryId] = ($deductions[$inventoryId] ?? 0) + (float) $part['quantity'] * (int) $quantity;
            }
        }
    }
    if (!$deductions) {
        throw new RuntimeException('Produk pada keranjang belum memiliki relasi persediaan atau resep.');
    }
    $inventoryIds = array_keys($deductions);
    sort($inventoryIds);
    $lockInventory = $pdo->prepare('SELECT id, stock FROM inventory_items WHERE id=? FOR UPDATE');
    foreach ($inventoryIds as $inventoryId) {
        $lockInventory->execute([$inventoryId]);
        $stock = $lockInventory->fetch();
        if (!$stock || (float) $stock['stock'] < $deductions[$inventoryId]) {
            throw new RuntimeException('Stok bahan atau produk tidak mencukupi untuk seluruh keranjang.');
        }
    }
    if ($method === 'Tunai' && $cash < $total) {
        throw new RuntimeException('Jumlah pembayaran tunai kurang dari total transaksi.');
    }
    $number = 'TRX-' . date('ymd-His') . '-' . random_int(100, 999);
    $pdo->prepare('INSERT INTO sales (sale_number, payment_method, cash_received, total, gross_profit) VALUES (?, ?, ?, ?, ?)')
        ->execute([$number, $method, $method === 'Tunai' ? $cash : $total, $total, $grossProfit]);
    $saleId = (int) $pdo->lastInsertId();
    $insertSaleItem = $pdo->prepare(
        'INSERT INTO sale_items (sale_id, product_id, product_name, sku_snapshot, quantity, unit_price, hpp_snapshot, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $line) {
        $product = $line['product'];
        $insertSaleItem->execute([$saleId, $product['id'], $product['name'], $product['sku'], $line['quantity'], $product['selling_price'], $line['hpp'], $line['total']]);
    }
    foreach ($deductions as $inventoryId => $quantity) {
        $pdo->prepare('UPDATE inventory_items SET stock=stock-? WHERE id=?')->execute([$quantity, $inventoryId]);
        store_movement($pdo, (int) $inventoryId, 'Penjualan', -$quantity, $number);
    }
    $pdo->commit();
    $_SESSION['cart'] = [];
    $_SESSION['last_receipt'] = ['id' => $saleId, 'number' => $number, 'total' => $total, 'cash' => $method === 'Tunai' ? $cash : $total, 'method' => $method, 'created_at' => date('Y-m-d H:i:s')];
    flash('Transaksi berhasil disimpan.');
    redirect('?page=pos&receipt=1');
}

function review_recommendation(PDO $pdo): never
{
    $productId = (int) ($_POST['product_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    if (!in_array($status, ['accepted', 'rejected'], true)) {
        throw new RuntimeException('Tindakan rekomendasi tidak valid.');
    }
    $product = $pdo->prepare('SELECT id, target_margin FROM products WHERE id=? AND active=1');
    $product->execute([$productId]);
    $row = $product->fetch();
    if (!$row) {
        throw new RuntimeException('Produk tidak ditemukan.');
    }
    $price = suggested_price(product_hpp($pdo, $productId), (float) $row['target_margin'], (int) setting('price_rounding', 500));
    $pdo->prepare('INSERT INTO recommendations (product_id, status, suggested_price, reviewed_at) VALUES (?, ?, ?, NOW())')
        ->execute([$productId, $status, $price]);
    flash($status === 'accepted' ? 'Rekomendasi disetujui. Terapkan perubahan harga secara terpisah.' : 'Rekomendasi ditolak.');
    redirect('?page=alerts');
}

function apply_recommendation(PDO $pdo): never
{
    $productId = (int) ($_POST['product_id'] ?? 0);
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id=? AND active=1 FOR UPDATE');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    $recommendation = $pdo->prepare("SELECT * FROM recommendations WHERE product_id=? AND status='accepted' ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $recommendation->execute([$productId]);
    $row = $recommendation->fetch();
    if (!$product || !$row) {
        throw new RuntimeException('Setujui rekomendasi harga sebelum menerapkannya.');
    }
    $price = (float) $row['suggested_price'];
    $pdo->prepare('UPDATE products SET selling_price=? WHERE id=?')->execute([$price, $productId]);
    $pdo->prepare('INSERT INTO price_history (product_id, product_name, old_price, new_price) VALUES (?, ?, ?, ?)')
        ->execute([$productId, $product['name'], $product['selling_price'], $price]);
    $pdo->prepare("UPDATE recommendations SET status='applied', reviewed_at=NOW() WHERE id=?")->execute([$row['id']]);
    $pdo->commit();
    flash('Harga produk diperbarui setelah persetujuan.');
    redirect('?page=alerts');
}

function save_settings(PDO $pdo): never
{
    $business = trim((string) ($_POST['business_name'] ?? ''));
    $margin = (float) ($_POST['margin_alert'] ?? 0);
    $rounding = (int) ($_POST['price_rounding'] ?? 0);
    if ($business === '' || $margin <= 0 || $margin >= 100 || !in_array($rounding, [500, 1000, 5000], true)) {
        throw new RuntimeException('Periksa nama usaha, batas margin, dan pembulatan harga.');
    }
    $save = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    foreach (['business_name' => $business, 'margin_alert' => (string) $margin, 'price_rounding' => (string) $rounding, 'notifications' => isset($_POST['notifications']) ? '1' : '0'] as $key => $value) {
        $save->execute([$key, $value]);
    }
    flash('Pengaturan berhasil disimpan.');
    redirect('?page=settings');
}

function reset_demo(PDO $pdo): never
{
    $tables = ['price_history', 'recommendations', 'cost_history', 'inventory_movements', 'sale_items', 'sales', 'purchase_items', 'purchase_invoices', 'product_recipes', 'products', 'suppliers', 'inventory_items', 'app_settings'];
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $table) {
        $pdo->exec('TRUNCATE TABLE ' . $table);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();
    $_SESSION['cart'] = [];
    session_write_close();
    redirect('setup.php');
}
