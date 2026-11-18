-- Database Sistem Informasi Perpustakaan (data terenkripsi hybrid AES-256-GCM + RSA-OAEP)
-- Import: mysql -u root < perpustakaan.sql   (atau lewat HeidiSQL/phpMyAdmin)
CREATE DATABASE IF NOT EXISTS perpustakaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpustakaan;

-- Konfigurasi: public key RSA, salt PBKDF2, record uji login (tanpa kunci rahasia)
CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(50) NOT NULL PRIMARY KEY,
  v MEDIUMTEXT NULL
) ENGINE=InnoDB;

-- Data buku: seluruh field (kode, judul, pengarang, dst.) ada di dalam `data` (ciphertext)
-- data      = AES-256-GCM(JSON buku, DEK acak)
-- lock_data = AES-256-GCM(RSA-OAEP(DEK, public key), KEK dari passphrase)
CREATE TABLE IF NOT EXISTS buku (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  data MEDIUMTEXT NOT NULL,
  lock_data TEXT NOT NULL,
  created_at VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

-- Riwayat aktivitas admin (juga terenkripsi dengan skema yang sama)
CREATE TABLE IF NOT EXISTS riwayat (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  data MEDIUMTEXT NOT NULL,
  lock_data TEXT NOT NULL,
  created_at VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

-- Pembatasan percobaan login
CREATE TABLE IF NOT EXISTS attempts (
  ip VARCHAR(45) NOT NULL,
  t INT UNSIGNED NOT NULL,
  KEY idx_ip (ip)
) ENGINE=InnoDB;
