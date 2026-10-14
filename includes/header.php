<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$user = auth_user();
$current_script = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Sistem Penggajian Karyawan</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="d-flex" id="wrapper">
    <!-- Sidebar Navigation -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading text-white d-flex align-items-center gap-2">
            <div class="bg-primary p-2 rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 38px; height: 38px;">
                <i class="fa-solid fa-money-check-dollar"></i>
            </div>
            <div>
                <span class="fs-6 fw-bold tracking-tight">PAYROLL PRO</span>
                <span class="d-block text-white-50" style="font-size: 0.7rem; font-weight: 500;">HR & PAYROLL SYSTEM</span>
            </div>
        </div>

        <div class="sidebar-nav">
            <!-- Main Navigation -->
            <div class="nav-section-title">Menu Utama</div>
            <a href="<?= base_url('dashboard.php') ?>" class="sidebar-link <?= (strpos($current_script, 'dashboard.php') !== false) ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>

            <?php if (is_admin()): ?>
                <div class="nav-section-title">Master Data</div>
                <a href="<?= base_url('jabatan/index.php') ?>" class="sidebar-link <?= (strpos($current_script, '/jabatan/') !== false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-briefcase"></i> Data Jabatan
                </a>
                <a href="<?= base_url('karyawan/index.php') ?>" class="sidebar-link <?= (strpos($current_script, '/karyawan/') !== false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Data Karyawan
                </a>

                <div class="nav-section-title">Transaksi & Operasional</div>
                <a href="<?= base_url('absensi/index.php') ?>" class="sidebar-link <?= (strpos($current_script, '/absensi/') !== false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i> Data Kehadiran
                </a>
                <a href="<?= base_url('penggajian/index.php') ?>" class="sidebar-link <?= (strpos($current_script, '/penggajian/') !== false && strpos($current_script, 'slip.php') === false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-wallet"></i> Olah Gaji (Payroll)
                </a>

                <div class="nav-section-title">Laporan</div>
                <a href="<?= base_url('laporan/index.php') ?>" class="sidebar-link <?= (strpos($current_script, '/laporan/') !== false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Penggajian
                </a>
            <?php else: ?>
                <!-- Employee Specific Menu -->
                <div class="nav-section-title">Layanan Karyawan</div>
                <a href="<?= base_url('penggajian/slip.php') ?>" class="sidebar-link <?= (strpos($current_script, 'slip.php') !== false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-lines"></i> Slip Gaji Saya
                </a>
                <a href="<?= base_url('absensi/index.php') ?>" class="sidebar-link <?= (strpos($current_script, '/absensi/') !== false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-day"></i> Riwayat Absensi
                </a>
            <?php endif; ?>

            <div class="nav-section-title">Akun & Pengaturan</div>
            <a href="<?= base_url('profile.php') ?>" class="sidebar-link <?= (strpos($current_script, 'profile.php') !== false) ? 'active' : '' ?>">
                <i class="fa-solid fa-user-gear"></i> Profil Saya
            </a>
            <a href="<?= base_url('logout.php') ?>" class="sidebar-link text-danger-emphasis">
                <i class="fa-solid fa-arrow-right-from-bracket text-danger"></i> Keluar (Logout)
            </a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light border-0 shadow-none d-lg-none" id="sidebarToggle">
                    <i class="fa-solid fa-bars fs-5"></i>
                </button>
                <div class="d-none d-md-block">
                    <span class="text-muted small fw-medium">
                        <i class="fa-regular fa-calendar me-1"></i> <?= format_tanggal(date('Y-m-d'), true) ?>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge <?= is_admin() ? 'badge-soft-primary' : 'badge-soft-success' ?> px-2.5 py-1.5 rounded-pill text-uppercase">
                    <i class="fa-solid <?= is_admin() ? 'fa-shield-halved' : 'fa-user' ?> me-1"></i> <?= $user['role'] ?>
                </span>
                
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                        </div>
                        <div class="text-start d-none d-sm-block">
                            <div class="fw-semibold small leading-tight"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($user['username']) ?></small>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li><a class="dropdown-item py-2" href="<?= base_url('profile.php') ?>"><i class="fa-solid fa-user me-2 text-muted"></i> Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="<?= base_url('logout.php') ?>"><i class="fa-solid fa-power-off me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Body Container -->
        <main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">
            <?php include __DIR__ . '/alerts.php'; ?>
