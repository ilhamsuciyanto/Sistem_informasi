<?php
$cart = $_SESSION['cart'] ?? [];
$search = trim((string) ($_GET['q'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$allProducts = products_rows($pdo, true);
$categories = array_values(array_unique(array_column($allProducts, 'category')));
$availableProducts = array_values(array_filter($allProducts, static fn(array $product): bool => ($search === '' || stripos($product['name'] . ' ' . $product['sku'], $search) !== false) && ($category === '' || $product['category'] === $category)));
$cartRows = [];
$cartTotal = 0.0;
foreach ($cart as $productId => $quantity) {
    $statement = $pdo->prepare('SELECT * FROM products WHERE id=? AND active=1');
    $statement->execute([(int) $productId]);
    $product = $statement->fetch();
    if (!$product) {
        unset($_SESSION['cart'][$productId]);
        continue;
    }
    $product['quantity'] = (int) $quantity;
    $product['line_total'] = (float) $product['selling_price'] * (int) $quantity;
    $product['available'] = product_stock($pdo, (int) $productId);
    $cartRows[] = $product;
    $cartTotal += $product['line_total'];
}
$receipt = null;
$receiptItems = [];
if (isset($_GET['receipt'], $_SESSION['last_receipt']) && (int) $_GET['receipt'] === 1) {
    $receipt = $_SESSION['last_receipt'];
    $stmt = $pdo->prepare('SELECT * FROM sale_items WHERE sale_id=?');
    $stmt->execute([$receipt['id']]);
    $receiptItems = $stmt->fetchAll();
}
page_heading('TRANSAKSI PENJUALAN', 'Kasir POS', 'Pilih produk, atur jumlah, lalu selesaikan pembayaran.', '<span class="data-chip"><i class="live-dot"></i>Kasir aktif</span>');
?>
<div class="pos-layout">
  <section class="pos-catalog">
    <div class="panel pos-filter">
      <form class="search-input" method="get"><input type="hidden" name="page" value="pos"><input type="hidden" name="category" value="<?= e($category) ?>"><?= icon('search', 16) ?><input name="q" value="<?= e($search) ?>" placeholder="Cari produk atau SKU..."></form>
      <div class="category-tabs"><a class="<?= $category === '' ? 'selected' : '' ?>" href="?page=pos">Semua</a><?php foreach ($categories as $item): ?><a class="<?= $category === $item ? 'selected' : '' ?>" href="?page=pos&category=<?= urlencode($item) ?>"><?= e($item) ?></a><?php endforeach; ?></div>
    </div>
    <div class="pos-product-grid">
      <?php foreach ($availableProducts as $product): $stock = product_stock($pdo, (int) $product['id']); ?>
      <div class="pos-product-card <?= $stock < 1 ? 'unavailable' : '' ?>"><div class="pos-product-art"><?= icon('products', 23) ?><span><?= e($product['category']) ?></span></div><div class="pos-product-info"><strong><?= e($product['name']) ?></strong><small><?= e($product['sku']) ?> · <?= $stock ?> tersedia</small><b><?= rupiah($product['selling_price']) ?></b></div><?php if ($stock > 0): ?><?php post_open('cart_add', 'pos'); ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><button class="add-product" aria-label="Tambah <?= e($product['name']) ?>">+</button></form><?php endif; ?></div>
      <?php endforeach; ?>
      <?php if (!$availableProducts): ?><div class="panel"><div class="empty-state"><strong>Produk tidak ditemukan</strong><p>Ubah pencarian atau kategori untuk melihat produk.</p></div></div><?php endif; ?>
    </div>
  </section>
  <aside class="panel cart-panel">
    <div class="cart-header"><div><h2>Keranjang</h2><p><?= array_sum(array_column($cartRows, 'quantity')) ?> item</p></div><?php post_open('cart_clear', 'pos'); ?><button class="text-button" <?= !$cartRows ? 'disabled' : '' ?>>Kosongkan</button></form></div>
    <div class="cart-items">
      <?php if (!$cartRows): ?><div class="empty-state"><span class="empty-icon"><?= icon('pos', 20) ?></span><strong>Keranjang kosong</strong><p>Pilih produk dari katalog untuk memulai transaksi.</p></div><?php endif; ?>
      <?php foreach ($cartRows as $line): ?><div class="cart-item"><div class="cart-item-copy"><strong><?= e($line['name']) ?></strong><small><?= rupiah($line['selling_price']) ?> / item</small><div class="quantity-control">
        <?php post_open('cart_update', 'pos'); ?><input type="hidden" name="product_id" value="<?= (int) $line['id'] ?>"><input type="hidden" name="quantity" value="<?= $line['quantity'] - 1 ?>"><button aria-label="Kurangi jumlah">−</button></form><span><?= $line['quantity'] ?></span>
        <?php post_open('cart_update', 'pos'); ?><input type="hidden" name="product_id" value="<?= (int) $line['id'] ?>"><input type="hidden" name="quantity" value="<?= $line['quantity'] + 1 ?>"><button aria-label="Tambah jumlah" <?= $line['quantity'] >= $line['available'] ? 'disabled' : '' ?>>+</button></form>
      </div></div><strong><?= rupiah($line['line_total']) ?></strong></div><?php endforeach; ?>
    </div>
    <div class="cart-checkout">
      <?php post_open('checkout', 'pos'); ?>
      <label class="field">Metode pembayaran<select name="payment_method" id="paymentMethod"><option>Tunai</option><option>QRIS</option><option>Debit</option></select></label>
      <label class="field cash-field">Jumlah dibayar (Rp)<input type="number" name="cash_received" min="<?= e($cartTotal) ?>" placeholder="<?= (int) $cartTotal ?>" value="<?= isset($_POST['cash_received']) ? e($_POST['cash_received']) : '' ?>"></label>
      <div class="checkout-total"><span>Total pembayaran</span><strong><?= rupiah($cartTotal) ?></strong></div>
      <p class="checkout-note">Stok dikurangi dan HPP transaksi disimpan sebagai snapshot.</p>
      <button class="button button-primary full-button" <?= !$cartRows ? 'disabled' : '' ?>><?= icon('check', 15) ?> Selesaikan transaksi</button>
      </form>
    </div>
  </aside>
</div>
<?php if ($receipt): ?><div class="modal-backdrop"><section class="modal receipt-modal"><div class="modal-header"><h2>Transaksi berhasil</h2><a class="icon-button" href="?page=pos">×</a></div><div class="modal-body"><div class="receipt-success"><span class="receipt-check"><?= icon('check', 24) ?></span><strong>Pembayaran diterima</strong><small><?= e($receipt['number']) ?> · <?= e(date_id($receipt['created_at'], true)) ?></small></div><div class="receipt-lines"><?php foreach ($receiptItems as $item): ?><div><span><?= e($item['product_name']) ?> × <?= e($item['quantity']) ?></span><strong><?= rupiah($item['line_total']) ?></strong></div><?php endforeach; ?></div><div class="receipt-total"><span>Total · <?= e($receipt['method']) ?></span><strong><?= rupiah($receipt['total']) ?></strong></div><?php if ($receipt['method'] === 'Tunai'): ?><div class="receipt-payment"><span>Dibayar <?= rupiah($receipt['cash']) ?></span><span>Kembali <?= rupiah($receipt['cash'] - $receipt['total']) ?></span></div><?php endif; ?><button class="button button-secondary full-button print-button" onclick="window.print()"><?= icon('reports', 15) ?> Cetak bukti transaksi</button></div></section></div><?php unset($_SESSION['last_receipt']); endif; ?>
