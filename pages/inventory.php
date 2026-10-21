<?php
$editingId = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM inventory_items WHERE id=?');
    $stmt->execute([$editingId]);
    $editing = $stmt->fetch() ?: null;
}
$inventory = inventory_rows($pdo);
$search = trim((string) ($_GET['q'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$categories = array_values(array_unique(array_column($inventory, 'category')));
$visible = array_values(array_filter($inventory, static fn(array $item): bool => ($search === '' || stripos($item['name'] . ' ' . $item['sku'], $search) !== false) && ($category === '' || $item['category'] === $category)));
$low = count(array_filter($inventory, static fn(array $item): bool => (float) $item['stock'] <= (float) $item['min_stock']));
$stockValue = array_sum(array_map(static fn(array $item): float => (float) $item['stock'] * (float) $item['average_cost'], $inventory));
$movesToday = $pdo->prepare('SELECT COUNT(*) FROM inventory_movements WHERE DATE(created_at)=?');
$movesToday->execute([date('Y-m-d')]);
page_heading('STOK & PERSEDIAAN', 'Persediaan', 'Pantau kuantitas, biaya rata-rata, dan pergerakan stok.', '<a class="button button-primary" href="?page=inventory&new=1">' . icon('plus', 16) . ' Tambah barang</a>');
?>
<div class="metrics-grid compact-metrics">
  <article class="metric-card"><span class="metric-icon"><?= icon('inventory', 18) ?></span><span class="metric-label">Jenis barang</span><strong class="metric-value"><?= count($inventory) ?></strong><span class="metric-note">Bahan, kemasan, dan ritel</span></article>
  <article class="metric-card"><span class="metric-icon amber"><?= icon('alerts', 18) ?></span><span class="metric-label">Perlu restock</span><strong class="metric-value"><?= $low ?></strong><span class="metric-note">Stok di bawah minimum</span></article>
  <article class="metric-card"><span class="metric-icon green"><?= icon('hpp', 18) ?></span><span class="metric-label">Nilai persediaan</span><strong class="metric-value"><?= rupiah($stockValue) ?></strong><span class="metric-note">Biaya rata-rata berjalan</span></article>
  <article class="metric-card"><span class="metric-icon blue"><?= icon('clock', 18) ?></span><span class="metric-label">Pergerakan hari ini</span><strong class="metric-value"><?= (int) $movesToday->fetchColumn() ?></strong><span class="metric-note">Pembelian dan penjualan</span></article>
</div>
<?php if (isset($_GET['new']) || $editing): ?>
<section class="panel form-panel" id="inventoryForm"><div class="panel-heading"><div><h2><?= $editing ? 'Ubah barang persediaan' : 'Tambah barang persediaan' ?></h2><p>Nilai stok yang disunting akan tercatat sebagai penyesuaian.</p></div><a class="icon-button" href="?page=inventory">×</a></div>
  <?php post_open('inventory_save', 'inventory'); ?><input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
  <div class="form-grid"><label class="field">Nama barang<input required name="name" value="<?= e($editing['name'] ?? '') ?>"></label><label class="field">Kode barang / SKU<input required name="sku" value="<?= e($editing['sku'] ?? '') ?>"></label><label class="field">Kategori<input required name="category" value="<?= e($editing['category'] ?? 'Bahan baku') ?>"></label><label class="field">Satuan dasar<input required name="unit" value="<?= e($editing['unit'] ?? 'pcs') ?>" placeholder="gram, ml, pcs"></label><label class="field">Stok saat ini<input required type="number" min="0" step="any" name="stock" value="<?= e($editing['stock'] ?? 0) ?>"></label><label class="field">Biaya rata-rata per satuan (Rp)<input required type="number" min="0" step="any" name="average_cost" value="<?= e($editing['average_cost'] ?? 0) ?>"></label><label class="field">Stok minimum<input required type="number" min="0" step="any" name="min_stock" value="<?= e($editing['min_stock'] ?? 0) ?>"></label></div>
  <div class="inventory-cost-preview" aria-live="polite"><span class="panel-icon"><?= icon('hpp', 17) ?></span><div><strong>Nilai stok saat ini <b id="inventoryValuePreview"><?= rupiah((float) ($editing['stock'] ?? 0) * (float) ($editing['average_cost'] ?? 0)) ?></b></strong><small id="inventoryCostExplanation">Stok × biaya rata-rata per satuan. Ini nilai persediaan, bukan HPP produk.</small></div></div>
  <p class="inventory-hpp-help">HPP produk dihitung di <strong>Produk & menu</strong>: untuk menu F&amp;B, total takaran resep dikali biaya setiap bahan; untuk produk ritel, biaya bahan dikali takaran per unit jual. Setelah barang ini disimpan, pilih sebagai bahan/produk agar biaya ikut masuk ke perhitungan HPP.</p>
  <div class="modal-actions"><a class="button button-secondary" href="?page=inventory">Batal</a><button class="button button-primary">Simpan barang</button></div></form>
</section>
<?php endif; ?>
<section class="panel">
  <form class="toolbar" method="get"><input type="hidden" name="page" value="inventory"><label class="search-input"><?= icon('search', 16) ?><input name="q" value="<?= e($search) ?>" placeholder="Cari nama atau kode..."></label><select class="select-control" name="category"><option value="">Semua kategori</option><?php foreach ($categories as $item): ?><option value="<?= e($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select><button class="button button-secondary">Filter</button><span class="result-count"><?= count($visible) ?> barang</span></form>
  <div class="table-wrap"><table><thead><tr><th>NAMA BARANG</th><th>KATEGORI</th><th>STOK</th><th>BIAYA RATA-RATA</th><th>NILAI STOK</th><th>MINIMUM</th><th>STATUS</th><th></th></tr></thead><tbody><?php foreach ($visible as $item): $needsRestock = (float) $item['stock'] <= (float) $item['min_stock']; ?>
  <tr><td><div class="product-cell"><span class="tiny-product"><?= icon('inventory', 15) ?></span><span><strong><?= e($item['name']) ?></strong><small><?= e($item['sku']) ?> · <?= e($item['unit']) ?></small></span></div></td><td><?= e($item['category']) ?></td><td><strong><?= e(number_format((float) $item['stock'], 3, ',', '.')) ?></strong> <?= e($item['unit']) ?></td><td><?= rupiah($item['average_cost']) ?> / <?= e($item['unit']) ?></td><td><?= rupiah((float) $item['stock'] * (float) $item['average_cost']) ?></td><td><?= e($item['min_stock']) ?> <?= e($item['unit']) ?></td><td><span class="status-badge <?= $needsRestock ? 'warning' : 'good' ?>"><?= $needsRestock ? 'Restock' : 'Aman' ?></span></td><td><a class="text-button" href="?page=inventory&edit=<?= (int) $item['id'] ?>">Ubah</a></td></tr>
  <?php endforeach; ?></tbody></table><?php if (!$visible): ?><div class="empty-state"><strong>Barang tidak ditemukan</strong></div><?php endif; ?></div>
</section>
<section class="panel section-gap"><div class="panel-heading"><div><h2>Pergerakan persediaan</h2><p>Perubahan stok dari pembelian, penjualan, dan penyesuaian</p></div></div>
  <?php $movements = $pdo->query('SELECT m.*, i.name, i.unit FROM inventory_movements m LEFT JOIN inventory_items i ON i.id=m.inventory_id ORDER BY m.created_at DESC LIMIT 15')->fetchAll(); ?>
  <?php if (!$movements): ?><div class="empty-state"><span class="empty-icon"><?= icon('inventory', 19) ?></span><strong>Belum ada pergerakan</strong><p>Stok berubah saat pembelian, POS, atau penyesuaian.</p></div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>TANGGAL</th><th>BARANG</th><th>AKTIVITAS</th><th>PERUBAHAN</th><th>REFERENSI</th></tr></thead><tbody><?php foreach ($movements as $movement): ?><tr><td><?= e(date_id($movement['created_at'], true)) ?></td><td><?= e($movement['name'] ?? 'Barang dihapus') ?></td><td><?= e($movement['movement_type']) ?></td><td class="<?= $movement['quantity'] > 0 ? 'text-success' : 'text-danger' ?>"><?= $movement['quantity'] > 0 ? '+' : '' ?><?= e($movement['quantity']) ?> <?= e($movement['unit'] ?? '') ?></td><td><?= e($movement['reference_note']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
