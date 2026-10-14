<?php
require_once __DIR__ . '/../config/app.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf'] ?? '';

if (!verify_csrf_token($token)) {
    set_flash('danger', 'Token keamanan tidak valid.');
    header('Location: ' . base_url('penggajian/index.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM gaji WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$gaji = $stmt->fetch();

if (!$gaji) {
    set_flash('danger', 'Data penggajian tidak ditemukan.');
    header('Location: ' . base_url('penggajian/index.php'));
    exit;
}

$new_status = ($gaji['status'] === 'Dibayar') ? 'Pending' : 'Dibayar';
$tgl_bayar = ($new_status === 'Dibayar') ? date('Y-m-d') : null;

$stmtUpdate = $pdo->prepare("UPDATE gaji SET status = ?, tanggal_bayar = ? WHERE id = ?");
$stmtUpdate->execute([$new_status, $tgl_bayar, $id]);

set_flash('success', "Status pembayaran gaji berhasil diubah menjadi <strong>{$new_status}</strong>.");
header("Location: " . base_url("penggajian/index.php?bulan={$gaji['bulan']}&tahun={$gaji['tahun']}"));
exit;
