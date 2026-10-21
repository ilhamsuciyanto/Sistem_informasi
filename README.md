# Racik — Mart POS & Dynamic HPP

A PHP-native, local web application for retail and F&B point of sale, inventory, supplier purchases, weighted-average costing, and margin recommendations.

## Menjalankan di Laragon

1. Letakkan folder ini di `D:\Data\laragon\www\sistem_informasi` (folder `www` Laragon).
2. Buka Laragon dan jalankan **Apache** serta **MySQL**.
3. Buka `http://localhost/sistem_informasi/setup.php`.
4. Tekan **Buat database dan data demo**. Setup membuat database `racik_pos`, tabel, dan data contoh tanpa menghapus data yang sudah ada.
5. Buka `http://localhost/sistem_informasi/`.

Konfigurasi koneksi tersedia di `config/database.php`. Default Laragon yang dipakai adalah MySQL pada `127.0.0.1:3306`, user `root`, password kosong. Ubah nilai tersebut jika instalasi lokal Anda berbeda.

Alternatif: buat database `racik_pos` melalui phpMyAdmin, impor `database/schema.sql`, lalu kunjungi `setup.php` untuk mengisi seed data.

## Struktur folder

```text
actions/
  handle_post.php       Validasi form, transaksi POS, pembelian, harga, dan pengaturan
assets/
  css/app.css           Desain responsif, sistem warna, dan tipografi SF Pro-first
  js/app.js             Navigasi mobile dan baris dinamis untuk resep/pembelian
config/
  database.php          Koneksi PDO MySQL/MariaDB
database/
  schema.sql            Struktur tabel dan relasi
includes/
  functions.php         Format, helper HPP, stok, CSRF, dan komponen layout
  header.php
  footer.php
pages/
  dashboard.php
  pos.php
  products.php
  inventory.php
  purchases.php
  hpp.php
  alerts.php
  reports.php
  settings.php
index.php               Router halaman dan ekspor CSV
setup.php               Setup database dan seed demo
```

## Alur yang tersedia

- Dashboard menghitung penjualan, laba kotor, stok minimum, margin, grafik, dan aktivitas dari database. Membuka halaman tidak menambahkan transaksi.
- Produk dan persediaan dapat ditambah atau diubah. Produk yang sudah muncul pada transaksi tetap disimpan untuk menjaga laporan historis.
- POS menerima pembayaran tunai, QRIS, dan debit; memvalidasi stok dan uang tunai; mengurangi bahan resep/produk ritel di dalam transaksi database; serta menyimpan snapshot HPP.
- Nota pembelian menghitung biaya rata-rata tertimbang, menambah stok, mencatat pergerakan dan memperbarui riwayat HPP.
- Setup menambahkan garam halus (500 g, estimasi Rp5.000), lada bubuk (50 g, estimasi Rp12.000), dan kaldu bubuk (100 g, estimasi Rp10.000) sebagai stok dalam gram. Biaya satuan demo dihitung dari harga kemasan tersebut, bukan harga nasional tetap; sesuaikan dengan merek dan harga supplier/usaha setempat.
- Setup juga menambahkan bawang bombay sebagai stok 10 pcs, dengan estimasi biaya demo Rp5.000 per pcs. Harga aktual berbeda menurut ukuran dan lokasi; sesuaikan berdasarkan pembelian usaha.
- Rekomendasi harga dibulatkan menurut pengaturan dan memerlukan persetujuan sebelum harga produk diubah.
- Laporan dapat difilter tanggal dan diekspor ke CSV.
- Gunakan menu **Pengaturan → Reset seluruh data** untuk mengembalikan seed demo (memerlukan konfirmasi).

Tipografi menggunakan SF Pro sebagai prioritas (`SF Pro Display`, `SF Pro Text`) dan fallback font sistem. SF Pro tidak disalin/dibundel; pada komputer yang tidak memiliki font tersebut, browser memakai font sistem yang tersedia.

## Pemeriksaan

- Validasi sintaks semua file PHP: jalankan `php -l` pada file PHP di folder ini.
- Uji setup di Laragon, semua tautan sidebar, CRUD produk/persediaan, nota pembelian, checkout POS, laporan dan ekspor CSV.
- Uji stok tidak cukup, pembayaran tunai kurang, dan harga rekomendasi yang belum disetujui.
- Muat ulang browser untuk memastikan data tersimpan di MySQL, bukan hanya di sesi browser.

## Batasan demo

Aplikasi ini ditujukan untuk demo lokal, belum untuk penggunaan operasional langsung. Belum tersedia login/otorisasi pengguna, audit trail admin, pembatalan nota/transaksi, API server, sinkronisasi banyak kasir, pajak, integrasi akuntansi, backup otomatis, maupun notifikasi push. Untuk operasional sungguhan, tambahkan autentikasi dan hak akses, backup, log audit, aturan pembatalan yang konsisten, validasi satuan, dan uji beban sesuai kebutuhan.
