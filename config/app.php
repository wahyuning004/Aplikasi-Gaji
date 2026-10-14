<?php
// ======================================================
// PENGATURAN UMUM & HELPER SISTEM PENGGAJIAN
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Database connection
require_once __DIR__ . '/database.php';

// Base URL helper
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(__DIR__ . '/..');
    
    $subDir = '';
    if ($docRoot && $appRoot && strpos($appRoot, $docRoot) === 0) {
        $subDir = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
    } else {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (stripos($script, '/Aplikasi Gaji') !== false) {
            $subDir = '/Aplikasi Gaji';
        }
    }
    
    $url = $protocol . $host . rtrim($subDir, '/');
    $path = ltrim($path, '/');
    return $url . ($path !== '' ? '/' . $path : '');
}

// Format Rupiah
function format_rupiah($angka, $with_prefix = true) {
    $angka = floatval($angka);
    $formatted = number_format($angka, 0, ',', '.');
    return $with_prefix ? 'Rp ' . $formatted : $formatted;
}

// Format Tanggal Indonesia
function format_tanggal($tanggal, $tampilkan_hari = false) {
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    
    $hari_arr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan_arr = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($tanggal);
    $hari = $hari_arr[date('w', $timestamp)];
    $tgl = date('j', $timestamp);
    $bln = $bulan_arr[(int)date('n', $timestamp)];
    $thn = date('Y', $timestamp);
    
    if ($tampilkan_hari) {
        return "{$hari}, {$tgl} {$bln} {$thn}";
    }
    return "{$tgl} {$bln} {$thn}";
}

// Daftar Nama Bulan
function bulan_indonesia($bln) {
    $bulan_arr = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $bulan_arr[(int)$bln] ?? '';
}

function daftar_bulan() {
    return [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
}

// Flash Message Helpers
function set_flash($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $msg;
    }
    return null;
}

// Auth Helpers
function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function auth_user() {
    return $_SESSION['user'] ?? null;
}

function is_admin() {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Sesi Anda telah berakhir. Silakan login terlebih dahulu.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak! Anda tidak memiliki izin untuk membuka halaman tersebut.');
        header('Location: ' . base_url('dashboard.php'));
        exit;
    }
}

// CSRF Helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitization
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Terbilang Angka ke Kata Rupiah
function terbilang($angka) {
    $angka = abs(floatval($angka));
    $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    $hasil = '';

    if ($angka < 12) {
        $hasil = ' ' . $baca[(int)$angka];
    } elseif ($angka < 20) {
        $hasil = terbilang($angka - 10) . ' Belas';
    } elseif ($angka < 100) {
        $hasil = terbilang(floor($angka / 10)) . ' Puluh' . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $hasil = ' Seratus' . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $hasil = terbilang(floor($angka / 100)) . ' Ratus' . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $hasil = ' Seribu' . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $hasil = terbilang(floor($angka / 1000)) . ' Ribu' . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $hasil = terbilang(floor($angka / 1000000)) . ' Juta' . terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $hasil = terbilang(floor($angka / 1000000000)) . ' Milyar' . terbilang(fmod($angka, 1000000000));
    }

    return trim($hasil);
}

