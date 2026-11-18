<?php
require __DIR__ . '/lib/app.php'; require __DIR__ . '/lib/admin_layout.php'; require_login();
$id = (int)($_GET['id'] ?? 0);
$s = $db->prepare('SELECT * FROM buku WHERE id=?'); $s->execute([$id]); $row = $s->fetch(PDO::FETCH_ASSOC);
if (!$row) { header('Location: buku.php'); exit; }
$kek = base64_decode($_SESSION['kek']);
$pt = unseal($row['data'], $row['lock_data'], $_SESSION['priv'], $kek); $d = $pt ? json_decode($pt, true) : null;
if (!is_array($d)) exit('Gagal mendekripsi data ini.');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $d = book_from_post();
    if (!book_valid($d)) $err = 'Lengkapi kode, judul, pengarang, dan kategori.';
    else {
        [$data, $lock] = seal(json_encode($d, JSON_UNESCAPED_UNICODE), setting('pub'), $kek);
        $db->prepare('UPDATE buku SET data=?, lock_data=? WHERE id=?')->execute([$data, $lock, $id]);
        log_activity('Ubah buku', 'Buku #' . $id . ' diperbarui (dienkripsi ulang)');
        header('Location: buku.php?ok=1'); exit;
    }
}
admin_head('Edit Buku', 'buku.php'); ?>
<p class="sub">Data akan dienkripsi ulang dengan kunci data (DEK) baru</p><h1>Edit Buku #<?= $id ?></h1>
<?php book_form($d, $err, '🔒 Simpan Perubahan'); admin_foot();
