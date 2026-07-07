<?php
require_once __DIR__ . '/config.php';

$pageTitle = $pageTitle ?? 'ShoeShoeGaze';
$user = current_user();
$cartCount = 0;

/*
|--------------------------------------------------------------------------
| Hitung jumlah produk di keranjang pelanggan
|--------------------------------------------------------------------------
*/
if ($user && ($user['role'] ?? '') === 'customer') {
    $cartStmt = db()->prepare("
        SELECT COALESCE(SUM(quantity), 0)
        FROM cart_items
        WHERE user_id = ?
    ");

    $cartStmt->bind_param('i', $user['id']);
    $cartStmt->execute();
    $cartStmt->bind_result($cartCount);
    $cartStmt->fetch();
    $cartStmt->close();
}

/*
|--------------------------------------------------------------------------
| Penanda navigasi aktif
|--------------------------------------------------------------------------
*/
$currentPath = str_replace(
    '\\',
    '/',
    (string) ($_SERVER['SCRIPT_NAME'] ?? '')
);

$isHome =
    str_ends_with($currentPath, '/index.php')
    || str_ends_with($currentPath, BASE_PATH . '/');

$isProducts =
    str_contains($currentPath, '/pages/products.php')
    || str_contains($currentPath, '/pages/product_detail.php');

$isCart =
    str_contains($currentPath, '/pages/cart.php')
    || str_contains($currentPath, '/pages/checkout.php');

$isOrders =
    str_contains($currentPath, '/pages/orders.php')
    || str_contains($currentPath, '/pages/order_detail.php');

$isAdmin = str_contains($currentPath, '/admin/');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111413">
    <meta
        name="description"
        content="ShoeShoeGaze, toko sepatu online untuk sneakers, running, formal, dan sandal."
    >

    <title><?= e($pageTitle) ?> | ShoeShoeGaze</title>

    <!-- Logo pada tab browser -->
    <link
        rel="icon"
        type="image/svg+xml"
        href="<?= e(url('public/images/logo.png')) ?>"
    >
    <link
        rel="apple-touch-icon"
        href="<?= e(url('public/images/logo.png')) ?>"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= e(url('public/css/style.css')) ?>?v=6"
    >
</head>
<body>

<!-- Header berada di posisi paling atas -->
<header class="site-header">
    <div class="container nav-wrap">

        <a
            class="brand"
            href="<?= e(url()) ?>"
            aria-label="ShoeShoeGaze Beranda"
        >
            <span class="brand-mark brand-mark-logo">
                <img
                    class="brand-logo"
                    src="<?= e(url('public/images/logo.png')) ?>"
                    alt="Logo ShoeShoeGaze"
                    width="42"
                    height="42"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                >
                <span class="brand-logo-fallback" aria-hidden="true">SG</span>
            </span>

            <span class="brand-copy">
                <span>ShoeShoeGaze</span>
                <small>Footwear Store</small>
            </span>
        </a>

        <button
            class="nav-toggle"
            type="button"
            aria-label="Buka navigasi"
            aria-expanded="false"
            aria-controls="main-navigation"
        >
            ☰
        </button>

        <nav
            class="main-nav"
            id="main-navigation"
            aria-label="Navigasi utama"
        >
            <a
                class="<?= $isHome ? 'active' : '' ?>"
                href="<?= e(url()) ?>"
            >
                Beranda
            </a>

            <a
                class="<?= $isProducts ? 'active' : '' ?>"
                href="<?= e(url('pages/products.php')) ?>"
            >
                Koleksi
            </a>

            <?php if ($user && ($user['role'] ?? '') === 'customer'): ?>
                <a
                    class="<?= $isCart ? 'active' : '' ?>"
                    href="<?= e(url('pages/cart.php')) ?>"
                >
                    Keranjang
                    <span class="badge"><?= (int) $cartCount ?></span>
                </a>

                <a
                    class="<?= $isOrders ? 'active' : '' ?>"
                    href="<?= e(url('pages/orders.php')) ?>"
                >
                    Pesanan
                </a>
            <?php endif; ?>

            <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
                <a
                    class="<?= $isAdmin ? 'active' : '' ?>"
                    href="<?= e(url('admin/dashboard.php')) ?>"
                >
                    Dashboard Admin
                </a>
            <?php endif; ?>

            <?php if ($user): ?>
                <span
                    class="nav-user"
                    title="<?= e($user['name']) ?>"
                >
                    <?= e($user['name']) ?>
                </span>

                <a
                    class="btn btn-outline btn-sm"
                    href="<?= e(url('pages/logout.php')) ?>"
                >
                    Logout
                </a>
            <?php else: ?>
                <a href="<?= e(url('pages/login.php')) ?>">
                    Login
                </a>

                <a
                    class="btn btn-primary btn-sm"
                    href="<?= e(url('pages/register.php')) ?>"
                >
                    Buat Akun
                </a>
            <?php endif; ?>
        </nav>

    </div>
</header>

<!-- Announcement berada tepat di bawah header -->
<div class="announcement-bar">
    <div class="container announcement-inner">
        <span class="announcement-dot" aria-hidden="true"></span>
        <span>
            Gratis ongkir untuk pembelian tertentu dan retur mudah selama 7 hari
        </span>
    </div>
</div>

<main>
<?php foreach (consume_flashes() as $notice): ?>
    <div class="flash flash-<?= e($notice['type']) ?>">
        <?= e($notice['message']) ?>
    </div>
<?php endforeach; ?>
