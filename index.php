<?php
require __DIR__ . '/lib/app.php';
if (!setting('pub')) { header('Location: setup.php'); exit; }
if (logged_in()) { header('Location: dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $db->prepare('DELETE FROM attempts WHERE t<?')->execute([time() - 900]);
    $n = $db->prepare('SELECT COUNT(*) FROM attempts WHERE ip=?'); $n->execute([$ip]);
    if ($n->fetchColumn() >= 5) {
        $err = 'Terlalu banyak percobaan. Coba lagi 15 menit lagi.';
    } else {
        $sym = $_POST['sym_key'] ?? ''; $priv = trim($_POST['priv_key'] ?? '');
        $ok = false;
        if ($sym !== '' && openssl_pkey_get_private($priv)) {
            $k = kek($sym, base64_decode(setting('salt')));
            $ok = unseal(setting('check_data'), setting('check_lock'), $priv, $k) === 'ADMIN-CHECK-v1';
        }
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['kek'] = base64_encode($k); $_SESSION['priv'] = $priv; $_SESSION['last'] = time(); log_activity('Login', 'Masuk dengan kunci simetris & asimetris');
            header('Location: dashboard.php'); exit;
        }
        $db->prepare('INSERT INTO attempts VALUES(?,?)')->execute([$ip, time()]);
        $err = 'Kunci simetris atau asimetris tidak valid.';
    }
}
layout_head('Perpustakaan Digital – Masuk Admin'); ?>
<div class="card"><h1>📚 Perpustakaan Digital</h1>
<p>Sistem informasi data buku terenkripsi. Tidak ada username/password — akses admin hanya dengan <b>kunci simetris</b> dan <b>kunci asimetris</b>.</p></div>
<form class="card" method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
  <h2>Masuk ke Halaman Admin</h2>
  <?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>
  <label>Kunci Simetris (passphrase)</label>
  <input type="password" name="sym_key" required>
  <label>Kunci Asimetris (RSA Private Key, PEM)</label>
  <textarea name="priv_key" id="priv" placeholder="-----BEGIN PRIVATE KEY-----" required></textarea>
  <input type="file" id="pf" accept=".pem,.key,.txt" style="margin-top:8px">
  <button type="submit">Buka Brankas</button>
</form>
<script>
document.getElementById('pf').addEventListener('change',e=>{const f=e.target.files[0];if(!f)return;
const r=new FileReader();r.onload=()=>document.getElementById('priv').value=r.result;r.readAsText(f);});
</script>
<?php layout_foot();
