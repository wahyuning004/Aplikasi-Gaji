<?php
require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    header('Location: ' . base_url('dashboard.php'));
} else {
    header('Location: ' . base_url('login.php'));
}
exit;
