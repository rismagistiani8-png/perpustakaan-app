<?php
require __DIR__ . '/crypto.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:");

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
     CREATE TABLE IF NOT EXISTS attempts(ip TEXT, t INTEGER);
     CREATE TABLE IF NOT EXISTS riwayat(id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, lock_data TEXT, created_at TEXT);");
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function setting($k) { global $db; $s = $db->prepare('SELECT v FROM settings WHERE k=?'); $s->execute([$k]); return $s->fetchColumn() ?: null; }
function set_setting($k, $v) { global $db; $db->prepare('REPLACE INTO settings(k,v) VALUES(?,?)')->execute([$k, $v]); }
function csrf() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function check_csrf() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); exit('CSRF tidak valid'); } }
function logged_in() {
    if (empty($_SESSION['kek'])) return false;
    if (time() - ($_SESSION['last'] ?? 0) > 900) { $_SESSION = []; return false; } // idle 15 menit
    $_SESSION['last'] = time(); return true;
}
function layout_head($title) { ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?></title>
<style>
:root{--bg:#f1f5e9;--card:#ffffff;--tx:#2b3a24;--mut:#6b7a5e;--ac:#6a8f3f;--err:#c0392b;--ok:#3d7a2f}
*{box-sizing:border-box}body{margin:0;font:16px/1.5 system-ui,sans-serif;background:var(--bg);color:var(--tx)}
.wrap{max-width:820px;margin:0 auto;padding:24px}.card{background:var(--card);border-radius:14px;padding:20px;margin-bottom:16px;box-shadow:0 1px 3px #0001}
h1,h2{margin:.2em 0}p{color:var(--mut)}label{display:block;margin:14px 0 4px;font-weight:600}
input,textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #c9d6b5;background:#f8faf3;color:var(--tx);font:14px ui-monospace,monospace}
textarea{min-height:120px}button,.btn{background:var(--ac);color:#fff;border:0;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;margin-top:14px}
.btn2{background:#dbe5cb;color:#2b3a24}.err{color:var(--err)}.ok{color:var(--ok)}
code,pre{font:12px ui-monospace,monospace;word-break:break-all;white-space:pre-wrap;color:var(--mut)}
.top{display:flex;justify-content:space-between;align-items:center}
</style></head><body><div class="wrap"><?php }
function layout_foot() { echo '</div></body></html>'; }

function log_activity(string $action, string $detail = ''): void {
    global $db; if (empty($_SESSION['kek'])) return;
    // riwayat juga dienkripsi (hybrid) -> isinya tidak terbaca di database
    [$d, $l] = seal(json_encode(['a' => $action, 'd' => $detail, 'ip' => $_SERVER['REMOTE_ADDR'] ?? ''], JSON_UNESCAPED_UNICODE),
                    setting('pub'), base64_decode($_SESSION['kek']));
    $db->prepare('INSERT INTO riwayat(data,lock_data,created_at) VALUES(?,?,?)')->execute([$d, $l, date('Y-m-d H:i:s')]);
}
function require_login(): void { if (!logged_in()) { header('Location: index.php'); exit; } }
function decrypt_rows(string $table): array {
    global $db; $kek = base64_decode($_SESSION['kek']); $priv = $_SESSION['priv']; $out = [];
    foreach ($db->query("SELECT * FROM $table ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $pt = unseal($r['data'], $r['lock_data'], $priv, $kek); $j = $pt ? json_decode($pt, true) : null;
        $out[] = ['row' => $r, 'd' => is_array($j) ? $j : null];
    }
    return $out;
}
function load_buku(): array { return decrypt_rows('buku'); }
function load_riwayat(): array { return array_slice(decrypt_rows('riwayat'), 0, 200); }
const KATEGORI = ['Fiksi', 'Nonfiksi', 'Pendidikan', 'Sains & Teknologi', 'Referensi', 'Lainnya'];
