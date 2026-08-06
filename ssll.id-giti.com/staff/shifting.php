<?php
session_start();

// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$selected_shift_filter = $_GET['shift_filter'] ?? '';

// Ambil semua data karyawan yang relevan
$karyawan_list = [];
$sql = "SELECT nik, pin_absen, nama, shifting, nip 
        FROM karyawan 
        WHERE pin_absen IS NOT NULL 
          AND pin_absen <> 0 
          AND status_karyawan = 'aktif' 
          AND deleted_at IS NULL ";

if (!empty($selected_shift_filter)) {
    $sql .= " AND shifting = '" . $conn->real_escape_string($selected_shift_filter) . "' ";
}

$sql .= " ORDER BY nama ASC";
$result = $conn->query($sql);
if ($result) {
    $karyawan_list = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Shifting - Gravitti Tech</title>
    
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

        /* Top Action Card */
        .action-card-shift {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
        }

        /* Filter Card */
        .filter-card-shift {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
        }

        /* Table Card */
        .shift-card-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
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

        /* Tactile Segmented Shift Chips */
        .shift-pill-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .shift-chip {
            position: relative;
            cursor: pointer;
        }

        .shift-chip input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .shift-chip-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
            user-select: none;
        }

        .shift-chip:hover .shift-chip-label {
            background: #e2e8f0;
            color: #0f172a;
        }

        .shift-chip input[type="radio"]:checked + .shift-chip-label {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border-color: #1d4ed8;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .shift-chip input[type="radio"][value="M"]:checked + .shift-chip-label {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border-color: #047857;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        .shift-chip input[type="radio"][value="N"]:checked + .shift-chip-label {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            border-color: #6d28d9;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }

        .shift-chip input[type="radio"][value="S"]:checked + .shift-chip-label {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border-color: #0369a1;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .shift-chip input[type="radio"][value="T"]:checked + .shift-chip-label {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            border-color: #b45309;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <!-- Header Banner -->
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Pengaturan Shifting Karyawan</h1>
                <p class="small opacity-80 mb-0">Kelola dan sesuaikan jadwal shifting default untuk setiap karyawan secara realtime.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <!-- Request Shifting Top Action Bar -->
                <div class="action-card-shift no-print">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); font-size:1.4rem;">
                            <i class="fa-solid fa-user-clock"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Pengajuan Request Shift Khusus</h6>
                            <p class="text-muted small mb-0">Input penyesuaian shift sementara untuk karyawan tertentu berdasarkan tanggal.</p>
                        </div>
                    </div>
                    <a href="shift-req.php" class="btn btn-warning rounded-3 fw-bold px-4 py-2 text-dark shadow-sm">
                        <i class="fa-solid fa-hand-pointer me-1.5"></i> Request Shifting
                    </a>
                </div>

                <!-- Comprehensive Multi-Filter Bar -->
                <div class="filter-card-shift no-print">
                    <form method="GET" action="shifting.php" id="filterForm">
                        <div class="row g-2.5 align-items-center">
                            
                            <!-- Shift Filter Dropdown -->
                            <div class="col-12 col-md-5">
                                <label for="shift_filter" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i> Filter Shift Default</label>
                                <select id="shift_filter" name="shift_filter" class="form-select rounded-3" onchange="this.form.submit()">
                                    <option value="">-- Semua Shift --</option>
                                    <option value="P" <?php if ($selected_shift_filter == 'P') echo 'selected'; ?>>Shift P (Pagi - 07:00)</option>
                                    <option value="M" <?php if ($selected_shift_filter == 'M') echo 'selected'; ?>>Shift M (Tengah - 08:30)</option>
                                    <option value="N" <?php if ($selected_shift_filter == 'N') echo 'selected'; ?>>Shift N (Siang - 09:00)</option>
                                    <option value="S" <?php if ($selected_shift_filter == 'S') echo 'selected'; ?>>Shift S (Siang - 09:30)</option>
                                    <option value="T" <?php if ($selected_shift_filter == 'T') echo 'selected'; ?>>Shift T (Toko - 09:10)</option>
                                </select>
                            </div>

                            <!-- Live Instant Search -->
                            <div class="col-12 col-md-5">
                                <label for="searchShiftInput" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i> Pencarian Cepat</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                    <input type="text" id="searchShiftInput" class="form-control border-start-0 bg-light rounded-end-3" placeholder="Cari nama karyawan, NIK, atau PIN...">
                                </div>
                            </div>

                            <!-- Reset Button -->
                            <div class="col-12 col-md-2 mt-md-4">
                                <a href="shifting.php" class="btn btn-outline-secondary w-100 rounded-3 fw-bold py-2"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- Main Shift Table Card -->
                <div class="shift-card-main">
                    <form method="post" action="update_shift.php">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-sliders text-primary"></i>
                                Daftar Shifting Karyawan (<span class="text-primary"><?php echo count($karyawan_list); ?> Karyawan</span>)
                            </h5>
                            <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4 py-2 shadow-sm">
                                <i class="fa-solid fa-save me-1.5"></i>Simpan Perubahan
                            </button>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="shiftingTable" style="font-size: 0.88rem;">
                                    <thead class="table-custom-head">
                                        <tr>
                                            <th class="ps-3" width="90">NIK</th>
                                            <th width="90">PIN</th>
                                            <th>Nama Karyawan</th>
                                            <th>Pilihan Shifting Default</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($karyawan_list)): ?>
                                            <tr><td colspan="4" class="text-center p-5 text-muted">Tidak ada data karyawan yang sesuai filter.</td></tr>
                                        <?php endif; ?>

                                        <?php foreach ($karyawan_list as $karyawan): 
                                            $nik = $karyawan['nik'];
                                            $nama = $karyawan['nama'];
                                            $words = explode(' ', trim($nama));
                                            $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                            $curr_shift = $karyawan['shifting'];
                                        ?>
                                        <tr class="shift-row">
                                            <td class="ps-3 fw-semibold text-secondary"><?php echo htmlspecialchars($nik); ?></td>
                                            <td class="fw-mono text-muted"><?php echo htmlspecialchars($karyawan['pin_absen']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="emp-avatar"><?php echo $init; ?></span>
                                                    <span class="fw-bold text-dark" style="text-transform:capitalize;"><?php echo htmlspecialchars($nama); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="hidden" name="nik[]" value="<?php echo htmlspecialchars($nik); ?>">
                                                <input type="hidden" name="nip[]" value="<?php echo htmlspecialchars($karyawan['nip']); ?>">
                                                
                                                <div class="shift-pill-group">
                                                    
                                                    <!-- Shift Pagi (P) -->
                                                    <label class="shift-chip">
                                                        <input type="radio" value="P" name="shift_<?php echo htmlspecialchars($nik); ?>" <?php if ($curr_shift === 'P') echo 'checked'; ?>>
                                                        <span class="shift-chip-label"><i class="fa-solid fa-sun me-1 opacity-75"></i> Pagi (07.00)</span>
                                                    </label>

                                                    <!-- Shift Tengah (M) -->
                                                    <label class="shift-chip">
                                                        <input type="radio" value="M" name="shift_<?php echo htmlspecialchars($nik); ?>" <?php if ($curr_shift === 'M') echo 'checked'; ?>>
                                                        <span class="shift-chip-label"><i class="fa-solid fa-cloud-sun me-1 opacity-75"></i> Tengah (08.30)</span>
                                                    </label>

                                                    <!-- Shift Siang 09.00 (N) -->
                                                    <label class="shift-chip">
                                                        <input type="radio" value="N" name="shift_<?php echo htmlspecialchars($nik); ?>" <?php if ($curr_shift === 'N') echo 'checked'; ?>>
                                                        <span class="shift-chip-label"><i class="fa-solid fa-clock me-1 opacity-75"></i> Siang (09.00)</span>
                                                    </label>

                                                    <!-- Shift Siang 09.30 (S) -->
                                                    <label class="shift-chip">
                                                        <input type="radio" value="S" name="shift_<?php echo htmlspecialchars($nik); ?>" <?php if ($curr_shift === 'S') echo 'checked'; ?>>
                                                        <span class="shift-chip-label"><i class="fa-solid fa-cloud me-1 opacity-75"></i> Siang (09.30)</span>
                                                    </label>

                                                    <!-- Shift Toko 09.10 (T) -->
                                                    <label class="shift-chip">
                                                        <input type="radio" value="T" name="shift_<?php echo htmlspecialchars($nik); ?>" <?php if ($curr_shift === 'T') echo 'checked'; ?>>
                                                        <span class="shift-chip-label"><i class="fa-solid fa-store me-1 opacity-75"></i> Toko (09.10)</span>
                                                    </label>

                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card-footer bg-white p-3 text-end border-top">
                            <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4 py-2 shadow-sm">
                                <i class="fa-solid fa-save me-1.5"></i>Simpan Perubahan Shifting
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Live Instant Table Search
            $('#searchShiftInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#shiftingTable tbody tr.shift-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });
    </script>
</body>
</html>