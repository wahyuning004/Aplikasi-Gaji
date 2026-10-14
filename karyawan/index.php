<?php
$page_title = 'Data Karyawan';
require_once __DIR__ . '/../config/app.php';
require_admin();

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$filter_jabatan = (int)($_GET['jabatan'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');

$sql = "SELECT k.*, j.nama_jabatan, j.gaji_pokok, u.username 
        FROM karyawan k 
        JOIN jabatan j ON k.jabatan_id = j.id 
        LEFT JOIN users u ON k.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (k.nama LIKE ? OR k.nip LIKE ? OR k.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_jabatan > 0) {
    $sql .= " AND k.jabatan_id = ?";
    $params[] = $filter_jabatan;
}

if ($filter_status !== '') {
    $sql .= " AND k.status_kepegawaian = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY k.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$karyawan_list = $stmt->fetchAll();

// Get Jabatan list for filter
$jabatan_list = $pdo->query("SELECT * FROM jabatan ORDER BY nama_jabatan ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-md-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data Karyawan</h4>
        <p class="text-muted small mb-0">Kelola master data pegawai, jabatan, informasi rekening bank, dan akun login.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="<?= base_url('karyawan/create.php') ?>" class="btn btn-primary-gradient btn-sm">
            <i class="fa-solid fa-user-plus me-1"></i> Tambah Karyawan
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card-modern p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-search"></i></span>
                <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari NIP, Nama, atau Email..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="jabatan" class="form-select bg-light">
                <option value="">Semua Jabatan</option>
                <?php foreach ($jabatan_list as $jb): ?>
                    <option value="<?= $jb['id'] ?>" <?= $filter_jabatan == $jb['id'] ? 'selected' : '' ?>><?= htmlspecialchars($jb['nama_jabatan']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select bg-light">
                <option value="">Semua Status</option>
                <option value="Tetap" <?= $filter_status === 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                <option value="Kontrak" <?= $filter_status === 'Kontrak' ? 'selected' : '' ?>>Kontrak</option>
                <option value="Magang" <?= $filter_status === 'Magang' ? 'selected' : '' ?>>Magang</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <?php if ($search !== '' || $filter_jabatan > 0 || $filter_status !== ''): ?>
                <a href="<?= base_url('karyawan/index.php') ?>" class="btn btn-outline-secondary" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Table of Karyawan -->
<div class="card-modern">
    <div class="card-header-clean d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users text-primary me-2"></i> Daftar Karyawan</h6>
        <span class="badge bg-light text-muted border"><?= count($karyawan_list) ?> Data Ditemukan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>NIP</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th>No. Rekening</th>
                    <th>Tgl Masuk</th>
                    <th class="text-end" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($karyawan_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open fs-2 mb-2 d-block text-secondary"></i>
                            Tidak ditemukan data karyawan yang sesuai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($karyawan_list as $k): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($k['nama'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($k['nama']) ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($k['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($k['nip']) ?></span></td>
                            <td><span class="badge badge-soft-primary"><?= htmlspecialchars($k['nama_jabatan']) ?></span></td>
                            <td>
                                <?php 
                                $statusBadge = 'badge-soft-success';
                                if ($k['status_kepegawaian'] === 'Kontrak') $statusBadge = 'badge-soft-warning';
                                if ($k['status_kepegawaian'] === 'Magang') $statusBadge = 'badge-soft-info';
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($k['status_kepegawaian']) ?></span>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= htmlspecialchars($k['nama_bank']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($k['no_rekening']) ?></div>
                            </td>
                            <td class="text-muted small"><?= format_tanggal($k['tanggal_masuk']) ?></td>
                            <td class="text-end">
                                <a href="<?= base_url('karyawan/edit.php?id=' . $k['id']) ?>" class="btn btn-sm btn-outline-warning text-dark me-1" title="Edit">
                                    <i class="fa-solid fa-user-pen"></i>
                                </a>
                                <a href="<?= base_url('karyawan/delete.php?id=' . $k['id'] . '&csrf=' . csrf_token()) ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" data-confirm="Hapus karyawan '<?= htmlspecialchars($k['nama']) ?>'? Akun dan seluruh data absensi/gaji terkait akan terhapus." title="Hapus">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
