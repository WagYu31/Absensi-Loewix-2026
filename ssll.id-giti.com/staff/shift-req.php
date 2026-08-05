<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';

$role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    $bulan = $_GET['bulan'] ?? date('m');
    $tahun = $_GET['tahun'] ?? date('Y');
}

$query = "SELECT shift_req.*, karyawan.nama, karyawan.pin_absen AS pin, karyawan.nik
        FROM shift_req 
        JOIN karyawan ON karyawan.pin_absen = shift_req.nip";

if (!empty($bulan) && !empty($tahun)) {
    $query .= " WHERE MONTH(shift_req.tgl_mulai) = ? AND YEAR(shift_req.tgl_mulai) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $bulan, $tahun);
} else {
    $current_bulan = date('m');
    $current_tahun = date('Y');
    $query .= " WHERE MONTH(shift_req.tgl_mulai) = ? AND YEAR(shift_req.tgl_mulai) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $current_bulan, $current_tahun);
}

$result = $stmt->execute();

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

$dataa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Shifting 3D - Gravitti Tech</title>
    
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

        /* 3D Glass Cards */
        .card-3d-style {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 20px 40px -10px rgba(15, 23, 42, 0.1),
                0 10px 20px -10px rgba(15, 23, 42, 0.05),
                inset 0 1px 1px rgba(255, 255, 255, 0.9) !important;
            overflow: hidden;
        }

        .card-3d-style .card-header {
            background: #ffffff !important;
            padding: 1.25rem 1.5rem !important;
            border-bottom: 1px solid #f1f5f9 !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            font-size: 1rem !important;
        }

        .card-3d-style .card-body {
            padding: 1.5rem !important;
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

        .shift-badge {
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            padding: 5px 12px !important;
            border-radius: 20px !important;
            display: inline-block;
        }

        .shift-badge.shift-1 { background: rgba(16, 185, 129, 0.12) !important; color: #059669 !important; border: 1px solid rgba(16, 185, 129, 0.25); }
        .shift-badge.shift-2 { background: rgba(37, 99, 235, 0.12) !important; color: #2563eb !important; border: 1px solid rgba(37, 99, 235, 0.25); }
        .shift-badge.shift-3 { background: rgba(147, 51, 234, 0.12) !important; color: #9333ea !important; border: 1px solid rgba(147, 51, 234, 0.25); }
        .shift-badge.shift-4 { background: rgba(217, 119, 6, 0.12) !important; color: #d97706 !important; border: 1px solid rgba(217, 119, 6, 0.25); }
        .shift-badge.shift-sabtu { background: rgba(236, 72, 153, 0.12) !important; color: #ec4899 !important; border: 1px solid rgba(236, 72, 153, 0.25); }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-calendar-week me-2 text-primary-light"></i>Request Shifting Karyawan</h1>
                <p class="small mb-0 opacity-80">Kelola dan ajukan permintaan perubahan shift harian karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content px-0">
            <div class="container-fluid px-lg-4">
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 shadow-sm border-0 rounded-4" style="background: #d1fae5; border-left: 5px solid #10b981 !important; padding: 1rem 1.25rem;" role="alert">
                    <div class="fw-bold text-emerald-900 fs-6">
                        <i class="fa-solid fa-circle-check me-2 text-success fs-5"></i>
                        Permintaan shifting karyawan berhasil ditambahkan!
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="row g-4">
                    
                    <!-- Form Request Shifting (Kiri) -->
                    <div class="col-lg-5 col-md-6">
                        <div class="card card-3d-style">
                            <div class="card-header d-flex align-items-center">
                                <i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Ajukan Permintaan Shifting Baru
                            </div>
                            <div class="card-body">
                                <form method="post" action="update_req_shift.php">
                                    <div class="mb-3">
                                        <label for="nama_karyawan" class="form-label"><i class="fa-solid fa-user me-1 text-primary"></i>Nama Karyawan</label>
                                        <select class="form-select" id="nama_karyawan" name="pin" required>
                                            <option value="">-- Pilih Karyawan --</option>
                                            <?php
                                            include '../conn.php';
                                            $queryNK = "SELECT pin_absen, nama FROM karyawan WHERE pin_absen IS NOT NULL ORDER BY nama ASC";
                                            $resultNK = $conn->query($queryNK);
                                            if ($resultNK && $resultNK->num_rows > 0) {
                                                while ($rowNK = $resultNK->fetch_assoc()) {
                                                    echo '<option value="' . $rowNK['pin_absen'] . '">' . htmlspecialchars($rowNK['nama']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label for="tanggal_mulai" class="form-label"><i class="fa-solid fa-calendar-day me-1 text-primary"></i>Tgl Mulai</label>
                                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                                        </div>
                                        <div class="col-6">
                                            <label for="tanggal_selesai" class="form-label"><i class="fa-solid fa-calendar-check me-1 text-primary"></i>Tgl Selesai</label>
                                            <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                                        </div>
                                        <div class="col-12 mt-1">
                                            <small class="text-muted fst-italic" style="font-size: 0.75rem;">* Isi tanggal yang sama jika hanya 1 hari</small>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="shift" class="form-label"><i class="fa-solid fa-clock me-1 text-primary"></i>Pilih Shift</label>
                                        <select class="form-select" id="shift" name="shift" required>
                                            <option value="P">Shift 1 (07.00 s/d 16.00)</option>
                                            <option value="M">Shift 2 (08.30 s/d 17.30)</option>
                                            <option value="N">Shift 3 (09.00 s/d 18.00)</option>
                                            <option value="S">Shift 4 (09.30 s/d 18.30)</option>
                                            <option value="T">Shift Harco (09.10 s/d 18.00)</option>
                                            <option value="W">Sabtu (8.30 s/d 13.00)</option>
                                            <option value="TW">Harco Sabtu (9.00 s/d 13.00)</option>
                                            <option value="TEST">Shift Testing (24 Jam Kamera Test)</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-submit-3d w-100"><i class="fa-solid fa-paper-plane me-2"></i>Submit Request</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Filter & Tabel Data Shifting (Kanan) -->
                    <div class="col-lg-7 col-md-6">
                        <!-- Filter Card -->
                        <div class="card card-3d-style mb-4 no-print">
                            <div class="card-header d-flex align-items-center">
                                <i class="fa-solid fa-filter me-2 text-primary"></i>Filter Data Shifting
                            </div>
                            <div class="card-body">
                                <form method="post" class="row g-3 align-items-end">
                                    <div class="col-6 col-md-5">
                                        <label for="bulan" class="form-label">Bulan:</label>
                                        <select id="bulan" name="bulan" class="form-select">
                                            <?php
                                            $bulanNames = array(
                                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                            );
                                            foreach ($bulanNames as $bulanNum => $bulanName) {
                                                $selected = ($bulanNum == $bulan) ? 'selected' : '';
                                                echo "<option value='$bulanNum' $selected>$bulanName</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label for="tahun" class="form-label">Tahun:</label>
                                        <select id="tahun" name="tahun" class="form-select">
                                            <?php
                                            $tahunSekarang = date('Y');
                                            for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                                                $selected = ($i == $tahun) ? 'selected' : '';
                                                echo "<option value='$i' $selected>$i</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <button type="submit" class="btn btn-filter-3d w-100"><i class="fa-solid fa-magnifying-glass me-1"></i>Show</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Data Table Card -->
                        <div class="card card-3d-style">
                            <div class="card-header d-flex align-items-center">
                                <i class="fa-solid fa-list-check me-2 text-primary"></i>Data Shifting Karyawan - Periode <?php echo $bulanNames[$bulan] . ' ' . $tahun; ?>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle" id="tabel-shift-req">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 45px;">No</th>
                                                <th>Nama Karyawan</th>
                                                <th>Tgl Mulai</th>
                                                <th>Tgl Selesai</th>
                                                <th>Shifting</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $nomor_urut = 1;
                                        if (empty($dataa)) {
                                            echo '<tr><td colspan="6" class="text-center p-5 text-muted">Tidak ada data shifting untuk periode ini.</td></tr>';
                                        } else {
                                            $bulan_indo = [
                                                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                                                'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agu',
                                                'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
                                            ];
                                    
                                            foreach ($dataa as $data) {
                                                $time_mulai = strtotime($data['tgl_mulai']);
                                                $time_selesai = strtotime($data['tgl_selesai']);
                                    
                                                $tgl_mulai_en = date('d M Y', $time_mulai);
                                                $tgl_selesai_en = date('d M Y', $time_selesai);
                                    
                                                $tanggal_mulai_format = strtr($tgl_mulai_en, $bulan_indo);
                                                $tanggal_selesai_format = strtr($tgl_selesai_en, $bulan_indo);
                                    
                                                $shifting_display = $data['shifting'];
                                                $badge_class = "shift-1";
                                                switch ($data['shifting']) {
                                                    case 'P': $shifting_display = "Shift 1 (07.00 - 16.00)"; $badge_class = "shift-1"; break;
                                                    case 'M': $shifting_display = "Shift 2 (08.30 - 17.30)"; $badge_class = "shift-2"; break;
                                                    case 'N': $shifting_display = "Shift 3 (09.00 - 18.00)"; $badge_class = "shift-3"; break;
                                                    case 'S': $shifting_display = "Shift 4 (09.30 - 18.30)"; $badge_class = "shift-4"; break;
                                                    case 'T': $shifting_display = "Shift Harco (09.10 - 18.00)"; $badge_class = "shift-2"; break;
                                                    case 'W': $shifting_display = "Sabtu (8.30 - 13.00)"; $badge_class = "shift-sabtu"; break;
                                                    case 'TW': $shifting_display = "Harco Sabtu (9.00 - 13.00)"; $badge_class = "shift-sabtu"; break;
                                                    case 'TEST': $shifting_display = "Shift Testing (24 Jam Test)"; $badge_class = "shift-3"; break;
                                                }
                                    
                                                echo "<tr>";
                                                echo "<td class='text-center fw-bold text-secondary'>" . $nomor_urut++ . "</td>";
                                                echo "<td style='text-transform:capitalize; font-weight: 600;'>" . htmlspecialchars($data['nama']) . "</td>";
                                                echo "<td class='text-secondary'>" . $tanggal_mulai_format . "</td>";
                                                echo "<td class='text-secondary'>" . $tanggal_selesai_format . "</td>";
                                                echo "<td><span class='shift-badge " . $badge_class . "'>" . htmlspecialchars($shifting_display) . "</span></td>";
                                                echo "<td class='text-center'>";
                                                echo "<button class='btn btn-action-delete' title='Hapus Shifting' onclick=\"confirmDelete('" . htmlspecialchars($data['id']) . "')\"><i class='fas fa-trash'></i></button>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm("Apakah Anda yakin ingin menghapus data shifting ini?")) {
                window.location.href = "proses-delete-req-shift.php?id=" + id;
            }
        }
    </script>
</body>
</html>