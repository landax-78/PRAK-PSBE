<?php
require_once __DIR__ . '/../includes/config.php';
if (is_logged_in()) {
    redirect(($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Email dan password wajib diisi dengan benar.';
    } else {
        $stmt = db()->prepare('SELECT id,name,email,password,role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($account && password_verify($password, $account['password'])) {
            session_regenerate_id(true);
            unset($account['password']);
            $_SESSION['user'] = $account;
            flash('success', 'Login berhasil. Selamat datang, ' . $account['name'] . '.');
            redirect($account['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php');
        }
        $error = 'Email atau password tidak sesuai.';
    }
}
$pageTitle = 'Login';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-shell">
    <div class="auth-card">
        <span class="eyebrow">Welcome Back</span>
        <h1>Masuk ke akun</h1>
        <p>Akses keranjang, riwayat pesanan, dan proses checkout lebih cepat.</p>
        <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>Email<input type="email" name="email" value="<?= old('email') ?>" placeholder="nama@email.com" autocomplete="email" required></label>
            <label>Password<input type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required></label>
            <button class="btn btn-primary btn-block" type="submit">Login</button>
        </form>
        <p>Belum memiliki akun? <a href="<?= e(url('pages/register.php')) ?>">Daftar sekarang</a>.</p>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
