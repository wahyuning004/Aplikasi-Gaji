<?php
$page_title = 'Input Absensi Karyawan';
require_once __DIR__ . '/../config/app.php';
require_admin();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    $data_absensi = $_POST['absensi'] ?? [];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO absensi (karyawan_id, bulan, tahun, hadir, sakit, izin, alpa, lembur_jam) 
            VALUES (:karyawan_id, :bulan, :tahun, :hadir, :sakit, :izin, :alpa, :lembur_jam)
            ON DUPLICATE KEY UPDATE 
            hadir = VALUES(hadir),
            sakit = VALUES(sakit),
            izin = VALUES(izin),
            alpa = VALUES(alpa),
            lembur_jam = VALUES(lembur_jam)");

        foreach ($data_absensi as $karyawan_id => $row) {
            $stmt->execute([
                ':karyawan_id' => (int)$karyawan_id,
                ':bulan'       => $bulan,
                ':tahun'       => $tahun,
                ':hadir'       => (int)($row['hadir'] ?? 0),
                ':sakit'       => (int)($row['sakit'] ?? 0),
                ':izin'        => (int)($row['izin'] ?? 0),
                ':alpa'        => (int)($row['alpa'] ?? 0),
                ':lembur_jam'  => (int)($row['lembur_jam'] ?? 0)
            ]);
        }

        $pdo->commit();
        set_flash('success', "Data absensi periode <strong>" . bulan_indonesia($bulan) . " {$tahun}</strong> berhasil disimpan!");
        header("Location: " . base_url("absensi/index.php?bulan={$bulan}&tahun={$tahun}"));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('danger', 'Gagal menyimpan absensi: ' . $e->getMessage());
    }
}

// Fetch all employees and current attendance for this period
$stmt = $pdo->prepare("SELECT k.id as karyawan_id, k.nip, k.nama, j.nama_jabatan, 
    a.hadir, a.sakit, a.izin, a.alpa, a.lembur_jam 
    FROM karyawan k 
    JOIN jabatan j ON k.jabatan_id = j.id 
    LEFT JOIN absensi a ON k.id = a.karyawan_id AND a.bulan = ? AND a.tahun = ? 
    ORDER BY k.nama ASC");
$stmt->execute([$bulan, $tahun]);
$karyawan_list = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-md-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Input / Edit Absensi Massal</h4>
        <p class="text-muted small mb-0">Isi data kehadiran dan jam lembur karyawan untuk periode <strong><?= bulan_indonesia($bulan) . ' ' . $tahun ?></strong>.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="<?= base_url("absensi/index.php?bulan={$bulan}&tahun={$tahun}") ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Rekap
        </a>
    </div>
</div>

<div class="card-modern">
    <div class="card-header-clean d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-table-list text-primary me-2"></i> Formulir Rekap Kehadiran (<?= bulan_indonesia($bulan) . ' ' . $tahun ?>)
        </h6>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="setSemuaHadir(22)">
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Set Semua Hadir 22 Hari
        </button>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="bulan" value="<?= $bulan ?>">
        <input type="hidden" name="tahun" value="<?= $tahun ?>">

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Karyawan</th>
                        <th>Jabatan</th>
                        <th style="width: 120px;" class="text-center">Hadir (Hari)</th>
                        <th style="width: 120px;" class="text-center">Sakit (Hari)</th>
                        <th style="width: 120px;" class="text-center">Izin (Hari)</th>
                        <th style="width: 120px;" class="text-center">Alpa (Hari)</th>
                        <th style="width: 130px;" class="text-center">Lembur (Jam)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($karyawan_list)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada karyawan yang terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($karyawan_list as $k): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($k['nama']) ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($k['nip']) ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($k['nama_jabatan']) ?></span></td>
                                <td>
                                    <input type="number" min="0" max="31" name="absensi[<?= $k['karyawan_id'] ?>][hadir]" class="form-control text-center input-hadir" value="<?= $k['hadir'] !== null ? $k['hadir'] : 22 ?>" required>
                                </td>
                                <td>
                                    <input type="number" min="0" max="31" name="absensi[<?= $k['karyawan_id'] ?>][sakit]" class="form-control text-center" value="<?= (int)($k['sakit'] ?? 0) ?>" required>
                                </td>
                                <td>
                                    <input type="number" min="0" max="31" name="absensi[<?= $k['karyawan_id'] ?>][izin]" class="form-control text-center" value="<?= (int)($k['izin'] ?? 0) ?>" required>
                                </td>
                                <td>
                                    <input type="number" min="0" max="31" name="absensi[<?= $k['karyawan_id'] ?>][alpa]" class="form-control text-center" value="<?= (int)($k['alpa'] ?? 0) ?>" required>
                                </td>
                                <td>
                                    <input type="number" min="0" max="100" name="absensi[<?= $k['karyawan_id'] ?>][lembur_jam]" class="form-control text-center" value="<?= (int)($k['lembur_jam'] ?? 0) ?>" required>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-3 bg-light border-top d-flex justify-content-end gap-2">
            <a href="<?= base_url("absensi/index.php?bulan={$bulan}&tahun={$tahun}") ?>" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary-gradient px-4">
                <i class="fa-solid fa-save me-1"></i> Simpan Data Absensi
            </button>
        </div>
    </form>
</div>

<script>
function setSemuaHadir(jumlah) {
    document.querySelectorAll('.input-hadir').forEach(function(el) {
        el.value = jumlah;
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
