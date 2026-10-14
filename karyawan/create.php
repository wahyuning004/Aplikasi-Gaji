<?php
$page_title = 'Tambah Karyawan';
require_once __DIR__ . '/../config/app.php';
require_admin();

// Fetch Jabatan
$jabatan_list = $pdo->query("SELECT * FROM jabatan ORDER BY nama_jabatan ASC")->fetchAll();

// Auto-generate suggested NIP
$stmtNip = $pdo->query("SELECT nip FROM karyawan ORDER BY id DESC LIMIT 1");
$lastRow = $stmtNip->fetch();
$year = date('Y');
$suggested_nip = 'EMP' . $year . '001';
if ($lastRow && preg_match('/EMP\d{4}(\d+)/', $lastRow['nip'], $matches)) {
    $nextNum = str_pad((int)$matches[1] + 1, 3, '0', STR_PAD_LEFT);
    $suggested_nip = 'EMP' . $year . $nextNum;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? 'password123');
    $jabatan_id = (int)($_POST['jabatan_id'] ?? 0);
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $status_kepegawaian = trim($_POST['status_kepegawaian'] ?? 'Tetap');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? date('Y-m-d'));
    $no_rekening = trim($_POST['no_rekening'] ?? '');
    $nama_bank = trim($_POST['nama_bank'] ?? 'BCA');

    // Validation
    if (empty($nip)) $errors[] = 'NIP wajib diisi!';
    if (empty($nama)) $errors[] = 'Nama Lengkap wajib diisi!';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email valid wajib diisi!';
    if (strlen($password) < 6) $errors[] = 'Password akun minimal 6 karakter!';
    if ($jabatan_id <= 0) $errors[] = 'Pilih jabatan karyawan!';
    if (empty($no_rekening)) $errors[] = 'Nomor rekening wajib diisi!';

    // Check duplicate NIP
    $chk = $pdo->prepare("SELECT id FROM karyawan WHERE nip = ?");
    $chk->execute([$nip]);
    if ($chk->fetch()) $errors[] = "NIP '{$nip}' sudah digunakan oleh karyawan lain!";

    // Check duplicate username in users
    $chkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $chkUser->execute([$nip]);
    if ($chkUser->fetch()) $errors[] = "Username akun '{$nip}' sudah ada di sistem!";

    // Handle photo upload
    $foto_name = 'default.png';
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

            // 1. Create user account
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, foto) VALUES (?, ?, ?, 'karyawan', ?)");
            $stmtUser->execute([$nip, $password_hashed, $nama, $foto_name]);
            $user_id = $pdo->lastInsertId();

            // 2. Create employee record
            $stmtKaryawan = $pdo->prepare("INSERT INTO karyawan (user_id, nip, nama, email, jabatan_id, jenis_kelamin, no_telepon, alamat, status_kepegawaian, tanggal_masuk, no_rekening, nama_bank, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtKaryawan->execute([
                $user_id, $nip, $nama, $email, $jabatan_id, $jenis_kelamin, $no_telepon, $alamat, $status_kepegawaian, $tanggal_masuk, $no_rekening, $nama_bank, $foto_name
            ]);

            $pdo->commit();

            set_flash('success', "Karyawan <strong>{$nama}</strong> berhasil ditambahkan! Akun login telah dibuat (Username: <code>{$nip}</code>).");
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
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-plus text-primary me-2"></i> Tambah Karyawan Baru</h6>
                    <small class="text-muted">Formulir pendaftaran karyawan & otomatisasi akun akses sistem.</small>
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
                            <label class="form-label small fw-semibold">NIP (Nomor Induk Pegawai) <span class="text-danger">*</span></label>
                            <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($_POST['nip'] ?? $suggested_nip) ?>" required>
                            <small class="text-muted">NIP akan digunakan sebagai username login.</small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Jabatan <span class="text-danger">*</span></label>
                            <select name="jabatan_id" class="form-select" required>
                                <option value="">-- Pilih Jabatan --</option>
                                <?php foreach ($jabatan_list as $jb): ?>
                                    <option value="<?= $jb['id'] ?>" <?= (isset($_POST['jabatan_id']) && $_POST['jabatan_id'] == $jb['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jb['nama_jabatan']) ?> (<?= format_rupiah($jb['gaji_pokok']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Status Kepegawaian <span class="text-danger">*</span></label>
                            <select name="status_kepegawaian" class="form-select" required>
                                <option value="Tetap" <?= (($_POST['status_kepegawaian'] ?? '') === 'Tetap') ? 'selected' : '' ?>>Karyawan Tetap</option>
                                <option value="Kontrak" <?= (($_POST['status_kepegawaian'] ?? '') === 'Kontrak') ? 'selected' : '' ?>>Kontrak (PKWT)</option>
                                <option value="Magang" <?= (($_POST['status_kepegawaian'] ?? '') === 'Magang') ? 'selected' : '' ?>>Magang / Internship</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai Bekerja <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_masuk" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_masuk'] ?? date('Y-m-d')) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Password Akun Login <span class="text-danger">*</span></label>
                            <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($_POST['password'] ?? 'password123') ?>" required>
                            <small class="text-muted">Password awal untuk login karyawan.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i class="fa-solid fa-user me-2"></i>Data Pribadi & Kontak</h6></div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso, S.Kom" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Alamat Email Perusahaan / Pribadi <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="budi@perusahaan.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="Laki-laki" <?= (($_POST['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= (($_POST['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" class="form-control" placeholder="081234567890" value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Foto Profil (Opsional)</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Alamat Domisili Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Jl. Raya No. 123..."><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
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
                                    <option value="<?= $b ?>" <?= (($_POST['nama_bank'] ?? 'BCA') === $b) ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Nomor Rekening Bank <span class="text-danger">*</span></label>
                            <input type="text" name="no_rekening" class="form-control" placeholder="Nomor rekening tanpa spasi" value="<?= htmlspecialchars($_POST['no_rekening'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= base_url('karyawan/index.php') ?>" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary-gradient px-4">
                            <i class="fa-solid fa-save me-1"></i> Simpan Data Karyawan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
