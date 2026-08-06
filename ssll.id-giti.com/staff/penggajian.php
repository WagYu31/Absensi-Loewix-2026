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
    $bulan_gaji = $_GET['bulan'] ?? date('m');
    $tahun_gaji = $_GET['tahun'] ?? date('Y');
}

$selected_karyawan = $_GET['karyawan'] ?? '';

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
    $stmt->execute();
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

// Query List Karyawan Aktif
$sql_karyawan = "SELECT nip, nik, nama, gaji_pokok, tunjangan, jenis_gaji, nama_bank, nama_pemilik_rekening, nomor_rekening, tanggal_masuk FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND nip NOT IN ('001', '70326') ";

if (!empty($selected_karyawan)) {
    $sql_karyawan .= " AND (nik = '$selected_karyawan' OR nip = '$selected_karyawan') ";
}

$sql_karyawan .= " ORDER BY nama ASC";
$result_karyawan = $conn->query($sql_karyawan);
$karyawan_list = $result_karyawan->fetch_all(MYSQLI_ASSOC);

// Map Rincian Gaji Periode Ini
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

// --- BULK HIGH-PERFORMANCE QUERY 1: CASHBON PAYMENT ---
$pembayaran_cashbon_map = [];
$sql_cb_bulk = "SELECT nip, SUM(bayar) as total_bayar FROM bayar_cashbon WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? GROUP BY nip";
$stmt_cb = $conn->prepare($sql_cb_bulk);
$stmt_cb->bind_param("ss", $bulan_gaji, $tahun_gaji);
$stmt_cb->execute();
$res_cb = $stmt_cb->get_result();
while ($r = $res_cb->fetch_assoc()) {
    $pembayaran_cashbon_map[$r['nip']] = $r['total_bayar'];
}
$stmt_cb->close();

// Global Jatah Cuti & Holidays
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

// --- BULK HIGH-PERFORMANCE QUERY 2 & 3: LEAVE RECORDS ---
$cuti_ytd_map = [];
$cuti_bulan_map = [];

$year_start_denda_str = "$tahun_denda-01-01";
$end_date_denda_month_str = $end_date_denda_dt->format('Y-m-d');
$start_date_denda_month_str = $periode_denda_dt->format('Y-m-01');

$sql_cuti_ytd = "SELECT nip, tgl_mulai, tgl_selesai FROM cuti WHERE verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL AND tgl_selesai >= ? AND tgl_mulai <= ?";
$stmt_c_ytd = $conn->prepare($sql_cuti_ytd);
$stmt_c_ytd->bind_param("ss", $year_start_denda_str, $end_date_denda_month_str);
$stmt_c_ytd->execute();
$res_c_ytd = $stmt_c_ytd->get_result();
while ($r = $res_c_ytd->fetch_assoc()) {
    $cuti_ytd_map[$r['nip']][] = $r;
}
$stmt_c_ytd->close();

$sql_cuti_bln = "SELECT nip, tgl_mulai, tgl_selesai FROM cuti WHERE verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL AND tgl_selesai >= ? AND tgl_mulai <= ?";
$stmt_c_bln = $conn->prepare($sql_cuti_bln);
$stmt_c_bln->bind_param("ss", $start_date_denda_month_str, $end_date_denda_month_str);
$stmt_c_bln->execute();
$res_c_bln = $stmt_c_bln->get_result();
while ($r = $res_c_bln->fetch_assoc()) {
    $cuti_bulan_map[$r['nip']][] = $r;
}
$stmt_c_bln->close();

$bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penggajian Karyawan - Gravitti Tech</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    
    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0284c7 100%);
            --card-radius-lg: 24px;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #f1f5f9 !important;
            color: #0f172a;
        }

        .main-content-wrapper {
            background: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%) !important;
            min-height: 100vh;
        }

        /* Hero Header Banner */
        .page-specific-header {
            background: var(--header-gradient) !important;
            color: #ffffff;
            padding: 2.25rem 0 4.5rem 0 !important;
            margin-bottom: -50px !important;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-specific-header h1 {
            font-weight: 800 !important;
            font-size: 1.65rem !important;
            letter-spacing: -0.5px;
            color: #ffffff !important;
        }

        /* Top Summary Stat Widgets */
        .stat-widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-widget-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease-out;
        }

        .stat-widget-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.07);
        }

        .widget-val {
            font-weight: 800;
            font-size: 1.65rem;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .widget-lbl {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .widget-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .widget-icon-box.emerald { background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #ffffff; }
        .widget-icon-box.blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #ffffff; }
        .widget-icon-box.amber { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); color: #ffffff; }

        /* Main Card */
        .card-gaji-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .emp-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #334155;
            font-weight: 800;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        }

        .table-custom-head {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom-head th {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 2px solid #e2e8f0;
        }

        @media print {
            .no-print { display: none !important; }
            body, .main-content-wrapper, .dashboard-content, .card-gaji-main {
                background: white !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .table { font-size: 10pt !important; color: black !important; }
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <!-- Header Banner -->
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Laporan Penggajian Karyawan</h1>
                <p class="small opacity-80 mb-0">Generate, kelola, verifikasi, dan ekspor laporan rekapitulasi gaji karyawan per periode.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <?php
                if (isset($_SESSION['pesan_flash'])) {
                    $flash = $_SESSION['pesan_flash'];
                    echo '<div class="alert alert-' . $flash['tipe'] . ' alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert"><i class="fa-solid fa-circle-info me-2"></i>' . htmlspecialchars($flash['pesan']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    unset($_SESSION['pesan_flash']);
                }
                ?>
                
                <!-- Action Controls Toolbar Card -->
                <div class="card-gaji-main no-print">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            
                            <!-- Periode & Filter Karyawan Form -->
                            <div class="col-lg-6">
                                <form method="GET" action="penggajian.php" class="row g-2 align-items-end">
                                    <div class="col-6 col-sm-3">
                                        <label for="bulan" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar me-1 text-primary"></i>Bulan</label>
                                        <select id="bulan" name="bulan" class="form-select form-select-sm rounded-3">
                                            <?php foreach ($bulanNames as $num => $name): ?>
                                                <option value="<?php echo $num; ?>" <?php if($num == $bulan_gaji) echo 'selected'; ?>><?php echo $name; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-6 col-sm-3">
                                        <label for="tahun" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar-days me-1 text-primary"></i>Tahun</label>
                                        <select id="tahun" name="tahun" class="form-select form-select-sm rounded-3">
                                            <?php $currentYear = date('Y');
                                            for ($i = $currentYear; $i >= $currentYear - 10; $i--): ?>
                                                <option value="<?php echo $i; ?>" <?php if($i == $tahun_gaji) echo 'selected'; ?>><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-4">
                                        <label for="karyawan" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-user me-1 text-primary"></i>Karyawan</label>
                                        <select id="karyawan" name="karyawan" class="form-select form-select-sm rounded-3">
                                            <option value="">-- Semua --</option>
                                            <?php foreach ($karyawan_list as $kar): ?>
                                                <option value="<?php echo htmlspecialchars($kar['nik']); ?>" <?php if($kar['nik'] == $selected_karyawan || $kar['nip'] == $selected_karyawan) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($kar['nama']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-2">
                                        <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 fw-bold py-1.5"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Lock, Generate, and Export Actions -->
                            <div class="col-lg-6 text-lg-end">
                                <div class="d-flex flex-wrap justify-content-lg-end align-items-center gap-2">
                                    <button id="generate" class="btn btn-success rounded-3 fw-bold btn-sm px-3 py-1.5 shadow-sm" onclick="generateDataAndCashbon()" <?php if ($is_locked) echo 'disabled'; ?>>
                                        <i class="fa-solid fa-rotate me-1.5"></i>Generate Data
                                    </button>
                                    
                                    <button id="lock" class="btn btn-warning rounded-3 fw-bold btn-sm px-3 py-1.5 shadow-sm text-dark" onclick="lockData()" <?php if ($is_locked) echo 'disabled'; ?>>
                                        <i class="fa-solid fa-lock me-1.5"></i>Lock Data
                                    </button>
                                    
                                    <button id="unlock" class="btn btn-outline-secondary rounded-3 fw-bold btn-sm px-3 py-1.5" onclick="unlockData()" <?php if (!$is_locked) echo 'disabled'; ?>>
                                        <i class="fa-solid fa-lock-open me-1.5"></i>Unlock
                                    </button>

                                    <div class="btn-group" role="group">
                                        <button class="btn btn-dark rounded-3 fw-bold btn-sm px-3 py-1.5 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-download me-1.5"></i>Simpan Laporan
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end rounded-3 shadow border-0">
                                            <li><a class="dropdown-item py-2" href="#" onclick="exportTableToExcel('tabel-gaji', 'laporan-gaji-<?php echo $bulan_gaji . '-' . $tahun_gaji; ?>')"><i class="fa-solid fa-file-excel text-success me-2"></i> Simpan ke Excel (.xls)</a></li>
                                            <li><a class="dropdown-item py-2" href="#" onclick="exportTableToCSV('laporan-gaji-<?php echo $bulan_gaji . '-' . $tahun_gaji; ?>.csv')"><i class="fa-solid fa-file-csv text-info me-2"></i> Simpan ke CSV</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item py-2" href="#" onclick="window.print()"><i class="fa-solid fa-print me-2"></i> Cetak / Simpan PDF</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Main Data Card -->
                <div class="card-gaji-main">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-wallet text-primary"></i> 
                            Laporan Gaji Karyawan - Periode <?php echo $bulanNames[$bulan_gaji] . ' ' . $tahun_gaji; ?>
                            <?php if ($is_locked): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold ms-2"><i class="fa-solid fa-lock me-1"></i>TERKUNCI</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold ms-2"><i class="fa-solid fa-lock-open me-1"></i>TERBUKA</span>
                            <?php endif; ?>
                        </h5>

                        <!-- Instant Live Search Bar -->
                        <div class="input-group input-group-sm no-print" style="max-width: 260px;">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="searchGajiInput" class="form-control border-start-0 bg-light" placeholder="Cari nama / NIK karyawan...">
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-nowrap" id="tabel-gaji" style="font-size: 0.88rem;">
                                <thead class="table-custom-head">
                                    <tr>
                                        <th class="ps-3" width="50">No</th>
                                        <th width="80">NIK</th>
                                        <th>Nama Karyawan</th>
                                        <th class="text-end">Gaji Pokok</th>
                                        <th class="text-end">Gaji Mingguan</th>
                                        <th class="text-end">Total Tunjangan</th>
                                        <th class="text-end">Total Denda</th>
                                        <th class="text-end">Denda Cuti</th>
                                        <th class="text-end">Bayar Cashbon</th>
                                        <th class="text-end">Total Gaji Net</th>
                                        <th class="no-print text-center" width="90">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $grand_total = 0;
                                    $no = 1;
                                    if (empty($rincian_gaji_map)) {
                                        echo '<tr><td colspan="11" class="text-center p-5 text-muted">Data gaji untuk periode ini belum di-generate. Silakan klik tombol <strong>"Generate Data"</strong> di atas.</td></tr>';
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
                                                
                                                $user_cuti_ytd = $cuti_ytd_map[$nip] ?? [];
                                                foreach ($user_cuti_ytd as $cuti_row) {
                                                    $cuti_start = new DateTime(max($cuti_row['tgl_mulai'], $year_start_denda_str));
                                                    $cuti_end = new DateTime(min($cuti_row['tgl_selesai'], $end_date_denda_month_str));
                                                    $total_cuti_terpakai_ytd += hitungHariKerjaCuti($cuti_start->format('Y-m-d'), $cuti_end->format('Y-m-d'), $holidays);
                                                }

                                                $user_cuti_bln = $cuti_bulan_map[$nip] ?? [];
                                                foreach ($user_cuti_bln as $cuti_row_bulan) {
                                                    $cuti_start_bulan = new DateTime(max($cuti_row_bulan['tgl_mulai'], $start_date_denda_month_str));
                                                    $cuti_end_bulan = new DateTime(min($cuti_row_bulan['tgl_selesai'], $end_date_denda_month_str));
                                                    $total_cuti_terpakai_bulan_ini += hitungHariKerjaCuti($cuti_start_bulan->format('Y-m-d'), $cuti_end_bulan->format('Y-m-d'), $holidays);
                                                }
                                                
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

                                                $words = explode(' ', trim($karyawan['nama']));
                                                $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                        ?>
                                        <tr class="gaji-row">
                                            <td class="ps-3 text-secondary fw-semibold"><?php echo $no++; ?></td>
                                            <td class="fw-semibold text-secondary"><?php echo htmlspecialchars($karyawan['nik']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="emp-avatar"><?php echo $init; ?></span>
                                                    <span class="fw-bold text-dark" style="text-transform:capitalize;"><?php echo htmlspecialchars($karyawan['nama']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-end fw-medium text-secondary">Rp <?php echo number_format($data['gaji'], 0, ',', '.'); ?></td>
                                            <td class="text-end text-muted"><?php echo ($gaji_mingguan > 0) ? 'Rp ' . number_format($gaji_mingguan, 0, ',', '.') : '-'; ?></td>
                                            <td class="text-end text-success fw-medium">Rp <?php echo number_format($total_tunjangan, 0, ',', '.'); ?></td>
                                            <td class="text-end text-danger">Rp <?php echo number_format($data['denda'], 0, ',', '.'); ?></td>
                                            <td class="text-end text-danger">Rp <?php echo number_format($total_denda_cuti, 0, ',', '.'); ?></td>
                                            <td class="text-end text-warning-emphasis">Rp <?php echo number_format($bayar_cashbon, 0, ',', '.'); ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-emerald-subtle fw-bold fs-6 px-3 py-1.5" style="background: rgba(16, 185, 129, 0.12); color: #047857; border: 1px solid rgba(16, 185, 129, 0.2);">
                                                    Rp <?php echo number_format($total_gaji, 0, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td class="no-print text-center">
                                                <a href="slip-gaji.php?nip=<?php echo $nip; ?>&bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>" class="btn btn-outline-primary btn-sm rounded-3 px-2 py-1 me-1" title="Lihat Detail Slip Gaji"><i class="fa-solid fa-eye"></i></a>
                                                <button onclick="deleteGaji('<?php echo $data['id_rincian_gaji']; ?>')" class="btn btn-outline-danger btn-sm rounded-3 px-2 py-1" title="Hapus Data Gaji Ini" <?php if($is_locked) echo 'disabled'; ?>><i class="fa-solid fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        }
                                    }
                                    ?>
                                </tbody>
                                <?php if (!empty($rincian_gaji_map)): ?>
                                <tfoot>
                                    <tr class="fw-bold bg-light text-dark fs-6" style="border-top: 2px solid #cbd5e1;">
                                        <td colspan="9" class="text-end py-3">GRAND TOTAL PAYROLL:</td>
                                        <td class="text-end py-3 text-emerald fw-bold text-success fs-5">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
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

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Live Search Bar
            $('#searchGajiInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#tabel-gaji tbody tr.gaji-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });

        function deleteGaji(id_rincian_gaji) {
            if (confirm("Apakah Anda yakin ingin menghapus data gaji ini?")) {
                window.location.href = "penggajian.php?deleteID=" + id_rincian_gaji + "&bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>";
            }
        }

        function generateDataAndCashbon() {
            if (confirm("Apakah Anda yakin ingin me-generate data gaji dan cashbon untuk bulan ini?")) {
                let btn = document.getElementById("generate");
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...';
                btn.disabled = true;

                $.ajax({
                    url: 'sa-generate-cashbon-otomatis.php',
                    type: 'POST',
                    data: { bulan: '<?php echo $bulan_gaji; ?>', tahun: '<?php echo $tahun_gaji; ?>' },
                    dataType: 'json',
                    success: function(response) {
                        window.location.href = "proses-generate-gaji.php?bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>";
                    },
                    error: function() {
                        window.location.href = "proses-generate-gaji.php?bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>";
                    }
                });
            }
        }

        function lockData() {
            if (confirm("Apakah Anda yakin ingin mengunci data gaji periode ini? Data yang dikunci tidak dapat diubah atau dihapus.")) {
                window.location.href = "proses-lock-gaji.php?bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>&action=lock";
            }
        }

        function unlockData() {
            if (confirm("Apakah Anda yakin ingin membuka kunci data gaji periode ini?")) {
                window.location.href = "proses-lock-gaji.php?bulan=<?php echo $bulan_gaji; ?>&tahun=<?php echo $tahun_gaji; ?>&action=unlock";
            }
        }

        function exportTableToExcel(tableID, filename = ''){
            var downloadLink;
            var dataType = 'application/vnd.ms-excel';
            var tableSelect = document.getElementById(tableID);
            var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
            
            filename = filename?filename+'.xls':'excel_data.xls';
            downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            
            if(navigator.msSaveOrOpenBlob){
                var blob = new Blob(['\ufeff' + tableHTML], { type: dataType });
                navigator.msSaveOrOpenBlob( blob, filename);
            } else {
                downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
                downloadLink.download = filename;
                downloadLink.click();
            }
        }

        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#tabel-gaji tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length - 1; j++) {
                    var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                csv.push(row.join(","));
            }

            var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>