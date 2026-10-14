<?php
require_once __DIR__ . '/config/app.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash('info', 'Anda telah berhasil logout dari sistem.');
header('Location: ' . base_url('login.php'));
exit;
