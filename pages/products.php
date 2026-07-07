<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Produk';
$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);
$categories = db()->query('SELECT id,name FROM categories ORDER BY name');

if ($search !== '' && $categoryId > 0) {
    $like = '%' . $search . '%';
    $productsStmt = db()->prepare('SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND (p.name LIKE ? OR p.description LIKE ?) AND p.category_id=? ORDER BY p.created_at DESC');
    $productsStmt->bind_param('ssi', $like, $like, $categoryId);
} elseif ($search !== '') {
    $like = '%' . $search . '%';
    $productsStmt = db()->prepare('SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND (p.name LIKE ? OR p.description LIKE ?) ORDER BY p.created_at DESC');
    $productsStmt->bind_param('ss', $like, $like);
} elseif ($categoryId > 0) {
    $productsStmt = db()->prepare('SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND p.category_id=? ORDER BY p.created_at DESC');
    $productsStmt->bind_param('i', $categoryId);
} else {
    $productsStmt = db()->prepare('SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 ORDER BY p.created_at DESC');
}
$productsStmt->execute();
$products = $productsStmt->get_result();
$productsStmt->close();
$productCount = $products->num_rows;
require __DIR__ . '/../includes/header.php';
?>
<section class="catalog-hero">
    <div class="container">
        <span class="eyebrow">Shoe Collection</span>
        <h1>Temukan pasangan yang tepat</h1>
        <div class="catalog-meta">
            <span><?= (int) $productCount ?> produk tersedia</span>
            <?php if ($search !== ''): ?><span>Hasil pencarian: “<?= e($search) ?>”</span><?php endif; ?>
        </div>
    </div>
</section>
<section class="section container">
    <form class="filter-bar" method="get">
        <input type="search" name="q" placeholder="Cari nama atau jenis sepatu..." value="<?= e($search) ?>" aria-label="Cari produk">
        <select name="category" aria-label="Pilih kategori">
            <option value="0">Semua kategori</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-primary" type="submit">Cari Produk</button>
        <a class="btn btn-outline" href="<?= e(url('pages/products.php')) ?>">Reset</a>
    </form>

    <?php if ($products->num_rows === 0): ?>
        <div class="empty-state">
            <h3>Produk tidak ditemukan</h3>
            <p>Coba gunakan kata kunci lain atau pilih kategori yang berbeda.</p>
            <a class="btn btn-primary" href="<?= e(url('pages/products.php')) ?>">Lihat Semua Produk</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php while ($product = $products->fetch_assoc()): ?>
                <article class="product-card">
                    <span class="product-badge"><?= (int) $product['stock'] > 0 ? 'Ready' : 'Habis' ?></span>
                    <a class="product-image" href="<?= e(url('pages/product_detail.php?id=' . $product['id'])) ?>">
                        <img src="<?= e(url($product['image_path'] ?: 'public/images/product-placeholder.svg')) ?>" alt="<?= e($product['name']) ?>">
                    </a>
                    <div class="product-body">
                        <span class="product-category"><?= e($product['category_name'] ?? 'Tanpa Kategori') ?></span>
                        <h3><a href="<?= e(url('pages/product_detail.php?id=' . $product['id'])) ?>"><?= e($product['name']) ?></a></h3>
                        <div class="product-meta"><strong><?= rupiah($product['price']) ?></strong><span><?= (int) $product['stock'] ?> tersedia</span></div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
