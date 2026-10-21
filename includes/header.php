<?php
$activePage = $page ?? 'dashboard';
$businessName = (string) setting('business_name', 'Racik Coffee & Mart');
$menu = [
    'dashboard' => ['Dashboard', 'dashboard'],
    'pos' => ['Kasir POS', 'pos'],
    'products' => ['Produk & menu', 'products'],
    'inventory' => ['Persediaan', 'inventory'],
    'purchases' => ['Pembelian supplier', 'purchases'],
    'hpp' => ['Dynamic HPP', 'hpp'],
    'alerts' => ['Peringatan margin', 'alerts'],
    'reports' => ['Laporan', 'reports'],
    'settings' => ['Pengaturan', 'settings'],
];
$marginWarnings = count(margin_products($pdo));
$flashMessage = consume_flash();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f7f8fa">
  <title><?= e($menu[$activePage][0] ?? 'Dashboard') ?> · Racik</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="?page=dashboard"><span class="brand-mark">R</span><span class="brand-name">racik<small>BUSINESS SUITE</small></span></a>
    <div class="workspace"><span class="workspace-icon">R</span><span><strong><?= e($businessName) ?></strong><small>Workspace demo</small></span><span class="workspace-chevron">⌄</span></div>
    <div class="nav-caption">MENU UTAMA</div>
    <nav class="side-nav" aria-label="Navigasi utama">
      <?php foreach ($menu as $key => [$label, $symbol]): ?>
        <a class="nav-link <?= $activePage === $key ? 'active' : '' ?>" href="?page=<?= e($key) ?>">
          <?= icon($symbol, 18) ?><span><?= e($label) ?></span>
          <?php if ($key === 'alerts' && $marginWarnings > 0): ?><span class="nav-count"><?= $marginWarnings ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
      <div class="help-card"><span class="help-mark">?</span><strong>Butuh bantuan?</strong><p>Gunakan setup dan panduan di README aplikasi.</p><a href="README.md">Buka panduan <?= icon('arrow', 14) ?></a></div>
      <div class="user-profile"><span class="avatar">AD</span><span><strong>Admin Demo</strong><small>Administrator</small></span></div>
    </div>
  </aside>
  <div class="mobile-scrim" id="mobileScrim"></div>
  <main class="main-area">
    <header class="topbar">
      <div class="topbar-left"><button class="mobile-menu" id="mobileMenu" aria-label="Buka navigasi">☰</button><div class="breadcrumbs"><span>Workspace</span><span class="crumb-divider">/</span><strong><?= e($menu[$activePage][0] ?? 'Dashboard') ?></strong></div></div>
      <div class="topbar-right"><span class="saved-status"><i></i>Database MySQL aktif</span><span class="topbar-date"><?= e(date_id(date('Y-m-d'))) ?></span><a class="icon-button notification-button" href="?page=alerts" aria-label="Peringatan"><?= icon('bell', 18) ?><?php if ($marginWarnings && setting('notifications', '1') === '1'): ?><i></i><?php endif; ?></a></div>
    </header>
    <section class="page-content">
      <?php if ($flashMessage): ?><div class="flash <?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?><button type="button" class="flash-close" aria-label="Tutup">×</button></div><?php endif; ?>
