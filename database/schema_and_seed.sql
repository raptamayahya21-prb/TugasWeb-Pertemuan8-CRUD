-- ========================================================
-- SISTEM INVENTARIS BARANG (TUGAS RUTIN 8 - PEMROGRAMAN WEB)
-- Database: inventaris_db
-- DDL (Schema) & DML (Data Seeder)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `inventaris_db`
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `inventaris_db`;

-- --------------------------------------------------------
-- 1. Tabel: kategori
-- --------------------------------------------------------
DROP TABLE IF EXISTS `log_aktivitas`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `supplier`;
DROP TABLE IF EXISTS `kategori`;

CREATE TABLE `kategori` (
    `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `deskripsi` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2. Tabel: supplier
-- --------------------------------------------------------
CREATE TABLE `supplier` (
    `id_supplier` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_supplier` VARCHAR(120) NOT NULL,
    `kontak` VARCHAR(30) NOT NULL,
    `email` VARCHAR(100) NULL,
    `alamat` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3. Tabel: produk (Relasi FK ke kategori & supplier)
-- --------------------------------------------------------
CREATE TABLE `produk` (
    `id_produk` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_produk` VARCHAR(30) NOT NULL UNIQUE,
    `nama_produk` VARCHAR(150) NOT NULL,
    `id_kategori` INT NOT NULL,
    `id_supplier` INT NOT NULL,
    `stok` INT NOT NULL DEFAULT 0,
    `harga` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `satuan` VARCHAR(20) NOT NULL DEFAULT 'Unit',
    `kondisi` ENUM('Baru', 'Baik', 'Perlu Perbaikan', 'Rusak') DEFAULT 'Baru',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_produk_kategori` 
        FOREIGN KEY (`id_kategori`) 
        REFERENCES `kategori` (`id_kategori`) 
        ON UPDATE CASCADE 
        ON DELETE RESTRICT,
    CONSTRAINT `fk_produk_supplier` 
        FOREIGN KEY (`id_supplier`) 
        REFERENCES `supplier` (`id_supplier`) 
        ON UPDATE CASCADE 
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 4. Tabel: log_aktivitas (Fitur Bonus: Transaction on Delete / Audit Log)
-- --------------------------------------------------------
CREATE TABLE `log_aktivitas` (
    `id_log` INT AUTO_INCREMENT PRIMARY KEY,
    `aksi` VARCHAR(50) NOT NULL,
    `deskripsi` TEXT NOT NULL,
    `waktu` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================================
-- DATA SEEDER (Minimal 5 data per tabel)
-- Data realistis inventaris perlengkapan IT & kantor
-- ========================================================

-- Seeder Kategori (5 baris)
INSERT INTO `kategori` (`nama_kategori`, `deskripsi`) VALUES
('Komputer & Laptop', 'Perangkat komputer desktop, laptop operasional, dan workstation'),
('Monitor & Display', 'Layar monitor eksternal, proyektor, dan smart display presentasi'),
('Aksesoris & Periferal', 'Keyboard mekanik, mouse ergonomis, webcam, dan perlengkapan audio'),
('Peralatan Jaringan', 'Router Wi-Fi 6, switch managed, access point, dan kabel ethernet'),
('Perabot Kantor', 'Meja kerja ergonomis, kursi kantor hidrolik, dan lemari arsip');

-- Seeder Supplier (5 baris)
INSERT INTO `supplier` (`nama_supplier`, `kontak`, `email`, `alamat`) VALUES
('PT Synnex Metrodata Indonesia', '021-29345800', 'corporate@metrodata.co.id', 'Kawasan Komersial Cilandak, Jakarta Selatan'),
('PT Surya Graha Pratama', '0812-9844-3211', 'sales@suryagraha.id', 'Jl. Gatot Subroto No. 88, Medan'),
('CV Bintang Perkasa Solusindo', '0821-6577-9002', 'info@bintangperkasa.co.id', 'Jl. Brigjend Katamso No. 142, Medan'),
('PT Tera Data Indonusa', '021-6547890', 'enterprise@teradata.id', 'Mangga Dua Mall Lt. 4, Jakarta Pusat'),
('CV Mandiri Mitra Furniture', '0813-7722-4490', 'cs@mandirimakmur.com', 'Jl. Setia Budi Indah No. 56, Medan');

-- Seeder Produk (6 baris)
INSERT INTO `produk` (`kode_produk`, `nama_produk`, `id_kategori`, `id_supplier`, `stok`, `harga`, `satuan`, `kondisi`) VALUES
('PRD-NB-001', 'ThinkPad T14s Gen 4 (AMD Ryzen 7, 32GB RAM)', 1, 1, 12, 19500000.00, 'Unit', 'Baru'),
('PRD-MN-002', 'Dell UltraSharp U2723QE 27 Inch 4K UHD', 2, 2, 8, 8750000.00, 'Unit', 'Baru'),
('PRD-KB-003', 'Logitech MX Keys S Wireless Keyboard', 3, 3, 25, 1650000.00, 'Unit', 'Baru'),
('PRD-NW-004', 'Ubiquiti UniFi U6-Pro Wi-Fi 6 Access Point', 4, 1, 6, 2850000.00, 'Unit', 'Baik'),
('PRD-PR-005', 'Kursi Kerja Ergonomis Pexio Jervis Series', 5, 5, 15, 2350000.00, 'Unit', 'Baru'),
('PRD-MS-006', 'Logitech MX Master 3S Wireless Mouse', 3, 3, 20, 1520000.00, 'Unit', 'Baru');

-- Seeder Log Aktivitas Awal
INSERT INTO `log_aktivitas` (`aksi`, `deskripsi`) VALUES
('INITIALIZE', 'Database dan data seeder awal inventaris berhasil dipasang.');
