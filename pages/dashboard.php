<?php
$today = date('Y-m-d');
$stmt = $pdo->prepare('SELECT COUNT(*) AS transactions, COALESCE(SUM(total),0) AS sales, COALESCE(SUM(gross_profit),0) AS gross FROM sales WHERE DATE(created_at)=?');
$stmt->execute([$today]);
$todayStats = $stmt->fetch();
$lowStock = (int) $pdo->query('SELECT COUNT(*) FROM inventory_items WHERE stock <= min_stock')->fetchColumn();
$products = products_rows($pdo, true);
$lowMargin = 0;
$marginRows = [];
foreach ($products as $product) {
    $hpp = product_hpp($pdo, (int) $product['id']);
    $margin = product_margin((float) $product['selling_price'], $hpp);
    $marginRows[] = ['product' => $product, 'hpp' => $hpp, 'margin' => $margin];
    if ($margin < (float) setting('margin_alert', 35)) {
        $lowMargin++;
    }
}
usort($marginRows, static fn(array $a, array $b): int => $a['margin'] <=> $b['margin']);
$marginRows = array_slice($marginRows, 0, 4);
$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sum = $pdo->prepare('SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(created_at)=?');
    $sum->execute([$date]);
    $weekdays = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $chart[] = ['date' => $date, 'day' => $weekdays[(int) date('w', strtotime($date))], 'total' => (float) $sum->fetchColumn()];
}
$chartMax = max(array_column($chart, 'total')) ?: 1;
$recentSales = $pdo->query('SELECT * FROM sales ORDER BY created_at DESC LIMIT 5')->fetchAll();
$latestCosts = $pdo->query('SELECT ch.*, p.name FROM cost_history ch LEFT JOIN products p ON p.id=ch.product_id ORDER BY ch.created_at DESC LIMIT 4')->fetchAll();
$activeWarnings = margin_products($pdo);
page_heading('RINGKASAN BISNIS', 'Dashboard', 'Pantau kondisi operasional ' . (string) setting('business_name', 'Racik Coffee & Mart') . ' hari ini.', '<a class="button button-secondary" href="?page=reports">' . icon('reports', 16) . ' Lihat laporan</a>');
?>
<div class="metrics-grid">
  <article class="metric-card"><span class="metric-icon"><?= icon('pos', 18) ?></span><span class="metric-label">Penjualan hari ini</span><strong class="metric-value"><?= rupiah($todayStats['sales']) ?></strong><span class="metric-note"><?= (int) $todayStats['transactions'] ?> transaksi tercatat</span></article>
  <article class="metric-card"><span class="metric-icon green"><?= icon('hpp', 18) ?></span><span class="metric-label">Estimasi laba kotor</span><strong class="metric-value"><?= rupiah($todayStats['gross']) ?></strong><span class="metric-note">Berdasarkan snapshot HPP</span></article>
  <article class="metric-card"><span class="metric-icon amber"><?= icon('alerts', 18) ?></span><span class="metric-label">Produk margin tipis</span><strong class="metric-value"><?= $lowMargin ?></strong><span class="metric-note">Di bawah batas peringatan</span></article>
  <article class="metric-card"><span class="metric-icon red"><?= icon('inventory', 18) ?></span><span class="metric-label">Stok perlu diisi</span><strong class="metric-value"><?= $lowStock ?></strong><span class="metric-note">Mencapai batas minimum</span></article>
</div>
<div class="dashboard-grid">
  <section class="panel chart-panel">
    <div class="panel-heading"><div><h2>Tren penjualan</h2><p>Performa 7 hari terakhir</p></div><span class="range-chip">7 hari terakhir</span></div>
    <div class="chart-meta"><span><i></i> Penjualan</span><strong>Total <?= rupiah(array_sum(array_column($chart, 'total'))) ?></strong></div>
    <div class="chart"><div class="y-labels"><span><?= rupiah($chartMax) ?></span><span><?= rupiah($chartMax / 2) ?></span><span>Rp 0</span></div><div class="chart-plot"><div class="chart-gridline top"></div><div class="chart-gridline middle"></div><div class="chart-gridline bottom"></div><div class="chart-columns"><?php foreach ($chart as $day): ?><div class="chart-column"><span class="chart-value"><?= $day['total'] ? e(rupiah($day['total'])) : '' ?></span><div class="chart-bar-wrap"><i style="height:<?= $day['total'] ? max(4, ($day['total'] / $chartMax) * 100) : 2 ?>%"></i></div><span class="chart-day"><?= e($day['day']) ?></span></div><?php endforeach; ?></div></div></div>
  </section>
  <section class="panel">
    <div class="panel-heading"><div><h2>Perlu ditindaklanjuti</h2><p>Peringatan dari data terkini</p></div><span class="warning-count"><?= count($activeWarnings) + $lowStock ?></span></div>
    <div class="warning-list">
      <?php if (!$activeWarnings && !$lowStock): ?><div class="empty-state"><span class="empty-icon"><?= icon('check', 20) ?></span><strong>Semua terlihat baik</strong><p>Belum ada peringatan aktif.</p></div><?php endif; ?>
      <?php foreach (array_slice($activeWarnings, 0, 3) as $warning): ?><a class="warning-row" href="?page=alerts"><span class="warning-symbol"><?= icon('alerts', 16) ?></span><span class="warning-copy"><strong>Margin <?= e($warning['name']) ?> menipis</strong><small>Margin saat ini <?= number_format($warning['margin'], 1, ',', '.') ?>%</small></span><?= icon('arrow', 15) ?></a><?php endforeach; ?>
      <?php foreach ($pdo->query('SELECT * FROM inventory_items WHERE stock <= min_stock ORDER BY stock ASC LIMIT 3')->fetchAll() as $item): ?><a class="warning-row" href="?page=inventory"><span class="warning-symbol stock"><?= icon('inventory', 16) ?></span><span class="warning-copy"><strong>Stok <?= e($item['name']) ?> hampir habis</strong><small>Sisa <?= e($item['stock']) ?> <?= e($item['unit']) ?></small></span><?= icon('arrow', 15) ?></a><?php endforeach; ?>
    </div><a class="text-link" href="?page=alerts">Lihat semua peringatan <?= icon('arrow', 14) ?></a>
  </section>
  <section class="panel">
    <div class="panel-heading"><div><h2>Produk dengan margin terendah</h2><p>Urut berdasarkan margin saat ini</p></div><a class="quiet-link" href="?page=hpp">Lihat HPP <?= icon('arrow', 14) ?></a></div>
    <div class="table-wrap"><table><thead><tr><th>PRODUK</th><th>HPP</th><th>HARGA JUAL</th><th>MARGIN</th></tr></thead><tbody>
    <?php foreach ($marginRows as $row): ?><tr><td><div class="product-cell"><span class="tiny-product"><?= icon('products', 15) ?></span><span><strong><?= e($row['product']['name']) ?></strong><small><?= e($row['product']['sku']) ?></small></span></div></td><td><?= rupiah($row['hpp']) ?></td><td><?= rupiah($row['product']['selling_price']) ?></td><td><span class="status-badge <?= $row['margin'] < (float) setting('margin_alert', 35) ? 'warning' : 'good' ?>"><?= number_format($row['margin'], 1, ',', '.') ?>%</span></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <section class="panel">
    <div class="panel-heading"><div><h2>Transaksi terbaru</h2><p>Aktivitas penjualan terakhir</p></div><a class="quiet-link" href="?page=reports">Lihat laporan <?= icon('arrow', 14) ?></a></div>
    <?php if (!$recentSales): ?><div class="empty-state"><span class="empty-icon"><?= icon('pos', 20) ?></span><strong>Belum ada transaksi</strong><p>Transaksi kasir yang selesai tampil di sini.</p></div><?php else: ?>
    <div class="recent-list"><?php foreach ($recentSales as $sale): ?><div class="recent-row"><span class="recent-icon"><?= icon('pos', 16) ?></span><span class="recent-copy"><strong><?= e($sale['sale_number']) ?></strong><small><?= e(date_id($sale['created_at'], true)) ?> · <?= e($sale['payment_method']) ?></small></span><strong><?= rupiah($sale['total']) ?></strong></div><?php endforeach; ?></div><?php endif; ?>
  </section>
  <section class="panel cost-panel"><div class="panel-heading"><div><h2>Perubahan biaya terakhir</h2><p>HPP setelah pembelian terbaru</p></div><a class="quiet-link" href="?page=hpp">Riwayat HPP <?= icon('arrow', 14) ?></a></div>
    <?php if (!$latestCosts): ?><div class="empty-state"><span class="empty-icon"><?= icon('hpp', 20) ?></span><strong>Belum ada perubahan biaya</strong><p>Catat pembelian untuk memperbarui HPP.</p></div><?php else: foreach ($latestCosts as $cost): ?><div class="cost-row"><span><strong><?= e($cost['name'] ?? 'Produk diarsipkan') ?></strong><small><?= e(date_id($cost['created_at'])) ?></small></span><b class="<?= $cost['cost_change'] > 0 ? 'text-danger' : 'text-success' ?>"><?= $cost['cost_change'] > 0 ? '+' : '' ?><?= rupiah($cost['cost_change']) ?></b></div><?php endforeach; endif; ?>
  </section>
</div>
