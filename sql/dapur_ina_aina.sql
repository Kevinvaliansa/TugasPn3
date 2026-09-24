-- ============================================================
-- DATABASE SCHEMA: Dapur Ina Aina Restaurant System
-- Dibuat untuk: Tugas Pemrograman Berbasis Jaringan
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `dapur_ina_aina` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dapur_ina_aina`;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Tabel: users (Autentikasi Pengguna & Role)
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','kasir','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `no_hp` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`, `no_hp`) VALUES
('admin', '$2y$10$K5mKP3Uwal6ME61A/ehIzOXf4vHMOOIym.g0uSQWyAdwI08tDKM6K', 'Administrator Restoran', 'admin', '081234567890'),
('kasir', '$2y$10$x6X1C4P9Y6pQ8v2kF3A1h.fK1N/6pQ8v2kF3A1h.fK1N/b/Z9m/p1', 'Kasir Utama', 'kasir', '081234567891'),
('budi', '$2y$10$uA3x9N5b2kF3A1h.fK1N/6pQ8v2kF3A1h.fK1N/b/Z9m/p1C7uG4x', 'Budi Santoso', 'pelanggan', '081234567892'),
('pelanggan', '$2y$10$fK1N/6pQ8v2kF3A1h.fK1N/b/Z9m/p1C7uG4x4xK8W5C6uWqA3x9', 'Pelanggan Tamu', 'pelanggan', '081234567893');

-- ----------------------------
-- Tabel: kategori
-- ----------------------------
DROP TABLE IF EXISTS `kategori`;
CREATE TABLE `kategori` (
  `id_kategori` INT NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(50) NOT NULL,
  `ikon` VARCHAR(10) DEFAULT '🍽️',
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kategori` (`nama_kategori`, `ikon`) VALUES
('Makanan Utama', '🍛'),
('Appetizer', '🥗'),
('Minuman', '🥤');

-- ----------------------------
-- Tabel: produk
-- ----------------------------
DROP TABLE IF EXISTS `produk`;
CREATE TABLE `produk` (
  `id_produk` INT NOT NULL AUTO_INCREMENT,
  `id_kategori` INT NOT NULL,
  `nama_produk` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `harga` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `stok` INT NOT NULL DEFAULT 0,
  `status` ENUM('tersedia','habis') NOT NULL DEFAULT 'tersedia',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produk`),
  KEY `fk_produk_kategori` (`id_kategori`),
  CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `produk` (`id_kategori`, `nama_produk`, `deskripsi`, `foto`, `harga`, `stok`, `status`) VALUES
-- Makanan Utama (id_kategori = 1)
(1, 'Nasi Goreng Spesial', 'Nasi goreng dengan telur, ayam, dan sayuran pilihan', 'produk_6ab359b31ebcb.jpg', 35000, 50, 'tersedia'),
(1, 'Mie Goreng Seafood', 'Mie goreng dengan udang, cumi, dan bakso ikan', 'produk_6ab359983c5a3.jpg', 40000, 30, 'tersedia'),
(1, 'Ayam Bakar Madu', 'Ayam bakar dengan bumbu madu kecap spesial', 'produk_6ab34b572181d.png', 45000, 25, 'tersedia'),
(1, 'Ikan Bakar Rica-Rica', 'Ikan segar dibakar dengan sambal rica pedas', 'produk_6ab3515417fc7.jpg', 55000, 20, 'tersedia'),
(1, 'Soto Ayam Lamongan', 'Soto ayam dengan kuah bening, telur, dan tauge', 'produk_6ab359eb868bf.jpg', 30000, 40, 'tersedia'),
(1, 'Rendang Daging', 'Rendang daging sapi dengan bumbu rempah khas Padang', 'produk_6ab359c816bef.jpg', 60000, 15, 'tersedia'),
-- Appetizer (id_kategori = 2)
(2, 'Lumpia Goreng', 'Lumpia isi rebung dan udang, disajikan dengan saus asam manis', 'produk_6ab35a2065e4f.jpg', 20000, 60, 'tersedia'),
(2, 'Tahu Crispy', 'Tahu goreng crispy dengan saus sambal spesial', 'produk_6ab35a73b12eb.jpg', 15000, 80, 'tersedia'),
(2, 'Sup Tom Yam', 'Sup asam pedas khas Thailand dengan udang dan jamur', 'produk_6ab35a553c01f.jpg', 25000, 35, 'tersedia'),
(2, 'Salad Buah Segar', 'Salad buah segar dengan saus yogurt madu', 'produk_6ab35a39f1a67.jpg', 22000, 45, 'tersedia'),
(2, 'Bakwan Jagung', 'Bakwan jagung manis renyah dengan saus sambal', 'produk_6ab35a02679d6.jpg', 12000, 70, 'tersedia'),
-- Minuman (id_kategori = 3)
(3, 'Es Teh Manis', 'Teh manis dingin menyegarkan', 'produk_6ab35b0a82413.jpg', 8000, 100, 'tersedia'),
(3, 'Jus Alpukat', 'Jus alpukat segar dengan susu kental manis', 'produk_6ab35b9432215.jpg', 18000, 50, 'tersedia'),
(3, 'Es Jeruk Peras', 'Jeruk peras segar dengan es batu', 'produk_6ab35ab68cd3e.jpg', 12000, 60, 'tersedia'),
(3, 'Kopi Susu Kekinian', 'Kopi susu dengan gula aren dan es', 'produk_6ab35bb252dba.jpg', 22000, 40, 'tersedia'),
(3, 'Air Mineral', 'Air mineral 600ml', 'produk_6ab35a89d3112.jpg', 5000, 200, 'tersedia'),
(3, 'Jus Mangga', 'Jus mangga harum manis segar', 'produk_6ab35b9915fbc.jpg', 15000, 55, 'tersedia');

-- ----------------------------
-- Tabel: pelanggan
-- ----------------------------
DROP TABLE IF EXISTS `pelanggan`;
CREATE TABLE `pelanggan` (
  `id_pelanggan` INT NOT NULL AUTO_INCREMENT,
  `nama_pelanggan` VARCHAR(100) NOT NULL,
  `no_meja` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pelanggan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Tabel: pesanan
-- ----------------------------
DROP TABLE IF EXISTS `pesanan`;
CREATE TABLE `pesanan` (
  `id_pesanan` INT NOT NULL AUTO_INCREMENT,
  `id_pelanggan` INT NOT NULL,
  `total_harga` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `catatan` TEXT DEFAULT NULL,
  `status` ENUM('pending','diproses','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pesanan`),
  KEY `fk_pesanan_pelanggan` (`id_pelanggan`),
  CONSTRAINT `fk_pesanan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Tabel: detail_pesanan
-- ----------------------------
DROP TABLE IF EXISTS `detail_pesanan`;
CREATE TABLE `detail_pesanan` (
  `id_detail` INT NOT NULL AUTO_INCREMENT,
  `id_pesanan` INT NOT NULL,
  `id_produk` INT NOT NULL,
  `jumlah` INT NOT NULL DEFAULT 1,
  `harga_satuan` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `fk_detail_pesanan` (`id_pesanan`),
  KEY `fk_detail_produk` (`id_produk`),
  CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`),
  CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Tabel: transaksi
-- ----------------------------
DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE `transaksi` (
  `id_transaksi` INT NOT NULL AUTO_INCREMENT,
  `id_pesanan` INT NOT NULL,
  `kode_transaksi` VARCHAR(20) NOT NULL UNIQUE,
  `metode_bayar` ENUM('tunai','non tunai','debit','kredit') NOT NULL DEFAULT 'tunai',
  `total_bayar` DECIMAL(10,2) NOT NULL,
  `uang_bayar` DECIMAL(10,2) DEFAULT NULL COMMENT 'Untuk pembayaran tunai',
  `kembalian` DECIMAL(10,2) DEFAULT 0 COMMENT 'Untuk pembayaran tunai',
  `no_referensi` VARCHAR(50) DEFAULT NULL COMMENT 'Untuk pembayaran non-tunai',
  `nama_bank` VARCHAR(50) DEFAULT NULL COMMENT 'Untuk debit/kredit',
  `status` ENUM('pending','berhasil','gagal') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `fk_transaksi_pesanan` (`id_pesanan`),
  CONSTRAINT `fk_transaksi_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- View: v_laporan_penjualan (untuk kemudahan laporan)
-- ----------------------------
CREATE OR REPLACE VIEW `v_laporan_penjualan` AS
SELECT
  t.id_transaksi,
  t.kode_transaksi,
  t.created_at AS tanggal_transaksi,
  p2.nama_pelanggan,
  p2.no_meja,
  t.metode_bayar,
  t.total_bayar,
  t.status AS status_transaksi,
  WEEK(t.created_at) AS minggu,
  MONTH(t.created_at) AS bulan,
  YEAR(t.created_at) AS tahun
FROM `transaksi` t
JOIN `pesanan` p ON t.id_pesanan = p.id_pesanan
JOIN `pelanggan` p2 ON p.id_pelanggan = p2.id_pelanggan
WHERE t.status = 'berhasil';

-- ----------------------------
-- View: v_detail_laporan (untuk detail produk terlaris)
-- ----------------------------
CREATE OR REPLACE VIEW `v_detail_laporan` AS
SELECT
  pr.id_produk,
  pr.nama_produk,
  k.nama_kategori,
  SUM(dp.jumlah) AS total_terjual,
  SUM(dp.subtotal) AS total_pendapatan
FROM `detail_pesanan` dp
JOIN `produk` pr ON dp.id_produk = pr.id_produk
JOIN `kategori` k ON pr.id_kategori = k.id_kategori
JOIN `pesanan` p ON dp.id_pesanan = p.id_pesanan
JOIN `transaksi` t ON p.id_pesanan = t.id_pesanan
WHERE t.status = 'berhasil'
GROUP BY pr.id_produk, pr.nama_produk, k.nama_kategori;

SET FOREIGN_KEY_CHECKS = 1;