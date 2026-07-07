<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'shoeshoegaze';
const APP_SCHEMA_VERSION = '2.1';

function detect_base_path(): string
{
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $applicationRoot = realpath(dirname(__DIR__));

    if ($documentRoot !== false && $applicationRoot !== false) {
        $doc = str_replace('\\', '/', $documentRoot);
        $app = str_replace('\\', '/', $applicationRoot);
        if (str_starts_with(strtolower($app), strtolower($doc))) {
            $relative = substr($app, strlen($doc));
            return rtrim('/' . trim((string) $relative, '/'), '/');
        }
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/admin/', '/pages/', '/includes/'] as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            return rtrim(substr($scriptName, 0, $position), '/');
        }
    }

    $directory = str_replace('\\', '/', dirname($scriptName));
    return $directory === '/' ? '' : rtrim($directory, '/');
}

define('BASE_PATH', detect_base_path());

function url(string $path = ''): string
{
    $base = BASE_PATH;
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah(float|int|string $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    if ($submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba kembali.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Silakan login terlebih dahulu.');
        redirect('pages/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('403 Forbidden: Anda tidak memiliki akses ke halaman ini.');
    }
}

function require_customer(): void
{
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'customer') {
        flash('warning', 'Fitur ini hanya tersedia untuk akun customer.');
        redirect('admin/dashboard.php');
    }
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

$db = null;
$dbError = '';

if (!extension_loaded('mysqli')) {
    $dbError = 'Ekstensi mysqli belum aktif pada PHP.';
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
    $server = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($server->connect_errno) {
        $dbError = $server->connect_error;
    } else {
        $server->set_charset('utf8mb4');
        if (@$server->select_db(DB_NAME)) {
            $versionResult = @$server->query("SELECT meta_value FROM app_meta WHERE meta_key='schema_version' LIMIT 1");
            $versionRow = $versionResult instanceof mysqli_result ? $versionResult->fetch_assoc() : null;
            if (($versionRow['meta_value'] ?? '') === APP_SCHEMA_VERSION) {
                $db = $server;
            } else {
                $dbError = 'Struktur database belum sesuai versi aplikasi. Jalankan instalasi ulang.';
                $server->close();
            }
        } else {
            $dbError = "Database '" . DB_NAME . "' belum tersedia.";
            $server->close();
        }
    }
}

function db(): mysqli
{
    global $db;
    if (!$db instanceof mysqli) {
        throw new RuntimeException('Koneksi database belum tersedia.');
    }
    return $db;
}

if (!defined('ALLOW_NO_DATABASE') && !$db instanceof mysqli) {
    redirect('install.php');
}
