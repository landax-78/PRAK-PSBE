<?php
require_once __DIR__ . '/../includes/config.php';
if (is_logged_in()) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if (strlen($name) < 3) $error = 'Nama minimal terdiri dari 3 karakter.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
    elseif (strlen($password) < 8) $error = 'Password minimal terdiri dari 8 karakter.';
    elseif ($password !== $confirmation) $error = 'Konfirmasi password tidak sesuai.';
    else {
        $check = db()->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) $error = 'Email sudah terdaftar.';
        $check->close();
        if ($error === '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $role = 'customer';
            $stmt = db()->prepare('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)');
            $stmt->bind_param('ssss', $name, $email, $hash, $role);
            if ($stmt->execute()) {
                flash('success', 'Pendaftaran berhasil. Silakan login.');
                redirect('pages/login.php');
            }
            $error = 'Pendaftaran gagal. Silakan coba kembali.';
            $stmt->close();
        }
    }
}
$pageTitle = 'Daftar';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-shell">
    <div class="auth-card">
        <span class="eyebrow">Create Account</span>
        <h1>Buat akun baru</h1>
        <p>Simpan keranjang, lakukan checkout, dan pantau status pesanan Anda.</p>
        <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>Nama lengkap<input name="name" value="<?= old('name') ?>" placeholder="Nama lengkap" required></label>
            <label>Email<input type="email" name="email" value="<?= old('email') ?>" placeholder="nama@email.com" required></label>
            <label>Password<input type="password" name="password" minlength="8" placeholder="Minimal 8 karakter" required></label>
            <label>Konfirmasi password<input type="password" name="password_confirmation" minlength="8" placeholder="Ulangi password" required></label>
            <button class="btn btn-primary btn-block" type="submit">Daftar Sekarang</button>
        </form>
        <p>Sudah memiliki akun? <a href="<?= e(url('pages/login.php')) ?>">Login</a>.</p>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
