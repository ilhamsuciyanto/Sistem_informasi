<?php
$warnings = margin_products($pdo);
$priceHistory = $pdo->query('SELECT * FROM price_history ORDER BY created_at DESC LIMIT 50')->fetchAll();
page_heading('TINDAKAN BISNIS', 'Peringatan margin', 'Tinjau risiko margin dan rekomendasi harga sebelum menyetujui perubahan.');
?>
<div class="alert-summary"><span class="alert-summary-icon"><?= icon('alerts', 20) ?></span><span><strong><?= count($warnings) ?> produk perlu ditinjau</strong><small>Rekomendasi tidak mengubah harga jual secara otomatis.</small></span></div>
<?php if (!$warnings): ?><section class="panel"><div class="empty-state"><span class="empty-icon"><?= icon('check', 20) ?></span><strong>Tidak ada peringatan aktif</strong><p>Margin produk aktif berada di atas batas yang ditentukan.</p></div></section><?php else: ?>
<div class="alert-grid">
<?php foreach ($warnings as $product): ?>
  <article class="panel alert-card">
    <div class="alert-card-top"><div class="product-cell"><span class="tiny-product"><?= icon('products', 15) ?></span><span><strong><?= e($product['name']) ?></strong><small><?= e($product['sku']) ?></small></span></div><span class="status-badge <?= $product['recommendation_status'] === 'applied' ? 'good' : ($product['margin'] < (float) setting('margin_alert', 35) ? 'warning' : 'neutral') ?>"><?= $product['recommendation_status'] === 'applied' ? 'Diperbarui' : ($product['margin'] < (float) setting('margin_alert', 35) ? 'Margin rendah' : 'HPP naik') ?></span></div>
    <p class="alert-reason"><?= $product['margin'] < (float) setting('margin_alert', 35) ? 'Margin ' . number_format($product['margin'], 1, ',', '.') . '% berada di bawah batas ' . e(setting('margin_alert', 35)) . '%.' : 'HPP produk mengalami kenaikan setelah pembelian terakhir.' ?><?= $product['cost_increased'] && $product['margin'] < (float) setting('margin_alert', 35) ? ' HPP juga meningkat.' : '' ?></p>
    <div class="price-comparison"><span><small>HPP saat ini</small><strong><?= rupiah($product['hpp']) ?></strong></span><span><small>Harga jual</small><strong><?= rupiah($product['selling_price']) ?></strong></span><span><small>Margin</small><strong class="<?= $product['margin'] < (float) setting('margin_alert', 35) ? 'text-danger' : '' ?>"><?= number_format($product['margin'], 1, ',', '.') ?>%</strong></span></div>
    <div class="recommendation-box"><span><small>Rekomendasi · target <?= e($product['target_margin']) ?>%</small><strong><?= rupiah($product['suggested_price']) ?></strong></span><small class="recommendation-note">Dibulatkan ke kelipatan <?= rupiah(setting('price_rounding', 500)) ?></small></div>
    <?php if ($product['recommendation_status'] === 'applied'): ?><div class="applied-note"><?= icon('check', 15) ?> Harga rekomendasi telah diberlakukan.</div>
    <?php elseif ($product['recommendation_status'] === 'accepted'): ?><?php post_open('recommendation_apply', 'alerts'); ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><button class="button button-primary full-button">Terapkan harga yang disetujui</button></form>
    <?php else: ?><div class="alert-actions"><?php post_open('recommendation_review', 'alerts'); ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input type="hidden" name="status" value="rejected"><button class="button button-secondary">Tolak</button></form><?php post_open('recommendation_review', 'alerts'); ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input type="hidden" name="status" value="accepted"><button class="button button-primary">Setujui rekomendasi</button></form></div><?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<section class="panel section-gap"><div class="panel-heading"><div><h2>Riwayat perubahan harga</h2><p>Harga hanya berubah setelah persetujuan</p></div></div>
<?php if (!$priceHistory): ?><div class="empty-state"><span class="empty-icon"><?= icon('clock', 20) ?></span><strong>Belum ada perubahan harga</strong><p>Harga yang disetujui akan tersimpan di sini.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>TANGGAL</th><th>PRODUK</th><th>HARGA LAMA</th><th>HARGA BARU</th><th>TINDAKAN</th></tr></thead><tbody><?php foreach ($priceHistory as $history): ?><tr><td><?= e(date_id($history['created_at'], true)) ?></td><td><?= e($history['product_name']) ?></td><td><?= rupiah($history['old_price']) ?></td><td><?= rupiah($history['new_price']) ?></td><td><?= e($history['action_by']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
