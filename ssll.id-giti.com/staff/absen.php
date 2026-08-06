<?php
session_start();
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}
include '../conn.php';

$role = $_SESSION['role'];

$bulan = $_REQUEST['bulan'] ?? date('m');
$tahun = $_REQUEST['tahun'] ?? date('Y');
$selected_karyawan = $_REQUEST['karyawan'] ?? '';
$selected_shift = $_REQUEST['shift'] ?? '';
$selected_status = $_REQUEST['status_filter'] ?? '';

$bulanNames = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_bulan_terpilih = $bulanNames[$bulan] ?? date('F');

// Fetch active employees list for filter dropdown
$list_karyawan = [];
$res_kar_drop = $conn->query("SELECT nik, nip, nama FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND nip NOT IN ('001','70326') ORDER BY nama ASC");
if ($res_kar_drop) {
    while ($k = $res_kar_drop->fetch_assoc()) {
        $list_karyawan[] = $k;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Absensi - Gravitti Tech</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            background: #f8fafc;
        }

        /* 3D Action Cards */
        .action-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .action-card-modern {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            padding: 1.5rem;
            text-decoration: none !important;
            color: #0f172a !important;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .action-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.08);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .card-icon-badge {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .card-icon-badge.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
        }

        .card-icon-badge.emerald {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: #ffffff;
        }

        .card-icon-badge.amber {
            background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
            color: #ffffff;
        }

        .action-card-title {
            font-weight: 800;
            font-size: 1.05rem;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .action-card-desc {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.35;
        }

        /* Filter Card */
        .filter-card-modern {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        /* Table Design */
        .rekap-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .emp-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #334155;
            font-weight: 700;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }

        .emp-name-link {
            text-decoration: none !important;
            color: #0f172a !important;
            font-weight: 700;
            transition: color 0.2s ease;
        }

        .emp-name-link:hover {
            color: #2563eb !important;
        }

        .table-custom-head {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom-head th {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 2px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Rekapitulasi Absensi</h1>
                <p>Pantau rekapitulasi keterlambatan, ketidakhadiran, dan denda karyawan secara realtime.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <!-- Quick Action Cards Grid -->
                <div class="action-card-grid no-print">
                    <a href="../absensi/upload-absen.php" class="action-card-modern">
                        <div class="card-icon-badge blue">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <div class="action-card-title">Upload Absensi</div>
                            <div class="action-card-desc">Unggah data absensi dari mesin fingerprint atau file excel.</div>
                        </div>
                    </a>

                    <a href="request_jam.php" class="action-card-modern">
                        <div class="card-icon-badge emerald">
                            <i class="fa-solid fa-hand-pointer"></i>
                        </div>
                        <div>
                            <div class="action-card-title">Request Jam Absen</div>
                            <div class="action-card-desc">Input absen manual untuk karyawan yang tidak terekam.</div>
                        </div>
                    </a>

                    <a href="data-absensi.php" class="action-card-modern">
                        <div class="card-icon-badge amber">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <div>
                            <div class="action-card-title">Verifikasi Absensi</div>
                            <div class="action-card-desc">Tinjau dan verifikasi foto & lokasi absen karyawan.</div>
                        </div>
                    </a>
                </div>

                <!-- Comprehensive Multi-Filter Bar -->
                <div class="filter-card-modern no-print">
                    <form method="GET" action="absen.php" id="filterForm">
                        <div class="row g-2.5 align-items-end">
                            
                            <!-- Bulan -->
                            <div class="col-6 col-md-2">
                                <label for="bulan" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar me-1 text-primary"></i>Bulan</label>
                                <select id="bulan" name="bulan" class="form-select rounded-3">
                                    <?php foreach ($bulanNames as $bulanNum => $bulanName): ?>
                                        <option value="<?php echo $bulanNum; ?>" <?php if ($bulanNum == $bulan) echo 'selected'; ?>><?php echo $bulanName; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tahun -->
                            <div class="col-6 col-md-2">
                                <label for="tahun" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar-days me-1 text-primary"></i>Tahun</label>
                                <select id="tahun" name="tahun" class="form-select rounded-3">
                                    <?php $tahunSekarang = date('Y'); for ($i = $tahunSekarang; $i >= $tahunSekarang - 10; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php if ($i == $tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Karyawan -->
                            <div class="col-12 col-md-3">
                                <label for="karyawan" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-user me-1 text-primary"></i>Karyawan</label>
                                <select id="karyawan" name="karyawan" class="form-select rounded-3">
                                    <option value="">-- Semua Karyawan --</option>
                                    <?php foreach ($list_karyawan as $kar): ?>
                                        <option value="<?php echo htmlspecialchars($kar['nik']); ?>" <?php if ($kar['nik'] == $selected_karyawan || $kar['nip'] == $selected_karyawan) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($kar['nama']); ?> (NIK: <?php echo htmlspecialchars($kar['nik']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Shift -->
                            <div class="col-6 col-md-2">
                                <label for="shift" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>Shift</label>
                                <select id="shift" name="shift" class="form-select rounded-3">
                                    <option value="">-- Semua Shift --</option>
                                    <option value="P" <?php if ($selected_shift == 'P') echo 'selected'; ?>>Shift P (07:00)</option>
                                    <option value="M" <?php if ($selected_shift == 'M') echo 'selected'; ?>>Shift M (08:30)</option>
                                    <option value="N" <?php if ($selected_shift == 'N') echo 'selected'; ?>>Shift N (09:00)</option>
                                    <option value="S" <?php if ($selected_shift == 'S') echo 'selected'; ?>>Shift S (09:30)</option>
                                    <option value="T" <?php if ($selected_shift == 'T') echo 'selected'; ?>>Shift T (09:10)</option>
                                    <option value="TEST" <?php if ($selected_shift == 'TEST') echo 'selected'; ?>>Shift TEST (24 Jam)</option>
                                </select>
                            </div>

                            <!-- Tombol Submit & Reset -->
                            <div class="col-6 col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1 rounded-3 fw-bold py-2"><i class="fa-solid fa-filter me-1"></i> Filter Data</button>
                                <a href="absen.php" class="btn btn-outline-secondary rounded-3 px-3 py-2" title="Reset Semua Filter"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- Data Table Card -->
                <div class="rekap-card">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calendar-check text-primary"></i>
                                Data Absen: <span class="text-primary"><?php echo htmlspecialchars($nama_bulan_terpilih) . ' ' . htmlspecialchars($tahun); ?></span>
                            </h5>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="max-width: 240px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="searchTableInput" class="form-control border-start-0 bg-light" placeholder="Cari nama karyawan / NIK...">
                            </div>
                            <a href="validasi-absen.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="btn btn-success btn-sm rounded-3 fw-bold px-3">
                                <i class="fa-solid fa-check-double me-1.5"></i>Data Ini Sudah Benar
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover text-center align-middle mb-0" id="rekapTable" style="font-size: 0.86rem;">
                                <thead class="table-custom-head">
                                    <tr>
                                        <th rowspan="2" class="text-start ps-3">NIK</th>
                                        <th rowspan="2" class="text-start">Nama Karyawan</th>
                                        <th rowspan="2">Shift</th>
                                        <th colspan="2" class="border-start">Terlambat</th>
                                        <th colspan="2" class="border-start">Tidak Absen</th>
                                        <th rowspan="2" class="border-start">Hadir</th>
                                        <th rowspan="2">Cuti</th>
                                        <th rowspan="2">Alfa</th>
                                        <th rowspan="2" class="border-start" style="background:#e2e8f0; color:#0f172a;">Total Denda</th>
                                    </tr>
                                    <tr>
                                        <th class="border-start">Menit</th>
                                        <th>Rupiah</th>
                                        <th class="border-start">Jumlah</th>
                                        <th>Rupiah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM karyawan WHERE nip != '001' AND nip != '70326' AND nik != '114' AND status_karyawan = 'aktif' AND deleted_at IS NULL";
                                    
                                    if (!empty($selected_karyawan)) {
                                        $sql .= " AND (nik = '$selected_karyawan' OR nip = '$selected_karyawan')";
                                    }
                                    if (!empty($selected_shift)) {
                                        $sql .= " AND shifting = '$selected_shift'";
                                    }
                                    
                                    $sql .= " ORDER BY nama ASC";
                                    $result = $conn->query($sql);
                                    
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $nik = $row['nik'] ?? '-'; 
                                            $nama = $row['nama'] ?? '-';
                                            $shifting = $row['shifting'] ?? '-';

                                            // Initials Avatar Generator
                                            $words = explode(' ', trim($nama));
                                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

                                            echo "<tr class='rekap-row'>";
                                            echo "<td class='text-start ps-3 fw-semibold text-secondary'>" . htmlspecialchars($nik) . "</td>";
                                            echo "<td class='text-start'>
                                                    <div class='d-flex align-items-center'>
                                                        <span class='emp-avatar'>" . htmlspecialchars($initials) . "</span>
                                                        <a href='detail-absen.php?nik=" . urlencode($nik) . "&bulan=$bulan&tahun=$tahun' target='_blank' class='emp-name-link'>" . htmlspecialchars($nama) . "</a>
                                                    </div>
                                                  </td>";
                                            echo "<td><span class='badge bg-light text-dark border px-2 py-1'>" . htmlspecialchars($shifting) . "</span></td>";
                                            
                                            include "terlambat-db.php";
                                            
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='11' class='p-4 text-muted'>Tidak ada data karyawan aktif yang memenuhi kriteria filter.</td></tr>";
                                    }
                                    $conn->close();
                                    ?>
                                </tbody>
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
            // Instant Table Live Search
            $('#searchTableInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#rekapTable tbody tr.rekap-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });
    </script>
</body>
</html>