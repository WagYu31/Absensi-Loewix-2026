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
    <title>Presensi Online - Grav-Tech</title>
    <meta name="description" content="Halaman presensi online karyawan Grav-Tech" />
    <meta name="keywords" content="presensi, absen, online, salary, gaji, gravitti technology" />
    <meta name="author" content="Irviani" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/presensi-styles.css">
    
    <style>
        body { background-color: #f4f6f9; }
        
        .filter-card { 
            border-radius: 16px; 
            background: #ffffff; 
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        
        .riwayat-card { 
            border-radius: 18px; 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
            background: #ffffff; 
            overflow: hidden; 
            margin-bottom: 20px;
        }

        .riwayat-date-badge { 
            background: rgba(13, 110, 253, 0.08); 
            color: #0d6efd; 
            border-radius: 14px; 
            padding: 10px; 
            width: 60px; 
            text-align: center; 
        }

        .presensi-box { 
            background: #f8f9fc; 
            border-radius: 16px; 
            padding: 16px; 
            height: 100%; 
            display: flex; 
            flex-direction: column; 
            border: 1px solid rgba(0,0,0,0.02);
        }

        .empty-state-box {
            background: transparent;
            border: 1.5px dashed #dee2e6;
            align-items: center;
            justify-content: center;
        }

        .time-text { 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: #1e2022; 
            line-height: 1.2; 
            letter-spacing: -0.5px;
        }

        .location-text { 
            font-size: 0.8rem; 
            color: #6c757d; 
            font-weight: 500;
        }

        .status-badge { 
            font-size: 0.7rem; 
            font-weight: 600; 
            padding: 6px 12px; 
            border-radius: 50rem; 
            display: inline-block;
        }

        .foto-presensi { 
            width: 100%; 
            height: auto; 
            max-height: 200px; 
            object-fit: contain; 
            background-color: #f0f2f5;
            border-radius: 12px; 
            transition: transform 0.2s ease;
        }

        .foto-presensi:active {
            transform: scale(0.97);
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="presensi-header-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3 px-2 presensi-top-bar">
                    <h5 class="text-light mb-0 page-title-presensi">RIWAYAT PRESENSI</h5>
                    <div class="text-light text-end time-date-display">
                        <span id="realTimeClockDisplay" class="d-block fw-bold"><?php echo date('H:i:s'); ?></span>
                        <small><?php echo date('d F Y'); ?></small>
                    </div>
                </div>
                <div class="employee-info-presensi card card-body mx-1 rounded-4 border-0" style="box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                    <div class="d-flex align-items-center">
                        <img src="../uploads/<?php echo htmlspecialchars($photo); ?>"
                            alt="Foto Profil" class="employee-photo-presensi me-3 rounded-circle shadow-sm"
                            onerror="this.onerror=null; this.src='https://via.placeholder.com/60/003c9c/ffffff?Text=<?php echo strtoupper(substr($nama, 0, 1)); ?>';">
                        <div>
                            <h6 class="mb-0 fw-bold text-white"><?php echo htmlspecialchars($nama); ?></h6>
                            <small class="text-white"><?php echo htmlspecialchars($jabatan); ?> - NIK: <?php echo htmlspecialchars($nik); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-content presensi-main-content px-lg-4 px-md-3 px-2 mt-4">
            <div class="container p-0">

                <div class="card filter-card mb-4">
                    <div class="card-body p-3">
                        <form method="GET" action="" class="row g-2 align-items-end">
                            <div class="col-6 col-md-4">
                                <label class="form-label text-muted" style="font-size: 0.75rem; font-weight: 500; margin-bottom: 4px;">Pilih Bulan</label>
                                <select name="bulan" class="form-select form-select-sm rounded-3 shadow-none bg-light border-0">
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
                                <label class="form-label text-muted" style="font-size: 0.75rem; font-weight: 500; margin-bottom: 4px;">Pilih Tahun</label>
                                <select name="tahun" class="form-select form-select-sm rounded-3 shadow-none bg-light border-0">
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
                                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 py-2 fw-medium shadow-sm">
                                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="riwayat-presensi-manual-list">
                    <?php
                    $nip_session = $_SESSION['nip'];
                    $limit_riwayat = 5;
                    $page_riwayat = isset($_GET['page_manual']) ? (int)$_GET['page_manual'] : 1;
                    $page_riwayat = max($page_riwayat, 1);
                    $offset_riwayat = ($page_riwayat - 1) * $limit_riwayat;

                    $where_clause = "nip='$nip_session' AND MONTH(tgl_absen)='$filter_bulan' AND YEAR(tgl_absen)='$filter_tahun'";

                    $totalResultRiwayat = mysqli_query($conn, "SELECT COUNT(*) as total FROM (SELECT DISTINCT DATE(tgl_absen) FROM absen_manual WHERE $where_clause) AS distinct_dates");
                    $totalRowRiwayat = mysqli_fetch_assoc($totalResultRiwayat);
                    $totalDataRiwayat = $totalRowRiwayat['total'] ?? 0;
                    $totalPagesRiwayat = ceil($totalDataRiwayat / $limit_riwayat);

                    $query_riwayat_harian = "
                        SELECT DATE(tgl_absen) AS tanggal
                        FROM absen_manual 
                        WHERE $where_clause 
                        GROUP BY DATE(tgl_absen)
                        ORDER BY tanggal DESC
                        LIMIT $limit_riwayat OFFSET $offset_riwayat
                    ";
                    $result_riwayat_harian = mysqli_query($conn, $query_riwayat_harian);
                    $query_params = "&bulan=$filter_bulan&tahun=$filter_tahun";
                    ?>

                    <?php if (mysqli_num_rows($result_riwayat_harian) > 0): ?>
                        <?php while ($hari = mysqli_fetch_assoc($result_riwayat_harian)): ?>
                            <?php
                            $tgl_riwayat = $hari['tanggal'];
                            $query_detail_hari = "SELECT tipe_absen, TIME(tgl_absen) AS jam, verif, image, lokasi_absen, lokasi_koordinat 
                                  FROM absen_manual 
                                  WHERE nip = '$nip_session' AND DATE(tgl_absen) = '$tgl_riwayat' 
                                  ORDER BY tgl_absen ASC";
                            $result_detail_hari = mysqli_query($conn, $query_detail_hari);

                            $data_hari_ini = ['masuk' => null, 'pulang' => null];
                            while ($rec = mysqli_fetch_assoc($result_detail_hari)) {
                                if ($rec['tipe_absen'] === 'masuk') $data_hari_ini['masuk'] = $rec;
                                if ($rec['tipe_absen'] === 'pulang') $data_hari_ini['pulang'] = $rec;
                            }
                            ?>
                            
                            <div class="card riwayat-card">
                                <div class="card-body p-3 p-md-4">
                                    <?php 
                                        $timestamp = strtotime($tgl_riwayat);
                                        $daftar_hari = [
                                            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                                            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                                        ];
                                        $nama_hari_id = $daftar_hari[date('l', $timestamp)];
                                    ?>
                                    
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="riwayat-date-badge me-3">
                                            <span class="d-block fw-bold fs-4 lh-1"><?php echo date('d', $timestamp); ?></span>
                                            <small class="d-block mt-1 fw-bold text-uppercase" style="font-size: 0.65rem;"><?php echo date('M', $timestamp); ?></small>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark fs-5"><?php echo $nama_hari_id; ?></h6>
                                            <small class="text-muted" style="font-weight: 500;"><?php echo date('d F Y', $timestamp); ?></small>
                                        </div>
                                    </div>

                                    <div class="row g-2 g-md-3">
                                        <?php foreach (['masuk', 'pulang'] as $tipe_presensi): ?>
                                            <div class="col-6">
                                                <?php if ($data_hari_ini[$tipe_presensi]): 
                                                    $record = $data_hari_ini[$tipe_presensi];
                                                    
                                                    // Kembalikan variabel text lokasi lengkap
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
                                                        <div class="d-flex align-items-center mb-1">
                                                            <?php if($tipe_presensi == 'masuk'): ?>
                                                                <i class="fa-solid fa-arrow-right-to-bracket text-primary me-2"></i>
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-arrow-right-from-bracket text-info me-2"></i>
                                                            <?php endif; ?>
                                                            <span class="text-capitalize fw-semibold text-secondary" style="font-size: 0.85rem;"><?php echo $tipe_presensi; ?></span>
                                                        </div>

                                                        <div class="time-text mb-1"><?php echo htmlspecialchars($record['jam']); ?></div>
                                                        
                                                        <div class="location-text mb-2 text-truncate" style="max-width: 100%;">
                                                            <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                                            
                                                            <!-- Teks alamat lengkap, disembunyikan di HP (d-none) dan dimunculkan di Desktop (d-md-inline) -->
                                                            <span class="d-none d-md-inline me-1 text-muted" title="<?php echo $lokasi_absen_text; ?>">
                                                                <?php echo $lokasi_absen_text; ?> -
                                                            </span>
                                                            
                                                            <!-- Status Singkat -->
                                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($mobile_location_text); ?></span>
                                                        </div>

                                                        <div class="mb-3">
                                                            <?php if ($record['verif'] === 'Yes'): ?>
                                                                <span class="status-badge bg-success-subtle text-success-emphasis"><i class="fa-solid fa-check me-1"></i>Terverifikasi</span>
                                                            <?php elseif ($record['verif'] === 'No'): ?>
                                                                <span class="status-badge bg-danger-subtle text-danger-emphasis"><i class="fa-solid fa-xmark me-1"></i>Ditolak</span>
                                                            <?php else: ?>
                                                                <span class="status-badge bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-clock me-1"></i>Pending</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <?php if (!empty($record['image'])): ?>
                                                            <div class="mt-auto">
                                                                <img src="../uploads/attendance/<?php echo htmlspecialchars($record['image']); ?>" 
                                                                     alt="Foto <?php echo ucfirst($tipe_presensi); ?>" 
                                                                     class="foto-presensi shadow-sm" 
                                                                     style="cursor: pointer;"
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#imagePreviewModal" 
                                                                     onclick="previewImage(this.src)">
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="presensi-box empty-state-box">
                                                        <div class="d-flex align-items-center mb-2 w-100 justify-content-center opacity-50">
                                                            <?php if($tipe_presensi == 'masuk'): ?>
                                                                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>
                                                            <?php endif; ?>
                                                            <span class="text-capitalize fw-semibold" style="font-size: 0.85rem;"><?php echo $tipe_presensi; ?></span>
                                                        </div>
                                                        <span class="text-muted fw-medium" style="font-size: 0.8rem;">Belum ada data</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php if ($totalPagesRiwayat > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4 mb-5">
                                <ul class="pagination pagination-sm justify-content-center border-0">
                                    <li class="page-item <?php echo ($page_riwayat <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link shadow-sm rounded-start-pill px-3" href="?page_manual=<?php echo $page_riwayat - 1 . $query_params; ?>" style="border: none;">
                                            <i class="fa-solid fa-chevron-left me-1"></i> Prev
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $start_page = max(1, $page_riwayat - 1);
                                    $end_page = min($totalPagesRiwayat, $page_riwayat + 1);
                                    for ($p = $start_page; $p <= $end_page; $p++): 
                                    ?>
                                        <li class="page-item <?php echo ($p == $page_riwayat) ? 'active' : ''; ?>">
                                            <a class="page-link shadow-sm fw-bold mx-1 rounded-circle" href="?page_manual=<?php echo $p . $query_params; ?>" style="border: none; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page_riwayat >= $totalPagesRiwayat) ? 'disabled' : ''; ?>">
                                        <a class="page-link shadow-sm rounded-end-pill px-3" href="?page_manual=<?php echo $page_riwayat + 1 . $query_params; ?>" style="border: none;">
                                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5 rounded-4" style="background: transparent;">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white shadow-sm mb-3" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-calendar-xmark fa-2x text-muted opacity-50"></i>
                            </div>
                            <h6 class="text-dark fw-bold">Belum Ada Presensi</h6>
                            <p class="text-muted small">Tidak ada data untuk bulan dan tahun yang dipilih.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer mt-4 pb-4">
            <div class="container text-center">
                <small class="text-muted fw-medium">Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>.<br>Version 1.2.0</small>
            </div>
        </div>

    </div>
        
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-transparent">
                <div class="modal-header border-0 pb-0 position-absolute top-0 end-0 z-3">
                    <button type="button" class="btn-close bg-white rounded-circle p-2 m-2 shadow" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center">
                    <img src="" id="modalPreviewImage" class="img-fluid rounded-4 w-100" alt="Preview Foto Absen">
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