<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/config/app.php';
require_login();

$user = auth_user();
$current_month = (int)date('n');
$current_year = (int)date('Y');

if (is_admin()) {
    // 1. Total Karyawan
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM karyawan");
    $total_karyawan = (int)$stmt->fetch()['total'];

    // 2. Total Pengeluaran Gaji Bulan Ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(take_home_pay), 0) AS total_gaji, COUNT(*) as total_slip FROM gaji WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$current_month, $current_year]);
    $gaji_bulan_ini = $stmt->fetch();
    $total_pengeluaran_gaji = (float)$gaji_bulan_ini['total_gaji'];
    $total_slip_terbit = (int)$gaji_bulan_ini['total_slip'];

    // 3. Rekap Kehadiran Bulan Ini
    $stmt = $pdo->prepare("SELECT 
        COALESCE(SUM(hadir), 0) AS total_hadir,
        COALESCE(SUM(sakit), 0) AS total_sakit,
        COALESCE(SUM(izin), 0) AS total_izin,
        COALESCE(SUM(alpa), 0) AS total_alpa,
        COALESCE(SUM(lembur_jam), 0) AS total_lembur
        FROM absensi WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$current_month, $current_year]);
    $rekap_absensi = $stmt->fetch();

    // 4. Total Jabatan
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM jabatan");
    $total_jabatan = (int)$stmt->fetch()['total'];

    // 5. Data Chart Gaji 6 Bulan Terakhir
    $chart_labels = [];
    $chart_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $bln = (int)date('n', strtotime("-$i months"));
        $thn = (int)date('Y', strtotime("-$i months"));
        $chart_labels[] = bulan_indonesia($bln) . ' ' . $thn;

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(take_home_pay), 0) AS total FROM gaji WHERE bulan = ? AND tahun = ?");
        $stmt->execute([$bln, $thn]);
        $chart_data[] = (float)$stmt->fetch()['total'];
    }

    // 6. Gaji Terbaru
    $stmt = $pdo->prepare("SELECT g.*, k.nama AS nama_karyawan, k.nip, j.nama_jabatan 
        FROM gaji g 
        JOIN karyawan k ON g.karyawan_id = k.id 
        JOIN jabatan j ON k.jabatan_id = j.id 
        WHERE g.bulan = ? AND g.tahun = ? 
        ORDER BY g.id DESC LIMIT 5");
    $stmt->execute([$current_month, $current_year]);
    $recent_salaries = $stmt->fetchAll();

} else {
    // Data Karyawan Login
    $karyawan_id = $user['karyawan_id'];
    $stmt = $pdo->prepare("SELECT k.*, j.nama_jabatan, j.gaji_pokok, j.tunjangan_jabatan, j.uang_makan_per_hari 
        FROM karyawan k 
        JOIN jabatan j ON k.jabatan_id = j.id 
        WHERE k.id = ? LIMIT 1");
    $stmt->execute([$karyawan_id]);
    $karyawan_data = $stmt->fetch();

    // Slip Gaji Bulan Ini
    $stmt = $pdo->prepare("SELECT * FROM gaji WHERE karyawan_id = ? AND bulan = ? AND tahun = ? LIMIT 1");
    $stmt->execute([$karyawan_id, $current_month, $current_year]);
    $gaji_karyawan = $stmt->fetch();

    // Absensi Bulan Ini
    $stmt = $pdo->prepare("SELECT * FROM absensi WHERE karyawan_id = ? AND bulan = ? AND tahun = ? LIMIT 1");
    $stmt->execute([$karyawan_id, $current_month, $current_year]);
    $absen_karyawan = $stmt->fetch();

    // Riwayat Gaji Karyawan
    $stmt = $pdo->prepare("SELECT * FROM gaji WHERE karyawan_id = ? ORDER BY tahun DESC, bulan DESC LIMIT 6");
    $stmt->execute([$karyawan_id]);
    $riwayat_gaji = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<?php if (is_admin()): ?>
    <!-- Header Page & Greeting -->
    <div class="d-md-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Dashboard HRD & Payroll</h4>
            <p class="text-muted small mb-0">Pantau performa absensi, estimasi pengeluaran, dan status penggajian karyawan.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= base_url('absensi/index.php') ?>" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-calendar-check me-1"></i> Data Absensi
            </a>
            <a href="<?= base_url('penggajian/index.php') ?>" class="btn btn-primary-gradient btn-sm">
                <i class="fa-solid fa-calculator me-1"></i> Olah Gaji Periode Ini
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Karyawan</span>
                        <h3 class="fw-bold my-1 text-dark"><?= number_format($total_karyawan) ?></h3>
                        <span class="text-muted small"><i class="fa-solid fa-briefcase text-primary me-1"></i> <?= $total_jabatan ?> Jabatan Terdaftar</span>
                    </div>
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Gaji Bulan Ini</span>
                        <h3 class="fw-bold my-1 text-dark"><?= format_rupiah($total_pengeluaran_gaji) ?></h3>
                        <span class="text-muted small"><i class="fa-solid fa-receipt text-success me-1"></i> <?= $total_slip_terbit ?> Slip Terkalkulasi</span>
                    </div>
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Kehadiran</span>
                        <h3 class="fw-bold my-1 text-dark"><?= (int)$rekap_absensi['total_hadir'] ?> Hari</h3>
                        <span class="text-muted small"><i class="fa-solid fa-stopwatch text-info me-1"></i> <?= (int)$rekap_absensi['total_lembur'] ?> Jam Lembur</span>
                    </div>
                    <div class="stat-icon-wrapper bg-info-subtle text-info">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Tidak Hadir (Bln Ini)</span>
                        <h3 class="fw-bold my-1 text-dark"><?= ((int)$rekap_absensi['total_sakit'] + (int)$rekap_absensi['total_izin'] + (int)$rekap_absensi['total_alpa']) ?> Hari</h3>
                        <span class="text-danger small"><i class="fa-solid fa-user-xmark me-1"></i> <?= (int)$rekap_absensi['total_alpa'] ?> Alpa</span>
                    </div>
                    <div class="stat-icon-wrapper bg-danger-subtle text-danger">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card-modern h-100">
                <div class="card-header-clean d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i> Tren Pengeluaran Gaji (6 Bulan Terakhir)</h6>
                    <span class="badge bg-light text-muted border"><?= date('Y') ?></span>
                </div>
                <div class="p-3">
                    <canvas id="salaryTrendChart" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card-modern h-100">
                <div class="card-header-clean">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-pie text-secondary me-2"></i> Rekap Absensi Bulan <?= bulan_indonesia($current_month) ?></h6>
                </div>
                <div class="p-3 d-flex flex-column align-items-center justify-content-center">
                    <div style="max-height: 220px; width: 100%;">
                        <canvas id="attendanceDoughnutChart"></canvas>
                    </div>
                    <div class="row w-100 mt-3 text-center g-2 small">
                        <div class="col-3"><span class="badge badge-soft-success w-100">Hadir: <?= (int)$rekap_absensi['total_hadir'] ?></span></div>
                        <div class="col-3"><span class="badge badge-soft-info w-100">Sakit: <?= (int)$rekap_absensi['total_sakit'] ?></span></div>
                        <div class="col-3"><span class="badge badge-soft-warning w-100">Izin: <?= (int)$rekap_absensi['total_izin'] ?></span></div>
                        <div class="col-3"><span class="badge badge-soft-danger w-100">Alpa: <?= (int)$rekap_absensi['total_alpa'] ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Table: Gaji Terbaru -->
    <div class="card-modern mb-4">
        <div class="card-header-clean d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-money-check text-success me-2"></i> Penggajian Bulan <?= bulan_indonesia($current_month) . ' ' . $current_year ?></h6>
            <a href="<?= base_url('penggajian/index.php') ?>" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>NIP & Karyawan</th>
                        <th>Jabatan</th>
                        <th>Total Gaji (THP)</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_salaries)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-circle-exclamation fs-3 d-block mb-2 text-secondary"></i>
                                Belum ada data penggajian yang di-generate untuk bulan <?= bulan_indonesia($current_month) . ' ' . $current_year ?>.<br>
                                <a href="<?= base_url('penggajian/index.php') ?>" class="btn btn-sm btn-primary mt-2">Generate Gaji Sekarang</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_salaries as $sal): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($sal['nama_karyawan']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($sal['nip']) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sal['nama_jabatan']) ?></span></td>
                                <td class="fw-bold text-primary"><?= format_rupiah($sal['take_home_pay']) ?></td>
                                <td>
                                    <?php if ($sal['status'] === 'Dibayar'): ?>
                                        <span class="badge badge-soft-success"><i class="fa-solid fa-check me-1"></i> Dibayar</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-warning"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('penggajian/slip.php?id=' . $sal['id']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
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

    <!-- Chart Script -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Trend Chart
        const ctxTrend = document.getElementById('salaryTrendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Total Pengeluaran Gaji (Rp)',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.75)',
                    borderColor: 'rgb(79, 70, 229)',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });

        // Doughnut Chart Absensi
        const ctxPie = document.getElementById('attendanceDoughnutChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Sakit', 'Izin', 'Alpa'],
                datasets: [{
                    data: [
                        <?= (int)$rekap_absensi['total_hadir'] ?>,
                        <?= (int)$rekap_absensi['total_sakit'] ?>,
                        <?= (int)$rekap_absensi['total_izin'] ?>,
                        <?= (int)$rekap_absensi['total_alpa'] ?>
                    ],
                    backgroundColor: ['#10b981', '#0ea5e9', '#f59e0b', '#ef4444'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    });
    </script>

<?php else: ?>
    <!-- KARYAWAN DASHBOARD VIEW -->
    <div class="row g-4 mb-4">
        <!-- Profile Card -->
        <div class="col-12 col-md-4">
            <div class="card-modern p-4 text-center">
                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2.5rem;">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($karyawan_data['nama'] ?? $user['nama_lengkap']) ?></h5>
                <span class="badge badge-soft-primary mb-3"><?= htmlspecialchars($karyawan_data['nama_jabatan'] ?? '-') ?></span>

                <div class="text-start border-top pt-3 small">
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">NIP:</span>
                        <strong class="text-dark"><?= htmlspecialchars($karyawan_data['nip'] ?? '-') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Status Pegawai:</span>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($karyawan_data['status_kepegawaian'] ?? '-') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">No. Rekening:</span>
                        <strong class="text-dark"><?= htmlspecialchars(($karyawan_data['nama_bank'] ?? '') . ' - ' . ($karyawan_data['no_rekening'] ?? '-')) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Tanggal Masuk:</span>
                        <span class="text-dark"><?= format_tanggal($karyawan_data['tanggal_masuk'] ?? null) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Payroll Summary Card -->
        <div class="col-12 col-md-8">
            <div class="card-modern p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-receipt text-primary me-2"></i> Slip Gaji Bulan <?= bulan_indonesia($current_month) . ' ' . $current_year ?></h6>
                        <?php if ($gaji_karyawan): ?>
                            <?php if ($gaji_karyawan['status'] === 'Dibayar'): ?>
                                <span class="badge badge-soft-success"><i class="fa-solid fa-check me-1"></i> Telah Ditransfer</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning"><i class="fa-solid fa-clock me-1"></i> Sedang Diproses (Pending)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border">Belum Diterbitkan</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($gaji_karyawan): ?>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <span class="text-muted small">Total Pendapatan Bersih (Take Home Pay):</span>
                            <h2 class="fw-bold text-primary mb-0 mt-1"><?= format_rupiah($gaji_karyawan['take_home_pay']) ?></h2>
                            <small class="text-muted">Dihitung dari Gaji Pokok + Tunjangan + Lembur dikurangi potongan wajib.</small>
                        </div>

                        <div class="row g-2 small">
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center">
                                    <div class="text-muted">Gaji Pokok</div>
                                    <div class="fw-bold"><?= format_rupiah($gaji_karyawan['gaji_pokok']) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center">
                                    <div class="text-muted">Tunjangan</div>
                                    <div class="fw-bold"><?= format_rupiah($gaji_karyawan['tunjangan_jabatan']) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center">
                                    <div class="text-muted">Lembur & Makan</div>
                                    <div class="fw-bold"><?= format_rupiah($gaji_karyawan['insentif_lembur'] + $gaji_karyawan['uang_makan']) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center text-danger">
                                    <div>Total Potongan</div>
                                    <div class="fw-bold">- <?= format_rupiah($gaji_karyawan['total_potongan']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fa-solid fa-file-circle-question fs-2 text-secondary mb-2"></i>
                            <p class="mb-0">Slip gaji bulan <?= bulan_indonesia($current_month) ?> belum dikalkulasi oleh HRD.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted">Rekap Kehadiran Bulan Ini:</span>
                        <strong class="ms-1"><?= $absen_karyawan ? $absen_karyawan['hadir'] : 0 ?> Hari Hadir</strong>
                    </div>
                    <?php if ($gaji_karyawan): ?>
                        <a href="<?= base_url('penggajian/slip.php?id=' . $gaji_karyawan['id']) ?>" target="_blank" class="btn btn-primary-gradient btn-sm">
                            <i class="fa-solid fa-print me-1"></i> Cetak Slip Gaji
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Gaji Karyawan -->
    <div class="card-modern">
        <div class="card-header-clean">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i> Riwayat Penggajian Terakhir</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Gaji Pokok</th>
                        <th>Total Pendapatan</th>
                        <th>Total Potongan</th>
                        <th>Take Home Pay</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayat_gaji)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat gaji tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($riwayat_gaji as $rw): ?>
                            <tr>
                                <td><strong><?= bulan_indonesia($rw['bulan']) . ' ' . $rw['tahun'] ?></strong></td>
                                <td><?= format_rupiah($rw['gaji_pokok']) ?></td>
                                <td class="text-success"><?= format_rupiah($rw['total_pendapatan']) ?></td>
                                <td class="text-danger">- <?= format_rupiah($rw['total_potongan']) ?></td>
                                <td class="fw-bold text-primary"><?= format_rupiah($rw['take_home_pay']) ?></td>
                                <td>
                                    <?php if ($rw['status'] === 'Dibayar'): ?>
                                        <span class="badge badge-soft-success">Dibayar</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('penggajian/slip.php?id=' . $rw['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
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
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
