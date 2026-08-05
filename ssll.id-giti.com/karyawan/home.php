<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

$current_page_basename = basename($_SERVER['PHP_SELF']);
$asset_version = time();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan 3D - Gravitti Tech</title>
    <meta name="description" content="Dashboard profesional untuk karyawan Gravitti Tech" />

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

    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            --card-radius-lg: 24px;
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
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

        /* 3D Glassmorphic Cards */
        .card-3d-modern {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 12px 24px -12px rgba(15, 23, 42, 0.08) !important;
            transition: all 0.25s ease !important;
        }

        .profile-img-3d {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            object-fit: cover;
            border: 3.5px solid #ffffff;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
        }

        /* 3D Quick Action Menu Cards */
        .quick-action-grid-3d {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 14px;
        }

        .quick-action-card-3d {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            padding: 1.2rem 0.8rem;
            text-align: center;
            text-decoration: none !important;
            color: #334155 !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.03), 0 3px 0 #cbd5e1;
            transition: all 0.2s ease-out;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .quick-action-card-3d:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.15), 0 4px 0 #3b82f6;
            border-color: #3b82f6;
            color: #2563eb !important;
        }

        .quick-action-icon-3d {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
        }

        .quick-action-card-3d:hover .quick-action-icon-3d {
            transform: scale(1.1) rotate(4deg);
        }

        .qa-icon-profile { background: linear-gradient(135deg, #dbeafe, #eff6ff); color: #2563eb; }
        .qa-icon-absen { background: linear-gradient(135deg, #dcfce7, #f0fdf4); color: #16a34a; }
        .qa-icon-gaji { background: linear-gradient(135deg, #fef3c7, #fffbeb); color: #d97706; }
        .qa-icon-cuti { background: linear-gradient(135deg, #f3e8ff, #faf5ff); color: #9333ea; }
        .qa-icon-kalender { background: linear-gradient(135deg, #ffe4e6, #fff1f2); color: #e11d48; }
        .qa-icon-kinerja { background: linear-gradient(135deg, #ccfbf1, #f0fdfa); color: #0d9488; }
        .qa-icon-help { background: linear-gradient(135deg, #e0f2fe, #f0f9ff); color: #0284c7; }

        /* 3D Button */
        .btn-3d-sm {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 700 !important;
            font-size: 0.8rem !important;
            border-radius: 12px !important;
            padding: 6px 16px !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3), 0 2px 0 #1d4ed8 !important;
            transition: all 0.15s ease !important;
        }

        .btn-3d-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.4), 0 3px 0 #1e40af !important;
            color: #ffffff !important;
        }

        .stat-row-3d {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }

        .stat-row-3d:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-hand me-2 text-warning"></i>Selamat Datang, <?php echo htmlspecialchars(explode(' ', $nama)[0]); ?>!</h1>
                <p class="small mb-0 opacity-80">Semoga harimu produktif dan menyenangkan di Gravitti Technology.</p>
            </div>
        </div>

        <div class="dashboard-content px-0">
            <div class="container-fluid px-lg-4">
                <div class="row">
                    <!-- Profile Card 3D -->
                    <div class="col-xl-5 mb-4">
                        <div class="card card-3d-modern h-100 p-3">
                            <div class="card-body d-flex align-items-center gap-3">
                                <?php
                                $base_upload_path = '../uploads/';
                                $universal_default_image = $base_upload_path . 'default_avatar.png';
                                $image_source_for_profile = '';

                                if (!empty($photo)) {
                                    $image_source_for_profile = htmlspecialchars($base_upload_path . $photo);
                                } else {
                                    $initial = !empty($nama) ? strtoupper(substr($nama, 0, 1)) : 'U';
                                    $image_source_for_profile = 'https://via.placeholder.com/85/2563eb/ffffff?Text=' . $initial;
                                }
                                ?>
                                <div>
                                    <img src="<?php echo $image_source_for_profile; ?>"
                                        alt="Foto Profil" class="profile-img-3d"
                                        onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($universal_default_image); ?>';">
                                </div>
                                <div class="profile-info">
                                    <h5 class="fw-extrabold text-dark mb-1" style="letter-spacing: -0.5px;"><?php echo htmlspecialchars($nama); ?></h5>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-light text-dark border font-mono small">NIK: <?php echo htmlspecialchars($nik); ?></span>
                                        <span class="badge bg-primary-subtle text-primary small fw-bold"><?php echo htmlspecialchars($jabatan); ?></span>
                                    </div>
                                    <a href="profile.php" class="btn btn-3d-sm text-decoration-none">
                                        <i class="fa-solid fa-user-pen me-1"></i> Edit Profil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php include "data.php"; ?>

                    <!-- Stat Cards 3D -->
                    <div class="col-xl-7 mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card card-3d-modern h-100 p-0" style="overflow: hidden;">
                                    <div class="bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                                        <h6 class="fw-bold text-dark mb-0 fs-6">
                                            <i class="fa-solid fa-calendar-days text-primary me-2"></i>Ringkasan Absensi
                                        </h6>
                                        <span class="badge bg-light text-muted border small"><?php echo htmlspecialchars($periode_absensi_display_dash); ?></span>
                                    </div>
                                    <div class="p-3">
                                        <div class="stat-row-3d"><span class="text-secondary">Hari Kerja Efektif:</span> <strong class="text-dark fs-6"><?php echo $total_hari_kerja_dash; ?></strong></div>
                                        <div class="stat-row-3d"><span class="text-secondary">Hadir:</span> <strong class="text-success fs-6"><?php echo $jumlah_hadir_dash; ?></strong></div>
                                        <div class="stat-row-3d"><span class="text-secondary">Cuti (Disetujui):</span> <strong class="text-warning fs-6"><?php echo $jumlah_izin_sakit_dash; ?></strong></div>
                                        <div class="stat-row-3d"><span class="text-secondary">Total Terlambat:</span> <strong class="text-danger fs-6"><?php echo $total_menit_terlambat_dash; ?> <small>menit</small></strong></div>
                                        <a href="<?php echo $link_ke_detail_absen_dash; ?>" class="fw-bold text-primary text-decoration-none small mt-3 d-inline-block">Detail Absensi <i class="fa-solid fa-arrow-right-long ms-1"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-3d-modern h-100 p-0" style="overflow: hidden;">
                                    <div class="bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                                        <h6 class="fw-bold text-dark mb-0 fs-6">
                                            <i class="fa-solid fa-money-check-dollar text-success me-2"></i>Info Gaji Bulan Ini
                                        </h6>
                                    </div>
                                    <div class="p-3">
                                        <div class="stat-row-3d"><span class="text-secondary">Periode:</span> <strong class="text-dark"><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['periode']); ?></strong></div>
                                        <div class="stat-row-3d"><span class="text-secondary">Perkiraan Gaji Bersih:</span> <strong class="text-success fs-6"><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['gaji_bersih_rp']); ?></strong></div>
                                        <div class="stat-row-3d"><span class="text-secondary">Estimasi Tgl. Bayar:</span> <strong class="text-dark"><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['tanggal_bayar']); ?></strong></div>
                                        <a href="<?php echo $link_ke_riwayat_gaji_dash; ?>" class="fw-bold text-success text-decoration-none small mt-3 d-inline-block">Riwayat Gaji <i class="fa-solid fa-arrow-right-long ms-1"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3D Quick Action Menu Grid -->
                <div class="mb-4">
                    <h6 class="fw-extrabold text-dark mb-3"><i class="fa-solid fa-bolt me-2 text-warning"></i>Akses Cepat Menu Karyawan</h6>
                    <div class="quick-action-grid-3d">
                        <a href="profile.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-profile">
                                <i class="fa-solid fa-address-card"></i>
                            </div>
                            <span class="fw-bold small">Profil Saya</span>
                        </a>
                        <a href="absen.php?nik=<?php echo htmlspecialchars($nik); ?>#form-absen" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-absen">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <span class="fw-bold small">Absen Masuk</span>
                        </a>
                        <a href="riwayat-gaji.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-gaji">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <span class="fw-bold small">Lihat Gaji</span>
                        </a>
                        <a href="cuti.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-cuti">
                                <i class="fa-solid fa-person-walking-luggage"></i>
                            </div>
                            <span class="fw-bold small">Ajukan Cuti</span>
                        </a>
                        <a href="kalender_kerja.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-kalender">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <span class="fw-bold small">Kalender Kerja</span>
                        </a>
                        <a href="peringkat-kinerja.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-kinerja">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <span class="fw-bold small">Kinerja Saya</span>
                        </a>
                        <a href="help.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-help">
                                <i class="fa-solid fa-circle-question"></i>
                            </div>
                            <span class="fw-bold small">Bantuan</span>
                        </a>
                    </div>
                </div>

                <?php
                // --- AMBIL DATA PENGUMUMAN DARI DATABASE ---
                $pengumuman_list_db = [];
                $sql_pengumuman = "SELECT id, judul, isi, jenis, created_at, gambar, media 
                   FROM pengumuman 
                   WHERE deleted_at IS NULL 
                   ORDER BY created_at DESC 
                   LIMIT 3";

                $result_pengumuman = $conn->query($sql_pengumuman);
                if ($result_pengumuman && $result_pengumuman->num_rows > 0) {
                    while ($row_pengumuman = $result_pengumuman->fetch_assoc()) {
                        $pengumuman_list_db[] = $row_pengumuman;
                    }
                }
                ?>

                <!-- Pengumuman 3D Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card card-3d-modern p-0" style="overflow: hidden;">
                            <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold text-dark mb-0 fs-6"><i class="fa-solid fa-bullhorn text-danger me-2"></i>Pengumuman Terbaru</h6>
                                <a href="pengumuman.php" class="fw-bold text-primary text-decoration-none small">Lihat Semua <i class="fa-solid fa-angle-right ms-1"></i></a>
                            </div>
                            <div class="p-3">
                                <?php if (!empty($pengumuman_list_db)): ?>
                                    <?php foreach ($pengumuman_list_db as $item_db): ?>
                                        <div class="p-3 mb-2 rounded-4 bg-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <a href="#" class="fw-bold text-dark text-decoration-none" data-bs-toggle="modal" data-bs-target="#announcementDetailModal_<?php echo htmlspecialchars($item_db['id']); ?>">
                                                    <?php echo htmlspecialchars($item_db['judul']); ?>
                                                </a>
                                                <div class="small text-muted mt-1">
                                                    <i class="fa-regular fa-calendar-alt me-1"></i>
                                                    <?php
                                                    try {
                                                        $tanggal_pengumuman = new DateTime($item_db['created_at']);
                                                        echo htmlspecialchars($tanggal_pengumuman->format('d M Y, H:i'));
                                                    } catch (Exception $e) {
                                                        echo htmlspecialchars($item_db['created_at']);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($item_db['jenis'])): ?>
                                                <span class="badge bg-primary-subtle text-primary fw-bold rounded-pill px-3 py-2">
                                                    <?php echo htmlspecialchars($item_db['jenis']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center text-muted my-3">Tidak ada pengumuman terbaru saat ini.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer text-center my-4 text-muted small">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>

            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>