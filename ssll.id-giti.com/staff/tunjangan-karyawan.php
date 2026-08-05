<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../login.html');
    exit();
}

include '../conn.php';

$role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulan"])) {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    $bulan = date('m');
    $tahun = date('Y');
}

$query = "SELECT tunjangan_lainnya.*, karyawan.nama, karyawan.nip AS nipk, karyawan.nik, karyawan.status_karyawan
        FROM tunjangan_lainnya 
        JOIN karyawan ON karyawan.nip = tunjangan_lainnya.nip
        WHERE MONTH(tunjangan_lainnya.tanggal) = ? AND YEAR(tunjangan_lainnya.tanggal) = ? AND tunjangan_lainnya.ket1 = 'ganti'
        ORDER BY karyawan.nama ASC, tunjangan_lainnya.tanggal DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $bulan, $tahun);
$stmt->execute();
$dataa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$queryKar = "SELECT * FROM karyawan WHERE status_karyawan = 'aktif' AND nip NOT IN ('001', '70326') ORDER BY nama ASC";
$resultKar = $conn->query($queryKar);
$karyawan_list = $resultKar->fetch_all(MYSQLI_ASSOC);

$queryLockedDates = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
$resultLockedDates = $conn->query($queryLockedDates);
$lockedDates = array();
if ($resultLockedDates->num_rows > 0) {
    while ($rowLockedDate = $resultLockedDates->fetch_assoc()) {
        $lockedDates[] = $rowLockedDate['tahun'] . '-' . str_pad($rowLockedDate['bulan'], 2, '0', STR_PAD_LEFT);
    }
}

$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biaya Pengganti 3D - Gravitti Tech</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

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

        /* 3D Glass Cards & Accordion */
        .accordion-3d-item {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 20px 40px -10px rgba(15, 23, 42, 0.1),
                0 10px 20px -10px rgba(15, 23, 42, 0.05) !important;
            overflow: hidden;
        }

        .accordion-3d-item .accordion-button {
            background: #ffffff !important;
            padding: 1.25rem 1.5rem !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            font-size: 1rem !important;
            border-bottom: 1px solid #f1f5f9;
        }

        .accordion-3d-item .accordion-button:not(.collapsed) {
            color: #2563eb !important;
            background: rgba(37, 99, 235, 0.05) !important;
            box-shadow: none !important;
        }

        .main-card-3d.card {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 12px 24px -12px rgba(15, 23, 42, 0.08) !important;
            overflow: hidden;
        }

        .main-card-3d .card-header {
            background: #ffffff !important;
            padding: 1.25rem 1.5rem !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .form-label {
            font-size: 0.825rem !important;
            font-weight: 700 !important;
            color: #475569 !important;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-control, .form-select {
            border-radius: 14px !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 0.65rem 0.95rem !important;
            font-size: 0.9rem !important;
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

        /* Tactile 3D Buttons */
        .btn-submit-3d {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.9rem !important;
            border-radius: 14px !important;
            padding: 10px 24px !important;
            border: none !important;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-submit-3d:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.45), 0 4px 0 #1e40af !important;
            color: #ffffff !important;
        }

        .btn-submit-3d:active {
            transform: translateY(2px);
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3), 0 1px 0 #1e40af !important;
        }

        .btn-filter-3d {
            background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.875rem !important;
            border-radius: 12px !important;
            padding: 9px 18px !important;
            border: none !important;
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35), 0 3px 0 #0369a1 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-filter-3d:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
            box-shadow: 0 10px 20px rgba(2, 132, 199, 0.45), 0 4px 0 #0369a1 !important;
        }

        .btn-print-3d {
            background: var(--success-3d) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            border-radius: 12px !important;
            padding: 9px 16px !important;
            border: none !important;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35), 0 3px 0 #047857 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-print-3d:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.45), 0 4px 0 #065f46 !important;
        }

        .btn-action-delete {
            background: var(--danger-3d) !important;
            color: #ffffff !important;
            border-radius: 10px !important;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3), 0 2px 0 #b91c1c !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-action-delete:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(239, 68, 68, 0.4), 0 3px 0 #991b1b !important;
        }

        /* 3D Table Styling */
        .table thead th {
            background: #f8fafc !important;
            color: #475569 !important;
            font-weight: 800 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 1rem 1.25rem !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .table tbody td {
            padding: 1rem 1.25rem !important;
            vertical-align: middle;
            color: #334155 !important;
            font-size: 0.9rem !important;
        }

        .table-hover tbody tr:hover td {
            background-color: #f1f5f9 !important;
        }

        .nominal-badge {
            font-weight: 800 !important;
            color: #059669 !important;
            background: rgba(16, 185, 129, 0.1) !important;
            padding: 6px 14px !important;
            border-radius: 12px !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-file-invoice-dollar me-2 text-primary-light"></i>Biaya Pengganti</h1>
                <p class="small mb-0 opacity-80">Kelola data klaim & biaya pengganti harian karyawan (Tunjangan Lainnya).</p>
            </div>
        </div>

        <div class="dashboard-content px-0">
            <div class="container-fluid px-lg-4">
                
                <!-- Accordion Input Biaya Pengganti -->
                <div class="accordion mb-4 no-print" id="accordionTunjangan">
                    <div class="accordion-item accordion-3d-item border-0">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fa-solid fa-circle-plus me-2 text-primary"></i> Klik untuk Tambah Biaya Pengganti Baru
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionTunjangan">
                            <div class="accordion-body p-4">
                                <form action="../proses-tambah-data-tunjangan-lainnya-karyawan.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="nip-tunjangan" class="form-label"><i class="fa-solid fa-user me-1 text-primary"></i>Nama Karyawan</label>
                                            <select class="form-select" id="nip-tunjangan" name="nip_tunjangan" required>
                                                <option value="" disabled selected>-- Pilih Karyawan --</option>
                                                <?php foreach ($karyawan_list as $kar): ?>
                                                    <option value="<?php echo htmlspecialchars($kar['nip']); ?>"><?php echo htmlspecialchars($kar['nama']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-tunjangan" class="form-label"><i class="fa-solid fa-calendar-day me-1 text-primary"></i>Tanggal</label>
                                            <input type="date" class="form-control" id="tanggal-tunjangan" name="tanggal_tunjangan" onchange="checkLockedDates()" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="jumlah-tunjangan" class="form-label"><i class="fa-solid fa-money-bill-wave me-1 text-primary"></i>Jumlah (Rp)</label>
                                            <input type="number" class="form-control" id="jumlah-tunjangan" name="jumlah_tunjangan" placeholder="Contoh: 100000" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="keterangan-tunjangan" class="form-label"><i class="fa-solid fa-comment-dots me-1 text-primary"></i>Keterangan</label>
                                            <textarea class="form-control" id="keterangan-tunjangan" name="keterangan_tunjangan" rows="3" placeholder="Rincian keterangan biaya pengganti..." required></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-submit-3d"><i class="fa-solid fa-paper-plane me-2"></i>Simpan Biaya Pengganti</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Main Table -->
                <div class="card main-card-3d">
                    <div class="card-header">
                        <form method="POST" class="row g-2 align-items-center">
                            <div class="col-6 col-md-4">
                                <label class="form-label mb-1"><i class="fa-solid fa-calendar me-1 text-primary"></i>Bulan</label>
                                <select id="bulan" name="bulan" class="form-select">
                                    <?php 
                                    $bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                    foreach ($bulanNames as $num => $name) {
                                        echo "<option value='$num' " . ($num == $bulan ? 'selected' : '') . ">$name</option>";
                                    } ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1"><i class="fa-solid fa-calendar-days me-1 text-primary"></i>Tahun</label>
                                <select id="tahun" name="tahun" class="form-select">
                                    <?php 
                                    $tahunSekarang = date('Y');
                                    for ($i = $tahunSekarang; $i >= $tahunSekarang - 10; $i--) {
                                        echo "<option value='$i' " . ($i == $tahun ? 'selected' : '') . ">$i</option>";
                                    } ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-5 d-flex gap-2 align-items-end mt-md-0 mt-2">
                                <div class="flex-fill">
                                    <label class="form-label mb-1 opacity-0 d-none d-md-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-filter-3d w-100"><i class="fa-solid fa-magnifying-glass me-1"></i>Filter Data</button>
                                </div>
                                <div>
                                    <label class="form-label mb-1 opacity-0 d-none d-md-block">&nbsp;</label>
                                    <button type="button" onclick="printData()" class="btn btn-print-3d" title="Cetak Data">
                                        <i class="fa-solid fa-print me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">NIK</th>
                                        <th>Nama Karyawan</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Jumlah</th>
                                        <th class="text-center no-print" style="width: 80px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dataa)): ?>
                                        <tr><td colspan="6" class="text-center p-5 text-muted">Tidak ada data biaya pengganti untuk periode ini.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($dataa as $data): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary"><?php echo htmlspecialchars($data['nik']); ?></td>
                                        <td style="text-transform:capitalize; font-weight: 600;"><?php echo htmlspecialchars($data['nama']); ?></td>
                                        <td class="text-secondary"><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                                        <td><?php echo htmlspecialchars($data['keterangan']); ?></td>
                                        <td class="text-end"><span class="nominal-badge">Rp <?php echo number_format($data['jumlah'], 0, ',', '.'); ?></span></td>
                                        <td class="text-center no-print">
                                            <button onclick="deleteTunjangan('<?php echo $data['id_tunjangan_lain']; ?>')" class="btn btn-action-delete" title="Hapus Data">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        function deleteTunjangan(idTunjangan) {
            if (confirm("Apakah Anda yakin ingin menghapus data ini?")) {
                window.location.href = "../proses-hapus-data-tunjangan.php?id_tunjangan_lain=" + idTunjangan;
            }
        }

        function checkLockedDates() {
            const tanggalInput = document.getElementById("tanggal-tunjangan");
            const selectedDate = tanggalInput.value;
            const lockedDates = <?php echo json_encode($lockedDates); ?>;
            
            if (selectedDate) {
                const selectedYearMonth = selectedDate.substring(0, 7);
                if (lockedDates.includes(selectedYearMonth)) {
                    alert("Tanggal pada bulan dan tahun yang terkunci tidak dapat dipilih.");
                    tanggalInput.value = "";
                }
            }
        }

        function printData() {
            const bulan = document.getElementById("bulan").value;
            const tahun = document.getElementById("tahun").value;
            const url = "../print-tunjangan.php?bulan=" + bulan + "&tahun=" + tahun;
            window.open(url, "_blank");
        }

        $(document).ready(function() {
            $('#nip-tunjangan').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#collapseOne')
            });
        });
    </script>
</body>
</html>