<?php
// Helper form buku, dipakai oleh tambah.php dan edit.php.
function book_from_post(): array {
    $g = fn($k) => trim((string)($_POST[$k] ?? ''));
    return [
        'kode' => $g('kode'), 'judul' => $g('judul'), 'pengarang' => $g('pengarang'), 'penerbit' => $g('penerbit'),
        'tahun' => $g('tahun'), 'kategori' => $g('kategori'), 'stok' => max(0, (int)$g('stok')),
        'rak' => $g('rak'), 'sinopsis' => $g('sinopsis'),
    ];
}

function book_valid(array $d): bool {
    return $d['kode'] !== '' && $d['judul'] !== '' && $d['pengarang'] !== '' && in_array($d['kategori'], KATEGORI, true);
}

function book_form(array $d, string $err, string $btn): void {
    $v = fn($k) => h($d[$k] ?? '');
    ?>
<form class="panel" method="post">
  <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
<?php if ($err): ?>
  <p class="err"><?= h($err) ?></p>
<?php endif; ?>
  <label>Kode / ISBN</label>
  <input name="kode" required maxlength="50" value="<?= $v('kode') ?>">
  <label>Judul Buku</label>
  <input name="judul" required maxlength="250" value="<?= $v('judul') ?>">
  <label>Pengarang</label>
  <input name="pengarang" required maxlength="150" value="<?= $v('pengarang') ?>">
  <label>Penerbit</label>
  <input name="penerbit" maxlength="150" value="<?= $v('penerbit') ?>">
  <label>Tahun Terbit</label>
  <input type="number" name="tahun" min="1000" max="2100" value="<?= $v('tahun') ?>">
  <label>Kategori</label>
  <select name="kategori">
<?php foreach (KATEGORI as $k): ?>
    <option <?= ($d['kategori'] ?? '') === $k ? 'selected' : '' ?>><?= h($k) ?></option>
<?php endforeach; ?>
  </select>
  <label>Jumlah Eksemplar</label>
  <input type="number" name="stok" min="0" value="<?= $v('stok') !== '' ? $v('stok') : 1 ?>">
  <label>Lokasi Rak</label>
  <input name="rak" maxlength="50" value="<?= $v('rak') ?>">
  <label>Sinopsis</label>
  <textarea name="sinopsis"><?= $v('sinopsis') ?></textarea>
  <button class="btn"><?= h($btn) ?></button>
</form>
<?php
}
