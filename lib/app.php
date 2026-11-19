<?php
// Bootstrap: sesi, koneksi database, helper umum, dan layout halaman publik (login & setup).
require __DIR__ . '/crypto.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:");

const KATEGORI    = ['Fiksi', 'Nonfiksi', 'Pendidikan', 'Sains & Teknologi', 'Referensi', 'Lainnya'];
// Kunci simetris server (32 byte acak). Disimpan di berkas .php agar tidak bisa diunduh lewat URL.
const MASTER_FILE = __DIR__ . '/../storage/master.key.php';

// ---------- Database ----------
$cfg = require __DIR__ . '/../config.php';
if ($cfg['driver'] === 'mysql') {
    $db = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4", $cfg['user'], $cfg['pass']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // tabel dibuat lewat perpustakaan.sql
} else {
    $db = new PDO('sqlite:' . __DIR__ . '/../storage/app.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT);
               CREATE TABLE IF NOT EXISTS buku(id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, lock_data TEXT, created_at TEXT);
               CREATE TABLE IF NOT EXISTS riwayat(id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, lock_data TEXT, created_at TEXT);");
}

// ---------- Helper umum ----------
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// URL berkas di folder assets/ + versi (waktu ubah berkas) supaya cache browser ikut diperbarui
function asset(string $file): string { return 'assets/' . $file . '?v=' . (@filemtime(__DIR__ . '/../assets/' . $file) ?: 0); }

function setting($k) {
    global $db;
    $s = $db->prepare('SELECT v FROM settings WHERE k=?'); $s->execute([$k]);
    return $s->fetchColumn() ?: null;
}
function set_setting($k, $v): void { global $db; $db->prepare('REPLACE INTO settings(k,v) VALUES(?,?)')->execute([$k, $v]); }

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); exit('CSRF tidak valid'); }
}

// ---------- Sesi & kunci ----------
function logged_in(): bool {
    if (empty($_SESSION['kek'])) return false;
    if (time() - ($_SESSION['last'] ?? 0) > 900) { $_SESSION = []; return false; }   // idle 15 menit
    $_SESSION['last'] = time();
    return true;
}
function require_login(): void { if (!logged_in()) { header('Location: index.php'); exit; } }

// true = RSA private key cocok. false = mode hanya-lihat (data tampil sebagai ciphertext)
function is_unlocked(): bool { return !empty($_SESSION['ok']); }
function require_unlocked(): void { if (!is_unlocked()) { header('Location: buku.php'); exit; } }

function master_key(): string {
    $raw = @file_get_contents(MASTER_FILE);
    $k = $raw ? base64_decode(trim(substr($raw, (int)strpos($raw, "\n"))), true) : false;
    if (!$k || strlen($k) !== 32) {
        http_response_code(500);
        exit('Berkas kunci simetris server (storage/master.key.php) tidak ditemukan. Pulihkan dari cadangan.');
    }
    return $k;
}

// ---------- Data terenkripsi ----------
function log_activity(string $action, string $detail = ''): void {
    global $db;
    if (empty($_SESSION['kek'])) return;
    // riwayat juga dienkripsi (hybrid) -> isinya tidak terbaca di database
    [$d, $l] = seal(json_encode(['a' => $action, 'd' => $detail, 'ip' => $_SERVER['REMOTE_ADDR'] ?? ''], JSON_UNESCAPED_UNICODE),
                    setting('pub'), base64_decode($_SESSION['kek']));
    $db->prepare('INSERT INTO riwayat(data,lock_data,created_at) VALUES(?,?,?)')->execute([$d, $l, date('Y-m-d H:i:s')]);
}

// Setiap baris: ['row' => baris database, 'd' => data terdekripsi atau null kalau kunci tidak cocok]
function decrypt_rows(string $table): array {
    global $db;
    $kek = base64_decode($_SESSION['kek']); $priv = $_SESSION['priv']; $out = [];
    foreach ($db->query("SELECT * FROM $table ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $pt = $priv === '' ? null : unseal($r['data'], $r['lock_data'], $priv, $kek);
        $j  = $pt ? json_decode($pt, true) : null;
        $out[] = ['row' => $r, 'd' => is_array($j) ? $j : null];
    }
    return $out;
}
function load_buku(): array    { return decrypt_rows('buku'); }
function load_riwayat(): array { return array_slice(decrypt_rows('riwayat'), 0, 200); }

// Teks pengisi untuk mode terkunci; admin_foot() lalu menggantinya dengan potongan ciphertext asli
function masked(int $len = 16): string { return str_repeat('x', $len); }

// ---------- Layout halaman publik ----------
function layout_head(string $title): void { ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?></title>
<link rel="stylesheet" href="<?= h(asset('app.css')) ?>">
</head>
<body><div class="wrap">
<?php }
function layout_foot(): void { echo '</div></body></html>'; }
