<?php
require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password wajib diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if employee, get karyawan_id
            $karyawan_id = null;
            if ($user['role'] === 'karyawan') {
                $stmtKaryawan = $pdo->prepare("SELECT id FROM karyawan WHERE user_id = ? LIMIT 1");
                $stmtKaryawan->execute([$user['id']]);
                $karyawan = $stmtKaryawan->fetch();
                $karyawan_id = $karyawan['id'] ?? null;
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role' => $user['role'],
                'foto' => $user['foto'],
                'karyawan_id' => $karyawan_id
            ];

            set_flash('success', 'Selamat datang kembali, <strong>' . htmlspecialchars($user['nama_lengkap']) . '</strong>!');
            header('Location: ' . base_url('dashboard.php'));
            exit;
        } else {
            $error = 'Username atau Password salah. Silakan coba lagi!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Payroll Pro (Sistem Penggajian)</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .brand-badge {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            font-size: 1.75rem;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-sm-5">
    <div class="text-center mb-4">
        <div class="brand-badge">
            <i class="fa-solid fa-money-check-dollar"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">PAYROLL PRO</h4>
        <p class="text-muted small mb-0">Sistem Informasi Penggajian Terpadu</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> py-2 px-3 small d-flex align-items-center mb-3">
            <i class="fa-solid fa-info-circle me-2"></i>
            <div><?= $flash['message'] ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Username / NIP</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-user"></i></span>
                <input type="text" name="username" id="inputUsername" class="form-control bg-light border-start-0" placeholder="Masukkan username atau NIP" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold text-secondary mb-0">Password</label>
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" id="inputPassword" class="form-control bg-light border-start-0 border-end-0" placeholder="Masukkan password" required>
                <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn">
                    <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-gradient w-100 py-2.5 mt-2 fw-semibold">
            <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Sistem
        </button>
    </form>

    <!-- Quick Demo Accounts -->
    <div class="mt-4 pt-3 border-top">
        <div class="text-center small text-muted mb-2 fw-semibold">Demo Akun Cepat:</div>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="fillLogin('admin', 'admin123')">
                <i class="fa-solid fa-shield-halved me-1"></i> Admin (HRD)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="fillLogin('EMP2026001', 'password123')">
                <i class="fa-solid fa-user me-1"></i> Karyawan
            </button>
        </div>
        <div class="text-center mt-2" style="font-size: 0.72rem; color: #94a3b8;">
            Admin: <code>admin / admin123</code> | Karyawan: <code>EMP2026001 / password123</code>
        </div>
    </div>

    <!-- Copyright Developer -->
    <div class="text-center mt-3 pt-2 border-top" style="font-size: 0.73rem; color: #64748b;">
        &copy; <?= date('Y') ?> <strong>Payroll Pro</strong><br>
        Oleh: <strong>Eka Wahyuning Tiyasa</strong> (NIM: <strong>231011400032</strong>)<br>
        Teknik Informatika &bull; Universitas Pamulang
    </div>
</div>

<script>
function fillLogin(user, pass) {
    document.getElementById('inputUsername').value = user;
    document.getElementById('inputPassword').value = pass;
}

const toggleBtn = document.getElementById('togglePasswordBtn');
const passInput = document.getElementById('inputPassword');
const passIcon = document.getElementById('togglePasswordIcon');

toggleBtn.addEventListener('click', function () {
    if (passInput.type === 'password') {
        passInput.type = 'text';
        passIcon.classList.remove('fa-eye');
        passIcon.classList.add('fa-eye-slash');
    } else {
        passInput.type = 'password';
        passIcon.classList.remove('fa-eye-slash');
        passIcon.classList.add('fa-eye');
    }
});
</script>

</body>
</html>
