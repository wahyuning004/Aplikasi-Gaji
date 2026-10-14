<?php
// ======================================================
// KONFIGURASI KONEKSI DATABASE (PDO MYSQL)
// ======================================================

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'db_penggajian';
$db_user = 'root';
$db_pass = '';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage() . "<br>Pastikan MySQL Laragon/XAMPP sudah aktif dan database <b>db_penggajian</b> telah diimpor.");
}
