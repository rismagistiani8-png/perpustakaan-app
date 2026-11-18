<?php
require __DIR__ . '/lib/app.php'; require __DIR__ . '/lib/admin_layout.php'; require_login();
admin_head('Info Kriptografi', 'info.php'); ?>
<p class="sub">Penjelasan skema untuk laporan</p><h1>Info Kriptografi</h1>
<div class="panel"><h2 style="margin-top:0">Skema Hybrid Dua Kunci</h2>
<table><tr><th>Langkah</th><th>Algoritma</th><th>Kunci</th></tr>
<tr><td>1. Enkripsi isi data buku</td><td>AES-256-GCM (simetris)</td><td>DEK acak 256-bit per data</td></tr>
<tr><td>2. Pembungkusan DEK</td><td>RSA-3072 OAEP (asimetris)</td><td>Public key (di server)</td></tr>
<tr><td>3. Penguncian hasil langkah 2</td><td>AES-256-GCM (simetris)</td><td>KEK = PBKDF2-SHA256 (200.000 iterasi) dari passphrase</td></tr>
<tr><td>Dekripsi</td><td colspan="2">Butuh passphrase (kunci simetris) <b>dan</b> private key RSA (kunci asimetris)</td></tr></table></div>
<div class="panel"><h2 style="margin-top:0">Yang Terenkripsi</h2>
<p>Seluruh isi halaman admin: kode, judul, pengarang, penerbit, tahun, kategori, stok, rak, sinopsis, serta riwayat aktivitas. Database hanya menyimpan ciphertext. Login tidak memakai username/password, melainkan verifikasi dua kunci terhadap record uji terenkripsi.</p></div>
<?php admin_foot();
