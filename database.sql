-- ======================================================
-- DATABASE: SISTEM PENGGAJIAN KARYAWAN (PAYROLL SYSTEM)
-- Developer: Eka Wahyuning Tiyasa
-- NIM: 231011400032
-- Program Studi: Teknik Informatika - Universitas Pamulang
-- Compatible with MySQL 5.7+ & MySQL 8.0+ / MariaDB
-- ======================================================

CREATE DATABASE IF NOT EXISTS `db_penggajian` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_penggajian`;

-- --------------------------------------------------------
-- Table: users (Autentikasi & RBAC)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'karyawan') NOT NULL DEFAULT 'karyawan',
  `foto` VARCHAR(255) DEFAULT 'default.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: jabatan (Master Data Jabatan)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jabatan`;
CREATE TABLE `jabatan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_jabatan` VARCHAR(100) NOT NULL,
  `gaji_pokok` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tunjangan_jabatan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `uang_makan_per_hari` DECIMAL(15,2) NOT NULL DEFAULT 25000,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: karyawan (Master Data Karyawan)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `karyawan`;
CREATE TABLE `karyawan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `nip` VARCHAR(30) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `jabatan_id` INT NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL DEFAULT 'Laki-laki',
  `no_telepon` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `status_kepegawaian` ENUM('Tetap', 'Kontrak', 'Magang') NOT NULL DEFAULT 'Tetap',
  `tanggal_masuk` DATE NOT NULL,
  `no_rekening` VARCHAR(50) NOT NULL,
  `nama_bank` VARCHAR(50) NOT NULL DEFAULT 'BCA',
  `foto` VARCHAR(255) DEFAULT 'default.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`jabatan_id`),
  INDEX (`user_id`),
  CONSTRAINT `fk_karyawan_jabatan` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_karyawan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: absensi (Data Kehadiran & Lembur)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `absensi`;
CREATE TABLE `absensi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `karyawan_id` INT NOT NULL,
  `bulan` INT NOT NULL,
  `tahun` INT NOT NULL,
  `hadir` INT NOT NULL DEFAULT 0,
  `sakit` INT NOT NULL DEFAULT 0,
  `izin` INT NOT NULL DEFAULT 0,
  `alpa` INT NOT NULL DEFAULT 0,
  `lembur_jam` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_absensi_periode` (`karyawan_id`, `bulan`, `tahun`),
  INDEX (`karyawan_id`),
  CONSTRAINT `fk_absensi_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: gaji (Data Penggajian / Payroll)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `gaji`;
CREATE TABLE `gaji` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `karyawan_id` INT NOT NULL,
  `bulan` INT NOT NULL,
  `tahun` INT NOT NULL,
  `gaji_pokok` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tunjangan_jabatan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `uang_makan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `insentif_lembur` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `potongan_alpa` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `potongan_bpjs` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `potongan_pph21` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total_pendapatan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total_potongan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `take_home_pay` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status` ENUM('Pending', 'Dibayar') NOT NULL DEFAULT 'Pending',
  `tanggal_bayar` DATE DEFAULT NULL,
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_gaji_periode` (`karyawan_id`, `bulan`, `tahun`),
  INDEX (`karyawan_id`),
  CONSTRAINT `fk_gaji_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================================
-- DATA AWAL / SEEDER
-- ======================================================

-- 1. Insert Users
-- Default password:
-- admin -> admin123 ($2y$12$nB9o5QwPRJXcz9EKaNSBw.x/hVL8W.Rw1lNaKvvR7yYcqt0zmQcou)
-- karyawan -> password123 ($2y$12$ndEW.AJlcOSMO7IE2pYNjurK8cjvEaNW73XHpkKwFC.xzMPg5ZL3C)

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `foto`) VALUES
(1, 'admin', '$2y$12$nB9o5QwPRJXcz9EKaNSBw.x/hVL8W.Rw1lNaKvvR7yYcqt0zmQcou', 'Administrator HRD', 'admin', 'default.png'),
(2, 'EMP2026001', '$2y$12$ndEW.AJlcOSMO7IE2pYNjurK8cjvEaNW73XHpkKwFC.xzMPg5ZL3C', 'Budi Pratama, S.Kom', 'karyawan', 'default.png'),
(3, 'EMP2026002', '$2y$12$ndEW.AJlcOSMO7IE2pYNjurK8cjvEaNW73XHpkKwFC.xzMPg5ZL3C', 'Siti Rahmawati, S.E', 'karyawan', 'default.png'),
(4, 'EMP2026003', '$2y$12$ndEW.AJlcOSMO7IE2pYNjurK8cjvEaNW73XHpkKwFC.xzMPg5ZL3C', 'Rian Kurniawan', 'karyawan', 'default.png'),
(5, 'EMP2026004', '$2y$12$ndEW.AJlcOSMO7IE2pYNjurK8cjvEaNW73XHpkKwFC.xzMPg5ZL3C', 'Dewi Lestari, S.Sos', 'karyawan', 'default.png');

-- 2. Insert Jabatan
INSERT INTO `jabatan` (`id`, `nama_jabatan`, `gaji_pokok`, `tunjangan_jabatan`, `uang_makan_per_hari`) VALUES
(1, 'IT Supervisor & Senior Engineer', 8500000.00, 2500000.00, 35000.00),
(2, 'Finance & Accounting Officer', 6500000.00, 1800000.00, 30000.00),
(3, 'Staff Operasional & Logistik', 4800000.00, 1200000.00, 25000.00),
(4, 'Staff Administrasi & HR', 4500000.00, 1000000.00, 25000.00),
(5, 'UI/UX Designer & Frontend', 6200000.00, 1500000.00, 30000.00);

-- 3. Insert Karyawan
INSERT INTO `karyawan` (`id`, `user_id`, `nip`, `nama`, `email`, `jabatan_id`, `jenis_kelamin`, `no_telepon`, `alamat`, `status_kepegawaian`, `tanggal_masuk`, `no_rekening`, `nama_bank`, `foto`) VALUES
(1, 2, 'EMP2026001', 'Budi Pratama, S.Kom', 'budi.pratama@perusahaan.com', 1, 'Laki-laki', '081234567801', 'Jl. Sudirman No. 45, Jakarta Selatan', 'Tetap', '2023-01-15', '5320198821', 'BCA', 'default.png'),
(2, 3, 'EMP2026002', 'Siti Rahmawati, S.E', 'siti.rahma@perusahaan.com', 2, 'Perempuan', '081234567802', 'Jl. Thamrin No. 12, Jakarta Pusat', 'Tetap', '2023-03-01', '12400088912', 'Mandiri', 'default.png'),
(3, 4, 'EMP2026003', 'Rian Kurniawan', 'rian.k@perusahaan.com', 3, 'Laki-laki', '081234567803', 'Jl. Merdeka No. 88, Bogor', 'Kontrak', '2024-06-10', '0089123891', 'BRI', 'default.png'),
(4, 5, 'EMP2026004', 'Dewi Lestari, S.Sos', 'dewi.lestari@perusahaan.com', 4, 'Perempuan', '081234567804', 'Jl. Gatot Subroto No. 20, Depok', 'Tetap', '2024-02-01', '7100882312', 'BNI', 'default.png');

-- 4. Insert Absensi (Periode September 2026 & Oktober 2026)
INSERT INTO `absensi` (`karyawan_id`, `bulan`, `tahun`, `hadir`, `sakit`, `izin`, `alpa`, `lembur_jam`) VALUES
(1, 9, 2026, 22, 0, 0, 0, 8),
(2, 9, 2026, 21, 1, 0, 0, 4),
(3, 9, 2026, 20, 0, 1, 1, 10),
(4, 9, 2026, 22, 0, 0, 0, 2),
(1, 10, 2026, 20, 0, 1, 0, 6),
(2, 10, 2026, 21, 0, 0, 0, 5),
(3, 10, 2026, 19, 1, 0, 1, 8),
(4, 10, 2026, 21, 0, 0, 0, 0);

-- 5. Insert Gaji (Periode September 2026 - Sudah Dibayar)
-- Tarif kalkulasi:
-- Uang Makan = Hadir * Uang Makan Harian
-- Insentif Lembur = Lembur Jam * 25.000
-- Potongan Alpa = Alpa * 75.000
-- Potongan BPJS = 3% dari Gaji Pokok (2% Ketenagakerjaan + 1% Kesehatan)
-- Potongan PPh 21 = 2.5% dari (Gaji Pokok + Tunjangan)
INSERT INTO `gaji` (`karyawan_id`, `bulan`, `tahun`, `gaji_pokok`, `tunjangan_jabatan`, `uang_makan`, `insentif_lembur`, `potongan_alpa`, `potongan_bpjs`, `potongan_pph21`, `total_pendapatan`, `total_potongan`, `take_home_pay`, `status`, `tanggal_bayar`, `catatan`) VALUES
(1, 9, 2026, 8500000.00, 2500000.00, 770000.00, 200000.00, 0.00, 255000.00, 275000.00, 11970000.00, 530000.00, 11440000.00, 'Dibayar', '2026-09-28', 'Gaji bulan September 2026 telah ditransfer'),
(2, 9, 2026, 6500000.00, 1800000.00, 630000.00, 100000.00, 0.00, 195000.00, 207500.00, 9030000.00, 402500.00, 8627500.00, 'Dibayar', '2026-09-28', 'Gaji bulan September 2026 telah ditransfer'),
(3, 9, 2026, 4800000.00, 1200000.00, 500000.00, 250000.00, 75000.00, 144000.00, 150000.00, 6750000.00, 369000.00, 6381000.00, 'Dibayar', '2026-09-28', 'Gaji bulan September 2026 telah ditransfer'),
(4, 9, 2026, 4500000.00, 1000000.00, 550000.00, 50000.00, 0.00, 135000.00, 137500.00, 6100000.00, 272500.00, 5827500.00, 'Dibayar', '2026-09-28', 'Gaji bulan September 2026 telah ditransfer'),

-- Gaji Periode Oktober 2026 (Pending)
(1, 10, 2026, 8500000.00, 2500000.00, 700000.00, 150000.00, 0.00, 255000.00, 275000.00, 11850000.00, 530000.00, 11320000.00, 'Pending', NULL, 'Kalkulasi penggajian periode berjalan'),
(2, 10, 2026, 6500000.00, 1800000.00, 630000.00, 125000.00, 0.00, 195000.00, 207500.00, 9055000.00, 402500.00, 8652500.00, 'Pending', NULL, 'Kalkulasi penggajian periode berjalan'),
(3, 10, 2026, 4800000.00, 1200000.00, 475000.00, 200000.00, 75000.00, 144000.00, 150000.00, 6675000.00, 369000.00, 6306000.00, 'Pending', NULL, 'Kalkulasi penggajian periode berjalan'),
(4, 10, 2026, 4500000.00, 1000000.00, 525000.00, 0.00, 0.00, 135000.00, 137500.00, 6025000.00, 272500.00, 5752500.00, 'Pending', NULL, 'Kalkulasi penggajian periode berjalan');
