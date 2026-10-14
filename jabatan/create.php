<?php
$page_title = 'Tambah Jabatan';
require_once __DIR__ . '/../config/app.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $nama_jabatan = trim($_POST['nama_jabatan'] ?? '');
    // Clean currency string
    $gaji_pokok = floatval(str_replace(['Rp', '.', ' '], '', $_POST['gaji_pokok'] ?? '0'));
    $tunjangan_jabatan = floatval(str_replace(['Rp', '.', ' '], '', $_POST['tunjangan_jabatan'] ?? '0'));
    $uang_makan_per_hari = floatval(str_replace(['Rp', '.', ' '], '', $_POST['uang_makan_per_hari'] ?? '0'));

    if (empty($nama_jabatan)) {
        $errors[] = 'Nama Jabatan wajib diisi!';
    }
    if ($gaji_pokok <= 0) {
        $errors[] = 'Gaji Pokok harus lebih besar dari 0!';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO jabatan (nama_jabatan, gaji_pokok, tunjangan_jabatan, uang_makan_per_hari) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama_jabatan, $gaji_pokok, $tunjangan_jabatan, $uang_makan_per_hari]);

        set_flash('success', 'Data jabatan <strong>' . htmlspecialchars($nama_jabatan) . '</strong> berhasil ditambahkan!');
        header('Location: ' . base_url('jabatan/index.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card-modern">
            <div class="card-header-clean d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-plus text-primary me-2"></i> Tambah Jabatan Baru</h6>
                <a href="<?= base_url('jabatan/index.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger small py-2">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_jabatan" class="form-control" placeholder="Contoh: IT Support / Staff Keuangan" value="<?= htmlspecialchars($_POST['nama_jabatan'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Gaji Pokok (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="text" name="gaji_pokok" class="form-control input-currency" placeholder="0" value="<?= htmlspecialchars($_POST['gaji_pokok'] ?? '0') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tunjangan Jabatan (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="text" name="tunjangan_jabatan" class="form-control input-currency" placeholder="0" value="<?= htmlspecialchars($_POST['tunjangan_jabatan'] ?? '0') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Uang Makan per Hari Hadir (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="text" name="uang_makan_per_hari" class="form-control input-currency" placeholder="0" value="<?= htmlspecialchars($_POST['uang_makan_per_hari'] ?? '25000') ?>">
                        </div>
                        <small class="text-muted">Uang makan akan dikalikan dengan total hari hadir karyawan saat kalkulasi gaji.</small>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="<?= base_url('jabatan/index.php') ?>" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary-gradient px-4">
                            <i class="fa-solid fa-save me-1"></i> Simpan Jabatan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
