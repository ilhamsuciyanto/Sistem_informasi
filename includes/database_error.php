<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Koneksi database · Racik</title><link rel="stylesheet" href="assets/css/app.css"></head>
<body class="setup-body"><main class="setup-card"><span class="eyebrow">KONEKSI DATABASE</span><h1>Database belum siap</h1><p>Pastikan MySQL/MariaDB Laragon aktif. Jika database belum dibuat, jalankan setup terlebih dahulu.</p><div class="notice error"><strong>Pesan koneksi:</strong> <?= e($databaseError) ?></div><p><a class="button button-primary full-button" href="setup.php">Buka setup database</a></p><p class="setup-footnote">Pengaturan host, nama database, dan user dapat disesuaikan di <code>config/database.php</code>.</p></main></body></html>
