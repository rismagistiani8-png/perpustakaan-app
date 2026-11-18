<?php
require __DIR__ . '/lib/app.php'; require __DIR__ . '/lib/admin_layout.php'; require_login();
$err = ''; $d = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $d = book_from_post();
    if (!book_valid($d)) $err = 'Lengkapi kode, judul, pengarang, dan kategori.';
    else {
        [$data, $lock] = seal(json_encode($d, JSON_UNESCAPED_UNICODE), setting('pub'), base64_decode($_SESSION['kek']));
        $db->prepare('INSERT INTO buku(data,lock_data,created_at) VALUES(?,?,?)')->execute([$data, $lock, date('c')]);
        log_activity('Tambah buku', 'Buku baru dienkripsi & disimpan');
        header('Location: buku.php?ok=1'); exit;
    }
}
admin_head('Tambah Buku', 'tambah.php'); ?>
<p class="sub">Seluruh data dienkripsi sebelum masuk database</p><h1>Tambah Buku</h1>
<?php book_form($d, $err, '🔒 Enkripsi & Simpan'); admin_foot();
