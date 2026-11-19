<?php
// Kerangka halaman admin: sidebar, banner mode terkunci, dan pembungkus konten.
// Saat kunci tidak cocok, seluruh konten ditampung di buffer lalu diacak jadi ciphertext oleh admin_foot().
require __DIR__ . '/cipherize.php';

function admin_head(string $title, string $active): void {
    $locked = !is_unlocked();
    $menu = [
        'dashboard.php' => ['▦', 'Dashboard'],
        'buku.php'      => ['📚', 'Data Buku'],
        'tambah.php'    => ['＋', 'Tambah Buku'],
        'riwayat.php'   => ['🕘', 'Riwayat Aktivitas'],
        'info.php'      => ['🔐', 'Info Kriptografi'],
    ];
    ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> – Perpustakaan Digital</title>
<link rel="stylesheet" href="<?= h(asset('admin.css')) ?>">
</head>
<body>
<div class="app<?= $locked ? ' locked' : '' ?>">
<?php if ($locked) ob_start(); ?>
<aside>
  <div class="brand">📚 PERPUSTAKAAN</div>
  <small>MENU UTAMA</small>
<?php foreach ($menu as $file => [$icon, $label]): ?>
  <a href="<?= $file ?>" class="<?= $file === $active ? 'on' : '' ?>"><?= $icon ?> <?= $label ?></a>
<?php endforeach; ?>
  <form method="post" action="logout.php">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <button>⇦ Keluar</button>
  </form>
</aside>
<main<?= $locked ? ' class="locked"' : '' ?>>
<?php if ($locked): ?>
<div class="lockbar">⚠️ Kunci asimetris tidak cocok. Data tampil sebagai ciphertext (mode hanya-lihat); tambah, ubah, dan hapus dinonaktifkan.</div>
<?php endif; ?>
<?php
}

function admin_foot(): void {
    if (!is_unlocked()) echo cipherize((string)ob_get_clean());   // mode terkunci: semua teks jadi ciphertext
    echo '</main></div></body></html>';
}
