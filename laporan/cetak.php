<?php
$page_title = 'Cetak Rekap Laporan Penggajian';
require_once __DIR__ . '/../config/app.php';
require_admin();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$stmt = $pdo->prepare("SELECT g.*, k.nama as nama_karyawan, k.nip, k.status_kepegawaian, k.nama_bank, k.no_rekening, j.nama_jabatan 
    FROM gaji g 
    JOIN karyawan k ON g.karyawan_id = k.id 
    JOIN jabatan j ON k.jabatan_id = j.id 
    WHERE g.bulan = ? AND g.tahun = ? 
    ORDER BY k.nama ASC");
$stmt->execute([$bulan, $tahun]);
$laporan = $stmt->fetchAll();

// Totals
$total_gapok = 0;
$total_tunjangan = 0;
$total_makan = 0;
$total_lembur = 0;
$total_pot_alpa = 0;
$total_pot_bpjs = 0;
$total_pot_pph21 = 0;
$total_potongan = 0;
$total_thp = 0;

foreach ($laporan as $row) {
    $total_gapok += (float)$row['gaji_pokok'];
    $total_tunjangan += (float)$row['tunjangan_jabatan'];
    $total_makan += (float)$row['uang_makan'];
    $total_lembur += (float)$row['insentif_lembur'];
    $total_pot_alpa += (float)$row['potongan_alpa'];
    $total_pot_bpjs += (float)$row['potongan_bpjs'];
    $total_pot_pph21 += (float)$row['potongan_pph21'];
    $total_potongan += (float)$row['total_potongan'];
    $total_thp += (float)$row['take_home_pay'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Penggajian <?= bulan_indonesia($bulan) . ' ' . $tahun ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
        }
        .print-container {
            max-width: 1100px;
            margin: 20px auto;
            background: #ffffff;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .kop-surat {
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        table {
            font-size: 0.82rem;
        }
        table th {
            background-color: #f1f5f9 !important;
            text-align: center;
            vertical-align: middle;
        }
        @media print {
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .print-container {
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                padding: 10px !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 landscape;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Action buttons -->
    <div class="no-print d-flex justify-content-between align-items-center mb-3 mx-auto" style="max-width: 1100px;">
        <a href="<?= base_url("laporan/index.php?bulan={$bulan}&tahun={$tahun}") ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Laporan
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
            <i class="fa-solid fa-print me-1"></i> Cetak / Print Dokumen
        </button>
    </div>

    <div class="print-container">
        <!-- Kop Surat -->
        <div class="kop-surat d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-dark text-white p-2 rounded text-center" style="width: 50px; height: 50px;">
                    <i class="fa-solid fa-building-columns fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">PT SINAR MULTI KARYA UTAMA</h5>
                    <div style="font-size: 0.8rem;" class="text-muted">Pusat Bisnis Perkantoran Lantai 12, Jl. Jendral Sudirman No. 45, Jakarta Selatan</div>
                    <div style="font-size: 0.8rem;" class="text-muted">Telepon: (021) 555-0199 | Email: payroll@sinarmulti.co.id</div>
                </div>
            </div>
            <div class="text-end" style="font-size: 0.8rem;">
                <div><strong>Lampiran:</strong> Rekapitulasi Payroll</div>
                <div><strong>Tanggal Cetak:</strong> <?= format_tanggal(date('Y-m-d')) ?></div>
            </div>
        </div>

        <!-- Judul Laporan -->
        <div class="text-center mb-4">
            <h5 class="fw-bold text-uppercase mb-1">LAPORAN REKAPITULASI PENGGAJIAN KARYAWAN</h5>
            <div class="fw-semibold text-secondary small">PERIODE BULAN: <?= strtoupper(bulan_indonesia($bulan)) . ' ' . $tahun ?></div>
        </div>

        <!-- Tabel Data -->
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 35px;">No</th>
                        <th rowspan="2">NIP</th>
                        <th rowspan="2">Nama Karyawan</th>
                        <th rowspan="2">Jabatan</th>
                        <th colspan="4" class="text-center bg-light">Penghasilan / Penerimaan</th>
                        <th colspan="3" class="text-center bg-light">Potongan</th>
                        <th rowspan="2">Take Home Pay</th>
                        <th rowspan="2" style="width: 75px;">Status</th>
                    </tr>
                    <tr>
                        <th>Gaji Pokok</th>
                        <th>Tunjangan</th>
                        <th>Uang Makan</th>
                        <th>Lembur</th>
                        <th>Alpa</th>
                        <th>BPJS (3%)</th>
                        <th>PPh 21</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan)): ?>
                        <tr>
                            <td colspan="13" class="text-center py-4 text-muted">Belum ada data penggajian untuk periode terpilih.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($laporan as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="font-monospace text-center"><?= htmlspecialchars($row['nip']) ?></td>
                                <td><strong><?= htmlspecialchars($row['nama_karyawan']) ?></strong></td>
                                <td><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                                <td class="text-end"><?= format_rupiah($row['gaji_pokok'], false) ?></td>
                                <td class="text-end"><?= format_rupiah($row['tunjangan_jabatan'], false) ?></td>
                                <td class="text-end"><?= format_rupiah($row['uang_makan'], false) ?></td>
                                <td class="text-end"><?= format_rupiah($row['insentif_lembur'], false) ?></td>
                                <td class="text-end text-danger"><?= format_rupiah($row['potongan_alpa'], false) ?></td>
                                <td class="text-end text-danger"><?= format_rupiah($row['potongan_bpjs'], false) ?></td>
                                <td class="text-end text-danger"><?= format_rupiah($row['potongan_pph21'], false) ?></td>
                                <td class="text-end fw-bold"><?= format_rupiah($row['take_home_pay'], false) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $row['status'] === 'Dibayar' ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size: 0.7rem;">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($laporan)): ?>
                    <tfoot class="fw-bold bg-light">
                        <tr>
                            <td colspan="4" class="text-center">TOTAL KESELURUHAN (Rp)</td>
                            <td class="text-end"><?= format_rupiah($total_gapok, false) ?></td>
                            <td class="text-end"><?= format_rupiah($total_tunjangan, false) ?></td>
                            <td class="text-end"><?= format_rupiah($total_makan, false) ?></td>
                            <td class="text-end"><?= format_rupiah($total_lembur, false) ?></td>
                            <td class="text-end text-danger"><?= format_rupiah($total_pot_alpa, false) ?></td>
                            <td class="text-end text-danger"><?= format_rupiah($total_pot_bpjs, false) ?></td>
                            <td class="text-end text-danger"><?= format_rupiah($total_pot_pph21, false) ?></td>
                            <td class="text-end text-primary fw-bold"><?= format_rupiah($total_thp, false) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Terbilang Total Pengeluaran -->
        <?php if (!empty($laporan)): ?>
            <div class="p-2 border rounded bg-light mb-4" style="font-size: 0.85rem;">
                <strong>Total Pengeluaran Kas (THP):</strong> <?= format_rupiah($total_thp) ?>
                <span class="ms-2 fst-italic text-muted">(Terbilang: # <?= terbilang($total_thp) ?> Rupiah #)</span>
            </div>
        <?php endif; ?>

        <!-- Tanda Tangan Pejabat Berwenang -->
        <div class="row text-center mt-4 pt-2" style="font-size: 0.85rem;">
            <div class="col-4">
                <div class="mb-5">Dibuat Oleh,<br><strong>Staff HRD & Payroll</strong></div>
                <div class="fw-bold text-decoration-underline">Dewi Lestari, S.Sos</div>
                <div class="text-muted">Staff Administrasi & HR</div>
            </div>
            <div class="col-4">
                <div class="mb-5">Diperiksa Oleh,<br><strong>Finance & Accounting Manager</strong></div>
                <div class="fw-bold text-decoration-underline">Siti Rahmawati, S.E</div>
                <div class="text-muted">Finance & Accounting Officer</div>
            </div>
            <div class="col-4">
                <div class="mb-5">Jakarta, <?= date('d') . ' ' . bulan_indonesia((int)date('m')) . ' ' . date('Y') ?><br>Disetujui Oleh,<br><strong>Direktur Utama</strong></div>
                <div class="fw-bold text-decoration-underline">Ir. H. Hendra Wijaya, M.M</div>
                <div class="text-muted">Direktur Utama</div>
            </div>
        </div>

        <!-- Footer Rekapitulasi Laporan -->
        <div class="mt-4 pt-2 border-top text-center text-muted" style="font-size: 0.72rem;">
            Dicetak melalui Sistem Informasi Penggajian (Payroll Pro) &bull; Hak Cipta &copy; <?= date('Y') ?> <strong>Eka Wahyuning Tiyasa</strong> (NIM: <strong>231011400032</strong>) &bull; Program Studi Teknik Informatika - Universitas Pamulang.
        </div>
    </div>
</div>

</body>
</html>
