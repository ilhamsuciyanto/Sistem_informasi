<?php
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
$where = [];
$params = [];
if ($from !== '') { $where[] = 'DATE(created_at)>=?'; $params[] = $from; }
if ($to !== '') { $where[] = 'DATE(created_at)<=?'; $params[] = $to; }
$sql = 'SELECT * FROM sales' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$sales = $statement->fetchAll();
$totalSales = array_sum(array_column($sales, 'total'));
$grossProfit = array_sum(array_column($sales, 'gross_profit'));
$saleIds = array_column($sales, 'id');
$productTotals = [];
if ($saleIds) {
    $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
    $stmt = $pdo->prepare("SELECT product_name, SUM(quantity) AS quantity, SUM(line_total) AS total, SUM((unit_price-hpp_snapshot)*quantity) AS profit FROM sale_items WHERE sale_id IN ($placeholders) GROUP BY product_name ORDER BY quantity DESC");
    $stmt->execute($saleIds);
    $productTotals = $stmt->fetchAll();
}
$itemsSold = array_sum(array_column($productTotals, 'quantity'));
$averageSale = count($sales) ? $totalSales / count($sales) : 0;
$purchases = $pdo->query('SELECT pi.*, s.name AS supplier_name FROM purchase_invoices pi JOIN suppliers s ON s.id=pi.supplier_id ORDER BY pi.purchase_date DESC LIMIT 30')->fetchAll();
$queryParams = http_build_query(array_filter(['export' => 'sales', 'from' => $from, 'to' => $to], static fn($value): bool => $value !== ''));
page_heading('ANALISIS & EKSPOR', 'Laporan', 'Ringkasan penjualan berdasarkan transaksi dan snapshot HPP.', '<a class="button button-primary" href="?' . e($queryParams) . '">' . icon('reports', 16) . ' Ekspor CSV</a>');
?>
<form class="panel report-filters" method="get"><input type="hidden" name="page" value="reports"><span class="filter-title"><?= icon('reports', 16) ?><strong>Filter periode</strong></span><label class="field">Dari tanggal<input type="date" name="from" value="<?= e($from) ?>"></label><label class="field">Sampai tanggal<input type="date" name="to" value="<?= e($to) ?>"></label><button class="button button-secondary">Terapkan filter</button><a class="button button-secondary" href="?page=reports">Atur ulang</a></form>
<div class="metrics-grid compact-metrics">
  <article class="metric-card"><span class="metric-icon"><?= icon('pos', 18) ?></span><span class="metric-label">Total penjualan</span><strong class="metric-value"><?= rupiah($totalSales) ?></strong><span class="metric-note"><?= count($sales) ?> transaksi terpilih</span></article>
  <article class="metric-card"><span class="metric-icon green"><?= icon('hpp', 18) ?></span><span class="metric-label">Estimasi laba kotor</span><strong class="metric-value"><?= rupiah($grossProfit) ?></strong><span class="metric-note">Dari snapshot HPP transaksi</span></article>
  <article class="metric-card"><span class="metric-icon blue"><?= icon('reports', 18) ?></span><span class="metric-label">Rata-rata transaksi</span><strong class="metric-value"><?= rupiah($averageSale) ?></strong><span class="metric-note">Penjualan per transaksi</span></article>
  <article class="metric-card"><span class="metric-icon amber"><?= icon('products', 18) ?></span><span class="metric-label">Item terjual</span><strong class="metric-value"><?= e($itemsSold) ?></strong><span class="metric-note">Jumlah unit terjual</span></article>
</div>
<section class="panel"><div class="panel-heading"><div><h2>Rincian transaksi</h2><p>Histori dengan snapshot HPP pada saat transaksi</p></div><span class="result-count"><?= count($sales) ?> transaksi</span></div>
  <?php if (!$sales): ?><div class="empty-state"><span class="empty-icon"><?= icon('reports', 20) ?></span><strong>Belum ada data penjualan</strong><p>Selesaikan transaksi POS untuk mulai membuat laporan.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>NOMOR TRANSAKSI</th><th>TANGGAL</th><th>ITEM</th><th>PEMBAYARAN</th><th>TOTAL</th><th>LABA KOTOR</th></tr></thead><tbody><?php foreach ($sales as $sale): $countStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM sale_items WHERE sale_id=?'); $countStmt->execute([$sale['id']]); ?><tr><td><strong><?= e($sale['sale_number']) ?></strong></td><td><?= e(date_id($sale['created_at'], true)) ?></td><td><?= e($countStmt->fetchColumn()) ?> item</td><td><?= e($sale['payment_method']) ?></td><td><?= rupiah($sale['total']) ?></td><td><?= rupiah($sale['gross_profit']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<section class="panel section-gap"><div class="panel-heading"><div><h2>Produk terlaris</h2><p>Akumulasi unit dari transaksi sesuai filter</p></div></div>
  <?php if (!$productTotals): ?><div class="empty-state"><span class="empty-icon"><?= icon('products', 20) ?></span><strong>Belum ada produk terjual</strong><p>Produk muncul setelah transaksi diselesaikan.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>PRODUK</th><th>TERJUAL</th><th>PENJUALAN</th><th>ESTIMASI LABA KOTOR</th></tr></thead><tbody><?php foreach ($productTotals as $item): ?><tr><td><strong><?= e($item['product_name']) ?></strong></td><td><?= e($item['quantity']) ?></td><td><?= rupiah($item['total']) ?></td><td><?= rupiah($item['profit']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<section class="panel section-gap"><div class="panel-heading"><div><h2>Riwayat pembelian supplier</h2><p>Nota pembelian yang tersimpan</p></div></div>
  <?php if (!$purchases): ?><div class="empty-state"><strong>Belum ada nota pembelian</strong></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>NOMOR NOTA</th><th>SUPPLIER</th><th>TANGGAL</th><th>TOTAL</th></tr></thead><tbody><?php foreach ($purchases as $purchase): ?><tr><td><?= e($purchase['invoice_number']) ?></td><td><?= e($purchase['supplier_name']) ?></td><td><?= e(date_id($purchase['purchase_date'])) ?></td><td><?= rupiah($purchase['total']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
