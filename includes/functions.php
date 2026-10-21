<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(float|int|string|null $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function date_id(string|null $value, bool $withTime = false): string
{
    if (!$value) {
        return '—';
    }
    $timestamp = strtotime($value);
    $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $result = date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    return $withTime ? $result . ', ' . date('H:i', $timestamp) : $result;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function consume_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.');
    }
}

function setting(string $key, mixed $fallback = null): mixed
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM app_settings') as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $exception) {
            error_log('Settings belum tersedia: ' . $exception->getMessage());
        }
    }
    return $settings[$key] ?? $fallback;
}

function product_hpp(PDO $pdo, int $productId): float
{
    $statement = $pdo->prepare(
        "SELECT p.product_type, p.inventory_id, p.stock_quantity, i.average_cost
         FROM products p LEFT JOIN inventory_items i ON i.id = p.inventory_id
         WHERE p.id = ?"
    );
    $statement->execute([$productId]);
    $product = $statement->fetch();
    if (!$product) {
        return 0;
    }
    if ($product['product_type'] === 'retail') {
        return (float) ($product['average_cost'] ?? 0) * (float) $product['stock_quantity'];
    }
    $statement = $pdo->prepare(
        'SELECT COALESCE(SUM(r.quantity * i.average_cost), 0)
         FROM product_recipes r JOIN inventory_items i ON i.id = r.inventory_id
         WHERE r.product_id = ?'
    );
    $statement->execute([$productId]);
    return (float) $statement->fetchColumn();
}

function product_margin(float $price, float $hpp): float
{
    return $price > 0 ? (($price - $hpp) / $price) * 100 : 0;
}

function suggested_price(float $hpp, float $margin, int $rounding): float
{
    if ($margin < 0 || $margin >= 100 || $rounding <= 0) {
        return 0;
    }
    return ceil(($hpp / (1 - $margin / 100)) / $rounding) * $rounding;
}

function product_stock(PDO $pdo, int $productId): int
{
    $statement = $pdo->prepare('SELECT product_type, inventory_id, stock_quantity FROM products WHERE id = ?');
    $statement->execute([$productId]);
    $product = $statement->fetch();
    if (!$product) {
        return 0;
    }
    if ($product['product_type'] === 'retail') {
        $stock = $pdo->prepare('SELECT stock FROM inventory_items WHERE id = ?');
        $stock->execute([$product['inventory_id']]);
        return (int) floor((float) $stock->fetchColumn() / max((float) $product['stock_quantity'], 1));
    }
    $stock = $pdo->prepare(
        'SELECT MIN(FLOOR(i.stock / r.quantity))
         FROM product_recipes r JOIN inventory_items i ON i.id = r.inventory_id WHERE r.product_id = ?'
    );
    $stock->execute([$productId]);
    return (int) ($stock->fetchColumn() ?: 0);
}

function margin_products(PDO $pdo): array
{
    $products = $pdo->query("SELECT * FROM products WHERE active = 1 ORDER BY name")->fetchAll();
    $result = [];
    $alertMargin = (float) setting('margin_alert', 35);
    foreach ($products as $product) {
        $hpp = product_hpp($pdo, (int) $product['id']);
        $margin = product_margin((float) $product['selling_price'], $hpp);
        $stmt = $pdo->prepare('SELECT cost_change FROM cost_history WHERE product_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$product['id']]);
        $latestChange = $stmt->fetchColumn();
        $costIncreased = $latestChange !== false && (float) $latestChange > 0;
        if ($margin < $alertMargin || $costIncreased) {
            $product['hpp'] = $hpp;
            $product['margin'] = $margin;
            $product['cost_increased'] = $costIncreased;
            $product['suggested_price'] = suggested_price($hpp, (float) $product['target_margin'], (int) setting('price_rounding', 500));
            $status = $pdo->prepare('SELECT status FROM recommendations WHERE product_id = ? ORDER BY id DESC LIMIT 1');
            $status->execute([$product['id']]);
            $product['recommendation_status'] = $status->fetchColumn() ?: 'pending';
            $result[] = $product;
        }
    }
    return $result;
}

function inventory_rows(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM inventory_items ORDER BY name')->fetchAll();
}

function products_rows(PDO $pdo, bool $activeOnly = false): array
{
    return $pdo->query('SELECT * FROM products ' . ($activeOnly ? 'WHERE active = 1 ' : '') . 'ORDER BY name')->fetchAll();
}

function ensure_demo_seasonings(PDO $pdo): void
{
    $statement = $pdo->prepare(
        'INSERT IGNORE INTO inventory_items (name, sku, category, unit, stock, average_cost, min_stock)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ([
        ['Garam halus', 'BHN-007', 'Bahan baku', 'gram', 500, 10, 100],
        ['Lada bubuk', 'BHN-008', 'Bahan baku', 'gram', 50, 240, 10],
        ['Kaldu bubuk', 'BHN-009', 'Bahan baku', 'gram', 100, 100, 20],
        ['Bawang bombay', 'BHN-010', 'Bahan baku', 'pcs', 10, 5000, 3],
    ] as $item) {
        $statement->execute($item);
    }
}

function store_movement(PDO $pdo, int $inventoryId, string $type, float $quantity, string $reference): void
{
    $statement = $pdo->prepare(
        'INSERT INTO inventory_movements (inventory_id, movement_type, quantity, reference_note) VALUES (?, ?, ?, ?)'
    );
    $statement->execute([$inventoryId, $type, $quantity, $reference]);
}

function capture_product_cost_history(PDO $pdo, int $inventoryId, array $before): void
{
    $stmt = $pdo->prepare(
        'SELECT DISTINCT p.id, p.name FROM products p
         LEFT JOIN product_recipes r ON r.product_id = p.id
         WHERE p.inventory_id = ? OR r.inventory_id = ?'
    );
    $stmt->execute([$inventoryId, $inventoryId]);
    $insert = $pdo->prepare(
        'INSERT INTO cost_history (product_id, previous_hpp, new_hpp, cost_change) VALUES (?, ?, ?, ?)'
    );
    foreach ($stmt->fetchAll() as $product) {
        $oldHpp = (float) ($before[$product['id']] ?? 0);
        $newHpp = product_hpp($pdo, (int) $product['id']);
        $insert->execute([$product['id'], $oldHpp, $newHpp, $newHpp - $oldHpp]);
    }
}

function export_csv(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('Tidak dapat menyiapkan file CSV.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

function icon(string $name, int $size = 18): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'pos' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'products' => '<path d="M20.6 13.4 13.5 20.5a2 2 0 0 1-2.8 0L3.5 13.3a2 2 0 0 1-.6-1.4V4a1 1 0 0 1 1-1h7.9a2 2 0 0 1 1.4.6l7.4 7a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1"/>',
        'inventory' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/>',
        'purchases' => '<path d="M3 7h18l-2 13H5L3 7Z"/><path d="M8 7a4 4 0 0 1 8 0M8 12v3m8-3v3"/>',
        'hpp' => '<path d="M3 3v18h18"/><path d="m7 14 4-4 4 3 6-7"/>',
        'alerts' => '<path d="m10.3 3.9-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.1l-8-14a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 4h.01"/>',
        'reports' => '<path d="M3 3v18h18"/><path d="M18 17V9m-5 8V5m-5 12v-4"/>',
        'settings' => '<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.6a8 8 0 0 1-1.8 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.8-1l-1.7.6-1.4-2.4 1.4-1.1a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.6a8 8 0 0 1 1.8-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.8 1l1.7-.6 1.4 2.4-1.4 1.1a8 8 0 0 1 0 2Z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4"/>',
        'plus' => '<path d="M12 5v14m-7-7h14"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'arrow' => '<path d="M5 12h14m-7-7 7 7-7 7"/>',
        'box' => '<path d="m12 3 9 5v8l-9 5-9-5V8l9-5Z"/><path d="m3 8 9 5 9-5m-9 5v8"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    ];
    $body = $paths[$name] ?? $paths['box'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

function page_heading(string $eyebrow, string $title, string $description, string $action = ''): void
{
    echo '<div class="page-heading"><div><div class="eyebrow">' . e($eyebrow) . '</div><h1>' . e($title) . '</h1><p>' . e($description) . '</p></div>';
    if ($action !== '') {
        echo '<div class="heading-action">' . $action . '</div>';
    }
    echo '</div>';
}

function post_open(string $action, string $returnPage): void
{
    echo '<form method="post"><input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '"><input type="hidden" name="action" value="' . e($action) . '"><input type="hidden" name="return_page" value="' . e($returnPage) . '">';
}
