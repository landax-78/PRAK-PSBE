<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();
$stats = [];
foreach ([
    'users' => 'SELECT COUNT(*) AS total FROM users',
    'products' => 'SELECT COUNT(*) AS total FROM products',
    'orders' => 'SELECT COUNT(*) AS total FROM orders',
    'revenue' => "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE status!='cancelled'"
] as $key => $sql) {
    $stats[$key] = db()->query($sql)->fetch_assoc()['total'] ?? 0;
}
$latest = db()->query('SELECT o.*,u.name AS customer_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8');
$pageTitle = 'Dashboard';
require __DIR__ . '/admin_header.php';
?>
<div class="section-head">
    <div>
        <span class="eyebrow">Store Overview</span>
        <h1>Dashboard Admin</h1>
        <p class="section-subtitle">Pantau data produk, pengguna, pesanan, dan omzet ShoeShoeGaze dari satu halaman.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('admin/products.php')) ?>">Kelola Produk</a>
</div>
<div class="stat-grid">
    <div class="stat-card"><span>Total Pengguna</span><strong><?= (int) $stats['users'] ?></strong></div>
    <div class="stat-card"><span>Total Produk</span><strong><?= (int) $stats['products'] ?></strong></div>
    <div class="stat-card"><span>Total Pesanan</span><strong><?= (int) $stats['orders'] ?></strong></div>
    <div class="stat-card"><span>Total Omzet</span><strong><?= rupiah($stats['revenue']) ?></strong></div>
</div>
<div class="panel">
    <div class="section-head">
        <div><span class="eyebrow">Recent Orders</span><h2>Pesanan Terbaru</h2></div>
        <a href="<?= e(url('admin/orders.php')) ?>">Lihat semua</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php while ($order = $latest->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= e($order['invoice_number']) ?></strong><small><?= e(date('d-m-Y H:i', strtotime($order['created_at']))) ?></small></td>
                    <td><?= e($order['customer_name']) ?></td>
                    <td><?= rupiah($order['total_amount']) ?></td>
                    <td><span class="status status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                    <td><a href="<?= e(url('admin/order_detail.php?id=' . $order['id'])) ?>">Detail</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/admin_footer.php'; ?>
