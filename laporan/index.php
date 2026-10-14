<?php
$page_title = 'Laporan Penggajian';
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
$total_makan_lembur = 0;
$total_potongan = 0;
$total_thp = 0;

foreach ($laporan as $row) {
    $total_gapok += (float)$row['gaji_pokok'];
    $total_tunjangan += (float)$row['tunjangan_jabatan'];
    $total_makan_lembur += ((float)$row['uang_makan'] + (float)$row['insentif_lembur']);
    $total_potongan += (float)$row['total_potongan'];
    $total_thp += (float)$row['take_home_pay'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-md-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Laporan Rekapitulasi Penggajian</h4>
        <p class="text-muted small mb-0">Rekapitulasi total pengeluaran payroll bulanan perusahaan per periode.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="<?= base_url("laporan/cetak.php?bulan={$bulan}&tahun={$tahun}") ?>" target="_blank" class="btn btn-primary-gradient btn-sm">
            <i class="fa-solid fa-print me-1"></i> Cetak Rekap Laporan (Print / PDF)
        </a>
    </div>
</div>

<!-- Periode Selector -->
<div class="card-modern p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-muted mb-1">Pilih Bulan:</label>
            <select name="bulan" class="form-select bg-light">
                <?php foreach (daftar_bulan() as $num => $nama_bln): ?>
                    <option value="<?= $num ?>" <?= ($bulan === $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Pilih Tahun:</label>
            <select name="tahun" class="form-select bg-light">
                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= ($tahun === $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-12 col-md-2 mt-md-4 pt-md-1">
            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Tampilkan</button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="text-muted small fw-semibold">TOTAL GAJI POKOK</span>
            <h5 class="fw-bold text-dark my-1"><?= format_rupiah($total_gapok) ?></h5>
            <small class="text-muted"><?= count($laporan) ?> Karyawan</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="text-muted small fw-semibold">TOTAL TUNJANGAN</span>
            <h5 class="fw-bold text-secondary my-1"><?= format_rupiah($total_tunjangan) ?></h5>
            <small class="text-muted">Tunjangan Jabatan</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="text-muted small fw-semibold">TOTAL POTONGAN</span>
            <h5 class="fw-bold text-danger my-1">- <?= format_rupiah($total_potongan) ?></h5>
            <small class="text-muted">BPJS, PPh 21 & Alpa</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="text-muted small fw-semibold">TOTAL TAKE HOME PAY</span>
            <h5 class="fw-bold text-primary my-1"><?= format_rupiah($total_thp) ?></h5>
            <small class="text-muted">Beban Kas Perusahaan</small>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="card-modern">
    <div class="card-header-clean d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Rekap Gaji Periode <?= bulan_indonesia($bulan) . ' ' . $tahun ?>
        </h6>
        <span class="badge bg-light text-muted border"><?= count($laporan) ?> Baris Data</span>
    </div>

    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIP & Karyawan</th>
                    <th>Jabatan</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan</th>
                    <th>Makan & Lembur</th>
                    <th>Total Potongan</th>
                    <th>Take Home Pay</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 mb-2 d-block text-secondary"></i>
                            Tidak ada data penggajian untuk periode <?= bulan_indonesia($bulan) . ' ' . $tahun ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($laporan as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($row['nama_karyawan']) ?></strong>
                                <div class="text-muted small font-monospace"><?= htmlspecialchars($row['nip']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_jabatan']) ?></span></td>
                            <td><?= format_rupiah($row['gaji_pokok']) ?></td>
                            <td><?= format_rupiah($row['tunjangan_jabatan']) ?></td>
                            <td><?= format_rupiah($row['uang_makan'] + $row['insentif_lembur']) ?></td>
                            <td class="text-danger">- <?= format_rupiah($row['total_potongan']) ?></td>
                            <td><strong class="text-primary"><?= format_rupiah($row['take_home_pay']) ?></strong></td>
                            <td>
                                <?php if ($row['status'] === 'Dibayar'): ?>
                                    <span class="badge badge-soft-success">Dibayar</span>
                                <?php else: ?>
                                    <span class="badge badge-soft-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($laporan)): ?>
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                        <td><?= format_rupiah($total_gapok) ?></td>
                        <td><?= format_rupiah($total_tunjangan) ?></td>
                        <td><?= format_rupiah($total_makan_lembur) ?></td>
                        <td class="text-danger">- <?= format_rupiah($total_potongan) ?></td>
                        <td class="text-primary fs-6"><?= format_rupiah($total_thp) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
