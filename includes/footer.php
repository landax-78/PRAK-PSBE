<?php
$footerUser = $user ?? current_user();
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">

        <div class="footer-brand">
            <a
                class="brand footer-brand-logo"
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
                        loading="lazy"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                    >
                    <span class="brand-logo-fallback" aria-hidden="true">SG</span>
                </span>

                <span class="brand-copy">
                    <span>ShoeShoeGaze</span>
                    <small>Footwear Store</small>
                </span>
            </a>

            <p class="footer-description">
                Toko sepatu online dengan pilihan sneakers, running,
                formal, dan sandal untuk aktivitas harian.
            </p>
        </div>

        <div class="footer-links">
            <strong>Koleksi</strong>
            <a href="<?= e(url('pages/products.php')) ?>">Semua Produk</a>
            <a href="<?= e(url('pages/products.php?category=1')) ?>">Sneakers</a>
            <a href="<?= e(url('pages/products.php?category=2')) ?>">Running</a>
            <a href="<?= e(url('pages/products.php?category=3')) ?>">Formal</a>
        </div>

        <div class="footer-links">
            <strong>Akun</strong>

            <?php if ($footerUser && ($footerUser['role'] ?? '') === 'customer'): ?>
                <a href="<?= e(url('pages/orders.php')) ?>">Pesanan Saya</a>
                <a href="<?= e(url('pages/cart.php')) ?>">Keranjang</a>
                <a href="<?= e(url('pages/logout.php')) ?>">Logout</a>

            <?php elseif ($footerUser && ($footerUser['role'] ?? '') === 'admin'): ?>
                <a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard Admin</a>
                <a href="<?= e(url('admin/products.php')) ?>">Kelola Produk</a>
                <a href="<?= e(url('pages/logout.php')) ?>">Logout</a>

            <?php else: ?>
                <a href="<?= e(url('pages/login.php')) ?>">Login</a>
                <a href="<?= e(url('pages/register.php')) ?>">Daftar</a>
            <?php endif; ?>
        </div>

        <div class="footer-links">
            <strong>Layanan</strong>
            <span>Pengiriman ke seluruh Indonesia</span>
            <span>Pembayaran transfer dan COD</span>
            <span>Dukungan pelanggan setiap hari</span>
        </div>

    </div>

    <div class="container footer-bottom">
        <span>
            © <?= date('Y') ?> ShoeShoeGaze. PBL Pemrograman Sisi Back-End.
        </span>
        <span>PHP 8 · MySQL · HTML · CSS · JavaScript</span>
    </div>
</footer>

<script src="<?= e(url('public/js/main.js')) ?>"></script>
</body>
</html>
