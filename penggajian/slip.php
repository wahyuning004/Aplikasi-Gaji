<?php
$page_title = 'Slip Gaji Karyawan';
require_once __DIR__ . '/../config/app.php';
require_login();

$user = auth_user();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    if (is_admin()) {
        $stmt = $pdo->prepare("SELECT g.*, k.nama as nama_karyawan, k.nip, k.email, k.status_kepegawaian, k.no_rekening, k.nama_bank, j.nama_jabatan 
            FROM gaji g 
            JOIN karyawan k ON g.karyawan_id = k.id 
            JOIN jabatan j ON k.jabatan_id = j.id 
            WHERE g.id = ? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT g.*, k.nama as nama_karyawan, k.nip, k.email, k.status_kepegawaian, k.no_rekening, k.nama_bank, j.nama_jabatan 
            FROM gaji g 
            JOIN karyawan k ON g.karyawan_id = k.id 
            JOIN jabatan j ON k.jabatan_id = j.id 
            WHERE g.id = ? AND g.karyawan_id = ? LIMIT 1");
        $stmt->execute([$id, $user['karyawan_id']]);
    }
    $slip = $stmt->fetch();
} else {
    // If no id, for employee get latest salary
    if (!is_admin()) {
        $stmt = $pdo->prepare("SELECT g.*, k.nama as nama_karyawan, k.nip, k.email, k.status_kepegawaian, k.no_rekening, k.nama_bank, j.nama_jabatan 
            FROM gaji g 
            JOIN karyawan k ON g.karyawan_id = k.id 
            JOIN jabatan j ON k.jabatan_id = j.id 
            WHERE g.karyawan_id = ? 
            ORDER BY g.tahun DESC, g.bulan DESC LIMIT 1");
        $stmt->execute([$user['karyawan_id']]);
        $slip = $stmt->fetch();
    } else {
        set_flash('warning', 'Pilih slip gaji yang ingin dicetak.');
        header('Location: ' . base_url('penggajian/index.php'));
        exit;
    }
}

if (!$slip) {
    set_flash('danger', 'Data slip gaji tidak ditemukan atau belum diterbitkan.');
    header('Location: ' . (is_admin() ? base_url('penggajian/index.php') : base_url('dashboard.php')));
    exit;
}

// Fetch attendance details for this employee in that period
$stmtAbsen = $pdo->prepare("SELECT * FROM absensi WHERE karyawan_id = ? AND bulan = ? AND tahun = ? LIMIT 1");
$stmtAbsen->execute([$slip['karyawan_id'], $slip['bulan'], $slip['tahun']]);
$absen = $stmtAbsen->fetch();

$hadir = (int)($absen['hadir'] ?? 0);
$sakit = (int)($absen['sakit'] ?? 0);
$izin  = (int)($absen['izin'] ?? 0);
$alpa  = (int)($absen['alpa'] ?? 0);
$lembur = (int)($absen['lembur_jam'] ?? 0);

$terbilang_rupiah = terbilang($slip['take_home_pay']) . ' Rupiah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?= htmlspecialchars($slip['nama_karyawan']) ?> (<?= bulan_indonesia($slip['bulan']) . ' ' . $slip['tahun'] ?>)</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body {
            background-color: #f1f5f9;
            padding: 2rem 0;
        }
        .slip-box {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .kop-surat {
            border-bottom: 3px double #334155;
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .table-slip th {
            background-color: #f8fafc;
            color: #334155;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .table-slip td {
            font-size: 0.9rem;
            padding: 0.6rem 0.75rem;
        }
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .slip-box {
                box-shadow: none !important;
                border: none !important;
                padding: 1.5rem !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action Bar (Hidden on print) -->
    <div class="no-print d-flex justify-content-between align-items-center mb-4 max-w-820 mx-auto" style="max-width: 820px;">
        <a href="<?= is_admin() ? base_url("penggajian/index.php?bulan={$slip['bulan']}&tahun={$slip['tahun']}") : base_url('dashboard.php') ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary-gradient px-4">
                <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Official Payslip Box -->
    <div class="slip-box">
        <!-- Kop Surat Perusahaan -->
        <div class="kop-surat d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-building-columns fs-3"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark tracking-tight">PT SINAR MULTI KARYA UTAMA</h4>
                    <small class="text-muted d-block">Pusat Bisnis Perkantoran Lantai 12, Jl. Jendral Sudirman No. 45, Jakarta Selatan</small>
                    <small class="text-muted d-block">Telp: (021) 555-0199 | Email: hrd@sinarmulti.co.id</small>
                </div>
            </div>
            <div class="text-end">
                <span class="badge <?= $slip['status'] === 'Dibayar' ? 'badge-soft-success' : 'badge-soft-warning' ?> badge-status">
                    <?= $slip['status'] === 'Dibayar' ? 'LUNAS / DIBAYAR' : 'MENUNGGU PEMBAYARAN' ?>
                </span>
                <div class="text-muted small mt-1">Ref No: #PAY-<?= str_pad($slip['id'], 5, '0', STR_PAD_LEFT) ?></div>
            </div>
        </div>

        <!-- Judul Slip -->
        <div class="text-center my-3">
            <h5 class="fw-bold text-dark text-uppercase mb-1" style="letter-spacing: 0.05em;">SLIP GAJI KARYAWAN</h5>
            <div class="text-muted small fw-semibold">PERIODE: <?= strtoupper(bulan_indonesia($slip['bulan'])) . ' ' . $slip['tahun'] ?></div>
        </div>

        <!-- Informasi Karyawan & Absensi -->
        <div class="row g-3 p-3 bg-light rounded-3 mb-4 small">
            <div class="col-6 col-md-3">
                <span class="text-muted d-block">Nomor Induk Pegawai:</span>
                <strong class="text-dark font-monospace"><?= htmlspecialchars($slip['nip']) ?></strong>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted d-block">Nama Karyawan:</span>
                <strong class="text-dark"><?= htmlspecialchars($slip['nama_karyawan']) ?></strong>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted d-block">Jabatan:</span>
                <strong class="text-dark"><?= htmlspecialchars($slip['nama_jabatan']) ?></strong>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted d-block">Status Pegawai:</span>
                <strong class="text-dark"><?= htmlspecialchars($slip['status_kepegawaian']) ?></strong>
            </div>
            <div class="col-6 col-md-6 border-top pt-2">
                <span class="text-muted d-block">Rekening Pembayaran:</span>
                <strong class="text-dark"><?= htmlspecialchars($slip['nama_bank']) ?> - <?= htmlspecialchars($slip['no_rekening']) ?></strong>
            </div>
            <div class="col-6 col-md-6 border-top pt-2">
                <span class="text-muted d-block">Rekap Kehadiran Bulan Ini:</span>
                <strong class="text-dark">Hadir: <?= $hadir ?> | Sakit: <?= $sakit ?> | Izin: <?= $izin ?> | Alpa: <?= $alpa ?> | Lembur: <?= $lembur ?> Jam</strong>
            </div>
        </div>

        <!-- Rincian Penghasilan & Potongan -->
        <div class="row g-4 mb-4">
            <!-- Kolom Penerimaan -->
            <div class="col-12 col-md-6">
                <div class="border rounded-3 p-3 h-100">
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-2"><i class="fa-solid fa-plus-circle me-1"></i> PENERIMAAN (PENGHASILAN)</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Gaji Pokok</td>
                                <td class="text-end fw-semibold text-dark"><?= format_rupiah($slip['gaji_pokok']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tunjangan Jabatan</td>
                                <td class="text-end fw-semibold text-dark"><?= format_rupiah($slip['tunjangan_jabatan']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Uang Makan (<?= $hadir ?> Hari)</td>
                                <td class="text-end fw-semibold text-dark"><?= format_rupiah($slip['uang_makan']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Insentif Lembur (<?= $lembur ?> Jam)</td>
                                <td class="text-end fw-semibold text-dark"><?= format_rupiah($slip['insentif_lembur']) ?></td>
                            </tr>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <th class="text-dark pt-2">Total Penerimaan Kotor</th>
                                <th class="text-end text-success pt-2"><?= format_rupiah($slip['total_pendapatan']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Kolom Potongan -->
            <div class="col-12 col-md-6">
                <div class="border rounded-3 p-3 h-100">
                    <h6 class="fw-bold text-danger border-bottom pb-2 mb-2"><i class="fa-solid fa-minus-circle me-1"></i> POTONGAN GAJI</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Potongan Alpa (<?= $alpa ?> Hari)</td>
                                <td class="text-end fw-semibold text-dark"><?= format_rupiah($slip['potongan_alpa']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">BPJS (Ketenagakerjaan & Kes.)</td>
                                <td class="text-end fw-semibold text-dark"><?= format_rupiah($slip['potongan_bpjs']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">PPh 21 (Pajak Penghasilan)</td>
                                <td class="text-end fw-semibold text-dark"><?= format_rupiah($slip['potongan_pph21']) ?></td>
                            </tr>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <th class="text-dark pt-2">Total Potongan</th>
                                <th class="text-end text-danger pt-2">- <?= format_rupiah($slip['total_potongan']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Take Home Pay Box -->
        <div class="p-3 rounded-3 mb-4" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-semibold">GAJI BERSIH DITERIMA (TAKE HOME PAY)</span>
                    <h3 class="fw-bold mb-0 text-white mt-1"><?= format_rupiah($slip['take_home_pay']) ?></h3>
                </div>
                <div class="text-end d-none d-sm-block">
                    <span class="badge bg-white text-dark px-3 py-2 fw-semibold">
                        <i class="fa-regular fa-calendar-check me-1"></i> <?= $slip['tanggal_bayar'] ? 'Dibayar: ' . format_tanggal($slip['tanggal_bayar']) : 'Belum Ditransfer' ?>
                    </span>
                </div>
            </div>
            <div class="mt-2 pt-2 border-top border-white-25 small" style="border-color: rgba(255,255,255,0.15) !important;">
                <em>Terbilang: # <?= htmlspecialchars($terbilang_rupiah) ?> #</em>
            </div>
        </div>

        <!-- Tanda Tangan Dokumen -->
        <div class="row text-center mt-5 pt-3">
            <div class="col-6">
                <div class="small text-muted mb-5">Diterima Oleh Karyawan,</div>
                <div class="fw-bold text-dark text-decoration-underline"><?= htmlspecialchars($slip['nama_karyawan']) ?></div>
                <small class="text-muted font-monospace"><?= htmlspecialchars($slip['nip']) ?></small>
            </div>
            <div class="col-6">
                <div class="small text-muted mb-5">Jakarta, <?= date('d') . ' ' . bulan_indonesia((int)date('m')) . ' ' . date('Y') ?><br>HRD & Finance Manager,</div>
                <div class="fw-bold text-dark text-decoration-underline">Administrator HRD</div>
                <small class="text-muted">Head of Human Resources</small>
            </div>
        </div>

        <!-- Footer / Watermark Slip Gaji -->
        <div class="mt-5 pt-3 border-top text-center text-muted" style="font-size: 0.72rem;">
            Dokumen ini dicetak secara otomatis melalui <strong>Sistem Informasi Penggajian (Payroll Pro)</strong>.<br>
            Hak Cipta &copy; <?= date('Y') ?> &bull; Dikembangkan oleh: <strong>Eka Wahyuning Tiyasa</strong> (NIM: <strong>231011400032</strong>) &bull; Program Studi Teknik Informatika - Universitas Pamulang.
        </div>
    </div>
</div>

</body>
</html>
