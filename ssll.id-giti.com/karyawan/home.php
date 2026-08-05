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

    <!-- Google Fonts: Plus Jakarta Sans -->
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
            --success-3d: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);
            --warning-3d: linear-gradient(135deg, #d97706 0%, #f59e0b 50%, #b45309 100%);
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
            padding-bottom: 120px !important; /* Prevents bottom-nav overlap */
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

        /* 3D Glassmorphic Containers */
        .card-3d-modern {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 12px 24px -12px rgba(15, 23, 42, 0.08) !important;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        }

        .card-3d-modern:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 30px 60px -15px rgba(37, 99, 235, 0.18),
                0 15px 30px -10px rgba(15, 23, 42, 0.1) !important;
        }

        /* Profile Hero Styling */
        .profile-img-3d {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3.5px solid #ffffff;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-3d-edit {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 800 !important;
            font-size: 0.82rem !important;
            border-radius: 14px !important;
            padding: 8px 18px !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
            transition: all 0.2s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
        }

        .btn-3d-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.45), 0 4px 0 #1e40af !important;
            color: #ffffff !important;
        }

        /* 3D Stat Mini Cards Grid */
        .stat-grid-3d {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .stat-mini-card-3d {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            padding: 14px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-mini-card-3d:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.12);
        }

        .stat-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .stat-val-3d {
            font-size: 1.4rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .stat-lbl-3d {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Salary Card Glow */
        .salary-card-3d {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #065f46 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.3) !important;
            position: relative;
            overflow: hidden;
        }

        .salary-card-3d::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.25) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Quick Action Grid 3D */
        .quick-action-grid-3d {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 768px) {
            .quick-action-grid-3d {
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
            }
        }

        .quick-action-card-3d {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            padding: 1rem 0.5rem;
            text-align: center;
            text-decoration: none !important;
            color: #334155 !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.03), 0 3px 0 #cbd5e1;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quick-action-card-3d:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18), 0 4px 0 #3b82f6;
            border-color: #3b82f6;
            color: #2563eb !important;
        }

        .quick-action-icon-3d {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
        }

        .quick-action-card-3d:hover .quick-action-icon-3d {
            transform: scale(1.1) rotate(5deg);
        }

        .qa-text-3d {
            font-size: 0.75rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .qa-icon-profile { background: linear-gradient(135deg, #dbeafe, #eff6ff); color: #2563eb; }
        .qa-icon-absen { background: linear-gradient(135deg, #dcfce7, #f0fdf4); color: #16a34a; }
        .qa-icon-gaji { background: linear-gradient(135deg, #fef3c7, #fffbeb); color: #d97706; }
        .qa-icon-cuti { background: linear-gradient(135deg, #f3e8ff, #faf5ff); color: #9333ea; }
        .qa-icon-kalender { background: linear-gradient(135deg, #ffe4e6, #fff1f2); color: #e11d48; }
        .qa-icon-kinerja { background: linear-gradient(135deg, #ccfbf1, #f0fdfa); color: #0d9488; }
        .qa-icon-help { background: linear-gradient(135deg, #e0f2fe, #f0f9ff); color: #0284c7; }
        .qa-icon-install { background: linear-gradient(135deg, #d1fae5, #ecfdf5); color: #059669; }
    </style>
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <!-- 3D Header Banner -->
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-hand me-2 text-warning"></i>Selamat Datang, <?php echo htmlspecialchars(explode(' ', $nama)[0]); ?>!</h1>
                <p class="small mb-0 opacity-80">Semoga harimu produktif dan menyenangkan di Gravitti Technology.</p>
            </div>
        </div>

        <div class="dashboard-content px-0 pt-2">
            <div class="container-fluid px-lg-4">

                <!-- 1. Profile Hero Executive Card -->
                <div class="card card-3d-modern p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
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
                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                            <img src="<?php echo $image_source_for_profile; ?>"
                                alt="Foto Profil" class="profile-img-3d"
                                onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($universal_default_image); ?>';">
                            <div style="min-width: 0;">
                                <h5 class="fw-extrabold text-dark mb-1 fs-5" style="letter-spacing: -0.5px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                    <?php echo htmlspecialchars($nama); ?>
                                </h5>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="badge bg-light text-dark border font-mono small">NIK: <?php echo htmlspecialchars($nik); ?></span>
                                    <span class="badge bg-primary-subtle text-primary fw-bold small"><?php echo htmlspecialchars($jabatan); ?></span>
                                    <span class="badge bg-success-subtle text-success fw-bold small"><i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i>Aktif</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="profile.php" class="btn btn-3d-edit">
                                <i class="fa-solid fa-user-gear"></i>Edit Profil
                            </a>
                        </div>
                    </div>
                </div>

                <?php include "data.php"; ?>

                <!-- 2. Ringkasan Absensi Grid & Info Gaji -->
                <div class="row g-3 mb-4">
                    <!-- Ringkasan Absensi 4-Grid Cards -->
                    <div class="col-lg-7">
                        <div class="card card-3d-modern h-100 p-3">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h6 class="fw-extrabold text-dark mb-0 fs-6">
                                    <i class="fa-solid fa-chart-line text-primary me-2"></i>Ringkasan Absensi Anda
                                </h6>
                                <span class="badge bg-light text-secondary border fw-bold rounded-pill px-3 py-1"><?php echo htmlspecialchars($periode_absensi_display_dash); ?></span>
                            </div>

                            <div class="stat-grid-3d mb-3">
                                <div class="stat-mini-card-3d">
                                    <div class="stat-icon-badge bg-primary-subtle text-primary"><i class="fa-solid fa-calendar-check"></i></div>
                                    <div class="stat-val-3d text-dark"><?php echo $total_hari_kerja_dash; ?> <small class="fs-7 fw-normal text-muted">Hari</small></div>
                                    <div class="stat-lbl-3d">Hari Kerja Efektif</div>
                                </div>

                                <div class="stat-mini-card-3d">
                                    <div class="stat-icon-badge bg-success-subtle text-success"><i class="fa-solid fa-user-check"></i></div>
                                    <div class="stat-val-3d text-success"><?php echo $jumlah_hadir_dash; ?> <small class="fs-7 fw-normal text-muted">Hari</small></div>
                                    <div class="stat-lbl-3d">Total Hadir</div>
                                </div>

                                <div class="stat-mini-card-3d">
                                    <div class="stat-icon-badge bg-warning-subtle text-warning"><i class="fa-solid fa-umbrella-beach"></i></div>
                                    <div class="stat-val-3d text-warning"><?php echo $jumlah_izin_sakit_dash; ?> <small class="fs-7 fw-normal text-muted">Hari</small></div>
                                    <div class="stat-lbl-3d">Cuti (Disetujui)</div>
                                </div>

                                <div class="stat-mini-card-3d">
                                    <div class="stat-icon-badge bg-danger-subtle text-danger"><i class="fa-solid fa-clock"></i></div>
                                    <div class="stat-val-3d text-danger"><?php echo $total_menit_terlambat_dash; ?> <small class="fs-7 fw-normal text-muted">m</small></div>
                                    <div class="stat-lbl-3d">Total Terlambat</div>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="<?php echo $link_ke_detail_absen_dash; ?>" class="fw-extrabold text-primary text-decoration-none small">
                                    Lihat Detail Absensi <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Info Gaji Bulan Ini 3D Hero Card -->
                    <div class="col-lg-5">
                        <div class="card card-3d-modern salary-card-3d h-100 p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge bg-white text-dark fw-bold rounded-pill px-3 py-1 small opacity-90"><i class="fa-solid fa-wallet me-1 text-success"></i>Info Gaji Bulan Ini</span>
                                    <span class="small text-white-50 fw-semibold"><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['periode']); ?></span>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-white-50 fw-semibold text-uppercase letter-spacing-1 mb-1">Perkiraan Gaji Bersih</div>
                                    <div class="fw-extrabold text-white display-6" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                                        <?php echo htmlspecialchars($info_gaji_bulan_ini_dash['gaji_bersih_rp']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-top border-white border-opacity-25 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="small text-white-50" style="font-size: 0.75rem;">Estimasi Tgl. Bayar</div>
                                    <div class="fw-bold text-white small"><i class="fa-regular fa-calendar-check me-1"></i><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['tanggal_bayar']); ?></div>
                                </div>
                                <a href="<?php echo $link_ke_riwayat_gaji_dash; ?>" class="btn btn-light btn-sm rounded-pill px-3 py-1.5 fw-bold text-success shadow-sm">
                                    Riwayat Gaji <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Akses Cepat Menu Karyawan Grid -->
                <div class="mb-4">
                    <h6 class="fw-extrabold text-dark mb-3"><i class="fa-solid fa-bolt me-2 text-warning"></i>Akses Cepat Menu Karyawan</h6>
                    <div class="quick-action-grid-3d">
                        <a href="profile.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-profile">
                                <i class="fa-solid fa-address-card"></i>
                            </div>
                            <span class="qa-text-3d">Profil Saya</span>
                        </a>

                        <a href="absen.php?nik=<?php echo htmlspecialchars($nik); ?>#form-absen" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-absen">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <span class="qa-text-3d">Absen Masuk</span>
                        </a>

                        <a href="riwayat-gaji.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-gaji">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <span class="qa-text-3d">Lihat Gaji</span>
                        </a>

                        <a href="cuti.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-cuti">
                                <i class="fa-solid fa-person-walking-luggage"></i>
                            </div>
                            <span class="qa-text-3d">Ajukan Cuti</span>
                        </a>

                        <a href="kalender_kerja.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-kalender">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <span class="qa-text-3d">Kalender Kerja</span>
                        </a>

                        <a href="peringkat-kinerja.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-kinerja">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <span class="qa-text-3d">Kinerja Saya</span>
                        </a>

                        <a href="help.php" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-help">
                                <i class="fa-solid fa-circle-question"></i>
                            </div>
                            <span class="qa-text-3d">Pusat Bantuan</span>
                        </a>

                        <a href="javascript:void(0)" onclick="window.triggerPWAInstall && window.triggerPWAInstall()" class="quick-action-card-3d">
                            <div class="quick-action-icon-3d qa-icon-install">
                                <i class="fa-solid fa-mobile-screen-button"></i>
                            </div>
                            <span class="qa-text-3d text-success">Install HP</span>
                        </a>
                    </div>
                </div>

                <!-- 4. Pengumuman Terbaru 3D Card -->
                <?php
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

                <div class="card card-3d-modern p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-extrabold text-dark mb-0 fs-6"><i class="fa-solid fa-bullhorn text-danger me-2"></i>Pengumuman Terbaru</h6>
                        <a href="pengumuman.php" class="fw-bold text-primary text-decoration-none small">Lihat Semua <i class="fa-solid fa-angle-right ms-1"></i></a>
                    </div>

                    <div>
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

                <div class="footer text-center my-4 text-muted small">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.<br>
                    <small>Version 1.1.0</small>
                </div>

            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>