<?php
session_start();
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}
include '../conn.php';

$role = $_SESSION['role'];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    $currentMonth = date('m');
    $currentYear = date('Y');
    $bulan = $currentMonth;
    $tahun = $currentYear;
}
$bulanNames = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_bulan_terpilih = $bulanNames[$bulan];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Karyawan - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
    .action-card-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; margin-top: 1rem; }
    .action-card { flex: 1 1 calc(33% - 1.5rem); max-width: 300px; background-color: #fff; border: 1px solid #e0e0e0; border-radius: 0.75rem; padding: 1.5rem; text-align: center; text-decoration: none; color: #333; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.08); transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .action-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); text-decoration: none; color: #333; }
    .action-card .icon { font-size: 3rem; margin-bottom: 1rem; color: #0d6efd; }
    .action-card.primary .icon { color: #0d6efd; }
    .action-card.success .icon { color: #198754; }
    .action-card.warning .icon { color: #ffc107; }
    .action-card .card-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #343a40; }
    .action-card .card-description { font-size: 0.9rem; color: #6c757d; line-height: 1.4; }
    @media (max-width: 992px) { .action-card { flex: 1 1 calc(50% - 1.5rem); } }
    @media (max-width: 576px) { .action-card { flex: 1 1 100%; max-width: unset; } .action-card .icon { font-size: 2.5rem; } .action-card .card-title { font-size: 1.1rem; } }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Rekapitulasi Absensi</h1>
                <p>Lihat rekapitulasi keterlambatan dan denda absensi karyawan.</p>
            </div>
        </div>
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-body action-card-container">
                        <a href="../absensi/upload-absen.php" class="action-card primary">
                            <div class="icon"><i class="fa-solid fa-cloud-arrow-up"></i></div> <div class="card-title">Upload Absensi</div>
                            <div class="card-description">Unggah data absensi dari mesin fingerprint atau file lainnya.</div>
                        </a>
                        <a href="request_jam.php" class="action-card success">
                            <div class="icon"><i class="fa-solid fa-hand-pointer"></i></div> <div class="card-title">Request Jam Absen</div>
                            <div class="card-description">Input absen manual untuk karyawan yang tidak terekam dengan kondisi tertentu.</div>
                        </a>
                        <a href="data-absensi.php" class="action-card warning">
                            <div class="icon"><i class="fa-solid fa-clipboard-check"></i></div> <div class="card-title">Verifikasi Absensi</div>
                            <div class="card-description">Tinjau dan verifikasi data absensi karyawan secara rinci.</div>
                        </a>
                    </div>
                </div>
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-body">
                        <form method="POST" action="absen.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="bulan" class="form-label">Pilih Bulan</label>
                                    <select id="bulan" name="bulan" class="form-select">
                                        <?php foreach ($bulanNames as $bulanNum => $bulanName): ?>
                                            <option value="<?php echo $bulanNum; ?>" <?php if ($bulanNum == $bulan) echo 'selected'; ?>><?php echo $bulanName; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="tahun" class="form-label">Pilih Tahun</label>
                                    <select id="tahun" name="tahun" class="form-select">
                                        <?php $tahunSekarang = date('Y'); for ($i = $tahunSekarang; $i >= $tahunSekarang - 10; $i--): ?>
                                            <option value="<?php echo $i; ?>" <?php if ($i == $tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-calendar-check title-icon"></i> Data Absen: <?php echo htmlspecialchars($nama_bulan_terpilih) . ' ' . htmlspecialchars($tahun); ?></h5>
                        <a href="validasi-absen.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="btn btn-success"><i class="fa-solid fa-check-double me-2"></i>Data Ini Sudah Benar</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light align-middle">
                                    <tr>
                                        <th rowspan="2">NIK</th>
                                        <th rowspan="2">Nama</th>
                                        <th rowspan="2">Shift</th>
                                        <th colspan="2">Terlambat</th>
                                        <th colspan="2">Tidak Absen</th>
                                        <th rowspan="2">Hadir</th>
                                        <th rowspan="2">Cuti</th>
                                        <th rowspan="2">Alfa</th>
                                        <th rowspan="2" style="background:#e9ecef;">Total Denda</th>
                                    </tr>
                                    <tr>
                                        <th>Menit</th>
                                        <th>Rupiah</th>
                                        <th>Jumlah</th>
                                        <th>Rupiah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM karyawan WHERE nip != '001' AND nip != '70326' AND nik != '114' AND status_karyawan = 'aktif' AND deleted_at IS NULL ORDER BY nama ASC";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            // PERBAIKAN: Gunakan ?? '' untuk menghindari nilai NULL
                                            $nik = $row['nik'] ?? ''; 
                                            $nama = $row['nama'] ?? '';
                                            $shifting = $row['shifting'] ?? '';
                                    
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($nik) . "</td>";
                                            echo "<td><a href='detail-absen.php?nik=" . urlencode($nik) . "&bulan=$bulan&tahun=$tahun' target='_blank'>" . htmlspecialchars($nama) . "</a></td>";
                                            echo "<td>" . htmlspecialchars($shifting) . "</td>";
                                            
                                            include "terlambat-db.php";
                                            
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='11' class='p-4 text-muted'>Tidak ada data karyawan aktif untuk ditampilkan.</td></tr>";
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
</body>
</html>