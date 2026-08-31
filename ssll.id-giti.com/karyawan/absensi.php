<?php
if (function_exists('opcache_reset')) {
    @opcache_reset();
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
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
$todayDate = date('Y-m-d');

$final_shifting = $shifting;
$sql_shift_req = "SELECT shifting FROM shift_req WHERE nip = '$pinAbsen' AND '$todayDate' BETWEEN tgl_mulai AND tgl_selesai LIMIT 1";
$res_shift_req = $conn->query($sql_shift_req);
if ($res_shift_req && $res_shift_req->num_rows > 0) {
    $row_shift_req = $res_shift_req->fetch_assoc();
    $final_shifting = $row_shift_req['shifting'];
}

$current_page_basename = basename($_SERVER['PHP_SELF']); 
$asset_version = '2026.08.31.1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Presensi Online 3D - Gravitti Tech</title>
    
    <!-- PWA Web App Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/img/logo.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/footer.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/presensi-styles.css?v=<?php echo $asset_version; ?>">
    
    <style>
        :root {
            --hris-primary: #0f172a;
            --hris-accent: #2563eb;
            --hris-bg: #f8fafc;
            --hris-card-border: #e2e8f0;
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e40af 100%);
            --card-radius-lg: 24px;
            --btn-radius: 14px;
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
            --success-3d: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);
            --danger-3d: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #b91c1c 100%);
            --warning-3d: linear-gradient(135deg, #ea580c 0%, #f97316 50%, #c2410c 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #f8fafc !important;
            color: #0f172a !important;
        }

        .main-content-wrapper {
            background: #f8fafc !important;
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
        }

        .presensi-header-section {
            background: var(--header-gradient) !important;
            color: #fff;
            padding: 1.8rem 0 4.5rem 0 !important;
            margin-bottom: -55px !important;
            position: relative;
            z-index: 5;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-title-presensi {
            font-weight: 800 !important;
            font-size: 1.25rem !important;
            letter-spacing: -0.3px;
            color: #ffffff !important;
        }

        #realTimeClockDisplay {
            font-size: 1.15rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.5px;
            background: rgba(255, 255, 255, 0.12) !important;
            padding: 5px 16px !important;
            border-radius: 50px !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff !important;
        }

        .employee-info-presensi.card {
            background: rgba(255, 255, 255, 0.08) !important;
            backdrop-filter: blur(16px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            color: #fff !important;
            border-radius: 18px !important;
            padding: 1rem 1.25rem !important;
        }

        .employee-photo-presensi {
            width: 52px !important;
            height: 52px !important;
            border-radius: 50% !important;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.4) !important;
        }

        /* Executive HRIS Presensi Cards */
        .presensi-action-card.card {
            background: #ffffff !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid var(--hris-card-border) !important;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05) !important;
            margin-bottom: 24px;
            transform: none !important;
            transition: none !important;
        }

        .presensi-action-card .section-title-presensi-card {
            color: #64748b !important;
            font-weight: 700 !important;
            font-size: 0.78rem !important;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .presensi-action-card .schedule-display-presensi-card {
            font-size: 2.6rem !important;
            font-weight: 900 !important;
            line-height: 1.1;
            color: #0f172a !important;
            letter-spacing: -1.2px;
            margin: 0.3rem 0 !important;
        }

        @media (max-width: 576px) {
            .presensi-action-card .schedule-display-presensi-card {
                font-size: 2.1rem !important;
            }
        }

        .presensi-action-card .shift-name-presensi-card {
            font-size: 0.82rem !important;
            color: #2563eb !important;
            font-weight: 700 !important;
            background: #eff6ff !important;
            padding: 6px 16px !important;
            border-radius: 50px !important;
            display: inline-block;
            border: 1px solid #bfdbfe !important;
        }

        .status-area-presensi .location-status-presensi-card {
            font-size: 0.88rem !important;
            font-weight: 600 !important;
            padding: 0.9rem 1.1rem !important;
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 14px !important;
            color: #0f172a !important;
        }

        /* Ultra-Tactile 3D Buttons System */
        .button-area-presensi .btn {
            font-size: 0.95rem !important;
            font-weight: 800 !important;
            height: 54px !important;
            border-radius: 16px !important;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            cursor: pointer;
            position: relative;
            user-select: none;
        }

        .btn-check-in-presensi {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
            color: #ffffff !important;
            border-bottom: 4px solid #047857 !important;
            box-shadow: 0 8px 22px -4px rgba(16, 185, 129, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        .btn-check-in-presensi:hover:not(:disabled) {
            transform: translateY(-3px) scale(1.01);
            border-bottom-color: #065f46 !important;
            box-shadow: 0 12px 28px -4px rgba(16, 185, 129, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
        }

        .btn-check-in-presensi:active:not(:disabled) {
            transform: translateY(2px) scale(0.98);
            border-bottom-width: 1px !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
        }

        .btn-check-out-presensi {
            background: linear-gradient(135deg, #e11d48 0%, #f43f5e 100%) !important;
            color: #ffffff !important;
            border-bottom: 4px solid #be123c !important;
            box-shadow: 0 8px 22px -4px rgba(225, 29, 72, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        .btn-check-out-presensi:hover:not(:disabled) {
            transform: translateY(-3px) scale(1.01);
            border-bottom-color: #9f1239 !important;
            box-shadow: 0 12px 28px -4px rgba(225, 29, 72, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
        }

        .btn-check-out-presensi:active:not(:disabled) {
            transform: translateY(2px) scale(0.98);
            border-bottom-width: 1px !important;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3) !important;
        }

        .btn-check-in-presensi:disabled,
        .btn-check-out-presensi:disabled {
            background: #cbd5e1 !important;
            color: #64748b !important;
            border: 1px solid #cbd5e1 !important;
            border-bottom: 3.5px solid #94a3b8 !important;
            box-shadow: none !important;
            opacity: 0.7;
        }

        .btn-disabled-recorded,
        .btn-disabled-recorded:disabled {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            color: #334155 !important;
            border: 1.5px solid #cbd5e1 !important;
            border-bottom: 3.5px solid #cbd5e1 !important;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.04) !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.95 !important;
            font-weight: 800 !important;
        }

        .btn-disabled-locked,
        .btn-disabled-locked:disabled {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%) !important;
            color: #991b1b !important;
            border: 1.5px solid #fca5a5 !important;
            border-bottom: 3.5px solid #f87171 !important;
            box-shadow: 0 3px 8px rgba(220, 38, 38, 0.08) !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.95 !important;
            font-weight: 800 !important;
        }

        .btn-riwayat-absen {
            background: linear-gradient(135deg, #ea580c 0%, #f97316 100%) !important;
            color: #ffffff !important;
            border-bottom: 4px solid #c2410c !important;
            box-shadow: 0 8px 22px -4px rgba(234, 88, 12, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        .btn-riwayat-absen:hover {
            transform: translateY(-3px) scale(1.01);
            border-bottom-color: #9a3412 !important;
            box-shadow: 0 12px 28px -4px rgba(234, 88, 12, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
        }

        .btn-riwayat-absen:active {
            transform: translateY(2px) scale(0.98);
            border-bottom-width: 1px !important;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3) !important;
        }

        /* Right Panel Today Live Status Card */
        .today-live-card {
            background: #ffffff;
            border-radius: var(--card-radius-lg);
            border: 1px solid var(--hris-card-border);
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            margin-bottom: 24px;
        }

        .today-status-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 12px;
        }

        .today-status-photo {
            width: 56px;
            height: 64px;
            object-fit: cover !important;
            object-position: center top !important;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
        }

        /* Camera Modal */
        #cameraModal { z-index: 1060 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        #cameraModal .modal-dialog { max-width: 360px !important; width: 92% !important; margin: 0.75rem auto !important; }
        #cameraModal .modal-content { background: #0f172a !important; border-radius: 20px !important; border: 1px solid rgba(255, 255, 255, 0.15) !important; overflow: hidden; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6) !important; }
        #cameraModal .modal-header { background: #0f172a !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important; color: #ffffff !important; padding: 10px 14px !important; }
        #cameraModal .modal-title { font-weight: 800 !important; font-size: 0.95rem !important; color: #ffffff !important; }
        #cameraModal .modal-header .btn-close { filter: invert(1); opacity: 0.8; z-index: 1070 !important; }
        .camera-video-presensi, .photo-canvas-presensi, .photo-preview-img { width: 100% !important; height: 320px !important; max-height: 320px !important; min-height: 320px !important; object-fit: cover !important; object-position: center !important; display: block !important; background: #0f172a !important; border: none !important; margin: 0 !important; padding: 0 !important; }

        .capture-btn-presensi { position: absolute !important; bottom: 12px !important; left: 50% !important; transform: translateX(-50%) !important; width: 52px !important; height: 52px !important; border-radius: 50% !important; background: linear-gradient(135deg, #2563eb, #1d4ed8) !important; border: 3px solid #60a5fa !important; color: #ffffff !important; font-size: 1.2rem !important; display: flex; align-items: center !important; justify-content: center !important; cursor: pointer !important; z-index: 1070 !important; box-shadow: 0 6px 18px rgba(37, 99, 235, 0.6) !important; transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) !important; padding: 0 !important; outline: none !important; }
        .capture-btn-presensi i { font-size: 1.25rem !important; color: #ffffff !important; }
        .capture-btn-presensi:hover { transform: translateX(-50%) scale(1.08) !important; box-shadow: 0 10px 24px rgba(37, 99, 235, 0.8) !important; }

        #cameraModal .modal-footer { background: #0f172a !important; border-top: 1px solid rgba(255, 255, 255, 0.1) !important; padding: 10px 14px !important; display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 10px !important; }
        #cameraModal .modal-footer .btn-outline-secondary { flex: 1 !important; background: transparent !important; border: 1.5px solid #334155 !important; color: #cbd5e1 !important; border-radius: 12px !important; padding: 9px !important; font-size: 0.9rem !important; font-weight: 700 !important; z-index: 1070 !important; }
        #cameraModal .modal-footer .btn-primary { flex: 1 !important; background: linear-gradient(135deg, #2563eb, #1d4ed8) !important; border: none !important; color: #ffffff !important; border-radius: 12px !important; padding: 9px !important; font-size: 0.9rem !important; font-weight: 800 !important; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4) !important; z-index: 1070 !important; }

        .retake-btn-presensi { position: absolute !important; bottom: 12px !important; left: 50% !important; transform: translateX(-50%) !important; background: rgba(15, 23, 42, 0.85) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; color: #ffffff !important; border-radius: 20px !important; padding: 6px 16px !important; font-size: 0.8rem !important; font-weight: 700 !important; z-index: 1070 !important; backdrop-filter: blur(8px) !important; cursor: pointer !important; box-shadow: 0 4px 14px rgba(0,0,0,0.5) !important; }

        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.9); z-index: 9999; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; text-align: center; backdrop-filter: blur(8px); }
        .loading-spinner { width: 3.5rem; height: 3.5rem; border-width: 0.25em; color: #3b82f6; }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div id="fullScreenLoader" class="loading-overlay d-none">
        <div class="spinner-border loading-spinner mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
        <h5 class="fw-bold">Sedang Mengirim Data...</h5>
        <p class="small text-white-50">Mohon jangan tutup atau refresh halaman ini.</p>
    </div>

    <!-- Query Today's Live Attendance Records from absen_manual (camera photo records) -->
    <?php
    $sql_today_check = "SELECT tipe_absen, TIME(tgl_absen) as jam, verif, image, lokasi_absen FROM absen_manual WHERE (nip='$nip' OR nip='$nik' OR nip='$pinAbsen') AND DATE(tgl_absen)='$todayDate' ORDER BY tgl_absen ASC";
    $res_today_check = $conn->query($sql_today_check);
    $today_absen_data = ['masuk' => null, 'pulang' => null];
    if ($res_today_check && $res_today_check->num_rows > 0) {
        while ($r_today = $res_today_check->fetch_assoc()) {
            if ($r_today['tipe_absen'] === 'masuk') $today_absen_data['masuk'] = $r_today;
            if ($r_today['tipe_absen'] === 'pulang') $today_absen_data['pulang'] = $r_today;
        }
    }

    // Fallback display info for 'masuk' from table 'absen' if not found in 'absen_manual'
    if (empty($today_absen_data['masuk'])) {
        $sql_absen_masuk = "SELECT MIN(tgl_scan) as min_scan, TIME(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as jam_str FROM absen WHERE (nip='$nip' OR nip='$nik' OR nip='$pinAbsen') AND (DATE_FORMAT(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'), '%Y-%m-%d') = '$todayDate' OR DATE(tgl_scan) = '$todayDate')";
        $res_absen_masuk = $conn->query($sql_absen_masuk);
        if ($res_absen_masuk && $res_absen_masuk->num_rows > 0) {
            $r_absen = $res_absen_masuk->fetch_assoc();
            if (!empty($r_absen['min_scan'])) {
                $jam_fmt = !empty($r_absen['jam_str']) ? $r_absen['jam_str'] : date('H:i:s', strtotime($r_absen['min_scan']));
                $today_absen_data['masuk'] = [
                    'tipe_absen' => 'masuk',
                    'jam' => $jam_fmt,
                    'verif' => 'Yes',
                    'image' => '',
                    'lokasi_absen' => 'Presensi Mesin / Sistem'
                ];
            }
        }
    }
    // Calculate shift check-in time limit (Maksimal 1 jam setelah jadwal masuk shift)
    $limitHour = 10;
    $limitMinute = 0;
    $limitTimeStr = '10:00';

    if ($isSaturday && $final_shifting !== 'TEST') {
        $limitHour = 9;
        $limitMinute = 30;
        $limitTimeStr = '09:30';
    } else {
        switch ($final_shifting) {
            case 'P': $limitHour = 8; $limitMinute = 0; $limitTimeStr = '08:00'; break;
            case 'M': $limitHour = 9; $limitMinute = 30; $limitTimeStr = '09:30'; break;
            case 'N': $limitHour = 10; $limitMinute = 0; $limitTimeStr = '10:00'; break;
            case 'S': $limitHour = 10; $limitMinute = 30; $limitTimeStr = '10:30'; break;
            case 'T': $limitHour = 10; $limitMinute = 10; $limitTimeStr = '10:10'; break;
            case 'TEST': $limitHour = 23; $limitMinute = 59; $limitTimeStr = '23:59'; break;
            default: $limitHour = 10; $limitMinute = 0; $limitTimeStr = '10:00'; break;
        }
    }

    $currHour = (int)date('H');
    $currMinute = (int)date('i');
    $is_past_checkin_limit = ($final_shifting !== 'TEST') && ($currHour > $limitHour || ($currHour === $limitHour && $currMinute >= $limitMinute));
    ?>

    <div class="main-content-wrapper">
        <!-- Executive Header Section -->
        <div class="presensi-header-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <div>
                        <h5 class="page-title-presensi mb-0"><i class="fas fa-fingerprint me-2 text-info"></i>PRESENSI ONLINE</h5>
                        <small class="text-white-50" style="font-size: 0.78rem;">Portal Absensi Karyawan Real-Time</small>
                    </div>
                    <div class="text-end">
                        <span id="realTimeClockDisplay"><?php echo date('H:i:s'); ?></span>
                        <small class="d-block mt-1 text-white-50" style="font-size: 0.72rem;"><?php echo date('d F Y'); ?></small>
                    </div>
                </div>
                <div class="employee-info-presensi card border-0 mx-1">
                    <div class="d-flex align-items-center">
                        <?php
                        $user_photo_src = (!empty($photo) && file_exists('../uploads/' . $photo)) ? '../uploads/' . htmlspecialchars($photo) : '';
                        $first_letter = strtoupper(substr($nama, 0, 1));
                        $avatar_svg_fallback = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'><rect width='60' height='60' rx='30' fill='%232563eb'/><text x='50%' y='55%' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='sans-serif' font-size='24' font-weight='bold'>{$first_letter}</text></svg>";
                        ?>
                        <img src="<?php echo !empty($user_photo_src) ? $user_photo_src : $avatar_svg_fallback; ?>"
                            alt="Foto Profil" class="employee-photo-presensi me-3 shadow-sm"
                            onerror="this.onerror=null; this.src='<?php echo $avatar_svg_fallback; ?>';">
                        <div>
                            <h6 class="mb-0 fw-bold text-white fs-6"><?php echo htmlspecialchars($nama); ?></h6>
                            <small class="text-white-50" style="font-size: 0.8rem;"><?php echo htmlspecialchars($jabatan); ?> &bull; NIK: <?php echo htmlspecialchars($nik); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2-Column Corporate Grid Layout -->
        <div class="dashboard-content presensi-main-content px-lg-4 px-md-3 px-2 mt-4">
            <div class="container p-0">
                <div class="row g-4">

                    <!-- Left Column: Clock-In & Action Hub (7 Columns on Desktop) -->
                    <div class="col-12 col-lg-7">
                        <div class="card presensi-action-card" id="card3d">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-center mb-3">
                                    <h5 class="section-title-presensi-card mb-2"><i class="fa-regular fa-calendar-check me-2"></i>JADWAL ANDA HARI INI</h5>
                                    <p class="shift-name-presensi-card mb-2">
                                        Shift: <?php
                                                $shiftNames = ['P' => 'Pagi', 'M' => 'Tengah', 'N' => 'Siang', 'S' => 'Siang', 'T' => 'Harco (HC)', 'TEST' => 'Shift Testing (24 Jam)'];
                                                echo $shiftNames[$final_shifting] ?? $final_shifting;
                                                ?>
                                    </p>
                                    <?php
                                    $shiftSchedule = '';
                                    if ($isSaturday && $final_shifting !== 'TEST') {
                                        $shiftSchedule = '08.30 - 13.00';
                                    } else {
                                        switch ($final_shifting) {
                                            case 'P': $shiftSchedule = '07.00 - 16.00'; break;
                                            case 'M': $shiftSchedule = '08.30 - 17.30'; break;
                                            case 'N': $shiftSchedule = '09.00 - 18.00'; break;
                                            case 'S': $shiftSchedule = '09.30 - 18.30'; break;
                                            case 'T': $shiftSchedule = '09.10 - 18.00'; break;
                                            case 'TEST': $shiftSchedule = 'Bisa Masuk & Pulang Kapan Saja (Test Mode)'; break; 
                                            default: $shiftSchedule = 'Tidak Terdefinisi';
                                        }
                                    }
                                    ?>
                                    <p class="schedule-display-presensi-card my-2"><?php echo $shiftSchedule; ?></p>
                                </div>

                                <hr class="my-3 text-slate-200">

                                <div class="status-area-presensi mb-3">
                                    <div id="locationStatus" class="d-flex align-items-center justify-content-center location-status-presensi-card">
                                        <i class="fas fa-spinner fa-spin me-2 text-primary"></i> Mengambil lokasi...
                                    </div>
                                    <div id="locationWarning" class="alert alert-warning d-none text-center mt-2 py-2 small rounded-3">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        Anda di luar lokasi kantor, jika lokasi tidak sesuai, pastikan GPS handphone aktif dan browser tidak memblokir lokasi pada situs ini.
                                    </div>
                                    <div id="lateWarning" class="alert alert-danger d-none text-center mt-2 py-2 rounded-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <span id="lateMessage"></span>
                                    </div>
                                </div>

                                <div class="button-area-presensi mt-3">
                                    <!-- Tombol Masuk -->
                                    <?php if (!empty($today_absen_data['masuk'])): ?>
                                        <button class="btn btn-disabled-recorded w-100 mb-2 py-3" id="btnCheckIn" disabled>
                                            <i class="fas fa-circle-check me-2 text-success"></i>MASUK (TERCATAT)
                                        </button>
                                    <?php elseif ($is_past_checkin_limit): ?>
                                        <button class="btn btn-disabled-locked w-100 mb-2 py-3" id="btnCheckIn" disabled>
                                            <i class="fas fa-lock me-2 text-danger"></i>MASUK (TERKUNCI - LEWAT 1 JAM TELAT)
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-check-in-presensi w-100 mb-2 py-3" id="btnCheckIn" disabled>
                                            <i class="fas fa-camera me-2"></i>MASUK (CHECK-IN)
                                        </button>
                                    <?php endif; ?>

                                    <!-- Tombol Pulang -->
                                    <?php if (!empty($today_absen_data['pulang'])): ?>
                                        <button class="btn btn-disabled-recorded w-100 mb-2 py-3" id="btnCheckOut" disabled>
                                            <i class="fas fa-circle-check me-2 text-success"></i>PULANG (TERCATAT)
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-check-out-presensi w-100 mb-2 py-3" id="btnCheckOut" disabled>
                                            <i class="fas fa-door-open me-2"></i>PULANG (CHECK-OUT)
                                        </button>
                                    <?php endif; ?>

                                    <a href="riwayat-absen.php" class="btn btn-riwayat-absen w-100 py-3 text-decoration-none">
                                        <i class="fas fa-calendar-check me-2"></i><strong>CEK ABSEN KAMU DISINI</strong>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Today's Live Status & Guidelines (5 Columns on Desktop) -->
                    <div class="col-12 col-lg-5">
                        <div class="today-live-card">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Status Absen Hari Ini</h6>
                                <span class="badge bg-light text-secondary border fw-bold"><?php echo date('d M Y'); ?></span>
                            </div>

                            <!-- Check-In Live Box -->
                            <div class="today-status-box">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold text-slate-700 small"><i class="fa-solid fa-arrow-right-to-bracket text-primary me-1"></i>Absen Masuk</span>
                                    <?php if ($today_absen_data['masuk']): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis rounded-pill"><i class="fa-solid fa-check me-1"></i>Tercatat</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">Belum Absen</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($today_absen_data['masuk']): 
                                    $rec_tm = $today_absen_data['masuk'];
                                    $img_tm = $rec_tm['image'];
                                    $img_src_tm = '';
                                    if (!empty($img_tm)) {
                                        if (file_exists(__DIR__ . '/../uploads/attendance/' . $img_tm)) {
                                            $img_src_tm = '../uploads/attendance/' . htmlspecialchars($img_tm);
                                        } elseif (file_exists(__DIR__ . '/../uploads/' . $img_tm)) {
                                            $img_src_tm = '../uploads/' . htmlspecialchars($img_tm);
                                        }
                                    }
                                ?>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($img_src_tm)): ?>
                                            <img src="<?php echo $img_src_tm; ?>" alt="Foto Masuk" class="today-status-photo">
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-extrabold text-dark fs-5 lh-1 mb-1"><?php echo htmlspecialchars($rec_tm['jam']); ?> WIB</div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 180px;"><?php echo htmlspecialchars($rec_tm['lokasi_absen'] ?: 'Di Kantor'); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small py-2 text-center opacity-60"><i class="fa-regular fa-clock me-1"></i>Belum melakukan presensi masuk</div>
                                <?php endif; ?>
                            </div>

                            <!-- Check-Out Live Box -->
                            <div class="today-status-box mb-0">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold text-slate-700 small"><i class="fa-solid fa-arrow-right-from-bracket text-info me-1"></i>Absen Pulang</span>
                                    <?php if ($today_absen_data['pulang']): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis rounded-pill"><i class="fa-solid fa-check me-1"></i>Tercatat</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">Belum Absen</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($today_absen_data['pulang']): 
                                    $rec_tp = $today_absen_data['pulang'];
                                    $img_tp = $rec_tp['image'];
                                    $img_src_tp = '';
                                    if (!empty($img_tp)) {
                                        if (file_exists(__DIR__ . '/../uploads/attendance/' . $img_tp)) {
                                            $img_src_tp = '../uploads/attendance/' . htmlspecialchars($img_tp);
                                        } elseif (file_exists(__DIR__ . '/../uploads/' . $img_tp)) {
                                            $img_src_tp = '../uploads/' . htmlspecialchars($img_tp);
                                        }
                                    }
                                ?>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($img_src_tp)): ?>
                                            <img src="<?php echo $img_src_tp; ?>" alt="Foto Pulang" class="today-status-photo">
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-extrabold text-dark fs-5 lh-1 mb-1"><?php echo htmlspecialchars($rec_tp['jam']); ?> WIB</div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 180px;"><?php echo htmlspecialchars($rec_tp['lokasi_absen'] ?: 'Di Kantor'); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small py-2 text-center opacity-60"><i class="fa-regular fa-clock me-1"></i>Belum melakukan presensi pulang</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Radius & Guidelines Info Card -->
                        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;"><i class="fa-solid fa-circle-info text-info me-2"></i>Ketentuan Presensi</h6>
                            <ul class="text-muted small mb-0 ps-3" style="font-size: 0.78rem; line-height: 1.6;">
                                <li>Pastikan izin GPS / Lokasi pada browser handphone aktif.</li>
                                <li>Radius kantor maksimal adalah <strong>150 Meter</strong>.</li>
                                <li>Foto selfie presensi wajib diambil dengan jelas.</li>
                            </ul>
                        </div>
                    </div>

                </div>

                <div class="footer mt-3 pb-2 text-center">
                    <small class="text-muted fw-medium">Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>.<br>Version 1.2.0</small>
                </div>
            </div>
        </div>
        <?php include 'nav/bottom-nav.php'; ?>
    </div><!-- /main-content-wrapper -->

    <!-- Modal Camera Placed Outside Main Wrapper for Proper Body Level Stacking Context -->
    <div class="modal fade camera-modal-presensi" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 360px; width: 92%; margin: 0.5rem auto;">
            <div class="modal-content" style="background: #0f172a; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.15); overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
                <div class="modal-header" style="background: #0f172a; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 10px 14px; color: #fff;">
                    <h5 class="modal-title fs-6 fw-bold mb-0 text-white" id="attendanceTypeTitle"><i class="fas fa-camera me-2 text-primary"></i>Ambil Foto</h5>
                    <button type="button" class="btn-close btn-close-white opacity-75" id="closeCameraXBtn" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative" style="height: 320px; max-height: 320px; overflow: hidden; background: #0f172a;">
                    <video id="cameraVideo" autoplay playsinline style="width: 100%; height: 320px; object-fit: cover; object-position: center; display: none;"></video>
                    <div id="cameraPlaceholder" style="width: 100%; height: 320px; background: #0f172a; color: #94a3b8; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; box-sizing: border-box;">
                        <div class="placeholder-icon-wrapper mb-3" style="width: 80px; height: 80px; border-radius: 50%; background: rgba(37, 99, 235, 0.1); display: flex; align-items: center; justify-content: center; border: 2px dashed #2563eb;">
                            <i class="fas fa-camera text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="text-white fw-bold mb-1">Kamera Siap</h6>
                        <p class="small text-white-50 px-3" style="font-size: 0.75rem; margin: 0;">Ketuk tombol kamera biru di bawah untuk mulai mengambil foto selfie Anda.</p>
                    </div>
                    <img id="photoPreviewImg" class="d-none photo-preview-img" style="width: 100%; height: 320px; object-fit: cover; object-position: center; display: block;">
                    <canvas id="photoCanvas" class="d-none" style="display: none;"></canvas>
                    <input type="file" id="nativeCameraInput" accept="image/*" capture="user" class="d-none" style="display: none;">
                    <label for="nativeCameraInput" id="captureBtnLabel" class="capture-btn-presensi" title="Ambil Foto" style="cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-camera"></i></label>
                    <button id="retakeBtn" class="retake-btn-presensi d-none" title="Ulang Foto"><i class="fas fa-rotate-left me-1.5"></i>Foto Ulang</button>
                </div>
                <div class="modal-footer" style="background: #0f172a; border-top: 1px solid rgba(255, 255, 255, 0.1); padding: 10px 14px; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline-secondary flex-fill text-white-50 border-secondary rounded-3 py-2 fw-bold small" id="closeCameraBatalBtn">Batal</button>
                    <button type="button" class="btn btn-primary flex-fill rounded-3 py-2 fw-bold small text-white shadow" id="uploadPhotoBtn" disabled><i class="fas fa-cloud-arrow-up me-1.5"></i>Upload & Kirim</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const targetLat = <?php echo TARGET_OFFICE_LAT; ?>;
        const targetLng = <?php echo TARGET_OFFICE_LON; ?>;
        const employeeNip = '<?php echo htmlspecialchars($nip); ?>';
        const employeeName = '<?php echo htmlspecialchars($nama); ?>';
        const employeePin = '<?php echo htmlspecialchars($pinAbsen); ?>';
        const employeeNik = '<?php echo htmlspecialchars($nik); ?>';
        let userLat = null;
        let userLng = null;
        let userLocationAddress = "Lokasi tidak diketahui";
        let stream = null;
        let attendanceType = ''; 

        function closeCameraModal() {
            stopWebcamStream();
            $('#cameraModal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '');
        }

        $(document).on('click touchstart', '#closeCameraXBtn, #closeCameraBatalBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeCameraModal();
        });

        function updateClockDisplay() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            $('#realTimeClockDisplay').text(timeString);
        }

        function getDistanceFromLatLonInM(lat1, lon1, lat2, lon2) {
            function deg2rad(deg) { return deg * (Math.PI / 180); }
            const R = 6371000;
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function getUserLocation() {
            const locationStatusEl = $('#locationStatus');
            
            const cachedLat = localStorage.getItem('last_user_lat');
            const cachedLng = localStorage.getItem('last_user_lng');
            const cachedTime = localStorage.getItem('last_user_time');
            
            if (cachedLat && cachedLng && cachedTime && (Date.now() - parseInt(cachedTime)) < 3600000) {
                userLat = parseFloat(cachedLat);
                userLng = parseFloat(cachedLng);
                userLocationAddress = localStorage.getItem('last_user_addr') || `Lat: ${userLat.toFixed(4)}, Lng: ${userLng.toFixed(4)}`;
                
                const distance = getDistanceFromLatLonInM(userLat, userLng, targetLat, targetLng);
                let distanceText = distance !== null ? ` (Jarak: ${distance.toFixed(0)}m)` : '';
                let locationIconClass = distance !== null && distance <= 150 ? 'text-success' : 'text-warning';
                
                locationStatusEl.html(`<i class="fas fa-map-marker-alt ${locationIconClass} me-2"></i> ${userLocationAddress.substring(0, 35)}...${distanceText}`);
                if (distance > 150) { $('#locationWarning').removeClass('d-none'); } else { $('#locationWarning').addClass('d-none'); }
                
                checkAbsenConditions();
            } else {
                locationStatusEl.html('<i class="fas fa-spinner fa-spin me-2 text-primary"></i> Mengambil lokasi...');
            }

            if (!navigator.geolocation) {
                locationStatusEl.html('<i class="fas fa-times-circle text-danger me-2"></i> Geolocation tidak didukung.');
                checkAbsenConditions();
                return;
            }

            function handlePositionSuccess(position) {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                
                localStorage.setItem('last_user_lat', userLat);
                localStorage.setItem('last_user_lng', userLng);
                localStorage.setItem('last_user_time', Date.now());
                
                const distance = getDistanceFromLatLonInM(userLat, userLng, targetLat, targetLng);
                let distanceText = distance !== null ? ` (Jarak: ${distance.toFixed(0)}m)` : '';
                let locationIconClass = distance !== null && distance <= 150 ? 'text-success' : 'text-warning';
                
                locationStatusEl.html(`<i class="fas fa-map-marker-alt ${locationIconClass} me-2"></i> Lat: ${userLat.toFixed(4)}, Lng: ${userLng.toFixed(4)}${distanceText}`);
                if (distance > 150) { $('#locationWarning').removeClass('d-none'); } else { $('#locationWarning').addClass('d-none'); }
                
                checkAbsenConditions();

                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${userLat}&lon=${userLng}`, { signal: AbortSignal.timeout(3000) })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            userLocationAddress = data.display_name;
                            localStorage.setItem('last_user_addr', userLocationAddress);
                            locationStatusEl.html(`<i class="fas fa-map-marker-alt ${locationIconClass} me-2"></i> ${userLocationAddress.substring(0, 35)}...${distanceText}`);
                        }
                    })
                    .catch(() => { });
            }

            navigator.geolocation.getCurrentPosition(
                handlePositionSuccess,
                function(error) {
                    navigator.geolocation.getCurrentPosition(
                        handlePositionSuccess,
                        function(err) {
                            if (!userLat) {
                                let errorMsg = 'Gagal mengambil lokasi. ';
                                if (err.code === err.PERMISSION_DENIED) errorMsg += "Izin lokasi ditolak di HP.";
                                else errorMsg += "Aktifkan GPS HP Anda.";
                                locationStatusEl.html(`<i class="fas fa-exclamation-triangle text-warning me-2"></i> ${errorMsg}`);
                                checkAbsenConditions();
                            }
                        },
                        { enableHighAccuracy: true, timeout: 5000, maximumAge: 300000 }
                    );
                },
                { enableHighAccuracy: false, timeout: 3000, maximumAge: 300000 }
            );
        }

        function checkLateStatus() {
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            <?php
            $lateThresholdPHP = '';
            if ($isSaturday && $final_shifting !== 'TEST') {
                $lateThresholdPHP = '08:30';
            } else {
                switch ($final_shifting) {
                    case 'P': $lateThresholdPHP = '07:00'; break;
                    case 'M': $lateThresholdPHP = '08:30'; break;
                    case 'N': $lateThresholdPHP = '09:00'; break;
                    case 'S': $lateThresholdPHP = '09:30'; break;
                    case 'T': $lateThresholdPHP = '09:10'; break;
                    case 'TEST': $lateThresholdPHP = '09:30'; break;
                    default: $lateThresholdPHP = '23:59';
                }
            }
            echo "const lateThresholdJS = '$lateThresholdPHP';";
            ?>
            if (lateThresholdJS === '23:59') { $('#lateWarning').addClass('d-none'); return; }
            const [thresholdHour, thresholdMinute] = lateThresholdJS.split(':').map(Number);
            let isLate = false;
            if (currentHour > thresholdHour || (currentHour === thresholdHour && currentMinute > thresholdMinute)) { isLate = true; }
            const hasAlreadyCheckedIn = <?php echo !empty($today_absen_data['masuk']) ? 'true' : 'false'; ?>;
            if (isLate && !hasAlreadyCheckedIn) {
                $('#lateWarning').removeClass('d-none');
                $('#lateMessage').text('Anda terlambat!');
            } else { $('#lateWarning').addClass('d-none'); }
        }

        function checkAbsenConditions() {
            const isTestMode = <?php echo ($final_shifting === 'TEST') ? 'true' : 'false'; ?>;
            const isLocationActive = (userLat !== null && userLng !== null);
            const hasCheckedInToday = <?php echo !empty($today_absen_data['masuk']) ? 'true' : 'false'; ?>;
            const hasCheckedOutToday = <?php echo !empty($today_absen_data['pulang']) ? 'true' : 'false'; ?>;

            if (isTestMode) {
                $('#btnCheckIn').prop('disabled', !isLocationActive);
                $('#btnCheckOut').prop('disabled', !isLocationActive);
                return;
            }

            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            const limitHour = <?php echo $limitHour; ?>;
            const limitMinute = <?php echo $limitMinute; ?>;
            const limitTimeStr = '<?php echo $limitTimeStr; ?>';

            let isCheckInTime = false;
            if (currentHour < limitHour || (currentHour === limitHour && currentMinute < limitMinute)) {
                isCheckInTime = true;
            }

            // Atur status dan tampilan tombol Masuk
            if (hasCheckedInToday) {
                if ($('#btnCheckIn').length) {
                    $('#btnCheckIn')
                        .removeClass('btn-check-in-presensi btn-disabled-locked')
                        .addClass('btn-disabled-recorded')
                        .prop('disabled', true)
                        .html('<i class="fas fa-circle-check me-2 text-success"></i>MASUK (TERCATAT)');
                }
            } else if (!isCheckInTime) {
                // Sudah lewat 1 jam telat -> Kunci tombol masuk
                if ($('#btnCheckIn').length) {
                    $('#btnCheckIn')
                        .removeClass('btn-check-in-presensi btn-disabled-recorded')
                        .addClass('btn-disabled-locked')
                        .prop('disabled', true)
                        .html('<i class="fas fa-lock me-2 text-danger"></i>MASUK (TERKUNCI - LEWAT 1 JAM TELAT)');
                }
                $('#lateWarning').removeClass('d-none');
                $('#lateMessage').html('Waktu presensi masuk telah berakhir (maksimal 1 jam setelah jam masuk: <strong>' + limitTimeStr + ' WIB</strong>). Presensi masuk terkunci.');
            } else {
                // Masih dalam batas waktu masuk
                if ($('#btnCheckIn').length) {
                    $('#btnCheckIn')
                        .removeClass('btn-disabled-locked btn-disabled-recorded')
                        .addClass('btn-check-in-presensi')
                        .prop('disabled', !isLocationActive)
                        .html('<i class="fas fa-camera me-2"></i>MASUK (CHECK-IN)');
                }
            }

            if (hasCheckedInToday && !hasCheckedOutToday && isLocationActive) { 
                if ($('#btnCheckOut').length) $('#btnCheckOut').prop('disabled', false); 
            } else { 
                if ($('#btnCheckOut').length) $('#btnCheckOut').prop('disabled', true); 
            }
        }

        function getDeviceName() {
            const ua = navigator.userAgent;
            if (/android/i.test(ua)) {
                const match = ua.match(/\(([^)]+)\)/);
                if (match && match[1]) {
                    let parts = match[1].split(';');
                    return parts[parts.length - 1].trim().split(' Build')[0];
                }
                return "Android Device";
            }
            if (/iPhone|iPad|iPod/i.test(ua)) return "iPhone/iPad";
            return "PC/Browser";
        }

        let isLiveWebcam = false;
        let webcamStream = null;

        function stopWebcamStream() {
            if (webcamStream) {
                webcamStream.getTracks().forEach(track => track.stop());
                webcamStream = null;
            }
            const videoEl = document.getElementById('cameraVideo');
            if (videoEl) { videoEl.srcObject = null; }
            isLiveWebcam = false;
        }

        $('#btnCheckIn, #btnCheckOut').click(function(e) {
            if ($(this).prop('disabled')) return;

            const isCheckOutBtn = $(this).attr('id') === 'btnCheckOut';
            if (isCheckOutBtn) {
                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                const isTestMode = <?php echo ($final_shifting === 'TEST') ? 'true' : 'false'; ?>;
                const checkoutMinHour = <?php echo $isSaturday ? 13 : 15; ?>;
                if (!isTestMode && currentHour < checkoutMinHour) {
                    e.preventDefault();
                    // Calculate remaining time
                    const remainMins = (checkoutMinHour - currentHour - 1) * 60 + (60 - currentMinute);
                    const remainH = Math.floor(remainMins / 60);
                    const remainM = remainMins % 60;
                    let remainStr = '';
                    if (remainH > 0) remainStr += remainH + ' jam ';
                    remainStr += remainM + ' menit lagi';
                    $('#lockModalTime').text(checkoutMinHour + ':00 WIB');
                    $('#lockModalRemain').text(remainStr);
                    // Animate progress ring
                    const pct = ((currentHour * 60 + currentMinute) / (checkoutMinHour * 60)) * 100;
                    const dashVal = (pct / 100) * 251.2;
                    $('#lockProgressRing').css('stroke-dashoffset', 251.2 - dashVal);
                    $('#lockProgressPct').text(Math.round(pct) + '%');
                    $('#checkoutLockModal').addClass('show');
                    return false;
                }
            }

            attendanceType = isCheckOutBtn ? 'pulang' : 'masuk';
            $('#attendanceTypeTitle').html('<i class="fas fa-camera me-2 text-primary"></i>Ambil Foto Absen ' + (attendanceType === 'masuk' ? 'Masuk' : 'Pulang'));
            $('#cameraModal').modal('show');
        });

        $('#cameraModal').on('shown.bs.modal', function() { 
            $('#photoPreviewImg').attr('style', 'display: none !important; width: 100% !important; height: 320px !important; object-fit: cover !important;');
            $('#photoPreviewImg').attr('src', '');
            $('#uploadPhotoBtn').prop('disabled', true);
            $('#retakeBtn').attr('style', 'display: none !important;');
            $('#nativeCameraInput').val('');

            // Attempt WebRTC live stream first (for desktop & supported browsers)
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } })
                .then(function(s) {
                    webcamStream = s;
                    isLiveWebcam = true;
                    const videoEl = document.getElementById('cameraVideo');
                    videoEl.srcObject = s;
                    videoEl.play();
                    $('#cameraVideo').attr('style', 'display: block !important; width: 100% !important; height: 320px !important; object-fit: cover !important;');
                    $('#cameraPlaceholder').attr('style', 'display: none !important;');
                    $('#captureBtnLabel').attr('style', 'display: flex !important;');
                })
                .catch(function(err) {
                    // Fallback to native camera upload if getUserMedia fails
                    stopWebcamStream();
                    $('#cameraVideo').attr('style', 'display: none !important;');
                    $('#cameraPlaceholder').attr('style', 'display: flex !important; width: 100%; height: 320px; background: #0f172a; color: #94a3b8; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; box-sizing: border-box;');
                    $('#captureBtnLabel').attr('style', 'display: flex !important;');
                });
            } else {
                stopWebcamStream();
                $('#cameraVideo').attr('style', 'display: none !important;');
                $('#cameraPlaceholder').attr('style', 'display: flex !important; width: 100%; height: 320px; background: #0f172a; color: #94a3b8; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; box-sizing: border-box;');
                $('#captureBtnLabel').attr('style', 'display: flex !important;');
            }
        });

        $('#cameraModal').on('hidden.bs.modal', function() {
            stopWebcamStream();
            $('#cameraVideo').attr('style', 'display: none !important;');
            $('#photoPreviewImg').attr('style', 'display: none !important; width: 100% !important; height: 320px !important; object-fit: cover !important;');
            $('#photoPreviewImg').attr('src', '');
            $('#cameraPlaceholder').attr('style', 'display: flex !important; width: 100%; height: 320px; background: #0f172a; color: #94a3b8; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; box-sizing: border-box;');
            $('#captureBtnLabel').attr('style', 'display: flex !important;');
            $('#retakeBtn').attr('style', 'display: none !important;');
            $('#uploadPhotoBtn').prop('disabled', true).html('<i class="fas fa-cloud-arrow-up me-1.5"></i>Upload & Kirim');
        });

        $('#captureBtnLabel').click(function(e) {
            if (isLiveWebcam && webcamStream) {
                e.preventDefault(); // Stop file input from opening when live stream is active
                const videoEl = document.getElementById('cameraVideo');
                const canvas = document.getElementById('photoCanvas');
                const ctx = canvas.getContext('2d');
                let w = videoEl.videoWidth || 640;
                let h = videoEl.videoHeight || 480;
                const maxDim = 540;
                if (w > maxDim || h > maxDim) {
                    if (w >= h) {
                        h = Math.round((h * maxDim) / w);
                        w = maxDim;
                    } else {
                        w = Math.round((w * maxDim) / h);
                        h = maxDim;
                    }
                }
                canvas.width = w;
                canvas.height = h;
                ctx.drawImage(videoEl, 0, 0, w, h);
                
                const dataUrl = canvas.toDataURL('image/jpeg', 0.65);
                const photoImg = document.getElementById('photoPreviewImg');
                photoImg.src = dataUrl;
                
                stopWebcamStream();
                $('#cameraVideo').attr('style', 'display: none !important;');
                $('#photoPreviewImg').attr('style', 'display: block !important; width: 100% !important; height: 320px !important; object-fit: cover !important;');
                $('#captureBtnLabel').attr('style', 'display: none !important;');
                $('#retakeBtn').attr('style', 'display: block !important;');
                $('#uploadPhotoBtn').prop('disabled', false);
            }
        });

        $('#nativeCameraInput').change(function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(evt) {
                    const tempImg = new Image();
                    tempImg.onload = function() {
                        const canvas = document.createElement('canvas');
                        let w = tempImg.naturalWidth || tempImg.width || 640;
                        let h = tempImg.naturalHeight || tempImg.height || 480;
                        const maxDim = 540;

                        if (w > maxDim || h > maxDim) {
                            if (w >= h) {
                                h = Math.round((h * maxDim) / w);
                                w = maxDim;
                            } else {
                                w = Math.round((w * maxDim) / h);
                                h = maxDim;
                            }
                        }

                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(tempImg, 0, 0, w, h);

                        const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.65);
                        const photoImg = document.getElementById('photoPreviewImg');
                        photoImg.src = compressedDataUrl;
                        
                        stopWebcamStream();
                        $('#cameraVideo').attr('style', 'display: none !important;');
                        $('#photoPreviewImg').attr('style', 'display: block !important; width: 100% !important; height: 320px !important; object-fit: cover !important;');
                        $('#cameraPlaceholder').attr('style', 'display: none !important;');
                        $('#captureBtnLabel').attr('style', 'display: none !important;');
                        $('#retakeBtn').attr('style', 'display: block !important;');
                        $('#uploadPhotoBtn').prop('disabled', false);
                    };
                    tempImg.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        $('#retakeBtn').click(function() {
            stopWebcamStream();
            $('#nativeCameraInput').val('');
            $('#photoPreviewImg').attr('style', 'display: none !important; width: 100% !important; height: 320px !important; object-fit: cover !important;');
            $('#photoPreviewImg').attr('src', '');
            $('#uploadPhotoBtn').prop('disabled', true);
            
            // Re-trigger webcam stream if supported
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } })
                .then(function(s) {
                    webcamStream = s;
                    isLiveWebcam = true;
                    const videoEl = document.getElementById('cameraVideo');
                    videoEl.srcObject = s;
                    videoEl.play();
                    $('#cameraVideo').attr('style', 'display: block !important; width: 100% !important; height: 320px !important; object-fit: cover !important;');
                    $('#cameraPlaceholder').attr('style', 'display: none !important;');
                    $('#captureBtnLabel').attr('style', 'display: flex !important;');
                    $('#retakeBtn').attr('style', 'display: none !important;');
                })
                .catch(function(err) {
                    $('#cameraVideo').attr('style', 'display: none !important;');
                    $('#cameraPlaceholder').attr('style', 'display: flex !important; width: 100%; height: 320px; background: #0f172a; color: #94a3b8; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; box-sizing: border-box;');
                    $('#captureBtnLabel').attr('style', 'display: flex !important;');
                    $('#retakeBtn').attr('style', 'display: none !important;');
                });
            } else {
                $('#cameraVideo').attr('style', 'display: none !important;');
                $('#cameraPlaceholder').attr('style', 'display: flex !important; width: 100%; height: 320px; background: #0f172a; color: #94a3b8; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; box-sizing: border-box;');
                $('#captureBtnLabel').attr('style', 'display: flex !important;');
                $('#retakeBtn').attr('style', 'display: none !important;');
            }
        });

        $('#uploadPhotoBtn').click(function() {
            const photoImg = document.getElementById('photoPreviewImg');
            const imageData = photoImg.src;

            if (!imageData || !imageData.startsWith('data:image')) {
                alert('Gagal mengambil data foto. Silakan foto ulang.');
                return;
            }

            submitAttendance(imageData);
        });

        function submitAttendance(imageData) {
            const deviceName = getDeviceName();
            closeCameraModal();
            $('#fullScreenLoader').removeClass('d-none');
            window.onbeforeunload = function() { return "Proses pengiriman data sedang berlangsung."; };
            $.ajax({
                url: 'process_attendance.php',
                type: 'POST',
                data: { tipe_absen: attendanceType, foto_absen: imageData, nip: employeeNip, pin: employeePin, nik_karyawan: employeeNik, lokasi_absen: userLocationAddress, latitude: userLat, longitude: userLng, device_name: deviceName },
                dataType: 'json',
                timeout: 20000,
                success: function(response) {
                    window.onbeforeunload = null;
                    if (response && response.success) { 
                        alert('Absen ' + attendanceType + ' berhasil dicatat!'); 
                        location.reload(); 
                    } else { 
                        $('#fullScreenLoader').addClass('d-none'); 
                        alert('Gagal: ' + (response ? response.message : 'Respon tidak valid')); 
                        $('#cameraModal').modal('show'); 
                    }
                },
                error: function(xhr, status, error) { 
                    window.onbeforeunload = null; 
                    $('#fullScreenLoader').addClass('d-none'); 
                    let errorMsg = 'Terjadi kesalahan pengiriman data.';
                    if (xhr.status === 413) {
                        errorMsg = 'Ukuran foto terlalu besar untuk server. Silakan foto ulang.';
                    } else if (xhr.responseText) {
                        try {
                            const errObj = JSON.parse(xhr.responseText);
                            if (errObj.message) errorMsg = errObj.message;
                        } catch(e) {
                            errorMsg = 'Error Server (' + xhr.status + '): ' + xhr.responseText.substring(0, 150);
                        }
                    }
                    alert(errorMsg);
                    $('#cameraModal').modal('show'); 
                }
            });
        }

        $(document).ready(function() {
            updateClockDisplay();
            setInterval(updateClockDisplay, 1000);
            getUserLocation();



            var currentPath = "<?php echo $current_page_basename; ?>";
            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) { $(this).addClass('active'); }
            });
            $('.custom-nav__link').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) { $(this).addClass('active'); }
            });
        });
    </script>

    <!-- Premium Checkout Lock Modal -->
    <div id="checkoutLockModal" class="checkout-lock-overlay">
        <div class="checkout-lock-card">
            <div class="lock-icon-wrapper">
                <svg width="90" height="90" viewBox="0 0 90 90">
                    <circle cx="45" cy="45" r="40" fill="none" stroke="#1e293b" stroke-width="5"/>
                    <circle id="lockProgressRing" cx="45" cy="45" r="40" fill="none" stroke="url(#lockGrad)" stroke-width="5" stroke-linecap="round" stroke-dasharray="251.2" stroke-dashoffset="251.2" transform="rotate(-90 45 45)" style="transition: stroke-dashoffset 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);"/>
                    <defs><linearGradient id="lockGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#3b82f6"/><stop offset="100%" stop-color="#8b5cf6"/></linearGradient></defs>
                </svg>
                <div class="lock-icon-center">
                    <i class="fas fa-lock"></i>
                    <span id="lockProgressPct" class="lock-pct">0%</span>
                </div>
            </div>
            <h5 class="lock-title">Belum Waktunya Pulang</h5>
            <p class="lock-desc">Absen pulang dapat dilakukan mulai pukul</p>
            <div class="lock-time-badge" id="lockModalTime">15:00 WIB</div>
            <div class="lock-remain" id="lockModalRemain">-- menit lagi</div>
            <button class="lock-dismiss-btn" onclick="$('#checkoutLockModal').removeClass('show');">Mengerti</button>
        </div>
    </div>

    <style>
        .checkout-lock-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0); z-index: 10000;
            display: flex; justify-content: center; align-items: center;
            pointer-events: none; opacity: 0;
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            backdrop-filter: blur(0px);
        }
        .checkout-lock-overlay.show {
            background: rgba(15, 23, 42, 0.75);
            pointer-events: auto; opacity: 1;
            backdrop-filter: blur(12px);
        }
        .checkout-lock-card {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 28px;
            padding: 32px 28px 26px;
            max-width: 340px; width: 88%;
            text-align: center;
            backdrop-filter: blur(24px);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.15);
            transform: scale(0.7) translateY(40px);
            transition: transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .checkout-lock-overlay.show .checkout-lock-card {
            transform: scale(1) translateY(0);
        }
        .lock-icon-wrapper {
            position: relative; width: 90px; height: 90px; margin: 0 auto 18px;
        }
        .lock-icon-center {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            display: flex; flex-direction: column; align-items: center; gap: 2px;
        }
        .lock-icon-center i {
            font-size: 1.4rem; color: #f59e0b;
            animation: lockPulse 2s ease-in-out infinite;
        }
        @keyframes lockPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.15); opacity: 0.8; }
        }
        .lock-pct {
            font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.7); letter-spacing: 0.5px;
        }
        .lock-title {
            color: #ffffff; font-weight: 800; font-size: 1.2rem; margin-bottom: 6px;
        }
        .lock-desc {
            color: rgba(255, 255, 255, 0.6); font-size: 0.85rem; margin-bottom: 10px;
        }
        .lock-time-badge {
            display: inline-block; background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #ffffff; font-weight: 800; font-size: 1.5rem;
            padding: 8px 28px; border-radius: 16px; letter-spacing: 1px;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
            margin-bottom: 10px;
        }
        .lock-remain {
            color: #fbbf24; font-weight: 700; font-size: 0.88rem; margin-bottom: 22px;
            letter-spacing: 0.3px;
        }
        .lock-dismiss-btn {
            width: 100%; padding: 13px; border: none;
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff; font-weight: 800; font-size: 0.95rem;
            border-radius: 14px; cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s ease;
            backdrop-filter: blur(8px);
        }
        .lock-dismiss-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        .lock-dismiss-btn:active {
            transform: translateY(1px);
        }
    </style>
</body>
</html>