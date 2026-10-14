<?php
$page_title = 'Profil Saya';
require_once __DIR__ . '/config/app.php';
require_login();

$user = auth_user();
$errors = [];
$success = '';

// Fetch fresh user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$user_data = $stmt->fetch();

// If employee, get karyawan details
$karyawan_data = null;
if ($user_data['role'] === 'karyawan') {
    $stmtK = $pdo->prepare("SELECT k.*, j.nama_jabatan FROM karyawan k JOIN jabatan j ON k.jabatan_id = j.id WHERE k.user_id = ? LIMIT 1");
    $stmtK->execute([$user['id']]);
    $karyawan_data = $stmtK->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        if (empty($nama_lengkap)) {
            $errors[] = 'Nama lengkap wajib diisi!';
        }

        if (empty($errors)) {
            // Update users table
            $stmtUp = $pdo->prepare("UPDATE users SET nama_lengkap = ? WHERE id = ?");
            $stmtUp->execute([$nama_lengkap, $user['id']]);

            // If employee, update employee table
            if ($karyawan_data) {
                $stmtUpK = $pdo->prepare("UPDATE karyawan SET nama = ?, email = ?, no_telepon = ?, alamat = ? WHERE id = ?");
                $stmtUpK->execute([$nama_lengkap, $email, $no_telepon, $alamat, $karyawan_data['id']]);
            }

            // Update session
            $_SESSION['user']['nama_lengkap'] = $nama_lengkap;

            set_flash('success', 'Data profil berhasil diperbarui!');
            header('Location: ' . base_url('profile.php'));
            exit;
        }
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_password, $user_data['password'])) {
            $errors[] = 'Password saat ini salah!';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Password baru minimal 6 karakter!';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Konfirmasi password baru tidak cocok!';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtPass->execute([$new_hash, $user['id']]);

            set_flash('success', 'Password Anda berhasil diperbarui! Silakan gunakan password baru pada login berikutnya.');
            header('Location: ' . base_url('profile.php'));
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Profil & Pengaturan Akun</h4>
                <p class="text-muted small mb-0">Kelola identitas personal, email kontak, dan kata sandi akun Anda.</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger small py-2 mb-4">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Profile Identity Card -->
            <div class="col-12 col-md-4">
                <div class="card-modern p-4 text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold fs-1 mb-3" style="width: 80px; height: 80px;">
                        <?= strtoupper(substr($user_data['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user_data['nama_lengkap']) ?></h5>
                    <span class="badge badge-soft-primary mb-3 text-uppercase"><?= htmlspecialchars($user_data['role']) ?></span>

                    <div class="text-start border-top pt-3 small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Username:</span>
                            <span class="font-monospace text-dark"><?= htmlspecialchars($user_data['username']) ?></span>
                        </div>
                        <?php if ($karyawan_data): ?>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Jabatan:</span>
                                <span class="text-dark fw-semibold"><?= htmlspecialchars($karyawan_data['nama_jabatan']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Status:</span>
                                <span class="text-dark"><?= htmlspecialchars($karyawan_data['status_kepegawaian']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Terdaftar Sejak:</span>
                            <span class="text-dark"><?= format_tanggal($user_data['created_at']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Forms -->
            <div class="col-12 col-md-8">
                <!-- Edit Info -->
                <div class="card-modern mb-4">
                    <div class="card-header-clean">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-gear text-primary me-2"></i> Edit Informasi Akun</h6>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user_data['nama_lengkap']) ?>" required>
                            </div>

                            <?php if ($karyawan_data): ?>
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($karyawan_data['email']) ?>" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">No. Telepon</label>
                                        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($karyawan_data['no_telepon'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($karyawan_data['alamat'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary-gradient btn-sm px-3">
                                <i class="fa-solid fa-save me-1"></i> Simpan Perubahan Profil
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card-modern">
                    <div class="card-header-clean">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-lock text-warning me-2"></i> Ganti Password Akun</h6>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_password">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Password Saat Ini</label>
                                <input type="password" name="current_password" class="form-control" required placeholder="Masukkan password lama">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Password Baru</label>
                                    <input type="password" name="new_password" class="form-control" required placeholder="Minimal 6 karakter">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" class="form-control" required placeholder="Ketik ulang password baru">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-outline-warning text-dark btn-sm px-3 fw-semibold">
                                <i class="fa-solid fa-key me-1"></i> Perbarui Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
