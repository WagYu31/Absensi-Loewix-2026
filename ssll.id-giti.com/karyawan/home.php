<?php
session_start();

if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

$current_page_basename = basename($_SERVER['PHP_SELF']);
$asset_version = time();

$words = explode(' ', trim($nama));
$firstName = htmlspecialchars($words[0]);
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// Time-based greeting
$hour = date('H');
if ($hour < 11) {
    $greeting = "Selamat Pagi";
} elseif ($hour < 15) {
    $greeting = "Selamat Siang";
} elseif ($hour < 18) {
    $greeting = "Selamat Sore";
} else {
    $greeting = "Selamat Malam";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Gravitti Tech</title>
    
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
            --bg-canvas: #f8fafc;
            --card-radius: 20px;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --hero-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #0369a1 100%);
            --salary-gradient: linear-gradient(135deg, #064e3b 0%, #047857 60%, #0f766e 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background-color: var(--bg-canvas) !important;
            color: #0f172a;
        }

        .main-content-wrapper {
            background-color: var(--bg-canvas);
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.08) 0px, transparent 50%) !important;
            min-height: 100vh;
            padding-bottom: 110px !important;
        }

        /* Hero Profile Banner Container */
        .hero-banner-container {
            background: var(--hero-gradient);
            border-radius: 28px;
            padding: 2.25rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.3);
            margin-bottom: 2rem;
        }

        .hero-banner-container::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        .avatar-circle-hero {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            object-fit: cover;
            border: 3.5px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .hero-pill-badge {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #f8fafc;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
        }

        /* Modern Executive Card */
        .exec-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--card-radius);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .exec-card:hover {
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }

        .card-header-clean {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .card-title-clean {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Attendance Metrics Grid */
        .metric-item-card {
            background: #ffffff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            padding: 16px;
            transition: all 0.2s ease;
            position: relative;
        }

        .metric-item-card:hover {
            border-color: #e2e8f0;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
        }

        .metric-indicator-top {
            position: absolute;
            top: 0;
            left: 20px;
            right: 20px;
            height: 3px;
            border-radius: 0 0 4px 4px;
        }

        .metric-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
            margin-top: 4px;
            margin-bottom: 2px;
        }

        .metric-lbl {
            font-size: 0.73rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Salary Card Executive */
        .salary-exec-card {
            background: var(--salary-gradient);
            border-radius: var(--card-radius);
            color: #ffffff;
            padding: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px -10px rgba(4, 120, 87, 0.35);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .salary-exec-card::after {
            content: '';
            position: absolute;
            bottom: -40%;
            right: -20%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Quick Action Grid (Desktop vs Mobile Responsive) */
        .qa-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        @media (max-width: 991.98px) {
            .qa-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        .qa-card-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none !important;
            color: #0f172a !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .qa-card-item:hover {
            transform: translateY(-3px);
            border-color: #3b82f6;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.12);
            color: #2563eb !important;
        }

        .qa-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .qa-card-item:hover .qa-icon-wrapper {
            transform: scale(1.08);
        }

        .qa-text-title {
            font-size: 0.88rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .qa-text-sub {
            font-size: 0.73rem;
            color: #64748b;
            font-weight: 500;
        }

        /* Distinct Icon Styles */
        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-green { background: #f0fdf4; color: #16a34a; }
        .icon-amber { background: #fffbeb; color: #d97706; }
        .icon-purple { background: #faf5ff; color: #9333ea; }
        .icon-rose { background: #fff1f2; color: #e11d48; }
        .icon-teal { background: #f0fdfa; color: #0d9488; }
        .icon-sky { background: #f0f9ff; color: #0284c7; }
        .icon-emerald { background: #ecfdf5; color: #059669; }
    </style>
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="container-fluid px-3 px-lg-4 pt-4">

            <!-- 1. HERO PROFILE EXECUTIVE BANNER -->
            <div class="hero-banner-container">
                <div class="row align-items-center g-3">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-3.5">
                            <?php if (!empty($photo) && file_exists('../uploads/' . $photo)): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="Foto Profil" class="avatar-circle-hero">
                            <?php else: ?>
                                <div class="avatar-circle-hero"><?php echo $initials; ?></div>
                            <?php endif; ?>

                            <div>
                                <div class="small text-white-50 font-mono fw-semibold mb-1">
                                    <i class="fa-regular fa-sun me-1 text-warning"></i><?php echo $greeting; ?>,
                                </div>
                                <h2 class="fw-extrabold text-white mb-2" style="font-size: 1.75rem; letter-spacing: -0.5px;">
                                    <?php echo htmlspecialchars($nama); ?>
                                </h2>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="hero-pill-badge"><i class="fa-solid fa-id-card me-1.5 opacity-75"></i>NIK: <?php echo htmlspecialchars($nik); ?></span>
                                    <span class="hero-pill-badge"><i class="fa-solid fa-briefcase me-1.5 opacity-75"></i><?php echo htmlspecialchars($jabatan); ?></span>
                                    <span class="badge bg-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.78rem;">
                                        <i class="fa-solid fa-circle-check me-1"></i>AKTIF
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="absen.php?nik=<?php echo htmlspecialchars($nik); ?>#form-absen" class="btn btn-light rounded-pill px-4 py-2.5 fw-extrabold text-primary shadow-sm">
                            <i class="fa-solid fa-camera me-2"></i>Absen Sekarang
                        </a>
                    </div>
                </div>
            </div>

            <?php include "data.php"; ?>

            <!-- 2. MAIN DASHBOARD METRICS & SALARY (ROW 1) -->
            <div class="row g-4 mb-4">
                <!-- Attendance Metrics (Col 7) -->
                <div class="col-lg-7">
                    <div class="exec-card h-100">
                        <div class="card-header-clean">
                            <h6 class="card-title-clean">
                                <i class="fa-solid fa-chart-pie text-primary"></i>Ringkasan Kehadiran
                            </h6>
                            <span class="badge bg-slate-100 text-secondary border fw-bold rounded-pill px-3 py-1" style="font-size: 0.75rem;">
                                <?php echo htmlspecialchars($periode_absensi_display_dash); ?>
                            </span>
                        </div>

                        <div class="p-4">
                            <div class="row g-3 mb-3">
                                <div class="col-6 col-sm-3">
                                    <div class="metric-item-card">
                                        <div class="metric-indicator-top bg-primary"></div>
                                        <div class="metric-lbl">Hari Kerja</div>
                                        <div class="metric-val text-primary"><?php echo $total_hari_kerja_dash; ?></div>
                                        <span class="small text-muted" style="font-size: 0.7rem;">Efektif</span>
                                    </div>
                                </div>

                                <div class="col-6 col-sm-3">
                                    <div class="metric-item-card">
                                        <div class="metric-indicator-top bg-success"></div>
                                        <div class="metric-lbl">Kehadiran</div>
                                        <div class="metric-val text-success"><?php echo $jumlah_hadir_dash; ?></div>
                                        <span class="small text-muted" style="font-size: 0.7rem;">Total Hadir</span>
                                    </div>
                                </div>

                                <div class="col-6 col-sm-3">
                                    <div class="metric-item-card">
                                        <div class="metric-indicator-top bg-warning"></div>
                                        <div class="metric-lbl">Cuti / Izin</div>
                                        <div class="metric-val text-warning"><?php echo $jumlah_izin_sakit_dash; ?></div>
                                        <span class="small text-muted" style="font-size: 0.7rem;">Disetujui</span>
                                    </div>
                                </div>

                                <div class="col-6 col-sm-3">
                                    <div class="metric-item-card">
                                        <div class="metric-indicator-top bg-danger"></div>
                                        <div class="metric-lbl">Terlambat</div>
                                        <div class="metric-val text-danger"><?php echo $total_menit_terlambat_dash; ?></div>
                                        <span class="small text-muted" style="font-size: 0.7rem;">Menit akumulasi</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="<?php echo $link_ke_detail_absen_dash; ?>" class="fw-bold text-primary text-decoration-none small">
                                    Lihat Histori Absen Lengkap <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Executive Hero Card (Col 5) -->
                <div class="col-lg-5">
                    <div class="salary-exec-card h-100">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge bg-white text-emerald-900 fw-bold rounded-pill px-3 py-1.5 small shadow-sm">
                                    <i class="fa-solid fa-wallet me-1.5 text-success"></i>Info Gaji Bulan Ini
                                </span>
                                <span class="small text-white-50 font-mono fw-semibold"><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['periode']); ?></span>
                            </div>

                            <div class="my-3">
                                <div class="small text-emerald-200 fw-bold text-uppercase letter-spacing-1 mb-1" style="font-size: 0.75rem;">Estimasi Gaji Bersih</div>
                                <div class="fw-extrabold text-white display-6" style="font-size: 2.1rem; letter-spacing: -1px;">
                                    <?php echo htmlspecialchars($info_gaji_bulan_ini_dash['gaji_bersih_rp']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top border-white border-opacity-20 d-flex align-items-center justify-content-between mt-3">
                            <div>
                                <div class="small text-white-50" style="font-size: 0.73rem;">Estimasi Tgl. Bayar</div>
                                <div class="fw-bold text-white small"><i class="fa-regular fa-calendar-check me-1.5 text-emerald-300"></i><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['tanggal_bayar']); ?></div>
                            </div>
                            <a href="<?php echo $link_ke_riwayat_gaji_dash; ?>" class="btn btn-light rounded-pill px-3.5 py-1.5 fw-bold text-success shadow-sm btn-sm">
                                Slip Gaji <i class="fa-solid fa-chevron-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. QUICK ACTION MENU GRID (DESKTOP 4-COL vs MOBILE 2-COL) -->
            <div class="mb-4">
                <h6 class="fw-extrabold text-dark mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i>Akses Cepat Menu Karyawan</h6>
                <div class="qa-grid">
                    
                    <a href="profile.php" class="qa-card-item">
                        <div class="qa-icon-wrapper icon-blue">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Profil Saya</div>
                            <div class="qa-text-sub">Data & Kepegawaian</div>
                        </div>
                    </a>

                    <a href="absen.php?nik=<?php echo htmlspecialchars($nik); ?>#form-absen" class="qa-card-item">
                        <div class="qa-icon-wrapper icon-green">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Absen Masuk</div>
                            <div class="qa-text-sub">Kamera Presensi</div>
                        </div>
                    </a>

                    <a href="riwayat-gaji.php" class="qa-card-item">
                        <div class="qa-icon-wrapper icon-amber">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Riwayat Gaji</div>
                            <div class="qa-text-sub">Slip & Rincian</div>
                        </div>
                    </a>

                    <a href="cuti.php" class="qa-card-item">
                        <div class="qa-icon-wrapper icon-purple">
                            <i class="fa-solid fa-calendar-minus"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Ajukan Cuti</div>
                            <div class="qa-text-sub">Izin & Sakit</div>
                        </div>
                    </a>

                    <a href="kalender_kerja.php" class="qa-card-item">
                        <div class="qa-icon-wrapper icon-rose">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Kalender Kerja</div>
                            <div class="qa-text-sub">Libur & Agenda</div>
                        </div>
                    </a>

                    <a href="peringkat-kinerja.php" class="qa-card-item">
                        <div class="qa-icon-wrapper icon-teal">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Kinerja Saya</div>
                            <div class="qa-text-sub">Performa & Skor</div>
                        </div>
                    </a>

                    <a href="help.php" class="qa-card-item">
                        <div class="qa-icon-wrapper icon-sky">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Pusat Bantuan</div>
                            <div class="qa-text-sub">Panduan & CS</div>
                        </div>
                    </a>

                    <a href="profile.php" class="qa-card-item" onclick="triggerPWAInstall(); return false;">
                        <div class="qa-icon-wrapper icon-emerald">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <div>
                            <div class="qa-text-title">Install HP</div>
                            <div class="qa-text-sub">Aplikasi PWA</div>
                        </div>
                    </a>

                </div>
            </div>

            <!-- 4. PENGUMUMAN WIDGET -->
            <div class="exec-card p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h6 class="fw-extrabold text-dark mb-0">
                        <i class="fa-solid fa-bullhorn text-danger me-2"></i>Pengumuman Terbaru
                    </h6>
                    <a href="pengumuman.php" class="small fw-bold text-primary text-decoration-none">
                        Lihat Semua <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>

                <div class="text-center py-3 text-muted">
                    <i class="fa-solid fa-bell-slash text-slate-300 fs-3 mb-2 d-block"></i>
                    <p class="mb-0 small fw-medium">Tidak ada pengumuman terbaru saat ini.</p>
                </div>
            </div>

            <div class="footer text-center my-4 text-muted small">
                Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                <br><small>Version 1.1.0</small>
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