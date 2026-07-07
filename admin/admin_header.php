<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();
$pageTitle = $pageTitle ?? 'Admin';
$user = current_user();
$currentAdminPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111413">
    <title><?= e($pageTitle) ?> | Admin ShoeShoeGaze</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('public/css/style.css')) ?>">
</head>
<body class="admin-body">
<header class="admin-topbar">
    <div class="container nav-wrap">
        <a class="brand" href="<?= e(url('admin/dashboard.php')) ?>">
            <span class="brand-mark">SG</span>
            <span class="brand-copy"><span>ShoeShoeGaze</span><small>Admin Console</small></span>
        </a>
        <div class="admin-user">
            <span><?= e($user['name']) ?></span>
            <a href="<?= e(url()) ?>">Lihat Toko</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('pages/logout.php')) ?>">Logout</a>
        </div>
    </div>
</header>
<div class="admin-layout container">
    <aside class="admin-sidebar">
        <a class="<?= $currentAdminPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= e(url('admin/dashboard.php')) ?>"><span class="sidebar-icon">DB</span><span>Dashboard</span></a>
        <a class="<?= $currentAdminPage === 'categories.php' ? 'active' : '' ?>" href="<?= e(url('admin/categories.php')) ?>"><span class="sidebar-icon">KT</span><span>Kategori</span></a>
        <a class="<?= $currentAdminPage === 'products.php' ? 'active' : '' ?>" href="<?= e(url('admin/products.php')) ?>"><span class="sidebar-icon">PR</span><span>Produk</span></a>
        <a class="<?= $currentAdminPage === 'users.php' ? 'active' : '' ?>" href="<?= e(url('admin/users.php')) ?>"><span class="sidebar-icon">US</span><span>Pengguna</span></a>
        <a class="<?= in_array($currentAdminPage, ['orders.php', 'order_detail.php'], true) ? 'active' : '' ?>" href="<?= e(url('admin/orders.php')) ?>"><span class="sidebar-icon">OR</span><span>Pesanan</span></a>
    </aside>
    <section class="admin-content">
        <?php foreach (consume_flashes() as $notice): ?>
            <div class="flash flash-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div>
        <?php endforeach; ?>
