<?php
require __DIR__ . '/lib/app.php';

if (!setting('pub')) { header('Location: setup.php'); exit; }   // belum di-setup
if (logged_in())     { header('Location: dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $priv = trim($_POST['priv_key'] ?? '');
    // Kunci kosong / salah format / tidak cocok = tetap masuk, tapi mode terkunci (data tampil sebagai ciphertext)
    $valid = $priv !== '' && @openssl_pkey_get_private($priv) !== false;
    try { $ok = $valid && unseal(setting('check_data'), setting('check_lock'), $priv, master_key()) === 'ADMIN-CHECK-v1'; }
    catch (Throwable $e) { $ok = false; }

    session_regenerate_id(true);
    $_SESSION['kek']  = base64_encode(master_key());
    $_SESSION['priv'] = $valid ? $priv : '';
    $_SESSION['ok']   = $ok;
    $_SESSION['last'] = time();
    if ($ok) log_activity('Login', 'Masuk dengan kunci asimetris yang valid');
    header('Location: dashboard.php'); exit;
}

layout_head('Perpustakaan Digital – Masuk Admin'); ?>
<div class="card">
  <h1>📚 Perpustakaan Digital</h1>
  <p>Sistem informasi data buku terenkripsi. Akses admin memakai <b>kunci asimetris</b> (RSA private key). Jika kunci tidak cocok, halaman admin hanya menampilkan <b>ciphertext</b>.</p>
</div>
<form class="card" method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
  <h2>Masuk ke Halaman Admin</h2>
  <label>Kunci Asimetris (RSA Private Key, PEM)</label>
  <textarea name="priv_key" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
  <button type="submit">Buka Brankas</button>
</form>
<?php layout_foot();
