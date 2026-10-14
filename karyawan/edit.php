<?php
$page_title = 'Edit Karyawan';
require_once __DIR__ . '/../config/app.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT k.*, u.username FROM karyawan k LEFT JOIN users u ON k.user_id = u.id WHERE k.id = ? LIMIT 1");
$stmt->execute([$id]);
$karyawan = $stmt->fetch();

if (!$karyawan) {
    set_flash('danger', 'Data karyawan tidak ditemukan!');
    header('Location: ' . base_url('karyawan/index.php'));
    exit;
}

$jabatan_list = $pdo->query("SELECT * FROM jabatan ORDER BY nama_jabatan ASC")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $jabatan_id = (int)($_POST['jabatan_id'] ?? 0);
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $status_kepegawaian = trim($_POST['status_kepegawaian'] ?? 'Tetap');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? date('Y-m-d'));
    $no_rekening = trim($_POST['no_rekening'] ?? '');
    $nama_bank = trim($_POST['nama_bank'] ?? 'BCA');

    if (empty($nama)) $errors[] = 'Nama Lengkap wajib diisi!';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email valid wajib diisi!';
    if (!empty($password) && strlen($password) < 6) $errors[] = 'Password baru minimal 6 karakter!';
    if ($jabatan_id <= 0) $errors[] = 'Pilih jabatan karyawan!';
    if (empty($no_rekening)) $errors[] = 'Nomor rekening wajib diisi!';

    // Handle photo upload
    $foto_name = $karyawan['foto'];
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $foto_name = 'karyawan_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = __DIR__ . '/../assets/uploads/' . $foto_name;
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path);
        } else {
            $errors[] = 'Format foto harus berupa JPG, PNG, atau WEBP!';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Update user if exists
            if ($karyawan['user_id']) {
                if (!empty($password)) {
                    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("UPDATE users SET nama_lengkap = ?, password = ?, foto = ? WHERE id = ?");
                    $stmtUser->execute([$nama, $password_hashed, $foto_name, $karyawan['user_id']]);
                } else {
                    $stmtUser = $pdo->prepare("UPDATE users SET nama_lengkap = ?, foto = ? WHERE id = ?");
                    $stmtUser->execute([$nama, $foto_name, $karyawan['user_id']]);
                }
            }

            // 2. Update employee
            $stmtKaryawan = $pdo->prepare("UPDATE karyawan SET nama = ?, email = ?, jabatan_id = ?, jenis_kelamin = ?, no_telepon = ?, alamat = ?, status_kepegawaian = ?, tanggal_masuk = ?, no_rekening = ?, nama_bank = ?, foto = ? WHERE id = ?");
            $stmtKaryawan->execute([
                $nama, $email, $jabatan_id, $jenis_kelamin, $no_telepon, $alamat, $status_kepegawaian, $tanggal_masuk, $no_rekening, $nama_bank, $foto_name, $id
            ]);

            $pdo->commit();

            set_flash('success', "Data karyawan <strong>{$nama}</strong> berhasil diperbarui!");
            header('Location: ' . base_url('karyawan/index.php'));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="card-modern">
            <div class="card-header-clean d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-pen text-warning me-2"></i> Edit Data Karyawan</h6>
                    <small class="text-muted">Perbarui data profil, penempatan jabatan, atau reset password.</small>
                </div>
                <a href="<?= base_url('karyawan/index.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <div class="p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger small py-2 mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i class="fa-solid fa-id-card me-2"></i>Informasi Kepegawaian</h6></div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">NIP (Tidak dapat diubah)</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($karyawan['nip']) ?>" readonly>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Jabatan <span class="text-danger">*</span></label>
                            <select name="jabatan_id" class="form-select" required>
                                <?php foreach ($jabatan_list as $jb): ?>
                                    <option value="<?= $jb['id'] ?>" <?= ($karyawan['jabatan_id'] == $jb['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jb['nama_jabatan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Status Kepegawaian <span class="text-danger">*</span></label>
                            <select name="status_kepegawaian" class="form-select" required>
                                <option value="Tetap" <?= ($karyawan['status_kepegawaian'] === 'Tetap') ? 'selected' : '' ?>>Karyawan Tetap</option>
                                <option value="Kontrak" <?= ($karyawan['status_kepegawaian'] === 'Kontrak') ? 'selected' : '' ?>>Kontrak (PKWT)</option>
                                <option value="Magang" <?= ($karyawan['status_kepegawaian'] === 'Magang') ? 'selected' : '' ?>>Magang / Internship</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai Bekerja <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_masuk" class="form-control" value="<?= htmlspecialchars($karyawan['tanggal_masuk']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Reset Password (Opsional)</label>
                            <input type="text" name="password" class="form-control" placeholder="Kosongkan bila tidak ingin mengubah password">
                            <small class="text-muted">Isi hanya jika ingin mereset password akun login karyawan ini.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i class="fa-solid fa-user me-2"></i>Data Pribadi & Kontak</h6></div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($karyawan['nama']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($karyawan['email']) ?>" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="Laki-laki" <?= ($karyawan['jenis_kelamin'] === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= ($karyawan['jenis_kelamin'] === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($karyawan['no_telepon'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Ganti Foto Profil (Opsional)</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Alamat Domisili Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($karyawan['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i class="fa-solid fa-credit-card me-2"></i>Informasi Rekening Penggajian (Payroll)</h6></div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Nama Bank Penampung <span class="text-danger">*</span></label>
                            <select name="nama_bank" class="form-select" required>
                                <?php 
                                $banks = ['BCA', 'Mandiri', 'BNI', 'BRI', 'BSI', 'CIMB Niaga', 'Permata', 'Danamon'];
                                foreach ($banks as $b):
                                ?>
                                    <option value="<?= $b ?>" <?= ($karyawan['nama_bank'] === $b) ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Nomor Rekening Bank <span class="text-danger">*</span></label>
                            <input type="text" name="no_rekening" class="form-control" value="<?= htmlspecialchars($karyawan['no_rekening']) ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= base_url('karyawan/index.php') ?>" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary-gradient px-4">
                            <i class="fa-solid fa-save me-1"></i> Perbarui Data Karyawan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
