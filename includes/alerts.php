<?php
$flash = get_flash();
if ($flash):
    $type = htmlspecialchars($flash['type']);
    $msg = $flash['message'];
    $icon = 'fa-info-circle';
    if ($type === 'success') $icon = 'fa-check-circle';
    if ($type === 'danger') $icon = 'fa-exclamation-triangle';
    if ($type === 'warning') $icon = 'fa-exclamation-circle';
?>
    <div class="alert alert-<?= $type ?> alert-dismissible fade show alert-auto-dismiss shadow-sm d-flex align-items-center" role="alert">
        <i class="fa-solid <?= $icon ?> me-2 fs-5"></i>
        <div class="flex-grow-1"><?= $msg ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
