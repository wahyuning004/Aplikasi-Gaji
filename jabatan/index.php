<?php
$page_title = 'Data Jabatan';
require_once __DIR__ . '/../config/app.php';
require_admin();

// Fetch all jabatan
$stmt = $pdo->query("SELECT j.*, (SELECT COUNT(*) FROM karyawan k WHERE k.jabatan_id = j.id) as total_karyawan FROM jabatan j ORDER BY j.gaji_pokok DESC");
$jabatan_list = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data Jabatan</h4>
        <p class="text-muted small mb-0">Kelola daftar jabatan serta standar gaji pokok dan tunjangan perusahaan.</p>
    </div>
    <a href="<?= base_url('jabatan/create.php') ?>" class="btn btn-primary-gradient btn-sm">
        <i class="fa-solid fa-plus me-1"></i> Tambah Jabatan
    </a>
</div>

<div class="card-modern">
    <div class="card-header-clean d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-briefcase text-primary me-2"></i> Daftar Jabatan & Standar Gaji</h6>
        <span class="badge bg-light text-muted border"><?= count($jabatan_list) ?> Total Jabatan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Nama Jabatan</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan Jabatan</th>
                    <th>Uang Makan / Hari</th>
                    <th>Jumlah Pegawai</th>
                    <th class="text-end" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jabatan_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data jabatan. Silakan klik Tambah Jabatan.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($jabatan_list as $j): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($j['nama_jabatan']) ?></strong>
                            </td>
                            <td class="fw-semibold text-dark"><?= format_rupiah($j['gaji_pokok']) ?></td>
                            <td class="text-secondary"><?= format_rupiah($j['tunjangan_jabatan']) ?></td>
                            <td class="text-muted"><?= format_rupiah($j['uang_makan_per_hari']) ?></td>
                            <td>
                                <span class="badge badge-soft-info"><?= (int)$j['total_karyawan'] ?> Orang</span>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('jabatan/edit.php?id=' . $j['id']) ?>" class="btn btn-sm btn-outline-warning text-dark me-1" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="<?= base_url('jabatan/delete.php?id=' . $j['id'] . '&csrf=' . csrf_token()) ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" data-confirm="Hapus jabatan '<?= htmlspecialchars($j['nama_jabatan']) ?>'?" title="Hapus">
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
