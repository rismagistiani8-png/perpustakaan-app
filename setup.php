<?php
require __DIR__ . '/lib/app.php';

// Cari openssl.cnf (diperlukan PHP di Windows untuk membuat kunci RSA)
function ssl_opts(): array {
    $o = ['private_key_bits' => 3072, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
    $bin = dirname(PHP_BINARY);
    foreach ([getenv('OPENSSL_CONF'), "$bin/extras/ssl/openssl.cnf", "$bin/extras/openssl/openssl.cnf", "$bin/openssl.cnf",
              'C:/laragon/etc/ssl/openssl.cnf', 'C:/xampp/apache/conf/openssl.cnf', 'C:/xampp/php/extras/openssl/openssl.cnf'] as $p) {
        if ($p && is_file($p)) { $o['config'] = $p; break; }
    }
    return $o;
}

if (setting('pub')) { header('Location: index.php'); exit; }   // sudah di-setup: halaman ini terkunci

$err = ''; $privOut = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $o = ssl_opts(); $res = openssl_pkey_new($o);
    if (!$res) {
        $e = []; while ($m = openssl_error_string()) $e[] = $m;
        $err = 'Gagal membuat RSA. openssl aktif: ' . (extension_loaded('openssl') ? 'ya' : 'TIDAK')
             . ' | openssl.cnf: ' . ($o['config'] ?? 'TIDAK DITEMUKAN') . ' | ' . implode('; ', $e);
    } else {
        openssl_pkey_export($res, $privOut, null, $o);
        $pub    = openssl_pkey_get_details($res)['key'];
        $master = random_bytes(32);   // kunci simetris server
        if (@file_put_contents(MASTER_FILE, "<?php http_response_code(404); exit; ?>\n" . base64_encode($master)) === false) {
            $err = 'Gagal menulis storage/master.key.php (cek izin folder storage).'; $privOut = null;
        } else {
            [$d, $l] = seal('ADMIN-CHECK-v1', $pub, $master);
            // setup pertama: bersihkan sisa data lama yang tidak bisa didekripsi dengan kunci baru
            $db->exec('DELETE FROM buku'); $db->exec('DELETE FROM riwayat'); $db->exec('DELETE FROM settings');
            set_setting('pub', $pub); set_setting('check_data', $d); set_setting('check_lock', $l);
        }
    }
}

layout_head('Setup Perpustakaan Digital');
if ($privOut): ?>
<div class="card">
  <h1>✅ Setup selesai</h1>
  <p class="err"><b>Simpan Private Key ini SEKARANG.</b> Server tidak menyimpannya dan tidak bisa dipulihkan. Anda akan menempelkannya di halaman masuk.</p>
  <textarea id="pk" readonly><?= h($privOut) ?></textarea>
  <a class="btn" download="perpustakaan-private.pem" href="data:application/octet-stream;base64,<?= base64_encode($privOut) ?>">Unduh private.pem</a>
  <button type="button" class="btn2" onclick="const t=document.getElementById('pk');t.select();navigator.clipboard.writeText(t.value).then(()=>this.textContent='Tersalin ✓')">Salin ke clipboard</button>
  <a class="btn btn2" href="index.php">Ke halaman masuk</a>
</div>
<?php else: ?>
<form class="card" method="post">
  <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
  <h1>Setup Awal</h1>
  <p>Sistem membuat pasangan kunci RSA (asimetris) dan kunci simetris untuk server. Private key hanya ditampilkan sekali.</p>
<?php if ($err): ?>
  <p class="err"><?= h($err) ?></p>
<?php endif; ?>
  <button>Buat Kunci</button>
</form>
<?php endif;
layout_foot();
