<?php
$inventory = inventory_rows($pdo);
$suppliers = $pdo->query('SELECT name FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$invoices = $pdo->query(
    'SELECT pi.*, s.name AS supplier_name, COUNT(pit.id) AS line_count
     FROM purchase_invoices pi JOIN suppliers s ON s.id=pi.supplier_id
     LEFT JOIN purchase_items pit ON pit.invoice_id=pi.id
     GROUP BY pi.id ORDER BY pi.purchase_date DESC, pi.id DESC LIMIT 20'
)->fetchAll();
$details = (int) ($_GET['view'] ?? 0);
$invoiceDetail = null;
if ($details > 0) {
    $stmt = $pdo->prepare('SELECT pi.*, s.name AS supplier_name FROM purchase_invoices pi JOIN suppliers s ON s.id=pi.supplier_id WHERE pi.id=?');
    $stmt->execute([$details]);
    $invoiceDetail = $stmt->fetch() ?: null;
}
page_heading('PENGADAAN', 'Pembelian supplier', 'Catat nota pembelian untuk memperbarui stok dan HPP otomatis.');
?>
<div class="purchase-grid">
  <section class="panel">
    <div class="panel-heading"><div><h2>Nota pembelian baru</h2><p>Nota tersimpan tidak mengubah transaksi penjualan sebelumnya.</p></div><span class="panel-icon"><?= icon('purchases', 18) ?></span></div>
    <?php post_open('purchase_save', 'purchases'); ?>
      <div class="form-grid">
        <label class="field">Nama supplier<input list="suppliers" required name="supplier" placeholder="Pilih atau ketik nama supplier"><datalist id="suppliers"><?php foreach ($suppliers as $supplier): ?><option value="<?= e($supplier) ?>"><?php endforeach; ?></datalist></label>
        <label class="field">Nomor nota<input required name="invoice_number" value="INV-<?= e(date('Ymd-His')) ?>"></label>
        <label class="field">Tanggal pembelian<input required type="date" name="purchase_date" value="<?= e(date('Y-m-d')) ?>"></label>
      </div>
      <div class="section-title purchase-title">Barang yang dibeli <button type="button" class="text-button" id="addPurchaseLine"><?= icon('plus', 14) ?> Tambah baris</button></div>
      <div id="purchaseLines">
        <div class="purchase-line">
          <div class="purchase-select"><label>Barang persediaan</label><select name="inventory_id[]"><?php foreach ($inventory as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['name']) ?> (<?= e($item['unit']) ?>)</option><?php endforeach; ?></select></div>
          <div><label>Kuantitas</label><input required type="number" min="0.001" step="any" name="quantity[]" value="1"></div>
          <div><label>Harga beli / unit</label><input required type="number" min="1" step="any" name="unit_cost[]" placeholder="0"></div>
          <button type="button" class="icon-button purchase-remove" aria-label="Hapus baris">×</button>
        </div>
      </div>
      <label class="field purchase-notes">Catatan tambahan<textarea name="notes" rows="2" placeholder="Informasi tambahan pembelian..."></textarea></label>
      <div class="purchase-total"><span>Estimasi total nota</span><strong id="purchaseTotalPreview">Rp 0</strong></div>
      <p class="form-hint">Harga rata-rata persediaan dan HPP terkait dihitung ulang setelah nota disimpan.</p>
      <button class="button button-primary full-button"><?= icon('check', 15) ?> Simpan nota pembelian</button>
    </form>
  </section>
  <aside class="purchase-aside">
    <div class="panel info-panel"><span class="panel-icon"><?= icon('hpp', 18) ?></span><h3>Biaya rata-rata tertimbang</h3><p>Setiap nota memperbarui biaya rata-rata barang dan menghitung ulang HPP produk yang menggunakannya.</p><div class="formula-box">((stok lama × biaya lama) + (jumlah beli × harga beli)) ÷ stok baru</div></div>
    <div class="panel"><div class="panel-heading"><div><h2>Ringkasan pembelian</h2><p><?= count($invoices) ?> nota terakhir</p></div></div><?php if (!$invoices): ?><div class="empty-state"><strong>Belum ada nota pembelian</strong><p>Nota supplier akan tampil setelah disimpan.</p></div><?php else: foreach (array_slice($invoices, 0, 5) as $invoice): ?><div class="invoice-row"><span><strong><?= e($invoice['supplier_name']) ?></strong><small><?= e($invoice['invoice_number']) ?> · <?= e(date_id($invoice['purchase_date'])) ?></small></span><b><?= rupiah($invoice['total']) ?></b></div><?php endforeach; endif; ?></div>
  </aside>
</div>
<section class="panel section-gap"><div class="panel-heading"><div><h2>Riwayat nota supplier</h2><p>Nota pembelian yang telah dicatat</p></div></div>
  <?php if (!$invoices): ?><div class="empty-state"><span class="empty-icon"><?= icon('purchases', 20) ?></span><strong>Belum ada riwayat pembelian</strong><p>Nota yang disimpan akan muncul pada tabel ini.</p></div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>NOMOR NOTA</th><th>SUPPLIER</th><th>TANGGAL</th><th>BARIS BARANG</th><th>TOTAL</th><th></th></tr></thead><tbody><?php foreach ($invoices as $invoice): ?><tr><td><strong><?= e($invoice['invoice_number']) ?></strong></td><td><?= e($invoice['supplier_name']) ?></td><td><?= e(date_id($invoice['purchase_date'])) ?></td><td><?= (int) $invoice['line_count'] ?> baris</td><td><?= rupiah($invoice['total']) ?></td><td><a class="text-button" href="?page=purchases&view=<?= (int) $invoice['id'] ?>">Lihat rincian</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php if ($invoiceDetail): $stmt = $pdo->prepare('SELECT pi.*, i.name AS item_name, i.unit FROM purchase_items pi JOIN inventory_items i ON i.id=pi.inventory_id WHERE pi.invoice_id=?'); $stmt->execute([$details]); $detailLines = $stmt->fetchAll(); ?>
<div class="modal-backdrop"><section class="modal"><div class="modal-header"><div><h2>Nota <?= e($invoiceDetail['invoice_number']) ?></h2><p><?= e($invoiceDetail['supplier_name']) ?> · <?= e(date_id($invoiceDetail['purchase_date'])) ?></p></div><a class="icon-button" href="?page=purchases">×</a></div><div class="modal-body"><div class="table-wrap"><table><thead><tr><th>BARANG</th><th>JUMLAH</th><th>HARGA / UNIT</th><th>SUBTOTAL</th></tr></thead><tbody><?php foreach ($detailLines as $line): ?><tr><td><?= e($line['item_name']) ?></td><td><?= e($line['quantity']) ?> <?= e($line['unit']) ?></td><td><?= rupiah($line['unit_cost']) ?></td><td><?= rupiah($line['line_total']) ?></td></tr><?php endforeach; ?></tbody></table></div><div class="purchase-total"><span>Total nota</span><strong><?= rupiah($invoiceDetail['total']) ?></strong></div><p><?= e($invoiceDetail['notes'] ?? '') ?></p></div></section></div>
<?php endif; ?>
