<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

function getDistanceBetweenPoints($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'meters')
{
    $earthRadius = ($unit === 'kilometers') ? 6371 : 6371000;

    $latFrom = deg2rad(floatval($latitude1));
    $lonFrom = deg2rad(floatval($longitude1));
    $latTo = deg2rad(floatval($latitude2));
    $lonTo = deg2rad(floatval($longitude2));

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

define('TARGET_OFFICE_LAT', -6.130189784035325);
define('TARGET_OFFICE_LON', 106.75142085117402);
define('MAX_OFFICE_RADIUS_METERS', 150);

$currentDay = date('l');
$isSaturday = ($currentDay === 'Saturday');

$current_page_basename = basename($_SERVER['PHP_SELF']);

$filter_bulan = isset($_GET['bulan']) ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Presensi - Gravitti Tech</title>
    <meta name="description" content="Halaman presensi online karyawan Grav-Tech" />
    <meta name="author" content="Gravitti Technology" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/presensi-styles.css">
    
    <style>
        body { 
            background-color: #f8fafc; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }

        .presensi-header-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0284c7 100%) !important;
            color: #ffffff;
            padding: 2rem 0 4.5rem 0 !important;
            margin-bottom: -50px !important;
            position: relative;
            z-index: 5;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .page-title-presensi {
            font-weight: 800 !important;
            font-size: 1.25rem !important;
            letter-spacing: 0.5px;
            color: #ffffff !important;
        }

        #realTimeClockDisplay {
            font-size: 1.25rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.5px;
            background: rgba(255, 255, 255, 0.15) !important;
            padding: 4px 14px !important;
            border-radius: 50px !important;
            display: inline-block;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .employee-info-presensi.card {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            color: #fff !important;
            border-radius: 20px !important;
            padding: 1.1rem 1.4rem !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18) !important;
        }

        .filter-card { 
            border-radius: 20px !important; 
            background: #ffffff !important; 
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04) !important;
        }

        .filter-card .form-select {
            border-radius: 12px !important;
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 0.88rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            padding: 9px 14px !important;
        }

        .filter-card .form-select:focus {
            border-color: #0284c7 !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
        }

        .btn-filter-submit {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border-radius: 12px !important;
            padding: 9px 20px !important;
            border: none !important;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3) !important;
            transition: all 0.2s ease !important;
        }

        .btn-filter-submit:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.45) !important;
        }
        
        .riwayat-card { 
            border-radius: 22px !important; 
            border: 1px solid #e2e8f0 !important; 
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.04) !important; 
            background: #ffffff !important; 
            overflow: hidden !important; 
            margin-bottom: 22px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
        }

        .riwayat-card:hover {
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08) !important;
        }

        .riwayat-date-badge { 
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important; 
            color: #1d4ed8 !important; 
            border: 1px solid #bfdbfe !important;
            border-radius: 16px !important; 
            padding: 10px 14px !important; 
            min-width: 65px !important; 
            text-align: center !important; 
            box-shadow: 0 4px 10px rgba(29, 78, 216, 0.08) !important;
        }

        .presensi-box { 
            background: #f8fafc !important; 
            border-radius: 18px !important; 
            padding: 18px !important; 
            height: 100% !important; 
            display: flex !important; 
            flex-direction: column !important; 
            border: 1px solid #e2e8f0 !important;
            transition: all 0.2s ease !important;
        }

        .presensi-box:hover {
            border-color: #cbd5e1 !important;
            background: #ffffff !important;
        }

        .empty-state-box {
            background: #fafafa !important;
            border: 1.5px dashed #cbd5e1 !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 18px !important;
            padding: 24px 16px !important;
        }

        .time-text { 
            font-size: 1.65rem !important; 
            font-weight: 800 !important; 
            color: #0f172a !important; 
            line-height: 1.1 !important; 
            letter-spacing: -0.5px !important;
        }

        .location-text { 
            font-size: 0.8rem !important; 
            color: #64748b !important; 
            font-weight: 500 !important;
        }

        .status-badge { 
            font-size: 0.72rem !important; 
            font-weight: 700 !important; 
            padding: 6px 14px !important; 
            border-radius: 50rem !important; 
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .foto-presensi-container {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            background: #f1f5f9;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            margin-top: auto;
        }

        .foto-presensi { 
            width: 100%; 
            height: 145px; 
            object-fit: cover; 
            display: block;
            transition: transform 0.3s ease;
        }

        .foto-presensi-container:hover .foto-presensi {
            transform: scale(1.04);
        }

        .foto-overlay-badge {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(6px);
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 50px;
            pointer-events: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <!-- Header Section -->
        <div class="presensi-header-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3 px-2 presensi-top-bar">
                    <h5 class="text-light mb-0 page-title-presensi">
                        <i class="fa-solid fa-calendar-check me-2 text-info"></i>RIWAYAT PRESENSI
                    </h5>
                    <div class="text-light text-end time-date-display">
                        <span id="realTimeClockDisplay" class="d-block fw-bold"><?php echo date('H:i:s'); ?></span>
                        <small class="d-block mt-1 opacity-75" style="font-size: 0.75rem;"><?php echo date('d F Y'); ?></small>
                    </div>
                </div>
                <div class="employee-info-presensi card card-body mx-1 rounded-4 border-0">
                    <div class="d-flex align-items-center">
                        <?php
                        $user_photo_src = (!empty($photo) && file_exists(__DIR__ . '/../uploads/' . $photo)) ? '../uploads/' . htmlspecialchars($photo) : '';
                        $first_letter = strtoupper(substr($nama, 0, 1));
                        $avatar_svg_fallback = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'><rect width='60' height='60' rx='30' fill='%230284c7'/><text x='50%' y='55%' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='sans-serif' font-size='24' font-weight='bold'>{$first_letter}</text></svg>";
                        ?>
                        <img src="<?php echo !empty($user_photo_src) ? $user_photo_src : $avatar_svg_fallback; ?>"
                            alt="Foto Profil" class="employee-photo-presensi me-3 rounded-circle shadow-sm"
                            style="width: 56px; height: 56px; object-fit: cover; border: 2px solid rgba(255,255,255,0.4);"
                            onerror="this.onerror=null; this.src='<?php echo $avatar_svg_fallback; ?>';">
                        <div>
                            <h6 class="mb-0 fw-bold text-white fs-6"><?php echo htmlspecialchars($nama); ?></h6>
                            <small class="text-white-50" style="font-size: 0.8rem;"><?php echo htmlspecialchars($jabatan); ?> &bull; NIK: <?php echo htmlspecialchars($nik); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content presensi-main-content px-lg-4 px-md-3 px-2 mt-4">
            <div class="container p-0">

                <!-- Filter Card -->
                <div class="card filter-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <form method="GET" action="" class="row g-2 align-items-end">
                            <div class="col-6 col-md-4">
                                <label class="form-label text-muted" style="font-size: 0.75rem; font-weight: 600; margin-bottom: 4px;">
                                    <i class="fa-regular fa-calendar me-1"></i>Pilih Bulan
                                </label>
                                <select name="bulan" class="form-select">
                                    <?php
                                    $nama_bulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                                    for ($i = 1; $i <= 12; $i++) {
                                        $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                                        $selected = ($val == $filter_bulan) ? 'selected' : '';
                                        echo "<option value=\"$val\" $selected>{$nama_bulan[$i - 1]}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label text-muted" style="font-size: 0.75rem; font-weight: 600; margin-bottom: 4px;">
                                    <i class="fa-regular fa-calendar-days me-1"></i>Pilih Tahun
                                </label>
                                <select name="tahun" class="form-select">
                                    <?php
                                    $tahun_sekarang = date('Y');
                                    for ($t = $tahun_sekarang; $t >= $tahun_sekarang - 3; $t--) {
                                        $selected = ($t == $filter_tahun) ? 'selected' : '';
                                        echo "<option value=\"$t\" $selected>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-filter-submit w-100 py-2">
                                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- High-Performance Single Query Data Fetching -->
                <div class="riwayat-presensi-manual-list">
                    <?php
                    $nip_session = $_SESSION['nip'];
                    $limit_riwayat = 5;
                    $page_riwayat = isset($_GET['page_manual']) ? (int)$_GET['page_manual'] : 1;
                    $page_riwayat = max($page_riwayat, 1);
                    $offset_riwayat = ($page_riwayat - 1) * $limit_riwayat;

                    $where_clause = "nip='$nip_session' AND MONTH(tgl_absen)='$filter_bulan' AND YEAR(tgl_absen)='$filter_tahun'";

                    // Execute SINGLE optimized SQL Query for the month
                    $query_all = "SELECT tipe_absen, DATE(tgl_absen) AS tgl_date, TIME(tgl_absen) AS jam, verif, image, lokasi_absen, lokasi_koordinat 
                                  FROM absen_manual 
                                  WHERE $where_clause 
                                  ORDER BY tgl_absen DESC";
                    $result_all = mysqli_query($conn, $query_all);

                    $grouped_by_date = [];
                    if ($result_all && mysqli_num_rows($result_all) > 0) {
                        while ($row = mysqli_fetch_assoc($result_all)) {
                            $tdate = $row['tgl_date'];
                            if (!isset($grouped_by_date[$tdate])) {
                                $grouped_by_date[$tdate] = ['masuk' => null, 'pulang' => null];
                            }
                            if ($row['tipe_absen'] === 'masuk' && !$grouped_by_date[$tdate]['masuk']) {
                                $grouped_by_date[$tdate]['masuk'] = $row;
                            } elseif ($row['tipe_absen'] === 'pulang' && !$grouped_by_date[$tdate]['pulang']) {
                                $grouped_by_date[$tdate]['pulang'] = $row;
                            }
                        }
                    }

                    $totalDataRiwayat = count($grouped_by_date);
                    $totalPagesRiwayat = max(ceil($totalDataRiwayat / $limit_riwayat), 1);
                    $all_dates = array_keys($grouped_by_date);
                    $paged_dates = array_slice($all_dates, $offset_riwayat, $limit_riwayat);
                    $query_params = "&bulan=$filter_bulan&tahun=$filter_tahun";
                    ?>

                    <?php if (count($paged_dates) > 0): ?>
                        <?php foreach ($paged_dates as $tgl_riwayat): 
                            $data_hari_ini = $grouped_by_date[$tgl_riwayat];
                            $timestamp = strtotime($tgl_riwayat);
                            $daftar_hari = [
                                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                            ];
                            $nama_hari_id = $daftar_hari[date('l', $timestamp)];
                        ?>
                            <div class="card riwayat-card">
                                <div class="card-body p-3 p-md-4">
                                    
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="riwayat-date-badge me-3">
                                            <span class="d-block fw-bold fs-4 lh-1"><?php echo date('d', $timestamp); ?></span>
                                            <small class="d-block mt-1 fw-bold text-uppercase" style="font-size: 0.65rem;"><?php echo date('M', $timestamp); ?></small>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark fs-5"><?php echo $nama_hari_id; ?></h6>
                                            <small class="text-muted fw-semibold" style="font-size: 0.8rem;"><?php echo date('d F Y', $timestamp); ?></small>
                                        </div>
                                    </div>

                                    <div class="row g-2 g-md-3">
                                        <?php foreach (['masuk', 'pulang'] as $tipe_presensi): ?>
                                            <div class="col-12 col-md-6">
                                                <?php if ($data_hari_ini[$tipe_presensi]): 
                                                    $record = $data_hari_ini[$tipe_presensi];
                                                    $lokasi_absen_text = htmlspecialchars($record['lokasi_absen'] ?: '-');
                                                    $mobile_location_text = '-';

                                                    if (!empty($record['lokasi_koordinat']) && $record['lokasi_koordinat'] !== "Koordinat tidak valid/tersedia" && $record['lokasi_koordinat'] !== "Koordinat tidak tersedia") {
                                                        $coords = explode(',', $record['lokasi_koordinat']);
                                                        if (count($coords) == 2 && is_numeric(trim($coords[0])) && is_numeric(trim($coords[1]))) {
                                                            $distance = getDistanceBetweenPoints(trim($coords[0]), trim($coords[1]), TARGET_OFFICE_LAT, TARGET_OFFICE_LON);
                                                            if ($distance <= MAX_OFFICE_RADIUS_METERS) {
                                                                $mobile_location_text = "Di Kantor";
                                                            } else {
                                                                $mobile_location_text = "Luar Kantor";
                                                            }
                                                        } else {
                                                            $mobile_location_text = "Format Invalid";
                                                        }
                                                    } else {
                                                        if (!empty($record['lokasi_absen']) && !in_array($record['lokasi_absen'], ['Lokasi tidak terdeteksi', 'Alamat tidak terdeteksi', 'Koordinat tidak valid/tersedia', 'Koordinat tidak tersedia'])) {
                                                            $mobile_location_text = "Lokasi Teks";
                                                        } else {
                                                            $mobile_location_text = "Tdk Ada Lokasi";
                                                        }
                                                    }
                                                ?>
                                                    <div class="presensi-box">
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <div class="d-flex align-items-center">
                                                                <?php if($tipe_presensi == 'masuk'): ?>
                                                                    <i class="fa-solid fa-arrow-right-to-bracket text-primary me-2 fs-6"></i>
                                                                <?php else: ?>
                                                                    <i class="fa-solid fa-arrow-right-from-bracket text-info me-2 fs-6"></i>
                                                                <?php endif; ?>
                                                                <span class="text-capitalize fw-bold text-slate-700" style="font-size: 0.9rem;"><?php echo $tipe_presensi; ?></span>
                                                            </div>
                                                            
                                                            <div>
                                                                <?php if ($record['verif'] === 'Yes'): ?>
                                                                    <span class="status-badge bg-success-subtle text-success-emphasis"><i class="fa-solid fa-check"></i>Terverifikasi</span>
                                                                <?php elseif ($record['verif'] === 'No'): ?>
                                                                    <span class="status-badge bg-danger-subtle text-danger-emphasis"><i class="fa-solid fa-xmark"></i>Ditolak</span>
                                                                <?php else: ?>
                                                                    <span class="status-badge bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-clock"></i>Pending</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="time-text mb-2"><?php echo htmlspecialchars($record['jam']); ?></div>
                                                        
                                                        <div class="location-text mb-3 text-truncate" style="max-width: 100%;">
                                                            <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                                            <span class="d-none d-md-inline me-1 text-muted" title="<?php echo $lokasi_absen_text; ?>">
                                                                <?php echo $lokasi_absen_text; ?> -
                                                            </span>
                                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($mobile_location_text); ?></span>
                                                        </div>
                                                        
                                                        <?php if (!empty($record['image'])): 
                                                            $att_img_src = '';
                                                            $img_filename = $record['image'];
                                                            if (file_exists(__DIR__ . '/../uploads/attendance/' . $img_filename)) {
                                                                $att_img_src = '../uploads/attendance/' . htmlspecialchars($img_filename);
                                                            } elseif (file_exists(__DIR__ . '/../uploads/' . $img_filename)) {
                                                                $att_img_src = '../uploads/' . htmlspecialchars($img_filename);
                                                            } else {
                                                                $att_img_src = '../uploads/attendance/' . htmlspecialchars($img_filename);
                                                            }
                                                            $svg_photo_fallback = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='180' viewBox='0 0 300 180'><rect width='300' height='180' rx='12' fill='%23f1f5f9'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='sans-serif' font-size='13' font-weight='bold'>Foto Tidak Ditemukan</text></svg>";
                                                        ?>
                                                            <div class="foto-presensi-container" style="cursor: pointer;" onclick="previewImage('<?php echo $att_img_src; ?>')" data-bs-toggle="modal" data-bs-target="#imagePreviewModal">
                                                                <img src="<?php echo $att_img_src; ?>" 
                                                                     alt="Foto <?php echo ucfirst($tipe_presensi); ?>" 
                                                                     class="foto-presensi" 
                                                                     loading="lazy"
                                                                     onerror="if(this.src.indexOf('/uploads/attendance/')!=-1){this.src=this.src.replace('/uploads/attendance/','/uploads/');}else if(this.src.indexOf('/uploads/')!=-1){this.src=this.src.replace('/uploads/','/uploads/attendance/');}else{this.onerror=null;this.src='<?php echo $svg_photo_fallback; ?>';}">
                                                                <div class="foto-overlay-badge">
                                                                    <i class="fa-solid fa-magnifying-glass-plus me-1"></i>Perbesar
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="presensi-box empty-state-box">
                                                        <div class="d-flex align-items-center mb-2 w-100 justify-content-center opacity-50">
                                                            <?php if($tipe_presensi == 'masuk'): ?>
                                                                <i class="fa-solid fa-arrow-right-to-bracket me-2 text-primary"></i>
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-arrow-right-from-bracket me-2 text-info"></i>
                                                            <?php endif; ?>
                                                            <span class="text-capitalize fw-bold" style="font-size: 0.88rem;"><?php echo $tipe_presensi; ?></span>
                                                        </div>
                                                        <span class="text-muted fw-semibold" style="font-size: 0.8rem;">Belum ada data</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($totalPagesRiwayat > 1): ?>
                            <nav aria-label="Navigasi Halaman Presensi" class="mt-4 mb-5">
                                <ul class="pagination pagination-md justify-content-center gap-1 mb-0">
                                    <li class="page-item <?php echo ($page_riwayat <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link rounded-3 px-3 py-2 fw-semibold" href="?page_manual=<?php echo $page_riwayat - 1 . $query_params; ?>">
                                            <i class="fa-solid fa-chevron-left me-1"></i> Prev
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $start_page = max(1, $page_riwayat - 1);
                                    $end_page = min($totalPagesRiwayat, $page_riwayat + 1);
                                    for ($p = $start_page; $p <= $end_page; $p++): 
                                    ?>
                                        <li class="page-item <?php echo ($p == $page_riwayat) ? 'active' : ''; ?>">
                                            <a class="page-link rounded-3 px-3 py-2 fw-bold" href="?page_manual=<?php echo $p . $query_params; ?>"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page_riwayat >= $totalPagesRiwayat) ? 'disabled' : ''; ?>">
                                        <a class="page-link rounded-3 px-3 py-2 fw-semibold" href="?page_manual=<?php echo $page_riwayat + 1 . $query_params; ?>">
                                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="card border-0 rounded-4 shadow-sm p-5 text-center my-4 bg-white">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light mb-3 mx-auto" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-calendar-xmark fa-2x text-muted opacity-50"></i>
                            </div>
                            <h6 class="text-dark fw-bold fs-5 mb-1">Belum Ada Presensi</h6>
                            <p class="text-muted small mb-0">Tidak ada data presensi yang ditemukan untuk bulan dan tahun ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer mt-5 pb-4">
            <div class="container text-center">
                <small class="text-muted fw-medium">Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>.<br>Version 1.2.0</small>
            </div>
        </div>

    </div>
        
    <!-- Image Modal Preview -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-dark text-white overflow-hidden">
                <div class="modal-header border-0 p-3 bg-slate-900 text-white d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold m-0"><i class="fa-solid fa-image me-2 text-info"></i>Preview Foto Presensi</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center bg-black d-flex align-items-center justify-content-center" style="min-height: 350px;">
                    <img src="" id="modalPreviewImage" class="img-fluid w-100" style="max-height: 80vh; object-fit: contain;" alt="Preview Foto Absen">
                </div>
            </div>
        </div>
    </div>
        
    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function previewImage(imageSrc) {
        document.getElementById('modalPreviewImage').src = imageSrc;
    }
    </script>
</body>
</html>