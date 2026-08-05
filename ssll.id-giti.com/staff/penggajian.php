<?php
session_start();

if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include '../conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['bulan'])) {
    $bulan_gaji = $_POST["bulan"];
    $tahun_gaji = $_POST["tahun"];
} else {
    $bulan_gaji = date('m');
    $tahun_gaji = date('Y');
}

$periode_gaji_dt = new DateTime("$tahun_gaji-$bulan_gaji-01");
$periode_denda_dt = (clone $periode_gaji_dt)->modify('-1 month');
$bulan_denda = $periode_denda_dt->format('m');
$tahun_denda = $periode_denda_dt->format('Y');
$end_date_denda_dt = new DateTime($periode_denda_dt->format('Y-m-t'));

function isDataLocked($conn, $bulan, $tahun) {
    $stmt = $conn->prepare("SELECT kunci FROM kunci_gaji WHERE bulan = ? AND tahun = ? AND kunci = 'Lock'");
    $stmt->bind_param("ss", $bulan, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}
$is_locked = isDataLocked($conn, $bulan_gaji, $tahun_gaji);

function deleteGaji($conn, $id_rincian_gaji) {
    $stmt = $conn->prepare("DELETE FROM rincian_gaji WHERE id_rincian_gaji = ?");
    $stmt->bind_param("i", $id_rincian_gaji);
    if (!$stmt->execute()) {
    }
    $stmt->close();
}

if (isset($_GET['deleteID'])) {
    if (!$is_locked) { 
        deleteGaji($conn, $_GET['deleteID']);
        header("Location: penggajian.php?bulan=$bulan_gaji&tahun=$tahun_gaji");
        exit();
    } else {
        $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal menghapus. Data gaji untuk periode ini sudah terkunci.'];
        header("Location: penggajian.php?bulan=$bulan_gaji&tahun=$tahun_gaji");
        exit();
    }
}

$sql_karyawan = "SELECT nip, nik, nama, gaji_pokok, tunjangan, jenis_gaji, nama_bank, nama_pemilik_rekening, nomor_rekening, tanggal_masuk FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND nip NOT IN ('001', '70326') ORDER BY nama ASC";
$result_karyawan = $conn->query($sql_karyawan);
$karyawan_list = $result_karyawan->fetch_all(MYSQLI_ASSOC);

$rincian_gaji_map = [];
$sql_rincian = "SELECT * FROM rincian_gaji WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
$stmt_rincian = $conn->prepare($sql_rincian);
$stmt_rincian->bind_param("ss", $bulan_gaji, $tahun_gaji);
$stmt_rincian->execute();
$result_rincian = $stmt_rincian->get_result();
while ($row = $result_rincian->fetch_assoc()) {
    $rincian_gaji_map[$row['nip']] = $row; 
}
$stmt_rincian->close();

$pembayaran_cashbon_map = [];
$global_jatah_cuti = 0;
$holidays = [];

$stmt_quota = $conn->prepare("SELECT jumlah FROM jatah_cuti_tahunan WHERE tahun = ? LIMIT 1");
$stmt_quota->bind_param("s", $tahun_denda);
$stmt_quota->execute();
$result_quota = $stmt_quota->get_result();
if ($result_quota->num_rows > 0) {
    $global_jatah_cuti = (int)$result_quota->fetch_assoc()['jumlah'];
}
$stmt_quota->close();

$stmt_holidays = $conn->prepare("SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND YEAR(tanggal_merah) = ?");
$stmt_holidays->bind_param("s", $tahun_denda);
$stmt_holidays->execute();
$result_holidays = $stmt_holidays->get_result();
while ($row = $result_holidays->fetch_assoc()) {
    if (!empty($row['tanggal_merah'])) {
        $holidays[$row['tanggal_merah']] = true;
    }
}
$stmt_holidays->close();

if (!function_exists('hitungHariKerjaCuti')) {
    function hitungHariKerjaCuti($tgl_mulai, $tgl_selesai, $holidays) {
        if (empty($tgl_mulai) || empty($tgl_selesai) || $tgl_mulai == '0000-00-00' || $tgl_selesai == '0000-00-00') {
            return 0;
        }
        try {
            $start = new DateTime($tgl_mulai);
            $end = new DateTime($tgl_selesai);
            if ($start > $end) return 0;
            
            $end->modify('+1 day');
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);
            $duration = 0;
            
            foreach ($period as $date) {
                $dayOfWeek = $date->format('N');
                $dateString = $date->format('Y-m-d');
                if ($dayOfWeek != 7 && !isset($holidays[$dateString])) {
                    $duration++;
                }
            }
            return $duration;
        } catch (Exception $e) {
            return 0;
        }
    }
}

foreach ($karyawan_list as $karyawan) {
    $nip_karyawan = $karyawan['nip'];
    
    $sql_cb = "SELECT SUM(bayar) as total_bayar FROM bayar_cashbon WHERE nip = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
    $stmt_cb = $conn->prepare($sql_cb);
    $stmt_cb->bind_param("sss", $nip_karyawan, $bulan_gaji, $tahun_gaji);
    $stmt_cb->execute();
    $result_cb = $stmt_cb->get_result();
    $pembayaran_cashbon_map[$nip_karyawan] = $result_cb->fetch_assoc()['total_bayar'] ?? 0;
    $stmt_cb->close();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penggajian Karyawan - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .table-sm th, .table-sm td { font-size: 0.85rem; padding: 0.4rem; vertical-align: middle; }
        
        @media (max-width: 767.98px) {
            .page-specific-header { padding: 1.5rem 1rem; }
            .page-specific-header h1 { font-size: 1.5rem; }
            .card-header h5 { font-size: 1.1rem; }
            .mobile-action-stack { flex-direction: column; align-items: stretch; gap: 0.5rem; }
            .mobile-action-stack > * { width: 100%; text-align: center; }
            .mobile-action-stack .dropdown-menu { width: 100%; text-align: center; }
        }

        @media print {
            .no-print { display: none !important; }
            body, .main-content-wrapper, .dashboard-content, .card {
                background-color: white !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .table { font-size: 10pt !important; color: black !important; }
            .table-striped tbody tr:nth-of-type(odd) { background-color: transparent !important; }
            .card-header { border: none; text-align: center; }
            .container-fluid { padding: 0 !important; }
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Laporan Penggajian</h1>
                <p>Generate, kelola, dan lihat laporan gaji karyawan per periode.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <?php
                if (isset($_SESSION['pesan_flash'])) {
                    $flash = $_SESSION['pesan_flash'];
                    echo '<div class="alert alert-' . $flash['tipe'] . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($flash['pesan']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    unset($_SESSION['pesan_flash']);
                }
                ?>
                
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-lg-5">
                                <form method="POST" class="row g-2 align-items-end">
                                    <div class="col-12 col-sm-5">
                                        <label for="bulan" class="form-label">Periode Bulan</label>
                                        <select id="bulan" name="bulan" class="form-select">
                                            <?php $bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                            foreach ($bulanNames as $num => $name) {
                                                echo "<option value='$num' " . ($num == $bulan_gaji ? 'selected' : '') . ">$name</option>";
                                            } ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                            <label for="tahun" class="form-label d-none d-sm-block">&nbsp;</label>
                                        <select id="tahun" name="tahun" class="form-select">
                                            <?php $currentYear = date('Y');
                                            for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                                                echo "<option value='$i' " . ($i == $tahun_gaji ? 'selected' : '') . ">$i</option>";
                                            } ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-3">
                                            <label class="form-label d-none d-sm-block">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-7 text-lg-end mt-4 mt-lg-0">
                                    <label class="form-label d-none d-lg-block">&nbsp;</label>
                                <div class="d-flex flex-wrap justify-content-lg-end mobile-action-stack gap-2" role="group">
                                    <button id="generate" class="btn btn-success" onclick="generateDataAndCashbon()" <?php if ($is_locked) echo 'disabled'; ?>><i class="fa-solid fa-list-check me-1"></i> Generate Data</button>
                                    <button id="lock" class="btn btn-warning" onclick="lockData()" <?php if ($is_locked) echo 'disabled'; ?>><i class="fas fa-lock me-1"></i> Lock Data</button>
                                    <button id="unlock" class="btn btn-secondary" onclick="unlockData()" <?php if (!$is_locked) echo 'disabled'; ?>><i class="fas fa-lock-open me-1"></i> Unlock Data</button>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-dark dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-download me-1"></i> Simpan Laporan</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="exportTableToExcel('tabel-gaji', 'laporan-gaji-<?php echo $bulan_gaji . '-' . $tahun_gaji; ?>')"><i class="fas fa-file-excel me-2"></i> Simpan ke Excel (.xls)</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="exportTableToCSV('laporan-gaji-<?php echo $bulan_gaji . '-' . $tahun_gaji; ?>.csv')"><i class="fas fa-file-csv me-2"></i> Simpan ke CSV</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="fas fa-print me-2"></i> Cetak / Simpan ke PDF</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-5">
                        <div class="card-header">
                            <h5 class="mb-0">Laporan Gaji Karyawan - Periode <?php echo $bulanNames[$bulan_gaji] . ' ' . $tahun_gaji; ?></h5>
                        </div>
                        <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm mb-0 text-nowrap" id="tabel-gaji">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>NIK</th>
                                                <th>Nama</th>
                                                <th>Gaji Pokok</th>
                                                <th>Gaji Mingguan</th>
                                                <th>Total Tunjangan</th>
                                                <th>Total Denda</th>
                                                <th>Total Denda Cuti</th>
                                                <th>Bayar Cashbon</th>
                                                <th>Total Gaji</th>
                                                <th class="no-print text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $grand_total = 0;
                                            $no = 1;
                                            if (empty($rincian_gaji_map)) {
                                                echo '<tr><td colspan="11" class="text-center p-5 text-muted">Data gaji untuk periode ini belum di-generate. Silakan klik tombol "Generate Data".</td></tr>';
                                            } else {
                                                foreach ($karyawan_list as $karyawan) {
                                                    $nip = $karyawan['nip'];
                                                    if (isset($rincian_gaji_map[$nip])) {
                                                        $data = $rincian_gaji_map[$nip];
                                                        
                                                        $dataTMK = ['tunjangan_masa_kerja' => 0]; 
                                                        if (file_exists('get-tunjangan-masa-kerja.php')) {
                                                            $temp_karyawan_for_tmk = $karyawan;
                                                            include 'get-tunjangan-masa-kerja.php'; 
                                                            unset($temp_karyawan_for_tmk); 
                                                        }
                                                        $tunjangan_masa_kerja = $dataTMK['tunjangan_masa_kerja'] ?? 0;
                                                        
                                                        $total_tunjangan = ($karyawan['tunjangan'] ?? 0) + $tunjangan_masa_kerja + ($data['tunjangan_lainnya'] ?? 0);
                                                        $bayar_cashbon = $pembayaran_cashbon_map[$nip] ?? 0;
                                                        $gaji_mingguan = ($karyawan['jenis_gaji'] === 'mingguan') ? ($data['m1'] ?? 0) : 0;
                                                        
                                                        $total_denda_cuti = 0;
                                                        $jatah_cuti_karyawan_ini = 0;
                                                        
                                                        if (!empty($karyawan['tanggal_masuk']) && $karyawan['tanggal_masuk'] != '0000-00-00') {
                                                            try {
                                                                $tgl_masuk_plus_6_bulan = (new DateTime($karyawan['tanggal_masuk']))->modify('+6 months');
                                                                if ($tgl_masuk_plus_6_bulan <= $end_date_denda_dt) {
                                                                    $jatah_cuti_karyawan_ini = $global_jatah_cuti;
                                                                }
                                                            } catch (Exception $e) { }
                                                        }

                                                        $total_cuti_terpakai_ytd = 0;
                                                        $total_cuti_terpakai_bulan_ini = 0;
                                                        
                                                        $year_start_denda_str = "$tahun_denda-01-01";
                                                        $end_date_denda_month_str = $end_date_denda_dt->format('Y-m-d');

                                                        $stmt_cuti_ytd = $conn->prepare("SELECT tgl_mulai, tgl_selesai FROM cuti WHERE nip = ? AND verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL AND tgl_selesai >= ? AND tgl_mulai <= ?");
                                                        $stmt_cuti_ytd->bind_param("sss", $nip, $year_start_denda_str, $end_date_denda_month_str);
                                                        $stmt_cuti_ytd->execute();
                                                        $result_cuti_ytd = $stmt_cuti_ytd->get_result();
                                                        
                                                        while ($cuti_row = $result_cuti_ytd->fetch_assoc()) {
                                                            $cuti_start = new DateTime(max($cuti_row['tgl_mulai'], $year_start_denda_str));
                                                            $cuti_end = new DateTime(min($cuti_row['tgl_selesai'], $end_date_denda_month_str));
                                                            $total_cuti_terpakai_ytd += hitungHariKerjaCuti($cuti_start->format('Y-m-d'), $cuti_end->format('Y-m-d'), $holidays);
                                                        }
                                                        $stmt_cuti_ytd->close();

                                                        $start_date_denda_month_str = $periode_denda_dt->format('Y-m-01');

                                                        $stmt_cuti_bulan = $conn->prepare("SELECT tgl_mulai, tgl_selesai FROM cuti WHERE nip = ? AND verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL AND tgl_selesai >= ? AND tgl_mulai <= ?");
                                                        $stmt_cuti_bulan->bind_param("sss", $nip, $start_date_denda_month_str, $end_date_denda_month_str);
                                                        $stmt_cuti_bulan->execute();
                                                        $result_cuti_bulan = $stmt_cuti_bulan->get_result();

                                                        while ($cuti_row_bulan = $result_cuti_bulan->fetch_assoc()) {
                                                            $cuti_start_bulan = new DateTime(max($cuti_row_bulan['tgl_mulai'], $start_date_denda_month_str));
                                                            $cuti_end_bulan = new DateTime(min($cuti_row_bulan['tgl_selesai'], $end_date_denda_month_str));
                                                            $total_cuti_terpakai_bulan_ini += hitungHariKerjaCuti($cuti_start_bulan->format('Y-m-d'), $cuti_end_bulan->format('Y-m-d'), $holidays);
                                                        }
                                                        $stmt_cuti_bulan->close();
                                                        
                                                        $hari_kena_denda = 0;
                                                        $sisa_cuti_sebelum_bulan_ini = $jatah_cuti_karyawan_ini - ($total_cuti_terpakai_ytd - $total_cuti_terpakai_bulan_ini);
                                                        
                                                        if ($sisa_cuti_sebelum_bulan_ini < $total_cuti_terpakai_bulan_ini) {
                                                            $hari_kena_denda = $total_cuti_terpakai_bulan_ini - max(0, $sisa_cuti_sebelum_bulan_ini);
                                                        }
                                                        
                                                        $total_gaji_untuk_denda = $karyawan['gaji_pokok'] + $total_tunjangan;
                                                        $denda_per_hari = $total_gaji_untuk_denda / 26;
                                                        $total_denda_cuti = $denda_per_hari * $hari_kena_denda;
                                                        
                                                        $total_gaji = ($data['gaji'] ?? 0) - $gaji_mingguan + $total_tunjangan - ($data['denda'] ?? 0) - $bayar_cashbon - $total_denda_cuti;
                                                        $grand_total += $total_gaji;
                                                ?>
                                                <tr 
                                                    data-no="<?php echo $no; ?>"
                                                    data-nik="<?php echo htmlspecialchars($karyawan['nik']); ?>"
                                                    data-nama="<?php echo htmlspecialchars($karyawan['nama']); ?>"
                                                    data-gaji-pokok="<?php echo htmlspecialchars($data['gaji']); ?>"
                                                    data-gaji-mingguan="<?php echo htmlspecialchars($gaji_mingguan); ?>"
                                                    data-total-tunjangan="<?php echo htmlspecialchars($total_tunjangan); ?>"
                                                    data-total-denda="<?php echo htmlspecialchars($data['denda']); ?>"
                                                    data-total-denda-cuti="<?php echo htmlspecialchars($total_denda_cuti); ?>"
                                                    data-bayar-cashbon="<?php echo htmlspecialchars($bayar_cashbon); ?>"
                                                    data-nama-bank="<?php echo htmlspecialchars($karyawan['nama_bank']); ?>"
                                                    data-nama-pemilik-rekening="<?php echo htmlspecialchars($karyawan['nama_pemilik_rekening']); ?>"
                                                    data-nomor-rekening="<?php echo htmlspecialchars($karyawan['nomor_rekening']); ?>"
                                                    data-total-gaji="<?php echo htmlspecialchars($total_gaji); ?>"
                                                >
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($karyawan['nik']); ?></td>
                                                    <td style="text-transform:capitalize;"><?php echo htmlspecialchars($karyawan['nama']); ?></td>
                                                    <td>Rp <?php echo number_format($data['gaji'], 0, ',', '.'); ?></td>
                                                    <td><?php echo ($gaji_mingguan > 0) ? 'Rp ' . number_format($gaji_mingguan, 0, ',', '.') : '-'; ?></td>
                                                    <td>Rp <?php echo number_format($total_tunjangan, 0, ',', '.'); ?></td>
                                                    <td>Rp <?php echo number_format($data['denda'], 0, ',', '.'); ?></td>
                                                    <td>
                                                        Rp <?php echo number_format($total_denda_cuti, 0, ',', '.'); ?>
                                                    </td>
                                                    <td>Rp <?php echo number_format($bayar_cashbon, 0, ',', '.'); ?></td>
                                                    <td class="fw-bold">Rp <?php echo number_format($total_gaji, 0, ',', '.'); ?></td>
                                                    <td class="no-print text-center">
                                                        <a href="slip-gaji.php?nip=<?php echo $nip; ?>&bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>" class="btn btn-info btn-sm" title="Lihat Detail"><i class="fa-solid fa-eye"></i></a>
                                                        <button onclick="deleteGaji('<?php echo $data['id_rincian_gaji']; ?>')" class="btn btn-danger btn-sm" title="Hapus Data Gaji Ini" <?php if($is_locked) echo 'disabled'; ?>><i class="fa-solid fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                                <?php
                                                    }
                                                }
                                            }
                                            ?>
                                        </tbody>
                                        <?php if (!empty($rincian_gaji_map)): ?>
                                        <tfoot class="table-group-divider">
                                            <tr class="fw-bold bg-light">
                                                <td colspan="9" class="text-end">Grand Total</td>
                                                <td>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
                                                <td class="no-print"></td>
                                            </tr>
                                        </tfoot>
                                        <?php endif; ?>
                                    </table>
                                </div>
                        </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <?php $conn->close(); ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteGaji(id_rincian_gaji) {
            if (confirm("Apakah Anda yakin ingin menghapus data gaji ini?")) {
                window.location.href = "penggajian.php?deleteID=" + id_rincian_gaji + "&bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>";
            }
        }
        
        function generateDataAndCashbon() {
            if(confirm("Generate data akan membuat/memperbarui rincian gaji untuk semua karyawan di periode ini. Lanjutkan?")) {
                var bulan = document.getElementById("bulan").value;
                var tahun = document.getElementById("tahun").value;
                
                $.get("generate-data.php", { bulan: bulan, tahun: tahun })
                    .done(function() {
                        $.get("generate-cb.php", { bulan: bulan, tahun: tahun })
                            .done(function() {
                                alert("Data berhasil di-generate dan diperbarui.");
                                window.location.reload();
                            })
                            .fail(function() {
                                alert("Terjadi kesalahan saat meng-generate data cashbon.");
                            });
                    })
                    .fail(function() {
                        alert("Terjadi kesalahan saat meng-generate data gaji.");
                    });
            }
        }

        function lockData() {
            if(confirm("Apakah Anda yakin ingin mengunci data gaji periode ini? Setelah dikunci, data tidak bisa diubah atau dihapus.")) {
                var bulan = document.getElementById("bulan").value;
                var tahun = document.getElementById("tahun").value;
                window.location.href = "lock-data.php?bulan=" + bulan + "&tahun=" + tahun;
            }
        }

        function unlockData() {
            if(confirm("Apakah Anda yakin ingin membuka kunci data gaji periode ini?")) {
                var bulan = document.getElementById("bulan").value;
                var tahun = document.getElementById("tahun").value;
                window.location.href = "unlock-data.php?bulan=" + bulan + "&tahun=" + tahun;
            }
        }
        
        function exportTableToCSV(filename) {
            let csv = [];
            const rows = document.querySelectorAll("#tabel-gaji tr");
            
            for (const row of rows) {
                const cols = row.querySelectorAll("td, th");
                const rowData = [];
                for (const col of cols) {
                    if (!col.classList.contains('no-print')) {
                        let data = col.innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/(\s\s)/gm, " ");
                        data = `"${data.replace(/"/g, '""')}"`; 
                        rowData.push(data);
                    }
                }
                if (rowData.length > 0) {
                    csv.push(rowData.join(","));
                }
            }

            const csvFile = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
            const downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        function exportTableToExcel(tableID, filename = '') {
            const dataType = 'application/vnd.ms-excel';
            const tableSelect = document.getElementById(tableID);
        
            const formatKapital = (str) => {
                if (!str) return '';
                return str.toLowerCase().replace(/\b\w/g, function(huruf) {
                    return huruf.toUpperCase();
                });
            };
        
            let finalTableHTML = `
                        <table border="1">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Gaji Pokok</th>
                                    <th>Gaji Mingguan</th>
                                    <th>Total Tunjangan</th>
                                    <th>Total Denda</th>
                                    <th>Total Denda Cuti</th>
                                    <th>Bayar Cashbon</th>
                                    <th>Nama Bank</th>
                                    <th>Nama Pemilik Rekening</th>
                                    <th>Nomor Rekening</th>
                                    <th>Total Gaji</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
        
            const rows = tableSelect.querySelectorAll('tbody tr');
            rows.forEach((row) => {
                if (row.querySelector('td[colspan="11"]')) {
                    return;
                }
        
                const no = row.dataset.no;
                const nik = row.dataset.nik;
                const nama = row.dataset.nama;
                const gajiPokok = Math.round(parseFloat(row.dataset.gajiPokok || 0));
                const gajiMingguan = Math.round(parseFloat(row.dataset.gajiMingguan || 0));
                const totalTunjangan = Math.round(parseFloat(row.dataset.totalTunjangan || 0));
                const totalDenda = Math.round(parseFloat(row.dataset.totalDenda || 0));
                const totalDendaCuti = Math.round(parseFloat(row.dataset.totalDendaCuti || 0));
                const bayarCashbon = Math.round(parseFloat(row.dataset.bayarCashbon || 0));
                const namaBank = row.dataset.namaBank;
                const namaPemilikRekening = row.dataset.namaPemilikRekening;
                const nomorRekening = row.dataset.nomorRekening;
                const totalGaji = Math.round(parseFloat(row.dataset.totalGaji || 0));
        
                finalTableHTML += `
                            <tr>
                                <td>${no}</td>
                                <td>${nik}</td>
                                
                                <td>${formatKapital(nama)}</td>
                                
                                <td data-format="Currency">Rp ${gajiPokok.toLocaleString('id-ID')}</td>
                                <td data-format="Currency">${gajiMingguan > 0 ? 'Rp ' + gajiMingguan.toLocaleString('id-ID') : '-'}</td>
                                <td data-format="Currency">Rp ${totalTunjangan.toLocaleString('id-ID')}</td>
                                <td data-format="Currency">Rp ${totalDenda.toLocaleString('id-ID')}</td>
                                <td data-format="Currency">Rp ${totalDendaCuti.toLocaleString('id-ID')}</td>
                                <td data-format="Currency">Rp ${bayarCashbon.toLocaleString('id-ID')}</td>
                                
                                <td>${namaBank ? namaBank.toUpperCase() : '-'}</td>
                                
                                <td>${namaPemilikRekening}</td>
                                <td>${nomorRekening}</td>
                                <td data-format="Currency">Rp ${totalGaji.toLocaleString('id-ID')}</td>
                            </tr>
                        `;
            });
        
            finalTableHTML += `</tbody>`;
        
            const tfoot = tableSelect.querySelector('tfoot');
            if (tfoot) {
                const grandTotalCell = tfoot.querySelector('td:nth-last-child(2)');
                if (grandTotalCell) {
                    const grandTotalValue = Math.round(parseFloat(grandTotalCell.innerText.replace(/Rp\s*|\./g, '').replace(',', '.') || 0));
                    finalTableHTML += `
                                <tfoot>
                                    <tr>
                                        <td colspan="12" style="text-align:right; font-weight:bold;">Grand Total</td>
                                        <td data-format="Currency">Rp ${grandTotalValue.toLocaleString('id-ID')}</td>
                                    </tr>
                                </tfoot>
                            `;
                }
            }
            finalTableHTML += `</table>`;
        
            filename = filename ? filename + '.xls' : 'excel_data.xls';
        
            const downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
        
            const html_to_export = `
                        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                        <head>
                            <meta charset="utf-8">
                            </head>
                        <body>
                            ${finalTableHTML}
                        </body>
                        </html>
                    `;
        
            const blob = new Blob([html_to_export], {
                type: 'application/vnd.ms-excel;charset=utf-8;'
            });
        
            if (navigator.msSaveOrOpenBlob) {
                navigator.msSaveOrOpenBlob(blob, filename);
            } else {
                downloadLink.href = URL.createObjectURL(blob);
                downloadLink.download = filename;
                downloadLink.click();
            }
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>