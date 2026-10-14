<?php
require_once __DIR__ . '/../config/app.php';
require_admin();

$bulan = (int)($_POST['bulan'] ?? $_GET['bulan'] ?? date('n'));
$tahun = (int)($_POST['tahun'] ?? $_GET['tahun'] ?? date('Y'));

// Standard rates
$tarif_lembur_per_jam = 25000;
$tarif_potongan_alpa = 75000;
$persen_bpjs = 0.03; // 3%
$persen_pph21 = 0.025; // 2.5%

try {
    // 1. Fetch all active employees with their job data and attendance for this period
    $stmt = $pdo->prepare("SELECT k.id as karyawan_id, k.nama, k.nip, 
            j.gaji_pokok, j.tunjangan_jabatan, j.uang_makan_per_hari,
            COALESCE(a.hadir, 22) as hadir,
            COALESCE(a.alpa, 0) as alpa,
            COALESCE(a.lembur_jam, 0) as lembur_jam
        FROM karyawan k 
        JOIN jabatan j ON k.jabatan_id = j.id 
        LEFT JOIN absensi a ON k.id = a.karyawan_id AND a.bulan = ? AND a.tahun = ?");
    $stmt->execute([$bulan, $tahun]);
    $karyawan_list = $stmt->fetchAll();

    if (empty($karyawan_list)) {
        set_flash('warning', 'Tidak ada data karyawan yang dapat diproses.');
        header("Location: " . base_url("penggajian/index.php?bulan={$bulan}&tahun={$tahun}"));
        exit;
    }

    $pdo->beginTransaction();

    $stmtUpsert = $pdo->prepare("INSERT INTO gaji 
        (karyawan_id, bulan, tahun, gaji_pokok, tunjangan_jabatan, uang_makan, insentif_lembur, 
         potongan_alpa, potongan_bpjs, potongan_pph21, total_pendapatan, total_potongan, take_home_pay, status, catatan)
        VALUES 
        (:karyawan_id, :bulan, :tahun, :gaji_pokok, :tunjangan_jabatan, :uang_makan, :insentif_lembur, 
         :potongan_alpa, :potongan_bpjs, :potongan_pph21, :total_pendapatan, :total_potongan, :take_home_pay, 'Pending', :catatan)
        ON DUPLICATE KEY UPDATE 
        gaji_pokok = VALUES(gaji_pokok),
        tunjangan_jabatan = VALUES(tunjangan_jabatan),
        uang_makan = VALUES(uang_makan),
        insentif_lembur = VALUES(insentif_lembur),
        potongan_alpa = VALUES(potongan_alpa),
        potongan_bpjs = VALUES(potongan_bpjs),
        potongan_pph21 = VALUES(potongan_pph21),
        total_pendapatan = VALUES(total_pendapatan),
        total_potongan = VALUES(total_potongan),
        take_home_pay = VALUES(take_home_pay)");

    $count = 0;
    foreach ($karyawan_list as $row) {
        $gaji_pokok = (float)$row['gaji_pokok'];
        $tunjangan_jabatan = (float)$row['tunjangan_jabatan'];
        $uang_makan = (float)($row['hadir'] * $row['uang_makan_per_hari']);
        $insentif_lembur = (float)($row['lembur_jam'] * $tarif_lembur_per_jam);

        $total_pendapatan = $gaji_pokok + $tunjangan_jabatan + $uang_makan + $insentif_lembur;

        $potongan_alpa = (float)($row['alpa'] * $tarif_potongan_alpa);
        $potongan_bpjs = round($gaji_pokok * $persen_bpjs, 2);
        $potongan_pph21 = round(($gaji_pokok + $tunjangan_jabatan) * $persen_pph21, 2);

        $total_potongan = $potongan_alpa + $potongan_bpjs + $potongan_pph21;
        $take_home_pay = max(0, $total_pendapatan - $total_potongan);

        $catatan = "Kalkulasi otomatis periode " . bulan_indonesia($bulan) . " {$tahun}";

        $stmtUpsert->execute([
            ':karyawan_id'        => $row['karyawan_id'],
            ':bulan'              => $bulan,
            ':tahun'              => $tahun,
            ':gaji_pokok'         => $gaji_pokok,
            ':tunjangan_jabatan'  => $tunjangan_jabatan,
            ':uang_makan'         => $uang_makan,
            ':insentif_lembur'    => $insentif_lembur,
            ':potongan_alpa'      => $potongan_alpa,
            ':potongan_bpjs'      => $potongan_bpjs,
            ':potongan_pph21'     => $potongan_pph21,
            ':total_pendapatan'   => $total_pendapatan,
            ':total_potongan'     => $total_potongan,
            ':take_home_pay'      => $take_home_pay,
            ':catatan'            => $catatan
        ]);
        $count++;
    }

    $pdo->commit();
    set_flash('success', "Sukses! Kalkulasi gaji untuk <strong>{$count} karyawan</strong> periode <strong>" . bulan_indonesia($bulan) . " {$tahun}</strong> berhasil di-generate.");
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('danger', 'Gagal memproses perhitungan gaji: ' . $e->getMessage());
}

header("Location: " . base_url("penggajian/index.php?bulan={$bulan}&tahun={$tahun}"));
exit;
