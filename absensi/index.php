<?php
$page_title = 'Data Kehadiran (Absensi)';
require_once __DIR__ . '/../config/app.php';
require_login();

$user = auth_user();
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

if (is_admin()) {
    // Admin View: All employees attendance for selected month & year
    $stmt = $pdo->prepare("SELECT k.id as karyawan_id, k.nip, k.nama, j.nama_jabatan, 
        a.id as absensi_id, a.hadir, a.sakit, a.izin, a.alpa, a.lembur_jam 
        FROM karyawan k 
        JOIN jabatan j ON k.jabatan_id = j.id 
        LEFT JOIN absensi a ON k.id = a.karyawan_id AND a.bulan = ? AND a.tahun = ? 
        ORDER BY k.nama ASC");
    $stmt->execute([$bulan, $tahun]);
    $absensi_list = $stmt->fetchAll();
} else {
    // Employee View: Own attendance
    $karyawan_id = $user['karyawan_id'];
    $stmt = $pdo->prepare("SELECT a.*, k.nama, k.nip, j.nama_jabatan 
        FROM absensi a 
        JOIN karyawan k ON a.karyawan_id = k.id 
        JOIN jabatan j ON k.jabatan_id = j.id 
        WHERE a.karyawan_id = ? AND a.tahun = ? 
        ORDER BY a.bulan DESC");
    $stmt->execute([$karyawan_id, $tahun]);
    $absensi_list = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-md-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data Kehadiran & Lembur</h4>
        <p class="text-muted small mb-0">Kelola absensi karyawan (Hadir, Sakit, Izin, Alpa) dan total jam kerja lembur.</p>
    </div>
    <?php if (is_admin()): ?>
        <div class="mt-3 mt-md-0">
            <a href="<?= base_url("absensi/input.php?bulan={$bulan}&tahun={$tahun}") ?>" class="btn btn-primary-gradient btn-sm">
                <i class="fa-solid fa-pen-to-square me-1"></i> Input / Edit Absensi Periode Ini
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Periode Selector Card -->
<div class="card-modern p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <?php if (is_admin()): ?>
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Pilih Bulan:</label>
                <select name="bulan" class="form-select bg-light">
                    <?php foreach (daftar_bulan() as $num => $nama_bln): ?>
                        <option value="<?= $num ?>" <?= ($bulan === $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
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

<!-- Attendance Table -->
<div class="card-modern">
    <div class="card-header-clean d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-calendar-check text-primary me-2"></i>
            <?= is_admin() ? "Daftar Absensi Periode " . bulan_indonesia($bulan) . " {$tahun}" : "Riwayat Kehadiran Tahun {$tahun}" ?>
        </h6>
        <span class="badge bg-light text-muted border"><?= count($absensi_list) ?> Data</span>
    </div>

    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <?php if (is_admin()): ?>
                        <th>Karyawan</th>
                        <th>Jabatan</th>
                    <?php else: ?>
                        <th>Bulan</th>
                    <?php endif; ?>
                    <th class="text-center" style="width: 100px;">Hadir</th>
                    <th class="text-center" style="width: 100px;">Sakit</th>
                    <th class="text-center" style="width: 100px;">Izin</th>
                    <th class="text-center" style="width: 100px;">Alpa</th>
                    <th class="text-center" style="width: 120px;">Lembur (Jam)</th>
                    <?php if (is_admin()): ?>
                        <th class="text-center" style="width: 120px;">Status Input</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($absensi_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-calendar-xmark fs-2 mb-2 d-block text-secondary"></i>
                            Belum ada catatan absensi untuk periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($absensi_list as $row): ?>
                        <tr>
                            <?php if (is_admin()): ?>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['nip']) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_jabatan']) ?></span></td>
                            <?php else: ?>
                                <td><strong><?= bulan_indonesia($row['bulan']) ?></strong></td>
                            <?php endif; ?>

                            <td class="text-center">
                                <span class="badge badge-soft-success px-3 py-1.5 fs-6"><?= (int)($row['hadir'] ?? 0) ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-info px-3 py-1.5 fs-6"><?= (int)($row['sakit'] ?? 0) ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-warning px-3 py-1.5 fs-6"><?= (int)($row['izin'] ?? 0) ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-danger px-3 py-1.5 fs-6"><?= (int)($row['alpa'] ?? 0) ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3 py-1.5 fs-6"><i class="fa-regular fa-clock me-1 text-primary"></i> <?= (int)($row['lembur_jam'] ?? 0) ?> Jam</span>
                            </td>

                            <?php if (is_admin()): ?>
                                <td class="text-center">
                                    <?php if ($row['absensi_id']): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check me-1"></i> Tersimpan</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary"><i class="fa-solid fa-minus me-1"></i> Belum Diisi</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
