<?php
$settings = [
    'business_name' => (string) setting('business_name', 'Racik Coffee & Mart'),
    'margin_alert' => (float) setting('margin_alert', 35),
    'price_rounding' => (int) setting('price_rounding', 500),
    'notifications' => (string) setting('notifications', '1'),
];
page_heading('PREFERENSI', 'Pengaturan', 'Atur identitas usaha, batas margin, dan rekomendasi harga.');
?>
<div class="settings-layout">
  <section class="panel settings-panel"><div class="panel-heading"><div><h2>Pengaturan bisnis</h2><p>Preferensi untuk ringkasan dan peringatan aplikasi</p></div><span class="panel-icon"><?= icon('settings', 18) ?></span></div>
    <?php post_open('settings_save', 'settings'); ?>
      <label class="field settings-business">Nama usaha<input required name="business_name" value="<?= e($settings['business_name']) ?>"></label>
      <div class="form-grid">
        <label class="field">Batas peringatan margin (%)<input required type="number" min="1" max="99" step="0.1" name="margin_alert" value="<?= e($settings['margin_alert']) ?>"><small>Produk di bawah batas ini akan ditampilkan sebagai peringatan.</small></label>
        <label class="field">Pembulatan rekomendasi harga<select name="price_rounding"><?php foreach ([500, 1000, 5000] as $rounding): ?><option value="<?= $rounding ?>" <?= $settings['price_rounding'] === $rounding ? 'selected' : '' ?>><?= rupiah($rounding) ?></option><?php endforeach; ?></select><small>Rekomendasi dibulatkan ke atas ke kelipatan tersebut.</small></label>
      </div>
      <label class="checkbox-row"><input type="checkbox" name="notifications" value="1" <?= $settings['notifications'] === '1' ? 'checked' : '' ?>> Tampilkan notifikasi dalam aplikasi</label>
      <div class="modal-actions settings-actions"><button class="button button-primary"><?= icon('check', 15) ?> Simpan pengaturan</button></div>
    </form>
  </section>
  <aside class="settings-aside">
    <section class="panel info-panel"><span class="panel-icon"><?= icon('inventory', 18) ?></span><h3>Database aplikasi</h3><p>Data operasional tersimpan pada MySQL/MariaDB lokal melalui koneksi PHP PDO.</p><span class="data-chip"><i class="live-dot"></i>MySQL aktif</span></section>
    <section class="panel reset-panel"><span class="reset-icon"><?= icon('alerts', 18) ?></span><h3>Reset data demo</h3><p>Kembalikan katalog, stok, transaksi, dan pengaturan ke data awal.</p><?php post_open('demo_reset', 'settings'); ?><button class="button button-danger" data-confirm="Reset seluruh data demo? Semua data saat ini akan dihapus."><?= icon('products', 15) ?> Reset seluruh data</button></form></section>
  </aside>
</div>
