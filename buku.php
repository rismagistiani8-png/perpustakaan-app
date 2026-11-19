<?php
require __DIR__ . '/lib/app.php';
require __DIR__ . '/lib/admin_layout.php';
require_login();

$open = is_unlocked();

// Hapus buku (hanya saat kunci cocok)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'del') {
    require_unlocked();
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare('DELETE FROM buku WHERE id=?')->execute([$id]);
    log_activity('Hapus buku', "Buku #$id dihapus");
    header('Location: buku.php'); exit;
}

$q   = trim($_GET['q'] ?? '');
$all = load_buku();
if ($q !== '') $all = array_filter($all, fn($a) => $a['d'] && stripos(implode(' ', $a['d']), $q) !== false);

admin_head('Data Buku', 'buku.php'); ?>
<p class="sub">Seluruh data hasil enkripsi (didekripsi dengan kunci sesi Anda)</p>
<h1>Data Buku</h1>
<?php if (isset($_GET['ok'])): ?>
<p class="ok">Data buku berhasil dienkripsi &amp; disimpan.</p>
<?php endif; ?>

<form method="get" class="panel">
  <input name="q" value="<?= h($q) ?>" placeholder="Cari kode, judul, pengarang, penerbit…">
</form>

<div class="panel">
  <table>
    <tr><th>#</th><th>Kode</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Tahun</th><th>Stok</th><th>Aksi</th></tr>
<?php foreach ($all as $a): $d = $a['d']; $r = $a['row']; ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
<?php if ($d): ?>
      <td><?= h($d['kode'] ?? '') ?></td>
      <td><?= h($d['judul'] ?? '') ?></td>
      <td><?= h($d['pengarang'] ?? '') ?></td>
      <td><span class="tag"><?= h($d['kategori'] ?? '') ?></span></td>
      <td><?= h($d['tahun'] ?? '') ?></td>
      <td><?= (int)($d['stok'] ?? 0) ?></td>
<?php elseif ($open): ?>
      <td colspan="6" class="err">Gagal didekripsi</td>
<?php else: ?>
<?php foreach ([6, 20, 16, 10, 6, 4] as $w): ?>
      <td><?= masked($w) ?></td>
<?php endforeach; ?>
<?php endif; ?>
      <td>
        <details>
          <summary>Detail</summary>
<?php if ($d): ?>
          <p>
            <b>Penerbit:</b> <?= h($d['penerbit'] ?? '-') ?><br>
            <b>Rak:</b> <?= h($d['rak'] ?? '-') ?><br>
            <?= nl2br(h($d['sinopsis'] ?? '')) ?>
          </p>
<?php endif; ?>
          <small>Ciphertext (AES-256-GCM):</small><code><?= h($r['data']) ?></code>
          <small>Kunci data terbungkus (RSA-OAEP + AES):</small><code><?= h($r['lock_data']) ?></code>
        </details>
<?php if ($open): ?>
        <a class="btn sm" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
<?php endif; ?>
        <form method="post" class="inline" onsubmit="return confirm('Hapus buku ini?')">
          <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
          <input type="hidden" name="act" value="del">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn red"<?= $open ? '' : ' disabled' ?>>Hapus</button>
        </form>
      </td>
    </tr>
<?php endforeach; if (!$all): ?>
    <tr><td colspan="8">Tidak ada data.</td></tr>
<?php endif; ?>
  </table>
</div>
<?php admin_foot();
