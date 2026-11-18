<?php
require __DIR__ . '/lib/app.php'; require __DIR__ . '/lib/admin_layout.php'; require_login();
$all = load_buku(); $stok = 0; $kat = array_fill_keys(KATEGORI, 0);
foreach ($all as $a) if ($a['d']) { $stok += (int)($a['d']['stok'] ?? 0); $k = $a['d']['kategori'] ?? ''; if (isset($kat[$k])) $kat[$k]++; }
admin_head('Dashboard', 'dashboard.php'); ?>
<p class="sub">Sistem Informasi Perpustakaan</p><h1>Dashboard</h1>
<div class="cards">
  <div class="panel stat"><span>Total Judul Buku</span><b><?= count($all) ?></b></div>
  <div class="panel stat"><span>Total Eksemplar</span><b><?= $stok ?></b></div>
  <div class="panel stat"><span>Kategori Terpakai</span><b><?= count(array_filter($kat)) ?></b></div>
</div>
<div class="panel"><h2 style="margin-top:0">Jumlah Buku per Kategori</h2>
<table><tr><?php foreach ($kat as $k => $n): ?><th><?= h($k) ?></th><?php endforeach; ?></tr>
<tr><?php foreach ($kat as $n): ?><td><b><?= $n ?></b></td><?php endforeach; ?></tr></table></div>
<div class="panel"><h2 style="margin-top:0">Buku terbaru</h2>
<table><tr><th>Kode</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Stok</th></tr>
<?php foreach (array_slice($all, 0, 5) as $a): $d = $a['d']; ?><tr>
<?php if ($d): ?><td><?= h($d['kode'] ?? '') ?></td><td><?= h($d['judul'] ?? '') ?></td><td><?= h($d['pengarang'] ?? '') ?></td><td><span class="tag"><?= h($d['kategori'] ?? '') ?></span></td><td><?= (int)($d['stok'] ?? 0) ?></td>
<?php else: ?><td colspan="5" class="err">Gagal didekripsi</td><?php endif; ?></tr>
<?php endforeach; if (!$all): ?><tr><td colspan="5">Belum ada data buku.</td></tr><?php endif; ?></table></div>
<?php admin_foot();
