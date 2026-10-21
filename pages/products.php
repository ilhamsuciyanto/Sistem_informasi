<?php
$editingId = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
    $stmt->execute([$editingId]);
    $editing = $stmt->fetch() ?: null;
}
$inventory = inventory_rows($pdo);
$allProducts = products_rows($pdo);
$filter = (string) ($_GET['status'] ?? 'all');
$search = trim((string) ($_GET['q'] ?? ''));
$visibleProducts = array_values(array_filter($allProducts, static function (array $product) use ($filter, $search): bool {
    $matchesStatus = $filter === 'active' ? (bool) $product['active'] : ($filter === 'inactive' ? !(bool) $product['active'] : true);
    $matchesSearch = $search === '' || stripos($product['name'] . ' ' . $product['sku'], $search) !== false;
    return $matchesStatus && $matchesSearch;
}));
$recipes = [];
if ($editing) {
    $stmt = $pdo->prepare('SELECT inventory_id, quantity FROM product_recipes WHERE product_id=? ORDER BY id');
    $stmt->execute([$editingId]);
    $recipes = $stmt->fetchAll();
}
if (!$recipes) {
    $recipes = [['inventory_id' => '', 'quantity' => 1]];
}
page_heading('KATALOG', 'Produk & menu', 'Kelola produk, resep, harga jual, dan target margin.', '<a class="button button-primary" href="?page=products&new=1">' . icon('plus', 16) . ' Tambah produk</a>');
?>
<?php if (isset($_GET['new']) || $editing): ?>
<section class="panel form-panel" id="product-form" data-price-rounding="<?= (int) setting('price_rounding', 500) ?>">
  <div class="panel-heading"><div><h2><?= $editing ? 'Ubah produk' : 'Tambah produk' ?></h2><p>Atur data katalog dan bahan resep menu.</p></div><a class="icon-button" href="?page=products" aria-label="Tutup">×</a></div>
  <?php post_open('product_save', 'products'); ?>
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div class="form-grid">
      <label class="field">Nama produk<input required name="name" value="<?= e($editing['name'] ?? '') ?>" placeholder="Contoh: Es kopi susu"></label>
      <label class="field">SKU / kode<input required name="sku" value="<?= e($editing['sku'] ?? '') ?>" placeholder="MENU-003"></label>
      <label class="field">Kategori<input required name="category" value="<?= e($editing['category'] ?? 'Minuman') ?>"></label>
      <label class="field">Jenis produk<select name="product_type" id="productType"><option value="menu" <?= ($editing['product_type'] ?? 'menu') === 'menu' ? 'selected' : '' ?>>Menu F&B</option><option value="retail" <?= ($editing['product_type'] ?? '') === 'retail' ? 'selected' : '' ?>>Produk ritel</option></select></label>
      <label class="field">Harga jual (Rp)<input required type="number" min="1" name="selling_price" value="<?= e($editing['selling_price'] ?? '') ?>"></label>
      <label class="field">Target margin (%)<input required type="number" min="1" max="99" step="0.1" name="target_margin" value="<?= e($editing['target_margin'] ?? 40) ?>"></label>
      <div class="field retail-field"><span>Barang persediaan terkait</span><select name="inventory_id" required><?php foreach ($inventory as $item): ?><option value="<?= (int) $item['id'] ?>" data-cost="<?= e($item['average_cost']) ?>" data-unit="<?= e($item['unit']) ?>" <?= (int) ($editing['inventory_id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['name']) ?> (<?= e($item['unit']) ?> · <?= rupiah($item['average_cost']) ?>/unit)</option><?php endforeach; ?></select></div>
      <label class="field retail-field">Takaran per unit jual<input type="number" min="0.001" step="any" name="stock_quantity" value="<?= e($editing['stock_quantity'] ?? 1) ?>"></label>
      <div class="recipe-editor">
        <div class="section-title">Resep per porsi <button type="button" class="text-button" id="addRecipe"><?= icon('plus', 14) ?> Tambah bahan</button></div>
        <div id="recipeRows"><?php foreach ($recipes as $recipe): ?><div class="recipe-line"><select name="recipe_inventory_id[]" required><option value="" <?= empty($recipe['inventory_id']) ? 'selected' : '' ?>>Pilih bahan persediaan</option><?php foreach ($inventory as $item): ?><option value="<?= (int) $item['id'] ?>" data-cost="<?= e($item['average_cost']) ?>" data-unit="<?= e($item['unit']) ?>" <?= (int) $recipe['inventory_id'] === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['name']) ?> · <?= e($item['unit']) ?> (<?= rupiah($item['average_cost']) ?>/unit)</option><?php endforeach; ?></select><input aria-label="Takaran resep" name="recipe_quantity[]" type="number" min="0.001" step="any" value="<?= e($recipe['quantity']) ?>"><button type="button" class="icon-button recipe-remove" aria-label="Hapus bahan">×</button></div><?php endforeach; ?></div>
      </div>
      <section class="hpp-preview" id="hppPreview" aria-live="polite">
        <div class="hpp-preview-heading"><div><strong>Pratinjau perhitungan HPP</strong><small>Per porsi atau per unit jual · biaya mengikuti persediaan</small></div><span class="hpp-live">Dihitung otomatis</span></div>
        <div class="hpp-preview-values">
          <div><span>HPP</span><strong id="previewHpp">Rp 0</strong></div>
          <div><span>Laba kotor / unit</span><strong id="previewProfit">—</strong></div>
          <div><span>Margin</span><strong id="previewMargin">—</strong></div>
          <div><span>Harga saran sesuai target</span><strong id="previewPrice">—</strong></div>
        </div>
        <div class="hpp-breakdown" id="hppBreakdown"></div>
        <p class="hpp-preview-note" id="hppPreviewNote">HPP = jumlah takaran bahan × biaya rata-rata per satuan. Isi resep dan biaya persediaan untuk melihat hasil.</p>
      </section>
      <label class="checkbox-row"><input type="checkbox" name="active" value="1" <?= !isset($editing['active']) || $editing['active'] ? 'checked' : '' ?>> Produk aktif dan dapat dijual</label>
    </div>
    <div class="modal-actions"><a class="button button-secondary" href="?page=products">Batal</a><button class="button button-primary"><?= icon('check', 15) ?> Simpan produk</button></div>
  </form>
</section>
<?php endif; ?>
<section class="panel">
  <form class="toolbar" method="get"><input type="hidden" name="page" value="products"><label class="search-input"><?= icon('search', 16) ?><input name="q" value="<?= e($search) ?>" placeholder="Cari nama atau kode..."></label><select class="select-control" name="status"><option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua status</option><option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Aktif</option><option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Nonaktif</option></select><button class="button button-secondary">Cari</button><span class="result-count"><?= count($visibleProducts) ?> produk</span></form>
  <div class="table-wrap"><table><thead><tr><th>PRODUK</th><th>KATEGORI</th><th>JENIS</th><th>HPP</th><th>HARGA JUAL</th><th>MARGIN</th><th>STATUS</th><th></th></tr></thead><tbody>
  <?php foreach ($visibleProducts as $product): $hpp = product_hpp($pdo, (int) $product['id']); $margin = product_margin((float) $product['selling_price'], $hpp); $usedStmt = $pdo->prepare('SELECT COUNT(*) FROM sale_items WHERE product_id=?'); $usedStmt->execute([$product['id']]); $usedInSales = (int) $usedStmt->fetchColumn() > 0; ?>
    <tr><td><div class="product-cell"><span class="tiny-product"><?= icon('products', 15) ?></span><span><strong><?= e($product['name']) ?></strong><small><?= e($product['sku']) ?></small></span></div></td><td><?= e($product['category']) ?></td><td><?= $product['product_type'] === 'menu' ? 'Menu F&B' : 'Ritel' ?></td><td><?= rupiah($hpp) ?></td><td><?= rupiah($product['selling_price']) ?></td><td><span class="status-badge <?= $margin < (float) setting('margin_alert', 35) ? 'warning' : 'good' ?>"><?= number_format($margin, 1, ',', '.') ?>%</span></td><td><span class="status-badge <?= $product['active'] ? 'good' : 'neutral' ?>"><?= $product['active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td class="row-actions"><a class="text-button" href="?page=products&edit=<?= (int) $product['id'] ?>">Ubah</a><?php if (!$usedInSales): ?><?php post_open('product_remove', 'products'); ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><button class="text-button danger-link" data-confirm="Hapus produk ini?">Hapus</button></form><?php else: ?><?php post_open('product_toggle', 'products'); ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><button class="text-button <?= $product['active'] ? 'danger-link' : '' ?>"><?= $product['active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><?php endif; ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php if (!$visibleProducts): ?><div class="empty-state"><span class="empty-icon"><?= icon('products', 20) ?></span><strong>Produk tidak ditemukan</strong><p>Ubah pencarian atau tambahkan produk baru.</p></div><?php endif; ?>
  </div>
</section>
