<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');
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
        WHERE DATE(am.tgl_absen) = ? AND k.deleted_at IS NULL
        ORDER BY am.tgl_absen ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$result = $stmt->get_result();

$groupedData = [
    'Verification' => [],
    'Approved' => [],
    'Rejected' => []
];

while ($row = $result->fetch_assoc()) {
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Absensi - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .date-wrapper { display: flex; flex-direction: column; gap: 8px; }
        .date-row { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; }
        .date-row::-webkit-scrollbar { display: none; }
        .date-btn { min-width: 50px; padding: 4px; border-radius: 8px; border: 1px solid #dee2e6; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: #495057; }
        .date-btn .day-num { font-size: 1rem; font-weight: 700; line-height: 1.2; }
        .date-btn .day-name { font-size: 0.6rem; text-transform: uppercase; font-weight: 600; opacity: 0.8; }
        .date-btn.active { background-color: #0d6efd; color: #fff; border-color: #0d6efd; }
        .employee-card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 20px; border-top: 3px solid #0d6efd; }
        .prof-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 1px solid #eee; flex-shrink: 0; }
        .profile-info { display: flex; align-items: center; gap: 10px; }
        .abs-box { background: #f8f9fa; border: 1px solid #edf0f2; border-radius: 10px; padding: 12px; height: 100%; }
        .abs-photo { width: 65px; height: 65px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #dee2e6; flex-shrink: 0; }
        .abs-content { display: flex; gap: 12px; align-items: center; margin-top: 8px; }
        .abs-details { flex: 1; min-width: 0; font-size: 0.82rem; }
        .abs-details div { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .status-badge { font-size: 0.65rem; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; justify-content: center; line-height: 1; height: 22px; }
        .nav-tabs { border-bottom: 2px solid #f1f1f1; gap: 15px; }
        .nav-link { font-weight: 600; color: #888; border: none !important; padding: 10px 5px; font-size: 0.95rem; }
        .nav-link.active { color: #0d6efd !important; border-bottom: 3px solid #0d6efd !important; background: none !important; }
        .modal-img-preview { max-height: 50vh; width: auto; max-width: 90vw; border-radius: 10px; }
        .abs-header-flex { display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 8px; }
        .badge-group { display: flex; gap: 4px; align-items: center; }
        
        .loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.7); z-index: 9999;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            color: white; backdrop-filter: blur(2px);
        }
        .loading-spinner { width: 3rem; height: 3rem; border-width: 0.25em; }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    
    <div id="fullScreenLoader" class="loading-overlay d-none">
        <div class="spinner-border text-light loading-spinner mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="fw-bold">Memproses Data...</h5>
        <p class="small text-white-50">Mohon tunggu sebentar, jangan tutup halaman ini.</p>
    </div>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1 class="fs-4">Validasi Absensi Manual</h1>
                <p class="small opacity-75 mb-0">Verifikasi kehadiran foto harian karyawan</p>
            </div>
        </div>
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-2">
                        <form method="GET" class="row g-2 align-items-end mb-2">
                            <div class="col-6 col-md-3">
                                <label class="small fw-bold">Bulan</label>
                                <select name="bulan" class="form-select form-select-sm">
                                    <?php foreach ($bulanNames as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php if ($num == $bulan) echo 'selected'; ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="small fw-bold">Tahun</label>
                                <select name="tahun" class="form-select form-select-sm">
                                    <?php for ($i = date('Y'); $i >= date('Y') - 3; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php if ($i == $tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Tampilkan</button>
                            </div>
                        </form>
                        <div class="date-wrapper">
                            <?php foreach ($dateChunks as $chunk): ?>
                            <div class="date-row">
                                <?php foreach ($chunk as $wd): ?>
                                    <a href="?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>&tgl=<?php echo $wd['date']; ?>" class="date-btn <?php echo ($selectedDate === $wd['date']) ? 'active' : ''; ?>">
                                        <span class="day-num"><?php echo $wd['day']; ?></span>
                                        <span class="day-name"><?php echo $nama_hari_map[$wd['dayName']]; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4" id="absTab" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#verification">Verification (<?php echo count($groupedData['Verification']); ?>)</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#approved">Approved (<?php echo count($groupedData['Approved']); ?>)</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rejected">Rejected (<?php echo count($groupedData['Rejected']); ?>)</button></li>
                </ul>

                <div class="tab-content">
                    <?php foreach (['Verification', 'Approved', 'Rejected'] as $statusTab): ?>
                    <div class="tab-pane fade <?php echo ($statusTab === 'Verification') ? 'show active' : ''; ?>" id="<?php echo strtolower($statusTab); ?>">
                        <?php if (empty($groupedData[$statusTab])): ?>
                            <div class="text-center py-5 text-muted small">Tidak ada data absensi untuk kategori ini pada tanggal <?php echo date('d/m/Y', strtotime($selectedDate)); ?>.</div>
                        <?php else: ?>
                            <?php foreach ($groupedData[$statusTab] as $nip => $data): ?>
                            <div class="card employee-card">
                                <div class="card-header bg-white py-2 border-0">
                                    <div class="profile-info">
                                        <img src="../uploads/<?php echo htmlspecialchars($data['details']['pas_photo'] ?: 'default.png'); ?>" class="prof-img">
                                        <div class="lh-sm">
                                            <div class="fw-bold text-dark small"><?php echo htmlspecialchars($data['details']['nama']); ?></div>
                                            <div class="text-muted" style="font-size: 0.7rem;">NIK: <?php echo htmlspecialchars($data['details']['nik']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-1">
                                    <div class="row g-3">
                                        <?php foreach (['masuk', 'pulang'] as $type): ?>
                                        <div class="col-md-6">
                                            <div class="abs-box">
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
                                                    <div class="abs-header-flex">
                                                        <span class="small fw-bold <?php echo ($type === 'masuk') ? 'text-success' : 'text-danger'; ?>">ABSEN <?php echo strtoupper($type); ?></span>
                                                        <div class="badge-group">
                                                            <?php if($isAtOffice): ?>
                                                                <span class="badge bg-success status-badge"><i class="fa-solid fa-location-dot me-1"></i> DI KANTOR</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark status-badge"><i class="fa-solid fa-map-pin me-1"></i> LUAR KANTOR</span>
                                                            <?php endif; ?>
                                                            <span id="status-container-<?php echo $item['id']; ?>" style="margin-top:-2px;">
                                                                <span class="badge status-badge <?php echo ($item['verif'] === 'Yes') ? 'bg-success' : (($item['verif'] === 'No') ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                                                    <?php echo ($item['verif'] === 'Yes') ? 'Approved' : (($item['verif'] === 'No') ? 'Rejected' : 'Pending'); ?>
                                                                </span>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="abs-content mt-0">
                                                        <img src="../uploads/attendance/<?php echo htmlspecialchars($item['image']); ?>" class="abs-photo" onclick="previewImage(this.src)">
                                                        <div class="abs-details">
                                                            <div class="fw-bold text-primary mb-1"><i class="fa-solid fa-clock me-1"></i><?php echo date('H:i:s', strtotime($item['tgl_absen'])); ?></div>
                                                            <div class="text-muted" style="white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
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
                                                    <div class="btn-group btn-group-sm w-100 mt-3 shadow-sm">
                                                        <button class="btn btn-success" onclick="validateAttendance(<?php echo $item['id']; ?>, 'Yes', this)"><i class="fa-solid fa-check me-1"></i> Approve</button>
                                                        <button class="btn btn-danger" onclick="validateAttendance(<?php echo $item['id']; ?>, 'No', this)"><i class="fa-solid fa-xmark me-1"></i> Reject</button>
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

    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 bg-transparent shadow-none">
                <div class="modal-body p-0 d-flex justify-content-center">
                    <img src="" id="modalImg" class="modal-img-preview shadow-lg">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function previewImage(src) {
        $('#modalImg').attr('src', src);
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }

    function validateAttendance(id, status, button) {
        const container = $(`#status-container-${id}`);
        const group = $(button).parent();
        
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
                        const label = status === 'Yes' ? 'Approved' : 'Rejected';
                        container.html(`<span class="badge status-badge ${badgeClass}">${label}</span>`);
                    } else {
                        alert(res.message);
                        container.html('<span class="badge status-badge bg-warning text-dark">Pending</span>');
                    }
                }, 500);
            },
            error: function() {
                $('#fullScreenLoader').addClass('d-none');
                alert('Terjadi kesalahan saat memproses data.');
                container.html('<span class="badge status-badge bg-warning text-dark">Pending</span>');
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