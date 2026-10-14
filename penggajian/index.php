<?php
$page_title = 'Olah Gaji (Payroll)';
require_once __DIR__ . '/../config/app.php';
require_admin();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Fetch payroll data for this period
$stmt = $pdo->prepare("SELECT g.*, k.nama as nama_karyawan, k.nip, k.nama_bank, k.no_rekening, j.nama_jabatan 
    FROM gaji g 
    JOIN karyawan k ON g.karyawan_id = k.id 
    JOIN jabatan j ON k.jabatan_id = j.id 
    WHERE g.bulan = ? AND g.tahun = ? 
    ORDER BY k.nama ASC");
$stmt->execute([$bulan, $tahun]);
$gaji_list = $stmt->fetchAll();

// Summary stats for this period
$total_thp = 0;
$total_dibayar = 0;
$total_pending = 0;

foreach ($gaji_list as $row) {
    $total_thp += (float)$row['take_home_pay'];
    if ($row['status'] === 'Dibayar') {
        $total_dibayar++;
    } else {
        $total_pending++;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-md-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Olah Gaji Karyawan (Payroll)</h4>
        <p class="text-muted small mb-0">Hitung otomatis gaji pokok, tunjangan, uang makan, insentif lembur, dan potongan pajak/BPJS.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="<?= base_url("penggajian/generate.php?bulan={$bulan}&tahun={$tahun}") ?>" class="btn btn-primary-gradient btn-sm" onclick="return confirm('Kalkulasi ulang seluruh gaji karyawan periode <?= bulan_indonesia($bulan) . ' ' . $tahun ?>?')">
            <i class="fa-solid fa-calculator me-1"></i> Hitung Otomatis Periode Ini
        </a>
        <a href="<?= base_url("laporan/cetak.php?bulan={$bulan}&tahun={$tahun}") ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-print me-1"></i> Cetak Rekap
        </a>
    </div>
</div>

<!-- Periode Selector -->
<div class="card-modern p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-muted mb-1">Bulan Gaji:</label>
            <select name="bulan" class="form-select bg-light">
                <?php foreach (daftar_bulan() as $num => $nama_bln): ?>
                    <option value="<?= $num ?>" <?= ($bulan === $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Tahun Gaji:</label>
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

<!-- Stats for selected period -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Total Pengeluaran Gaji</span>
                    <h4 class="fw-bold my-1 text-primary"><?= format_rupiah($total_thp) ?></h4>
                    <span class="text-muted small">Periode <?= bulan_indonesia($bulan) . ' ' . $tahun ?></span>
                </div>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Gaji Telah Dibayar</span>
                    <h4 class="fw-bold my-1 text-success"><?= $total_dibayar ?> Slip</h4>
                    <span class="text-success small"><i class="fa-solid fa-check-circle me-1"></i> Siap transfer / lunas</span>
                </div>
                <div class="stat-icon-wrapper bg-success-subtle text-success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Menunggu Pembayaran</span>
                    <h4 class="fw-bold my-1 text-warning"><?= $total_pending ?> Slip</h4>
                    <span class="text-warning small"><i class="fa-solid fa-hourglass-half me-1"></i> Status Pending</span>
                </div>
                <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Table -->
<div class="card-modern">
    <div class="card-header-clean d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-money-check-dollar text-primary me-2"></i> Rincian Penggajian Karyawan
        </h6>
        <span class="badge bg-light text-muted border"><?= count($gaji_list) ?> Slip Dihasilkan</span>
    </div>

    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Jabatan</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan</th>
                    <th>Lembur & Makan</th>
                    <th>Potongan</th>
                    <th>Take Home Pay</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gaji_list)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-receipt fs-2 mb-2 d-block text-secondary"></i>
                            Belum ada kalkulasi gaji untuk periode <?= bulan_indonesia($bulan) . ' ' . $tahun ?>.<br>
                            <a href="<?= base_url("penggajian/generate.php?bulan={$bulan}&tahun={$tahun}") ?>" class="btn btn-sm btn-primary mt-2">
                                <i class="fa-solid fa-bolt me-1"></i> Hitung Gaji Periode Ini Sekarang
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gaji_list as $row): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($row['nama_karyawan']) ?></strong>
                                <div class="text-muted small font-monospace"><?= htmlspecialchars($row['nip']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_jabatan']) ?></span></td>
                            <td class="text-dark"><?= format_rupiah($row['gaji_pokok']) ?></td>
                            <td class="text-secondary"><?= format_rupiah($row['tunjangan_jabatan']) ?></td>
                            <td class="text-muted small">
                                <div>Makan: <?= format_rupiah($row['uang_makan']) ?></div>
                                <div>Lembur: <?= format_rupiah($row['insentif_lembur']) ?></div>
                            </td>
                            <td class="text-danger small">
                                <div>- <?= format_rupiah($row['total_potongan']) ?></div>
                                <span class="text-muted" style="font-size: 0.75rem;">(BPJS, Pajak, Alpa)</span>
                            </td>
                            <td>
                                <strong class="text-primary fs-6"><?= format_rupiah($row['take_home_pay']) ?></strong>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'Dibayar'): ?>
                                    <a href="<?= base_url("penggajian/update_status.php?id={$row['id']}&csrf=" . csrf_token()) ?>" class="badge badge-soft-success text-decoration-none" title="Klik untuk ubah jadi Pending" onclick="return confirm('Ubah status menjadi Pending?')">
                                        <i class="fa-solid fa-check me-1"></i> Dibayar
                                    </a>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= format_tanggal($row['tanggal_bayar']) ?></div>
                                <?php else: ?>
                                    <a href="<?= base_url("penggajian/update_status.php?id={$row['id']}&csrf=" . csrf_token()) ?>" class="badge badge-soft-warning text-decoration-none" title="Klik untuk tandai Dibayar" onclick="return confirm('Tandai gaji ini sudah dibayar / ditransfer?')">
                                        <i class="fa-solid fa-clock me-1"></i> Pending
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('penggajian/slip.php?id=' . $row['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Slip Gaji">
                                    <i class="fa-solid fa-print me-1"></i> Slip
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
