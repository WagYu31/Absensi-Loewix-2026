<?php
session_start();

// Cek keamanan: Hanya admin dan superadmin yang bisa akses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';

// --- PERUBAHAN: Fungsi diubah menjadi Soft Delete ---
function deleteKaryawan($conn, $nip) {
    date_default_timezone_set('Asia/Jakarta');
    $deleted_at_time = date('Y-m-d H:i:s');

    $query = "UPDATE karyawan SET deleted_at = ?, status_karyawan = 'tidak aktif' WHERE nip = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ss", $deleted_at_time, $nip);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "Data karyawan dengan NIP $nip telah berhasil dinonaktifkan dan diarsipkan.";
            } else {
                $message = "Tidak ada data karyawan yang ditemukan dengan NIP $nip.";
            }
            echo "<script>alert('$message'); window.location.href = 'data-karyawan.php';</script>";
        } else {
            $message = "Terjadi kesalahan saat mengarsipkan data: " . $stmt->error;
            echo "<script>alert('$message'); window.location.href = 'data-karyawan.php';</script>";
        }
        $stmt->close();
    } else {
        $message = "Gagal mempersiapkan query: " . $conn->error;
        echo "<script>alert('$message'); window.location.href = 'data-karyawan.php';</script>";
    }
}

if (isset($_GET['deleteNIP'])) {
    deleteKaryawan($conn, $_GET['deleteNIP']);
    exit();
}

// Fetch all active positions/jabatan for filter dropdown
$jabatanList = [];
$res_jabatan = $conn->query("SELECT DISTINCT jabatan FROM karyawan WHERE deleted_at IS NULL AND jabatan IS NOT NULL AND jabatan != '' ORDER BY jabatan ASC");
if ($res_jabatan) {
    while ($rj = $res_jabatan->fetch_assoc()) {
        $jabatanList[] = $rj['jabatan'];
    }
}

$query = "SELECT nik, nama, jabatan, tanggal_masuk, nomor_handphone, alamat, status_karyawan, nip, gaji_pokok, pas_photo FROM karyawan WHERE deleted_at IS NULL AND nip NOT IN (SELECT nip FROM users WHERE role = 'superadmin') ORDER BY nama ASC";
$result = $conn->query($query);
if (!$result) {
    die("Query gagal dieksekusi: " . $conn->error);
}
$karyawanData = $result->fetch_all(MYSQLI_ASSOC);

$query_check_zero_gaji = "SELECT COUNT(*) AS count FROM karyawan WHERE gaji_pokok = 0 AND nip NOT IN ('001', '70326') AND deleted_at IS NULL";
$result_check_zero_gaji = $conn->query($query_check_zero_gaji);
$zero_gaji_count = 0;
if ($result_check_zero_gaji) {
    $zero_gaji_count = $result_check_zero_gaji->fetch_assoc()['count'];
}

$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan 3D - Gravitti Tech</title>
    
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
            --btn-radius: 14px;
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

        .page-specific-header p {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
        }

        /* 3D Filter Card */
        .filter-box-card {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            padding: 1.25rem 1.5rem !important;
            margin-bottom: 1.5rem !important;
            box-shadow: 
                0 20px 40px -10px rgba(15, 23, 42, 0.08),
                0 10px 20px -10px rgba(15, 23, 42, 0.04),
                inset 0 1px 1px rgba(255, 255, 255, 0.9) !important;
        }

        .filter-box-card label {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            color: #475569 !important;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .filter-box-card .form-control, .filter-box-card .form-select {
            border-radius: 12px !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 0.55rem 0.85rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            background-color: #f8fafc !important;
            transition: all 0.2s ease;
        }

        .filter-box-card .form-control:focus, .filter-box-card .form-select:focus {
            border-color: #3b82f6 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
        }

        /* 3D Main Table Card */
        .main-table-card.card {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 12px 24px -12px rgba(15, 23, 42, 0.08) !important;
            overflow: hidden;
        }

        .main-table-card .card-header {
            background: #ffffff !important;
            padding: 1.25rem 1.5rem !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .main-table-card .card-title {
            font-weight: 800 !important;
            color: #1e293b !important;
            font-size: 1.1rem !important;
        }

        .title-icon {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 0.6rem;
        }

        /* Tactile 3D Buttons */
        .btn-add-karyawan {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            border-radius: 12px !important;
            padding: 8px 18px !important;
            border: none !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-add-karyawan:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.45), 0 4px 0 #1e40af !important;
            color: #ffffff !important;
        }

        .btn-add-karyawan:active {
            transform: translateY(2px);
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3), 0 1px 0 #1e40af !important;
        }

        .btn-action-view {
            background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%) !important;
            color: #ffffff !important;
            border-radius: 10px !important;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none !important;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3), 0 2px 0 #0369a1 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-action-view:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(2, 132, 199, 0.4), 0 3px 0 #0369a1 !important;
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

        /* Custom 3D Table Styling */
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

        .avatar-circle-sm {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .status-pill-badge {
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            display: inline-block;
        }

        .status-pill-badge.aktif {
            background: rgba(16, 185, 129, 0.12) !important;
            color: #059669 !important;
            border: 1px solid rgba(16, 185, 129, 0.25) !important;
        }

        .status-pill-badge.tidak-aktif {
            background: rgba(100, 116, 139, 0.12) !important;
            color: #64748b !important;
            border: 1px solid rgba(100, 116, 139, 0.25) !important;
        }

        /* 3D Glowing Switch Toggle */
        .form-check-input:checked {
            background-color: #10b981 !important;
            border-color: #059669 !important;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.4) !important;
        }

        .table-hover .highlight-gaji-nol td,
        .table-hover .highlight-gaji-nol:hover td {
            background-color: #fffbeb !important;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-id-badge me-2 text-primary-light"></i>Data Karyawan</h1>
                <p class="small mb-0 opacity-80">Kelola & verifikasi seluruh data karyawan yang terdaftar di sistem.</p>
            </div>
        </div>

        <div class="dashboard-content px-0">
            <div class="container-fluid px-lg-4">
                
                <?php if ($zero_gaji_count > 0): ?>
                <div class="alert alert-warning d-flex align-items-center justify-content-between mb-3 shadow-sm border-0 rounded-4" style="background: rgba(254, 243, 199, 0.9); border-left: 4px solid #f59e0b !important;" role="alert">
                    <div class="fw-semibold text-amber-900">
                        <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>
                        Terdapat <strong><?php echo $zero_gaji_count; ?> karyawan</strong> yang belum memiliki gaji pokok.
                    </div>
                    <a href="input-gaji.php" class="btn btn-sm btn-dark fw-bold rounded-3">Input Gaji Sekarang</a>
                </div>
                <?php endif; ?>

                <!-- Multi-Filter Card -->
                <div class="filter-box-card">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <label class="mb-1"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Pencarian Cepat</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="Cari nama, NIK, Jabatan, atau No. HP..." onkeyup="applyFilter()">
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="mb-1"><i class="fa-solid fa-toggle-on me-1 text-primary"></i>Status Karyawan</label>
                            <select id="filterStatus" class="form-select" onchange="applyFilter()">
                                <option value="all">Semua Status (Aktif & Non-Aktif)</option>
                                <option value="aktif">Hanya Aktif</option>
                                <option value="tidak aktif">Hanya Tidak Aktif</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="mb-1"><i class="fa-solid fa-briefcase me-1 text-primary"></i>Jabatan</label>
                            <select id="filterJabatan" class="form-select" onchange="applyFilter()">
                                <option value="all">Semua Jabatan</option>
                                <?php foreach ($jabatanList as $j): ?>
                                    <option value="<?php echo htmlspecialchars(strtolower($j)); ?>"><?php echo htmlspecialchars($j); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="mb-1">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary w-100 fw-bold rounded-3" style="font-size: 0.85rem; padding: 0.55rem 0.85rem;" onclick="resetFilter()"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button>
                        </div>
                    </div>
                </div>

                <div class="card main-table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-users title-icon"></i>Daftar Karyawan Aktif & Non-Aktif</h5>
                        <a href="data-karyawan-baru.php" class="btn btn-add-karyawan"><i class="fa-solid fa-plus me-2"></i>Tambah Karyawan</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle" id="karyawanTable">
                                <thead>
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama Karyawan</th>
                                        <th>Jabatan</th>
                                        <th>Tgl. Masuk</th>
                                        <th>No. Handphone</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($karyawanData)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-4">Belum ada data karyawan.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($karyawanData as $karyawan) :
                                        if($karyawan['nip'] != '001' && $karyawan['nip'] != '70326') :
                                            $highlightClass = ($karyawan['gaji_pokok'] == '0.00' || $karyawan['gaji_pokok'] == 0) ? 'highlight-gaji-nol' : '';
                                    ?>
                                        <tr class="<?php echo $highlightClass; ?> karyawan-row" 
                                            data-name="<?php echo htmlspecialchars(strtolower($karyawan['nama'])); ?>"
                                            data-nik="<?php echo htmlspecialchars(strtolower($karyawan['nik'])); ?>"
                                            data-jabatan="<?php echo htmlspecialchars(strtolower($karyawan['jabatan'])); ?>"
                                            data-phone="<?php echo htmlspecialchars(strtolower($karyawan['nomor_handphone'])); ?>"
                                            data-status="<?php echo htmlspecialchars(strtolower($karyawan['status_karyawan'])); ?>">
                                            <td class="fw-bold text-secondary"><?php echo htmlspecialchars($karyawan['nik']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="../uploads/<?php echo htmlspecialchars($karyawan['pas_photo'] ?: 'default.png'); ?>" class="avatar-circle-sm" onerror="this.onerror=null; this.src='https://via.placeholder.com/40/003c9c/ffffff?Text=<?php echo strtoupper(substr($karyawan['nama'], 0, 1)); ?>';">
                                                    <div>
                                                        <div class="fw-bold text-dark" style="text-transform:capitalize;"><?php echo htmlspecialchars($karyawan['nama']); ?></div>
                                                        <small class="text-muted" style="font-size: 0.75rem;">NIP: <?php echo htmlspecialchars($karyawan['nip']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                 <?php if (!empty($karyawan['jabatan'])): ?>
                                                     <span class="badge bg-light text-dark fw-bold border px-2 py-1"><?php echo htmlspecialchars($karyawan['jabatan']); ?></span>
                                                 <?php else: ?>
                                                     <span class="text-muted small">-</span>
                                                 <?php endif; ?>
                                             </td>
                                             <td class="text-secondary">
                                                 <?php echo (!empty($karyawan['tanggal_masuk']) && $karyawan['tanggal_masuk'] !== '0000-00-00') ? date('d M Y', strtotime($karyawan['tanggal_masuk'])) : '-'; ?>
                                             </td>
                                             <td>
                                                 <?php if (!empty($karyawan['nomor_handphone'])): 
                                                     $nomorHandphone = $karyawan['nomor_handphone'];
                                                     $waLink = 'https://api.whatsapp.com/send?phone=' . (substr($nomorHandphone, 0, 1) === '0' ? '62' . substr($nomorHandphone, 1) : $nomorHandphone);
                                                 ?>
                                                     <a href="<?php echo $waLink; ?>" target="_blank" class="text-decoration-none fw-semibold text-success"><i class="fa-brands fa-whatsapp me-1 fs-6"></i><?php echo htmlspecialchars($nomorHandphone); ?></a>
                                                 <?php else: ?>
                                                     <span class="text-muted small">-</span>
                                                 <?php endif; ?>
                                             </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                                                    <input class="form-check-input my-0" type="checkbox" role="switch" 
                                                           id="switch-status-<?php echo $karyawan['nip']; ?>"
                                                           onchange="updateStatus('<?php echo $karyawan['nip']; ?>', this)" 
                                                           <?php if ($karyawan['status_karyawan'] === 'aktif') echo 'checked'; ?>>
                                                    <label class="form-check-label fw-bold small mb-0 status-label-text" id="label-status-<?php echo $karyawan['nip']; ?>" style="text-transform:capitalize; cursor:pointer;" for="switch-status-<?php echo $karyawan['nip']; ?>">
                                                        <?php echo htmlspecialchars($karyawan['status_karyawan']); ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="view-profile-karyawan.php?nip=<?php echo $karyawan['nip']; ?>" class="btn btn-action-view" title="Lihat Profil">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                    <button onclick="deleteKaryawan('<?php echo $karyawan['nip']; ?>')" class="btn btn-action-delete" title="Hapus Karyawan">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endif;
                                    endforeach; ?>
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
    function applyFilter() {
        const query = $('#searchInput').val().toLowerCase().trim();
        const selectedStatus = $('#filterStatus').val();
        const selectedJabatan = $('#filterJabatan').val();

        $('.karyawan-row').each(function() {
            const name = $(this).attr('data-name') || '';
            const nik = $(this).attr('data-nik') || '';
            const jabatan = $(this).attr('data-jabatan') || '';
            const phone = $(this).attr('data-phone') || '';
            const status = $(this).attr('data-status') || '';

            const matchSearch = query === '' || name.includes(query) || nik.includes(query) || jabatan.includes(query) || phone.includes(query);
            const matchStatus = selectedStatus === 'all' || status === selectedStatus;
            const matchJabatan = selectedJabatan === 'all' || jabatan === selectedJabatan;

            if (matchSearch && matchStatus && matchJabatan) {
                $(this).removeClass('d-none');
            } else {
                $(this).addClass('d-none');
            }
        });
    }

    function resetFilter() {
        $('#searchInput').val('');
        $('#filterStatus').val('all');
        $('#filterJabatan').val('all');
        $('.karyawan-row').removeClass('d-none');
    }

    function deleteKaryawan(nip) {
        if (confirm("Apakah Anda yakin ingin menghapus seluruh data karyawan dengan NIP " + nip + "?\n\nTindakan ini tidak dapat diurungkan!")) {
            window.location.href = "data-karyawan.php?deleteNIP=" + nip;
        }
    }

    function updateStatus(nip, checkbox) {
        const isChecked = checkbox.checked;
        const newStatus = isChecked ? 'aktif' : 'tidak aktif';
        const labelEl = $('#label-status-' + nip);
        const rowEl = $(checkbox).closest('.karyawan-row');
        
        $(checkbox).prop('disabled', true);
        
        $.ajax({
            url: 'update-status-karyawan.php',
            type: 'GET',
            data: { nip: nip, status: newStatus },
            dataType: 'json',
            success: function(res) {
                $(checkbox).prop('disabled', false);
                if (res.success) {
                    labelEl.text(res.status);
                    rowEl.attr('data-status', res.status);
                    applyFilter();
                } else {
                    alert('Gagal mengubah status: ' + res.message);
                    checkbox.checked = !isChecked;
                }
            },
            error: function(xhr, status, error) {
                $(checkbox).prop('disabled', false);
                alert('Terjadi kesalahan koneksi saat mengubah status.');
                checkbox.checked = !isChecked;
            }
        });
    }
    </script>
</body>
</html>