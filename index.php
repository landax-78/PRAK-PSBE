<?php
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Beranda';

/*
|--------------------------------------------------------------------------
| Produk Unggulan
|--------------------------------------------------------------------------
| Hanya menampilkan produk aktif.
*/
$featured = db()->query("
    SELECT
        p.*,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON c.id = p.category_id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 8
");

/*
|--------------------------------------------------------------------------
| Daftar Kategori
|--------------------------------------------------------------------------
| Menghitung jumlah produk aktif di setiap kategori.
*/
$categories = db()->query("
    SELECT
        c.id,
        c.name,
        c.description,
        c.image_path,
        c.created_at,
        c.updated_at,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.is_active = 1
    GROUP BY
        c.id,
        c.name,
        c.description,
        c.image_path,
        c.created_at,
        c.updated_at
    ORDER BY c.name ASC
");

require __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero">
    <div class="container hero-grid">

        <div class="hero-copy">
            <span class="eyebrow">New Season Collection</span>

            <h1>
                Sepatu tepat untuk
                <span>setiap langkah.</span>
            </h1>

            <p>
                Koleksi pilihan dengan desain modern, nyaman dipakai,
                dan mudah dipadukan untuk aktivitas harian.
            </p>

            <div class="hero-actions">
                <a
                    class="btn btn-primary"
                    href="<?= e(url('pages/products.php')) ?>"
                >
                    Belanja Koleksi
                </a>

                <?php if (!is_logged_in()): ?>
                    <a
                        class="btn btn-outline"
                        href="<?= e(url('pages/register.php')) ?>"
                    >
                        Buat Akun
                    </a>
                <?php else: ?>
                    <a
                        class="btn btn-outline"
                        href="<?= e(url('pages/orders.php')) ?>"
                    >
                        Cek Pesanan
                    </a>
                <?php endif; ?>
            </div>

            <div class="hero-meta">
                <div class="hero-meta-item">
                    <strong>100%</strong>
                    <span>Produk terpilih</span>
                </div>

                <div class="hero-meta-item">
                    <strong>7 Hari</strong>
                    <span>Retur mudah</span>
                </div>

                <div class="hero-meta-item">
                    <strong>COD</strong>
                    <span>Pembayaran fleksibel</span>
                </div>
            </div>
        </div>

        <div class="hero-art">
            <img
                src="<?= e(url('public/images/hero-shoe.png')) ?>"
                alt="Koleksi sepatu ShoeShoeGaze"
                onerror="this.onerror=null; this.src='<?= e(url('public/images/product-placeholder.svg')) ?>';"
            >

            <div class="hero-floating-card">
                <strong>Koleksi Terbaru</strong>
                <span>Model baru untuk gaya kasual dan aktif.</span>
            </div>
        </div>

    </div>
</section>

<!-- Layanan -->
<section class="service-strip">
    <div class="container service-grid">

        <div class="service-item">
            <div class="service-image-wrapper">
                <img
                    class="service-image"
                    src="<?= e(url('public/images/services/product-selected.png')) ?>"
                    alt="Produk terpilih"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='<?= e(url('public/images/product-placeholder.svg')) ?>';"
                >
            </div>

            <div class="service-content">
                <strong>Produk Terpilih</strong>
                <span>Koleksi yang dikurasi</span>
            </div>
        </div>

        <div class="service-item">
            <div class="service-image-wrapper">
                <img
                    class="service-image"
                    src="<?= e(url('public/images/services/fast-shipping.png')) ?>"
                    alt="Pengiriman cepat"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='<?= e(url('public/images/product-placeholder.svg')) ?>';"
                >
            </div>

            <div class="service-content">
                <strong>Pengiriman Cepat</strong>
                <span>Proses pesanan efisien</span>
            </div>
        </div>

        <div class="service-item">
            <div class="service-image-wrapper">
                <img
                    class="service-image"
                    src="<?= e(url('public/images/services/secure-payment.png')) ?>"
                    alt="Pembayaran aman"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='<?= e(url('public/images/product-placeholder.svg')) ?>';"
                >
            </div>

            <div class="service-content">
                <strong>Pembayaran Aman</strong>
                <span>Transfer bank atau COD</span>
            </div>
        </div>

        <div class="service-item">
            <div class="service-image-wrapper">
                <img
                    class="service-image"
                    src="<?= e(url('public/images/services/easy-return.png')) ?>"
                    alt="Retur mudah"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='<?= e(url('public/images/product-placeholder.svg')) ?>';"
                >
            </div>

            <div class="service-content">
                <strong>Retur Mudah</strong>
                <span>Dukungan selama 7 hari</span>
            </div>
        </div>

    </div>
</section>

<!-- Kategori -->
<section class="section container">
    <div class="section-head">
        <div>
            <span class="eyebrow">Shop by Category</span>
            <h2>Pilih berdasarkan kebutuhan</h2>

            <p class="section-subtitle">
                Temukan model yang sesuai untuk gaya kasual, olahraga,
                acara formal, dan aktivitas santai.
            </p>
        </div>
    </div>

    <div class="category-grid">

        <?php if ($categories && $categories->num_rows > 0): ?>

            <?php while ($category = $categories->fetch_assoc()): ?>

                <?php
                $categoryImage = !empty($category['image_path'])
                    ? $category['image_path']
                    : 'public/images/category-placeholder.jpg';
                ?>

                <a
                    class="category-card"
                    href="<?= e(url('pages/products.php?category=' . $category['id'])) ?>"
                >
                    <div class="category-image-wrapper">
                        <img
                            class="category-image"
                            src="<?= e(url($categoryImage)) ?>"
                            alt="Kategori <?= e($category['name']) ?>"
                            loading="lazy"
                            onerror="this.onerror=null; this.src='<?= e(url('public/images/product-placeholder.svg')) ?>';"
                        >
                    </div>

                    <div class="category-content">
                        <div class="category-info">
                            <span class="category-count">
                                <?= (int) $category['product_count'] ?> produk
                            </span>

                            <strong class="category-name">
                                <?= e($category['name']) ?>
                            </strong>
                        </div>

                        <span class="category-arrow">↗</span>
                    </div>
                </a>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-state">
                <p>Belum ada kategori yang tersedia.</p>
            </div>

        <?php endif; ?>

    </div>
</section>

<!-- Produk Unggulan -->
<section class="section section-muted">
    <div class="container">

        <div class="section-head">
            <div>
                <span class="eyebrow">Featured Products</span>
                <h2>Koleksi unggulan</h2>

                <p class="section-subtitle">
                    Pilihan terbaru dengan desain yang mudah dipakai
                    untuk berbagai aktivitas.
                </p>
            </div>

            <a href="<?= e(url('pages/products.php')) ?>">
                Lihat semua
            </a>
        </div>

        <div class="product-grid">

            <?php if ($featured && $featured->num_rows > 0): ?>

                <?php while ($product = $featured->fetch_assoc()): ?>

                    <?php
                    $productImage = !empty($product['image_path'])
                        ? $product['image_path']
                        : 'public/images/product-placeholder.svg';
                    ?>

                    <article class="product-card">

                        <span class="product-badge">New</span>

                        <a
                            href="<?= e(url('pages/product_detail.php?id=' . $product['id'])) ?>"
                            class="product-image"
                        >
                            <img
                                src="<?= e(url($productImage)) ?>"
                                alt="<?= e($product['name']) ?>"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='<?= e(url('public/images/product-placeholder.svg')) ?>';"
                            >
                        </a>

                        <div class="product-body">
                            <span class="product-category">
                                <?= e($product['category_name'] ?? 'Tanpa Kategori') ?>
                            </span>

                            <h3>
                                <a
                                    href="<?= e(url('pages/product_detail.php?id=' . $product['id'])) ?>"
                                >
                                    <?= e($product['name']) ?>
                                </a>
                            </h3>

                            <div class="product-meta">
                                <strong>
                                    <?= rupiah($product['price']) ?>
                                </strong>

                                <span>
                                    <?= (int) $product['stock'] ?> tersedia
                                </span>
                            </div>
                        </div>

                    </article>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-state">
                    <p>Belum ada produk aktif yang tersedia.</p>
                </div>

            <?php endif; ?>

        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
