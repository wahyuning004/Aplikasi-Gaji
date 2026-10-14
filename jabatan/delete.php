<?php
require_once __DIR__ . '/../config/app.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf'] ?? '';

if (!verify_csrf_token($token)) {
    set_flash('danger', 'Token keamanan tidak valid.');
    header('Location: ' . base_url('jabatan/index.php'));
    exit;
}

// Check if still used by employees
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM karyawan WHERE jabatan_id = ?");
$stmt->execute([$id]);
if ($stmt->fetch()['count'] > 0) {
    set_flash('danger', 'Jabatan tidak dapat dihapus karena masih digunakan oleh data karyawan aktif!');
    header('Location: ' . base_url('jabatan/index.php'));
    exit;
}

$stmt = $pdo->prepare("DELETE FROM jabatan WHERE id = ?");
$stmt->execute([$id]);

set_flash('success', 'Data jabatan berhasil dihapus.');
header('Location: ' . base_url('jabatan/index.php'));
exit;
