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

$words = explode(' ', trim($nama));
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Gravitti Tech</title>
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

    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0284c7 100%);
            --card-radius-lg: 24px;
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
            --success-3d: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);
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
            padding-bottom: 120px !important;
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

        /* Glassmorphic Cards */
        .card-3d-modern {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04) !important;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        }

        .card-3d-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 35px rgba(37, 99, 235, 0.1) !important;
        }

        /* Avatar Box */
        .avatar-circle-dash {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            object-fit: cover;
            border: 3.5px solid #ffffff;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .btn-3d-edit {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 800 !important;
            font-size: 0.82rem !important;
            border-radius: 12px !important;
            padding: 9px 20px !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3) !important;
            transition: all 0.2s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
        }

        .btn-3d-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4) !important;
            color: #ffffff !important;
        }

        /* Stat Grid */
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
        }

        .stat-mini-card-3d:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.1);
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

        /* Salary Card */
        .salary-card-3d {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #065f46 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.25) !important;
            position: relative;
            overflow: hidden;
        }

        /* Quick Action Grid */
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quick-action-card-3d:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.15);
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
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
        }

        .quick-action-card-3d:hover .quick-action-icon-3d {
            transform: scale(1.08);
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
        <!-- Header Banner -->
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
                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                            <?php if (!empty($photo) && file_exists('../uploads/' . $photo)): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="Foto Profil" class="avatar-circle-dash">
                            <?php else: ?>
                                <div class="avatar-circle-dash"><?php echo $initials; ?></div>
                            <?php endif; ?>
                            
                            <div style="min-width: 0;">
                                <h5 class="fw-extrabold text-dark mb-1 fs-5" style="letter-spacing: -0.5px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                    <?php echo htmlspecialchars($nama); ?>
                                </h5>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="badge bg-light text-dark border font-mono small">NIK: <?php echo htmlspecialchars($nik); ?></span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold small"><?php echo htmlspecialchars($jabatan); ?></span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold small"><i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i>Aktif</span>
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
                                <i class="fa-solid fa-headset"></i>
                            </div>
                            <span class="qa-text-3d">Pusat Bantuan</span>
                        </a>

                        <a href="profile.php" class="quick-action-card-3d" onclick="triggerPWAInstall(); return false;">
                            <div class="quick-action-icon-3d qa-icon-install">
                                <i class="fa-solid fa-mobile-screen-button"></i>
                            </div>
                            <span class="qa-text-3d">Install HP</span>
                        </a>
                    </div>
                </div>

                <!-- 4. Pengumuman Terbaru Card -->
                <div class="card card-3d-modern p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-extrabold text-dark mb-0">
                            <i class="fa-solid fa-bullhorn text-danger me-2"></i>Pengumuman Terbaru
                        </h6>
                        <a href="pengumuman.php" class="small fw-bold text-primary text-decoration-none">
                            Lihat Semua <i class="fa-solid fa-angle-right ms-1"></i>
                        </a>
                    </div>

                    <div class="text-center py-3 text-muted">
                        <i class="fa-solid fa-bell-slash text-light-emphasis fs-3 mb-2 d-block"></i>
                        <p class="mb-0 small">Tidak ada pengumuman terbaru saat ini.</p>
                    </div>
                </div>

                <div class="footer text-center my-4 text-muted small">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function triggerPWAInstall() {
            alert("Aplikasi web siap diinstall di HP Anda. Silakan gunakan menu 'Add to Home Screen' pada browser Anda.");
        }
    </script>
</body>

</html>