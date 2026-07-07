<?php
declare(strict_types=1);
define('ALLOW_NO_DATABASE', true);
require_once __DIR__ . '/includes/config.php';
$checks = [
    ['PHP 8.1 atau lebih baru', version_compare(PHP_VERSION, '8.1.0', '>=')],
    ['Ekstensi mysqli aktif', extension_loaded('mysqli')],
    ['Folder uploads dapat ditulis', is_writable(__DIR__ . '/public/uploads')],
    ['Koneksi dan database tersedia', $db instanceof mysqli],
];
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pemeriksaan Sistem</title><link rel="stylesheet" href="<?= e(url('public/css/style.css')) ?>"></head><body>
<div class="auth-shell"><div class="auth-card wide-card"><h1>Pemeriksaan Sistem</h1><p>BASE_PATH terdeteksi: <code><?= e(BASE_PATH === '' ? '/' : BASE_PATH) ?></code></p>
<table class="table"><thead><tr><th>Pemeriksaan</th><th>Status</th></tr></thead><tbody><?php foreach($checks as [$label,$ok]): ?><tr><td><?= e($label) ?></td><td><span class="status status-<?= $ok ? 'delivered' : 'cancelled' ?>"><?= $ok ? 'OK' : 'GAGAL' ?></span></td></tr><?php endforeach; ?></tbody></table>
<?php if (!$db instanceof mysqli): ?><div class="flash flash-warning"><?= e($dbError) ?></div><a class="btn btn-primary" href="<?= e(url('install.php')) ?>">Buka Instalasi</a><?php else: ?><a class="btn btn-primary" href="<?= e(url()) ?>">Buka Toko</a><?php endif; ?></div></div></body></html>
