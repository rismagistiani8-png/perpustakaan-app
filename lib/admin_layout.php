<?php
function admin_head(string $title, string $active): void {
    $menu = ['dashboard.php' => ['▦', 'Dashboard'], 'buku.php' => ['📚', 'Data Buku'],
             'tambah.php' => ['＋', 'Tambah Buku'], 'riwayat.php' => ['🕘', 'Riwayat Aktivitas'], 'info.php' => ['🔐', 'Info Kriptografi']]; ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> – Perpustakaan Digital</title>
<style>
*{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,sans-serif;background:#f1f5e9;color:#2b3a24}
.app{display:flex;min-height:100vh}
aside{width:250px;background:#3d5a2f;color:#e8f1d9;padding:24px 16px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
aside .brand{font-weight:800;font-size:18px;color:#fff;margin-bottom:28px}
aside small{display:block;margin:0 0 8px 12px;font-size:12px;letter-spacing:.08em;color:#b9d09b}
aside a{display:block;padding:11px 14px;border-radius:12px;color:#e8f1d9;text-decoration:none;margin-bottom:6px}
aside a.on{background:#6a8f3f;color:#fff;font-weight:600}aside a:hover:not(.on){background:#4d6e3a}
aside form{margin-top:auto}aside button{width:100%;text-align:left;background:none;border:0;color:#e8f1d9;padding:11px 14px;font:inherit;cursor:pointer;border-radius:12px}
aside button:hover{background:#4d6e3a}
main{flex:1;padding:32px;min-width:0}
.sub{color:#6b7a5e;margin:0}h1{margin:0 0 22px;font-size:28px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:22px}
.panel{background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px #0001;margin-bottom:20px;overflow-x:auto}
.stat b{display:block;font-size:34px}.stat span{color:#6b7a5e}
table{width:100%;border-collapse:collapse}th{text-align:left;color:#3d4a33;padding:10px 8px;border-bottom:2px solid #dbe5cb}
td{padding:10px 8px;border-bottom:1px solid #f1f5e9;vertical-align:top}
label{display:block;margin:14px 0 4px;font-weight:600}
input,select,textarea{width:100%;padding:10px;border:1px solid #c9d6b5;border-radius:10px;font:inherit;background:#fff}
textarea{min-height:130px}.btn{background:#6a8f3f;color:#fff;border:0;padding:10px 18px;border-radius:10px;font-weight:600;cursor:pointer;margin-top:14px;text-decoration:none;display:inline-block}
.btn.red{background:#dc2626;padding:6px 12px;margin:6px 0 0}.ok{color:#3d7a2f}.err{color:#dc2626}
code{display:block;font:11px ui-monospace,monospace;word-break:break-all;color:#6b7a5e;background:#eef3e3;padding:8px;border-radius:8px;margin:4px 0}
.tag{background:#dcebc8;color:#3d5a2f;padding:2px 10px;border-radius:99px;font-size:13px}
@media(max-width:760px){.app{flex-direction:column}aside{width:100%;height:auto;position:static}main{padding:18px}}
</style></head><body><div class="app">
<aside><div class="brand">📚 PERPUSTAKAAN</div><small>MENU UTAMA</small>
<?php foreach ($menu as $f => [$ic, $lb]): ?><a href="<?= $f ?>" class="<?= $f === $active ? 'on' : '' ?>"><?= $ic ?> <?= $lb ?></a><?php endforeach; ?>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><button>⇦ Keluar</button></form></aside>
<main><?php }
function admin_foot(): void { echo '</main></div></body></html>'; }

function book_from_post(): array {
    $g = fn($k) => trim((string)($_POST[$k] ?? ''));
    return ['kode' => $g('kode'), 'judul' => $g('judul'), 'pengarang' => $g('pengarang'), 'penerbit' => $g('penerbit'),
            'tahun' => $g('tahun'), 'kategori' => $g('kategori'), 'stok' => max(0, (int)$g('stok')), 'rak' => $g('rak'), 'sinopsis' => $g('sinopsis')];
}
function book_valid(array $d): bool {
    return $d['kode'] !== '' && $d['judul'] !== '' && $d['pengarang'] !== '' && in_array($d['kategori'], KATEGORI, true);
}
function book_form(array $d, string $err, string $btn): void { $v = fn($k) => h($d[$k] ?? ''); ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
<?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>
<label>Kode / ISBN</label><input name="kode" required maxlength="50" value="<?= $v('kode') ?>">
<label>Judul Buku</label><input name="judul" required maxlength="250" value="<?= $v('judul') ?>">
<label>Pengarang</label><input name="pengarang" required maxlength="150" value="<?= $v('pengarang') ?>">
<label>Penerbit</label><input name="penerbit" maxlength="150" value="<?= $v('penerbit') ?>">
<label>Tahun Terbit</label><input type="number" name="tahun" min="1000" max="2100" value="<?= $v('tahun') ?>">
<label>Kategori</label><select name="kategori"><?php foreach (KATEGORI as $k): ?><option <?= ($d['kategori'] ?? '') === $k ? 'selected' : '' ?>><?= h($k) ?></option><?php endforeach; ?></select>
<label>Jumlah Eksemplar</label><input type="number" name="stok" min="0" value="<?= $v('stok') !== '' ? $v('stok') : 1 ?>">
<label>Lokasi Rak</label><input name="rak" maxlength="50" value="<?= $v('rak') ?>">
<label>Sinopsis</label><textarea name="sinopsis"><?= $v('sinopsis') ?></textarea>
<button class="btn"><?= h($btn) ?></button></form>
<?php }
