<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

// Flag untuk menampilkan / menyembunyikan tombol Edit Jam (Set false untuk menyembunyikan)
$allow_edit_jam = false;

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');
$filter_karyawan = $_GET['karyawan'] ?? '';
$filter_lokasi = $_GET['lokasi'] ?? '';
$filter_tipe = $_GET['tipe'] ?? '';

$bulanNames = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

function getDistanceBetweenPoints($latitude1, $longitude1, $latitude2, $longitude2) {
    $earthRadius = 6371000;
    $latFrom = deg2rad($latitude1);
    $lonFrom = deg2rad($longitude1);
    $latTo = deg2rad($latitude2);
    $lonTo = deg2rad($longitude2);
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

$targetLat = -6.130189784035325;
$targetLon = 106.75142085117402;

$list_karyawan = [];
$res_kar = $conn->query("SELECT nip, nik, nama FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL ORDER BY nama ASC");
if ($res_kar) {
    while($rk = $res_kar->fetch_assoc()) {
        $list_karyawan[] = $rk;
    }
}

$holidays = [];
$sql_holidays = "SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND MONTH(tanggal_merah) = ? AND YEAR(tanggal_merah) = ?";
$stmt_h = $conn->prepare($sql_holidays);
$stmt_h->bind_param("ss", $bulan, $tahun);
$stmt_h->execute();
$res_h = $stmt_h->get_result();
while($row_h = $res_h->fetch_assoc()) {
    $holidays[] = $row_h['tanggal_merah'];
}
$stmt_h->close();

function isWorkingDay($date, $holidays) {
    $dayOfWeek = date('N', strtotime($date));
    if ($dayOfWeek == 7) return false;
    if (in_array($date, $holidays)) return false;
    return true;
}

$workingDays = [];
$daysInMonth = date('t', strtotime("$tahun-$bulan-01"));
for ($i = 1; $i <= $daysInMonth; $i++) {
    $dateStr = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i);
    if (isWorkingDay($dateStr, $holidays)) {
        $workingDays[] = [
            'day' => $i,
            'date' => $dateStr,
            'dayName' => date('l', strtotime($dateStr))
        ];
    }
}

$today = date('Y-m-d');
$currentMonthYear = date('m-Y', strtotime($today)) === "$bulan-$tahun";
$defaultDate = "";

if ($currentMonthYear && isWorkingDay($today, $holidays)) {
    $defaultDate = $today;
} else {
    for ($i = count($workingDays) - 1; $i >= 0; $i--) {
        if ($workingDays[$i]['date'] <= $today || !$currentMonthYear) {
            $defaultDate = $workingDays[$i]['date'];
            break;
        }
    }
}

$selectedDate = $_GET['tgl'] ?? $defaultDate;

$sql = "SELECT am.*, k.nama, k.nik, k.pas_photo 
        FROM absen_manual am
        JOIN karyawan k ON am.nip = k.nip
        WHERE DATE(am.tgl_absen) = ? AND k.deleted_at IS NULL";

$params = [$selectedDate];
$types = "s";

if (!empty($filter_karyawan)) {
    $sql .= " AND (k.nip = ? OR k.nik = ?)";
    $params[] = $filter_karyawan;
    $params[] = $filter_karyawan;
    $types .= "ss";
}

if (!empty($filter_tipe) && in_array($filter_tipe, ['masuk', 'pulang'])) {
    $sql .= " AND am.tipe_absen = ?";
    $params[] = $filter_tipe;
    $types .= "s";
}

$sql .= " ORDER BY am.tgl_absen ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$groupedData = [
    'Verification' => [],
    'Approved' => [],
    'Rejected' => []
];

while ($row = $result->fetch_assoc()) {
    $isAtOffice = false;
    if (!empty($row['lokasi_koordinat']) && $row['lokasi_koordinat'] !== "Koordinat tidak valid/tersedia") {
        $coords = explode(',', $row['lokasi_koordinat']);
        if (count($coords) == 2) {
            $dist = getDistanceBetweenPoints($coords[0], $coords[1], $targetLat, $targetLon);
            if ($dist <= 150) $isAtOffice = true;
        }
    }

    if ($filter_lokasi === 'kantor' && !$isAtOffice) continue;
    if ($filter_lokasi === 'luar' && $isAtOffice) continue;

    $statusKey = 'Verification';
    if ($row['verif'] === 'Yes') {
        $statusKey = 'Approved';
    } elseif ($row['verif'] === 'No') {
        $statusKey = 'Rejected';
    }

    $groupedData[$statusKey][$row['nip']]['details'] = [
        'nama' => $row['nama'],
        'nik' => $row['nik'],
        'pas_photo' => $row['pas_photo']
    ];
    $groupedData[$statusKey][$row['nip']][$row['tipe_absen']] = $row;
}
$stmt = null;

$nama_hari_map = ['Monday'=>'SEN','Tuesday'=>'SEL','Wednesday'=>'RAB','Thursday'=>'KAM','Friday'=>'JUM','Saturday'=>'SAB','Sunday'=>'MIN'];
$dateChunks = array_chunk($workingDays, ceil(count($workingDays) / 2));
$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Absensi 3D - Gravitti Tech</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">

    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            --card-radius-lg: 24px;
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
            --success-3d: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);
            --danger-3d: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #b91c1c 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #f1f5f9 !important;
        }

        .main-content-wrapper {
            background: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%) !important;
            min-height: 100vh;
        }

        /* 3D Header Banner */
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

        /* 3D Glass Filter Card */
        .filter-section-card {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 20px 40px -10px rgba(15, 23, 42, 0.1),
                0 10px 20px -10px rgba(15, 23, 42, 0.05) !important;
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
        }

        .form-label {
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            color: #475569 !important;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-control, .form-select {
            border-radius: 12px !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 0.55rem 0.85rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            background-color: #f8fafc !important;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3b82f6 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
        }

        /* 3D Working Days Picker Buttons */
        .date-wrapper { display: flex; flex-direction: column; gap: 10px; align-items: center; }
        .date-row { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; padding: 2px 0; }
        .date-row::-webkit-scrollbar { display: none; }
        
        .date-btn {
            min-width: 52px;
            padding: 8px 6px;
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #475569;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03), 0 2px 0 #cbd5e1;
            transition: all 0.15s ease-out;
        }

        .date-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08), 0 3px 0 #94a3b8;
            color: #1e293b;
        }

        .date-btn .day-num { font-size: 1.1rem; font-weight: 800; line-height: 1.1; }
        .date-btn .day-name { font-size: 0.65rem; text-transform: uppercase; font-weight: 700; opacity: 0.75; }

        .date-btn.active {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            border-color: #2563eb !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
        }
        .date-btn.active .day-name { opacity: 0.9; }

        /* 3D Nav Tabs */
        .nav-tabs-3d {
            border-bottom: 2px solid #e2e8f0 !important;
            gap: 12px;
        }

        .nav-tabs-3d .nav-link {
            font-weight: 800 !important;
            font-size: 0.9rem !important;
            color: #64748b !important;
            border: none !important;
            padding: 10px 20px !important;
            border-radius: 14px 14px 0 0 !important;
            background: transparent !important;
            transition: all 0.2s ease;
        }

        .nav-tabs-3d .nav-link.active {
            color: #2563eb !important;
            background: #ffffff !important;
            border-bottom: 3px solid #2563eb !important;
            box-shadow: 0 -4px 12px rgba(37, 99, 235, 0.08) !important;
        }

        /* 3D Employee Attendance Card */
        .employee-card-3d {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 20px 40px -10px rgba(15, 23, 42, 0.08),
                0 10px 20px -10px rgba(15, 23, 42, 0.04) !important;
            margin-bottom: 1.5rem !important;
            overflow: hidden;
            border-top: 4px solid #3b82f6 !important;
        }

        .employee-card-3d .card-header {
            background: #ffffff !important;
            padding: 1rem 1.25rem !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .prof-img {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .abs-box-3d {
            background: #f8fafc !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 16px !important;
            padding: 1rem !important;
            height: 100%;
        }

        .abs-photo {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s ease;
        }

        .abs-photo:hover {
            transform: scale(1.05);
        }

        .status-badge-3d {
            font-size: 0.7rem !important;
            padding: 4px 10px !important;
            border-radius: 20px !important;
            font-weight: 800 !important;
            letter-spacing: 0.3px;
        }

        /* Tactile 3D Buttons */
        .btn-approve-3d {
            background: var(--success-3d) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.8rem !important;
            border-radius: 10px !important;
            padding: 6px 14px !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3), 0 2px 0 #047857 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-approve-3d:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.4), 0 3px 0 #065f46 !important;
            color: #ffffff !important;
        }

        .btn-reject-3d {
            background: var(--danger-3d) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.8rem !important;
            border-radius: 10px !important;
            padding: 6px 14px !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3), 0 2px 0 #b91c1c !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-reject-3d:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(239, 68, 68, 0.4), 0 3px 0 #991b1b !important;
            color: #ffffff !important;
        }

        .loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(15, 23, 42, 0.75); z-index: 9999;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            color: white; backdrop-filter: blur(4px);
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    
    <div id="fullScreenLoader" class="loading-overlay d-none">
        <div class="spinner-border text-primary loading-spinner mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="fw-bold">Memproses Data...</h5>
        <p class="small text-white-50">Mohon tunggu sebentar, jangan tutup halaman ini.</p>
    </div>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-clipboard-user me-2 text-primary-light"></i>Validasi Absensi Manual</h1>
                <p class="small opacity-80 mb-0">Verifikasi kehadiran foto & titik lokasi harian seluruh karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content px-0">
            <div class="container-fluid px-lg-4">
                
                <!-- Expanded Multi-Filter Card -->
                <div class="filter-section-card">
                    <form method="GET" id="filterForm" class="row g-2 align-items-end mb-2">
                        <input type="hidden" name="tgl" value="<?php echo htmlspecialchars($selectedDate); ?>">
                        
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1"><i class="fa-solid fa-calendar me-1 text-primary"></i>Bulan</label>
                            <select name="bulan" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($bulanNames as $num => $name): ?>
                                    <option value="<?php echo $num; ?>" <?php if ($num == $bulan) echo 'selected'; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1"><i class="fa-solid fa-calendar-days me-1 text-primary"></i>Tahun</label>
                            <select name="tahun" class="form-select" onchange="this.form.submit()">
                                <?php for ($i = date('Y'); $i >= date('Y') - 3; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php if ($i == $tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label mb-1"><i class="fa-solid fa-user me-1 text-primary"></i>Karyawan</label>
                            <select name="karyawan" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Semua Karyawan --</option>
                                <?php foreach ($list_karyawan as $kar): ?>
                                    <option value="<?php echo htmlspecialchars($kar['nip']); ?>" <?php if ($filter_karyawan == $kar['nip']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($kar['nama']) . ' (NIK: ' . htmlspecialchars($kar['nik']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1"><i class="fa-solid fa-location-dot me-1 text-primary"></i>Lokasi Absen</label>
                            <select name="lokasi" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Lokasi</option>
                                <option value="kantor" <?php if ($filter_lokasi == 'kantor') echo 'selected'; ?>>Di Kantor (&le;150m)</option>
                                <option value="luar" <?php if ($filter_lokasi == 'luar') echo 'selected'; ?>>Luar Kantor (&gt;150m)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1"><i class="fa-solid fa-clock me-1 text-primary"></i>Tipe Absen</label>
                            <select name="tipe" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Tipe</option>
                                <option value="masuk" <?php if ($filter_tipe == 'masuk') echo 'selected'; ?>>Hanya Masuk</option>
                                <option value="pulang" <?php if ($filter_tipe == 'pulang') echo 'selected'; ?>>Hanya Pulang</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-1">
                            <a href="data-absensi.php" class="btn btn-outline-secondary w-100 fw-bold rounded-3" style="font-size: 0.85rem; padding: 0.55rem 0.5rem;" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                        </div>
                    </form>

                    <!-- Realtime Search Input -->
                    <div class="row g-2 mt-2">
                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-primary"></i></span>
                                <input type="text" id="liveSearchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama karyawan atau NIK secara cepat..." onkeyup="filterLiveCards()">
                            </div>
                        </div>
                    </div>

                    <hr class="my-3 text-secondary opacity-20">

                    <!-- 3D Working Days Date Picker -->
                    <div class="date-wrapper">
                        <?php foreach ($dateChunks as $chunk): ?>
                        <div class="date-row">
                            <?php foreach ($chunk as $wd): ?>
                                <a href="?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>&karyawan=<?php echo urlencode($filter_karyawan); ?>&lokasi=<?php echo urlencode($filter_lokasi); ?>&tipe=<?php echo urlencode($filter_tipe); ?>&tgl=<?php echo $wd['date']; ?>" class="date-btn <?php echo ($selectedDate === $wd['date']) ? 'active' : ''; ?>">
                                    <span class="day-num"><?php echo $wd['day']; ?></span>
                                    <span class="day-name"><?php echo $nama_hari_map[$wd['dayName']]; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Status Nav Tabs 3D -->
                <ul class="nav nav-tabs nav-tabs-3d mb-4" id="absTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#verification">
                            <i class="fa-solid fa-hourglass-half me-2 text-warning"></i>Verification (<?php echo count($groupedData['Verification']); ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#approved">
                            <i class="fa-solid fa-circle-check me-2 text-success"></i>Approved (<?php echo count($groupedData['Approved']); ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#rejected">
                            <i class="fa-solid fa-circle-xmark me-2 text-danger"></i>Rejected (<?php echo count($groupedData['Rejected']); ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <?php foreach (['Verification', 'Approved', 'Rejected'] as $statusTab): ?>
                    <div class="tab-pane fade <?php echo ($statusTab === 'Verification') ? 'show active' : ''; ?>" id="<?php echo strtolower($statusTab); ?>">
                        <?php if (empty($groupedData[$statusTab])): ?>
                            <div class="text-center py-5 text-muted small bg-white rounded-4 shadow-sm">
                                <i class="fa-solid fa-clipboard-check fa-3x mb-3 text-primary opacity-30"></i><br>
                                <span class="fw-bold fs-6">Tidak ada data absensi untuk kategori ini.</span><br>
                                Pada tanggal <?php echo date('d M Y', strtotime($selectedDate)); ?>.
                            </div>
                        <?php else: ?>
                            <?php foreach ($groupedData[$statusTab] as $nip => $data): ?>
                            <div class="card employee-card-3d search-target-card" data-employee-name="<?php echo htmlspecialchars(strtolower($data['details']['nama'])); ?>" data-employee-nik="<?php echo htmlspecialchars(strtolower($data['details']['nik'])); ?>">
                                <div class="card-header bg-white py-2 border-0">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="../uploads/<?php echo htmlspecialchars($data['details']['pas_photo'] ?: 'default.png'); ?>" class="prof-img" onerror="this.onerror=null; this.src='https://via.placeholder.com/40/003c9c/ffffff?Text=<?php echo strtoupper(substr($data['details']['nama'], 0, 1)); ?>';">
                                        <div>
                                            <div class="fw-bold text-dark fs-6" style="text-transform:capitalize;"><?php echo htmlspecialchars($data['details']['nama']); ?></div>
                                            <small class="text-muted" style="font-size: 0.75rem;">NIK: <?php echo htmlspecialchars($data['details']['nik']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-1">
                                    <div class="row g-3">
                                        <?php foreach (['masuk', 'pulang'] as $type): ?>
                                        <div class="col-md-6">
                                            <div class="abs-box-3d">
                                                <?php if (isset($data[$type])): 
                                                    $item = $data[$type];
                                                    $isAtOffice = false;
                                                    if (!empty($item['lokasi_koordinat']) && $item['lokasi_koordinat'] !== "Koordinat tidak valid/tersedia") {
                                                        $coords = explode(',', $item['lokasi_koordinat']);
                                                        if (count($coords) == 2) {
                                                            $dist = getDistanceBetweenPoints($coords[0], $coords[1], $targetLat, $targetLon);
                                                            if ($dist <= 150) $isAtOffice = true;
                                                        }
                                                    }
                                                ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="badge <?php echo ($type === 'masuk') ? 'bg-emerald-100 text-success border border-success-subtle' : 'bg-rose-100 text-danger border border-danger-subtle'; ?> fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                                            ABSEN <?php echo strtoupper($type); ?>
                                                        </span>
                                                        <div class="d-flex gap-1 align-items-center">
                                                            <?php if($isAtOffice): ?>
                                                                <span class="badge bg-success status-badge-3d"><i class="fa-solid fa-location-dot me-1"></i> DI KANTOR</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark status-badge-3d"><i class="fa-solid fa-map-pin me-1"></i> LUAR KANTOR</span>
                                                            <?php endif; ?>
                                                            <span id="status-container-<?php echo $item['id']; ?>">
                                                                <span class="badge status-badge-3d <?php echo ($item['verif'] === 'Yes') ? 'bg-success' : (($item['verif'] === 'No') ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                                                    <?php echo ($item['verif'] === 'Yes') ? 'APPROVED' : (($item['verif'] === 'No') ? 'REJECTED' : 'PENDING'); ?>
                                                                </span>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-3 align-items-center mt-2">
                                                        <img src="../uploads/attendance/<?php echo htmlspecialchars($item['image']); ?>" class="abs-photo" onclick="previewImage(this.src)">
                                                        <div class="flex-grow-1 min-w-0" style="font-size: 0.85rem;">
                                                            <div class="fw-bold text-primary mb-1 d-flex align-items-center justify-content-between">
                                                                <span><i class="fa-solid fa-clock me-1"></i><?php echo date('H:i:s', strtotime($item['tgl_absen'])); ?></span>
                                                                <?php if ($allow_edit_jam): ?>
                                                                <button class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" style="font-size: 0.7rem;" title="Edit Jam Absen Ini" onclick="openEditTimeModal(<?php echo $item['id']; ?>, '<?php echo date('H:i:s', strtotime($item['tgl_absen'])); ?>', '<?php echo htmlspecialchars(addslashes($data['details']['nama'])); ?>', '<?php echo strtoupper($type); ?>')">
                                                                    <i class="fa-solid fa-pen-to-square me-1"></i>Edit Jam
                                                                </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="text-muted" style="white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; font-size: 0.8rem;">
                                                                <i class="fa-solid fa-map-marker-alt me-1 text-danger"></i>
                                                                <?php if (!empty($item['lokasi_koordinat']) && $item['lokasi_koordinat'] !== "Koordinat tidak valid/tersedia"): ?>
                                                                    <a href="https://www.google.com/maps?q=<?php echo $item['lokasi_koordinat']; ?>" target="_blank" class="text-decoration-none text-muted" title="Klik untuk lihat di Maps">
                                                                        <?php echo htmlspecialchars($item['lokasi_absen']); ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <?php echo htmlspecialchars($item['lokasi_absen']); ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-2 w-100 mt-3">
                                                        <button class="btn btn-approve-3d flex-fill" onclick="validateAttendance(<?php echo $item['id']; ?>, 'Yes', this)"><i class="fa-solid fa-check me-1"></i> Approve</button>
                                                        <button class="btn btn-reject-3d flex-fill" onclick="validateAttendance(<?php echo $item['id']; ?>, 'No', this)"><i class="fa-solid fa-xmark me-1"></i> Reject</button>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center py-4 text-muted small fst-italic opacity-50">Data absen <?php echo $type; ?> belum tersedia</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 bg-transparent shadow-none">
                <div class="modal-body p-0 d-flex justify-content-center">
                    <img src="" id="modalImg" style="max-height: 80vh; width: auto; max-width: 90vw; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Time Modal -->
    <div class="modal fade" id="editTimeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header py-3 bg-light rounded-top-4">
                    <h6 class="modal-title fw-bold text-dark mb-0"><i class="fa-solid fa-clock me-2 text-primary"></i>Edit Jam Absen</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <input type="hidden" id="editTimeId">
                    <p class="small text-muted mb-2" id="editTimeEmployeeLabel"></p>
                    <div class="form-group mb-1">
                        <label class="small fw-bold mb-1">Pilih Jam Baru (HH:MM:SS):</label>
                        <input type="time" step="1" class="form-control form-control-lg fw-bold text-center text-primary" id="editTimeInput">
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm fw-bold rounded-3" onclick="saveEditedTime()"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function filterLiveCards() {
        const query = $('#liveSearchInput').val().toLowerCase().trim();
        $('.search-target-card').each(function() {
            const name = $(this).attr('data-employee-name') || '';
            const nik = $(this).attr('data-employee-nik') || '';
            if (name.includes(query) || nik.includes(query)) {
                $(this).removeClass('d-none');
            } else {
                $(this).addClass('d-none');
            }
        });
    }

    function previewImage(src) {
        $('#modalImg').attr('src', src);
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }

    function openEditTimeModal(id, currentTime, employeeName, typeName) {
        $('#editTimeId').val(id);
        $('#editTimeInput').val(currentTime);
        $('#editTimeEmployeeLabel').html(`Karyawan: <strong>${employeeName}</strong><br>Absen: <strong>${typeName}</strong>`);
        new bootstrap.Modal(document.getElementById('editTimeModal')).show();
    }

    function saveEditedTime() {
        const id = $('#editTimeId').val();
        const newTime = $('#editTimeInput').val();
        if (!newTime) {
            alert('Silakan pilih jam baru terlebih dahulu.');
            return;
        }

        $('#fullScreenLoader').removeClass('d-none');
        $.ajax({
            url: 'edit_jam_absen.php',
            type: 'POST',
            data: { id_absen: id, jam_baru: newTime },
            dataType: 'json',
            success: function(res) {
                $('#fullScreenLoader').addClass('d-none');
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                $('#fullScreenLoader').addClass('d-none');
                alert('Terjadi kesalahan koneksi.');
            }
        });
    }

    function validateAttendance(id, status, button) {
        const container = $(`#status-container-${id}`);
        
        $('#fullScreenLoader').removeClass('d-none');
        
        $.ajax({
            url: 'proses_validasi_absen.php',
            type: 'POST',
            data: { id_absen: id, status: status },
            dataType: 'json',
            success: function(res) {
                setTimeout(() => {
                    $('#fullScreenLoader').addClass('d-none');
                    
                    if (res.success) {
                        const badgeClass = status === 'Yes' ? 'bg-success' : 'bg-danger';
                        const label = status === 'Yes' ? 'APPROVED' : 'REJECTED';
                        container.html(`<span class="badge status-badge-3d ${badgeClass}">${label}</span>`);
                    } else {
                        alert(res.message);
                        container.html('<span class="badge status-badge-3d bg-warning text-dark">PENDING</span>');
                    }
                }, 500);
            },
            error: function() {
                $('#fullScreenLoader').addClass('d-none');
                alert('Terjadi kesalahan saat memproses data.');
                container.html('<span class="badge status-badge-3d bg-warning text-dark">PENDING</span>');
            }
        });
    }
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>