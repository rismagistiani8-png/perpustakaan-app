# Sistem Informasi Perpustakaan – Kriptografi Simetris & Asimetris (PHP)

Aplikasi data buku perpustakaan. Seluruh konten halaman admin disimpan terenkripsi.
Login admin = input **kunci simetris** (passphrase) + **kunci asimetris** (RSA private key PEM).

## Fitur
Dashboard · Data Buku (cari, detail + ciphertext, edit, hapus) · Tambah Buku · Riwayat Aktivitas (terenkripsi) · Info Kriptografi

## Kebutuhan
PHP 8.1+ (ekstensi openssl, pdo_mysql) dan MySQL/MariaDB (mis. Laragon/XAMPP).

## Instalasi (Laragon)
1. Ekstrak ke `C:\laragon\www\perpustakaan`
2. Import database: `perpustakaan.sql` (HeidiSQL: File > Load SQL file > F9)
3. Cek `config.php` (default root, tanpa password)
4. Buka `http://localhost/perpustakaan` -> Setup (buat passphrase, unduh private.pem) -> login

## Skema Kriptografi
plaintext --AES-256-GCM(DEK acak)--> data
DEK --RSA-3072 OAEP(public key)--> wrapped
wrapped --AES-256-GCM(KEK=PBKDF2(passphrase))--> lock_data

## Struktur Database (`perpustakaan.sql`)
settings, buku, riwayat, attempts. Tabel buku & riwayat hanya berisi `data` dan `lock_data` (ciphertext).

## Link Kode Program
https://github.com/<username>/perpustakaan-kriptografi   <- ganti setelah diunggah
