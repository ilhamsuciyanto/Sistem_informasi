<?php
$products = products_rows($pdo, true);
$rows = [];
foreach ($products as $product) {
    $hpp = product_hpp($pdo, (int) $product['id']);
    $margin = product_margin((float) $product['selling_price'], $hpp);
    $stmt = $pdo->prepare('SELECT * FROM cost_history WHERE product_id=? ORDER BY created_at DESC, id DESC LIMIT 1');
    $stmt->execute([$product['id']]);
    $history = $stmt->fetch();
    $rows[] = ['product' => $product, 'hpp' => $hpp, 'margin' => $margin, 'history' => $history];
}
$historyRows = $pdo->query('SELECT ch.*, p.name FROM cost_history ch LEFT JOIN products p ON p.id=ch.product_id ORDER BY ch.created_at DESC LIMIT 50')->fetchAll();
$warningCount = count(array_filter($rows, static fn(array $row): bool => $row['margin'] < (float) setting('margin_alert', 35)));
$averageMargin = count($rows) ? array_sum(array_column($rows, 'margin')) / count($rows) : 0;
page_heading('PEMANTAUAN BIAYA', 'Dynamic HPP', 'Biaya produk dihitung dari biaya rata-rata persediaan terbaru.', '<span class="data-chip"><i class="live-dot"></i>Perhitungan langsung</span>');
?>
<div class="metrics-grid compact-metrics">
  <article class="metric-card"><span class="metric-icon"><?= icon('hpp', 18) ?></span><span class="metric-label">Produk dipantau</span><strong class="metric-value"><?= count($rows) ?></strong><span class="metric-note">Produk aktif</span></article>
  <article class="metric-card"><span class="metric-icon blue"><?= icon('clock', 18) ?></span><span class="metric-label">Perubahan tercatat</span><strong class="metric-value"><?= (int) $pdo->query('SELECT COUNT(*) FROM cost_history')->fetchColumn() ?></strong><span class="metric-note">Riwayat perubahan biaya</span></article>
  <article class="metric-card"><span class="metric-icon amber"><?= icon('alerts', 18) ?></span><span class="metric-label">Margin perlu perhatian</span><strong class="metric-value"><?= $warningCount ?></strong><span class="metric-note">Di bawah <?= e(setting('margin_alert', 35)) ?>%</span></article>
  <article class="metric-card"><span class="metric-icon green"><?= icon('reports', 18) ?></span><span class="metric-label">Rata-rata margin</span><strong class="metric-value"><?= number_format($averageMargin, 1, ',', '.') ?>%</strong><span class="metric-note">Produk aktif</span></article>
</div>
<section class="panel"><div class="panel-heading"><div><h2>HPP dan margin per produk</h2><p>Penjualan historis tetap memakai snapshot HPP saat transaksi.</p></div></div>
  <div class="table-wrap"><table><thead><tr><th>PRODUK</th><th>HPP SAAT INI</th><th>HPP SEBELUMNYA</th><th>PERUBAHAN</th><th>HARGA JUAL</th><th>LABA KOTOR / UNIT</th><th>MARGIN</th></tr></thead><tbody>
  <?php foreach ($rows as $row): $history = $row['history']; ?>
  <tr><td><div class="product-cell"><span class="tiny-product"><?= icon('products', 15) ?></span><span><strong><?= e($row['product']['name']) ?></strong><small><?= e($row['product']['sku']) ?> · target <?= e($row['product']['target_margin']) ?>%</small></span></div></td><td><strong><?= rupiah($row['hpp']) ?></strong></td><td><?= $history ? rupiah($history['previous_hpp']) : '—' ?></td><td><?php if ($history): ?><span class="<?= $history['cost_change'] > 0 ? 'text-danger' : 'text-success' ?>"><?= $history['cost_change'] > 0 ? '+' : '' ?><?= rupiah($history['cost_change']) ?></span><?php else: ?>—<?php endif; ?></td><td><?= rupiah($row['product']['selling_price']) ?></td><td><?= rupiah((float) $row['product']['selling_price'] - $row['hpp']) ?></td><td><span class="status-badge <?= $row['margin'] < (float) setting('margin_alert', 35) ? 'warning' : 'good' ?>"><?= number_format($row['margin'], 1, ',', '.') ?>%</span></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
</section>
<section class="panel section-gap"><div class="panel-heading"><div><h2>Riwayat perubahan HPP</h2><p>Perubahan biaya dari pencatatan pembelian</p></div></div>
  <?php if (!$historyRows): ?><div class="empty-state"><span class="empty-icon"><?= icon('hpp', 20) ?></span><strong>Belum ada riwayat HPP</strong><p>Catat pembelian bahan untuk memperbarui biaya.</p></div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>TANGGAL</th><th>PRODUK</th><th>HPP SEBELUMNYA</th><th>HPP BARU</th><th>PERUBAHAN</th></tr></thead><tbody><?php foreach ($historyRows as $history): ?><tr><td><?= e(date_id($history['created_at'], true)) ?></td><td><?= e($history['name'] ?? 'Produk diarsipkan') ?></td><td><?= rupiah($history['previous_hpp']) ?></td><td><?= rupiah($history['new_hpp']) ?></td><td class="<?= $history['cost_change'] > 0 ? 'text-danger' : 'text-success' ?>"><?= $history['cost_change'] > 0 ? '+' : '' ?><?= rupiah($history['cost_change']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
