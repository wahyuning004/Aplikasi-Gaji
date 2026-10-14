<?php
require_once __DIR__ . '/../config/app.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf'] ?? '';

if (!verify_csrf_token($token)) {
    set_flash('danger', 'Token keamanan tidak valid.');
    header('Location: ' . base_url('karyawan/index.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT user_id, nama, foto FROM karyawan WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$karyawan = $stmt->fetch();

if (!$karyawan) {
    set_flash('danger', 'Data karyawan tidak ditemukan.');
    header('Location: ' . base_url('karyawan/index.php'));
    exit;
}

try {
    $pdo->beginTransaction();

    // Delete photo if custom
    if ($karyawan['foto'] && $karyawan['foto'] !== 'default.png') {
        $photoFile = __DIR__ . '/../assets/uploads/' . $karyawan['foto'];
        if (file_exists($photoFile)) {
            @unlink($photoFile);
        }
    }

    // Delete employee
    $stmtDel = $pdo->prepare("DELETE FROM karyawan WHERE id = ?");
    $stmtDel->execute([$id]);

    // Delete user
    if ($karyawan['user_id']) {
        $stmtDelUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmtDelUser->execute([$karyawan['user_id']]);
    }

    $pdo->commit();
    set_flash('success', "Data karyawan <strong>" . htmlspecialchars($karyawan['nama']) . "</strong> berhasil dihapus.");
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('danger', 'Gagal menghapus data: ' . $e->getMessage());
}

header('Location: ' . base_url('karyawan/index.php'));
exit;
