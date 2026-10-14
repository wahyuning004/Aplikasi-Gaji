# 💼 Payroll Pro - Sistem Informasi Penggajian Karyawan

Aplikasi web sistem penggajian karyawan (Payroll System) berbasis **PHP & MySQL** dengan arsitektur modular yang rapi, aman, dan siap pakai di lingkungan localhost **Laragon** atau **XAMPP**. Dilengkapi antarmuka modern yang responsif menggunakan Bootstrap 5, FontAwesome 6, dan analitik interaktif Chart.js.

> **Pengembang Aplikasi:**  
> **Nama:** Eka Wahyuning Tiyasa  
> **NIM:** 231011400032  
> **Program Studi:** Teknik Informatika  
> **Perguruan Tinggi:** Universitas Pamulang (UNPAM)

---

## 🚀 Fitur Utama & Modul Sistem

### 1. 🔐 Autentikasi & Hak Akses (RBAC)
- **Multi-Role Login**:
  - **Admin (HRD / Keuangan)**: Akses penuh ke seluruh data master, input kehadiran, generate penggajian, laporan keuangan, dan manajemen user.
  - **Karyawan**: Akses khusus untuk melihat profil pribadi, ringkasan kehadiran bulanan, riwayat gaji, dan cetak slip gaji pribadi.
- **Proteksi Session & CSRF**: Seluruh form transaksi dilindungi dengan validasi token anti-CSRF dan session authentication guard.
- **Tombol Demo Akun Cepat**: Tersedia tombol otomatis pengisi formulir login untuk kemudahan demonstrasi.

### 2. 📊 Dashboard Interaktif & Analitik
- **Admin**:
  - Statistik KPI: Total Karyawan Aktif, Total Pengeluaran Kas Gaji Bulan Ini, Rekap Kehadiran (Hari & Lembur), dan Status Pembayaran.
  - **Grafik Tren Pengeluaran**: Visualisasi histori pengeluaran gaji selama 6 bulan terakhir.
  - **Grafik Donut Rekap Absensi**: Visualisasi persentase kehadiran (Hadir, Sakit, Izin, Alpa).
  - Tabel cepat slip gaji bulan berjalan dan shortcut olah gaji.
- **Karyawan**:
  - Kartu identitas pegawai, nomor rekening payroll, rincian slip bulan berjalan, dan riwayat gaji terdahulu.

### 3. 👥 Master Data (CRUD Lengkap)
- **Data Jabatan (`/jabatan/`)**:
  - Kelola nama jabatan, gaji pokok, tunjangan jabatan, dan uang makan harian.
  - Dilengkapi proteksi integritas data (tidak bisa dihapus jika masih digunakan oleh karyawan aktif).
- **Data Karyawan (`/karyawan/`)**:
  - Kelola NIP, nama lengkap, email, penempatan jabatan, jenis kelamin, status kepegawaian (Tetap, Kontrak, Magang), tanggal masuk kerja, nomor rekening, nama bank, dan foto profil.
  - Otomatis membuatkan akun pengguna (*User Account*) sehingga karyawan dapat langsung login ke portal mandiri menggunakan NIP sebagai username.

### 4. 📅 Transaksi Kehadiran & Lembur (`/absensi/`)
- Filter fleksibel per periode Bulan & Tahun.
- **Fitur Batch Input Massal**: HRD dapat mengisi jumlah hari Hadir, Sakit, Izin, Alpa, dan jam lembur semua karyawan dalam satu layar tanpa perlu input satu per satu.
- Tombol pintar: *Set Semua Hadir 22 Hari* untuk mempercepat administrasi kehadiran.

### 5. 💰 Olah Gaji Otomatis (Payroll Engine) (`/penggajian/`)
- **Formula Hitung Otomatis**:
  - `Total Pendapatan Kotor` = Gaji Pokok + Tunjangan Jabatan + (Hari Hadir × Uang Makan) + (Jam Lembur × Rp 25.000)
  - `Total Potongan` = (Hari Alpa × Rp 75.000) + BPJS (3% Gaji Pokok) + PPh 21 (2.5% Gaji Pokok & Tunjangan)
  - `Take Home Pay (Gaji Bersih)` = Total Pendapatan Kotor − Total Potongan
- **Manajemen Status Pembayaran**: Toggle status antara **Pending** dan **Dibayar** (tanggal transfer otomatis tercatat saat status diubah menjadi dibayar).

### 6. 🖨️ Cetak Slip Gaji & Laporan Bulanan
- **Slip Gaji Karyawan (`/penggajian/slip.php`)**:
  - Format slip gaji standar perusahaan resmi dengan Kop Surat, nomor referensi slip, rincian komponen penerimaan vs potongan, dan tanggal transfer.
  - Terbilang rupiah otomatis (contoh: *"# Sebelas Juta Tiga Ratus Dua Puluh Ribu Rupiah #"*).
  - Tanda tangan penerima karyawan & pengesahan manajemen HRD.
  - Tombol cetak langsung ke printer atau simpan sebagai file PDF (`@media print` siap cetak).
- **Laporan Rekapitulasi Penggajian Bulanan (`/laporan/`)**:
  - Rekap menyeluruh seluruh komponen gaji dan grand total pengeluaran per periode.
  - Versi cetak formal format **A4 Landscape** (`/laporan/cetak.php`) lengkap dengan tanda tangan pembuat, pemeriksa, dan direktur utama.

---

## 📂 Struktur Folder Proyek

```text
Aplikasi Gaji/
├── assets/
│   ├── css/
│   │   └── style.css            # Desain kustom, tema modern, dan styling cetak (@media print)
│   ├── js/
│   │   └── main.js              # Interaktivitas UI, sidebar toggling, dan format rupiah
│   └── uploads/                 # Direktori penyimpanan foto karyawan
├── config/
│   ├── database.php             # Koneksi database PDO MySQL
│   └── app.php                  # Helper functions (base_url, format_rupiah, terbilang, auth, flash)
├── includes/
│   ├── header.php               # Komponen header, navbar, dan sidebar navigasi
│   ├── footer.php               # Komponen footer dan pemanggil skrip JS
│   └── alerts.php               # Komponen flash alert notifikasi
├── absensi/
│   ├── index.php                # Rekapitulasi absensi per periode bulan/tahun
│   └── input.php                # Formulir input absensi massal karyawan
├── jabatan/
│   ├── index.php                # Daftar master jabatan & skala upah
│   ├── create.php               # Tambah jabatan baru
│   ├── edit.php                 # Edit jabatan & upah
│   └── delete.php               # Hapus jabatan
├── karyawan/
│   ├── index.php                # Daftar karyawan (pencarian & filter jabatan/status)
│   ├── create.php               # Tambah karyawan baru + buat akun user
│   ├── edit.php                 # Edit data profil, rekening & reset password
│   └── delete.php               # Hapus data karyawan beserta akun
├── penggajian/
│   ├── index.php                # Dashboard olah gaji & monitoring status bayar
│   ├── generate.php             # Engine kalkulasi gaji otomatis berbasis absensi
│   ├── update_status.php        # Toggle status pembayaran (Pending <-> Dibayar)
│   └── slip.php                 # Tampilan cetak slip gaji karyawan formal
├── laporan/
│   ├── index.php                # Rekap laporan bulanan
│   └── cetak.php                # Halaman siap print/PDF rekap bulanan A4 Landscape
├── profile.php                  # Pengaturan akun pribadi & ganti password
├── index.php                    # Redirector utama
├── login.php                    # Halaman autentikasi login multi-role
├── logout.php                   # Logout & penghapusan sesi
├── dashboard.php                # Dashboard utama (Admin & Karyawan)
├── database.sql                 # Skrip skema database & data dummy awal
└── README.md                    # Dokumentasi panduan instalasi & penggunaan
```

---

## 🔑 Informasi Akun Pengguna (Demo Login)

| Role | Username / NIP | Password | Nama Lengkap | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Administrator HRD | Hak akses penuh seluruh modul sistem |
| **Karyawan** | `EMP2026001` | `password123` | Budi Pratama, S.Kom | IT Supervisor (Akses slip & profil) |
| **Karyawan** | `EMP2026002` | `password123` | Siti Rahmawati, S.E | Finance Officer (Akses slip & profil) |
| **Karyawan** | `EMP2026003` | `password123` | Rian Kurniawan | Staff Operasional (Akses slip & profil) |
| **Karyawan** | `EMP2026004` | `password123` | Dewi Lestari, S.Sos | Staff HR & Admin (Akses slip & profil) |

---

## ⚙️ Cara Menjalankan di Laragon / XAMPP

1. **Jalankan Apache & MySQL** pada Laragon / XAMPP.
2. Database **`db_penggajian`** sudah otomatis dibuat dan diisi data awal.
   *(Jika ingin mengimpor ulang secara manual, buka phpMyAdmin atau MySQL CLI lalu import file [database.sql](file:///d:/laragon/www/Aplikasi%20Gaji/database.sql)).*
3. Buka browser dan akses URL:
   ```text
   http://localhost/Aplikasi Gaji/
   ```
   *Atau jika menggunakan Laragon Virtual Host:*
   ```text
   http://aplikasi-gaji.test/
   ```
4. Masuk dengan akun **Admin** (`admin` / `admin123`) untuk mengelola seluruh data, atau salah satu akun **Karyawan** untuk melihat portal mandiri pegawai.
