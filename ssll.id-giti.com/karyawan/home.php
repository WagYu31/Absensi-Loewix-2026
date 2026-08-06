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
            --salary-gradient: linear-gradient(135deg, #064e3b 0%, #022c22 60%, #0f172a 100%);
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

        /* Executive Hero Card Styling */
        .hero-banner-container {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f2b48 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 1.75rem 2rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px -10px rgba(15, 23, 42, 0.25);
            margin-bottom: 1.75rem;
        }

        .hero-banner-container::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .avatar-circle-hero {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-indicator-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            background: #10b981;
            border: 2.5px solid #0f172a;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);
        }

        .greeting-subtitle {
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 2px;
        }

        .hero-user-name {
            font-size: 1.45rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .hero-meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .meta-chip {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #e2e8f0;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .meta-chip-status {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
        }

        .hero-action-wrapper {
            flex-shrink: 0;
        }

        @media (max-width: 767.98px) {
            .hero-action-wrapper {
                width: 100%;
                margin-top: 6px;
            }
        }

        .btn-absen-hero {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.9rem !important;
            padding: 12px 26px !important;
            border-radius: 14px !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35) !important;
            transition: all 0.2s ease !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none !important;
        }

        @media (max-width: 767.98px) {
            .btn-absen-hero {
                width: 100%;
                padding: 13px 20px !important;
            }
        }

        .btn-absen-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.5) !important;
            color: #ffffff !important;
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

        /* Executive Metric Box Styles */
        .metric-item-card {
            border-radius: 18px;
            transition: all 0.25s ease;
            position: relative;
        }

        .metric-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.06);
        }

        .metric-box-blue {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%) !important;
            border: 1.5px solid #bfdbfe !important;
        }

        .metric-box-green {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%) !important;
            border: 1.5px solid #bbf7d0 !important;
        }

        .metric-box-amber {
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%) !important;
            border: 1.5px solid #fde68a !important;
        }

        .metric-box-rose {
            background: linear-gradient(135deg, #fff1f2 0%, #ffffff 100%) !important;
            border: 1.5px solid #fecdd3 !important;
        }

        .metric-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
        }

        .metric-lbl {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Salary Card Executive Masterpiece */
        .salary-exec-card {
            background: var(--salary-gradient) !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
            border-radius: var(--card-radius) !important;
            color: #ffffff !important;
            padding: 24px !important;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px -10px rgba(4, 120, 87, 0.35) !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .salary-exec-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.25) 0%, transparent 70%);
            pointer-events: none;
        }

        .salary-chip-icon {
            width: 40px;
            height: 30px;
            border-radius: 7px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .salary-badge-pill {
            background: rgba(255, 255, 255, 0.12) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.78rem !important;
            padding: 6px 14px !important;
            border-radius: 50px !important;
            white-space: nowrap !important;
        }

        .salary-breakdown-row {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 12px 16px;
            margin-top: 14px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-slip-gaji {
            background: #ffffff !important;
            color: #047857 !important;
            font-weight: 800 !important;
            font-size: 0.82rem !important;
            padding: 8px 18px !important;
            border-radius: 50px !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-slip-gaji:hover {
            background: #f0fdf4 !important;
            color: #064e3b !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25) !important;
        }

        .btn-toggle-salary-eye {
            background: rgba(255, 255, 255, 0.12) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            outline: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .btn-toggle-salary-eye:hover {
            background: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            transform: scale(1.08);
        }

        /* Quick Action Grid Responsive Breakpoints */
        .qa-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        @media (max-width: 575.98px) {
            .qa-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }

            .hero-banner-container {
                padding: 1.25rem 1.25rem !important;
                border-radius: 20px !important;
            }

            .avatar-circle-hero {
                width: 60px !important;
                height: 60px !important;
                font-size: 1.35rem !important;
            }

            .hero-user-name {
                font-size: 1.25rem !important;
            }

            .salary-exec-card {
                padding: 18px 16px !important;
                border-radius: 20px !important;
            }

            .salary-badge-pill {
                font-size: 0.7rem !important;
                padding: 4px 10px !important;
            }

            .salary-chip-icon {
                width: 32px !important;
                height: 26px !important;
                font-size: 0.8rem !important;
            }

            .btn-slip-gaji {
                font-size: 0.75rem !important;
                padding: 6px 14px !important;
            }
        }

        @media (min-width: 576px) and (max-width: 991.98px) {
            .qa-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 12px !important;
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
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                        <div class="position-relative flex-shrink-0">
                            <?php if (!empty($photo) && file_exists('../uploads/' . $photo)): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="Foto Profil" class="avatar-circle-hero">
                            <?php else: ?>
                                <div class="avatar-circle-hero"><?php echo $initials; ?></div>
                            <?php endif; ?>
                            <span class="status-indicator-dot" title="Status Aktif"></span>
                        </div>

                        <div style="min-width: 0;">
                            <div class="greeting-subtitle">
                                <i class="fa-regular fa-sun text-warning me-1.5"></i><?php echo $greeting; ?>,
                            </div>
                            <h3 class="hero-user-name">
                                <?php echo htmlspecialchars($nama); ?>
                            </h3>
                            <div class="hero-meta-row">
                                <span class="meta-chip"><i class="fa-solid fa-address-card opacity-75"></i>NIK: <?php echo htmlspecialchars($nik); ?></span>
                                <span class="meta-chip"><i class="fa-solid fa-briefcase opacity-75"></i><?php echo htmlspecialchars($jabatan); ?></span>
                                <span class="meta-chip-status"><i class="fa-solid fa-circle-check me-1"></i>AKTIF</span>
                            </div>
                        </div>
                    </div>

                    <div class="hero-action-wrapper">
                        <a href="absen.php?nik=<?php echo htmlspecialchars($nik); ?>#form-absen" class="btn-absen-hero">
                            <i class="fa-solid fa-camera"></i>
                            <span>Absen Masuk Sekarang</span>
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
                        <div class="card-header-clean cursor-pointer collapsed" data-bs-toggle="collapse" data-bs-target="#collapseRingkasanKehadiran" aria-expanded="false" aria-controls="collapseRingkasanKehadiran" style="cursor: pointer; user-select: none;" title="Klik untuk Buka/Tutup Ringkasan Kehadiran">
                            <h6 class="card-title-clean">
                                <i class="fa-solid fa-chart-pie text-primary"></i>
                                <span>Ringkasan Kehadiran</span>
                                <i class="fa-solid fa-chevron-down text-muted small ms-1" id="iconChevronRingkasan" style="transition: transform 0.25s ease;"></i>
                            </h6>
                            <span class="badge bg-slate-100 text-secondary border fw-bold rounded-pill px-3 py-1" style="font-size: 0.75rem;">
                                <?php echo htmlspecialchars($periode_absensi_display_dash); ?>
                            </span>
                        </div>

                        <div class="collapse" id="collapseRingkasanKehadiran">
                            <div class="p-4">
                                <div class="row g-3 mb-3">
                                    
                                    <div class="col-6 col-sm-3">
                                        <div class="metric-item-card metric-box-blue p-3 h-100 d-flex flex-column justify-content-between">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="metric-lbl text-primary">Hari Kerja</div>
                                                <div class="metric-icon-box bg-primary text-white"><i class="fa-solid fa-calendar-check"></i></div>
                                            </div>
                                            <div>
                                                <div class="text-primary mb-1" style="font-size: 1.75rem; font-weight: 800;"><?php echo $total_hari_kerja_dash; ?> <span class="fs-6 text-muted fw-normal">Hari</span></div>
                                                <span class="badge bg-primary-subtle text-primary fw-bold rounded-pill px-2.5 py-1" style="font-size: 0.68rem;">Efektif Bulan Ini</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6 col-sm-3">
                                        <div class="metric-item-card metric-box-green p-3 h-100 d-flex flex-column justify-content-between">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="metric-lbl text-success">Kehadiran</div>
                                                <div class="metric-icon-box bg-success text-white"><i class="fa-solid fa-user-check"></i></div>
                                            </div>
                                            <div>
                                                <div class="text-success mb-1" style="font-size: 1.75rem; font-weight: 800;"><?php echo $jumlah_hadir_dash; ?> <span class="fs-6 text-muted fw-normal">Hari</span></div>
                                                <span class="badge bg-success-subtle text-success fw-bold rounded-pill px-2.5 py-1" style="font-size: 0.68rem;">Total Hadir</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6 col-sm-3">
                                        <div class="metric-item-card metric-box-amber p-3 h-100 d-flex flex-column justify-content-between">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="metric-lbl text-warning" style="color: #b45309;">Cuti / Izin</div>
                                                <div class="metric-icon-box bg-warning text-white"><i class="fa-solid fa-umbrella-beach"></i></div>
                                            </div>
                                            <div>
                                                <div class="text-warning mb-1" style="font-size: 1.75rem; font-weight: 800;"><?php echo $jumlah_izin_sakit_dash; ?> <span class="fs-6 text-muted fw-normal">Hari</span></div>
                                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold rounded-pill px-2.5 py-1" style="font-size: 0.68rem;">Izin Disetujui</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6 col-sm-3">
                                        <div class="metric-item-card metric-box-rose p-3 h-100 d-flex flex-column justify-content-between">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="metric-lbl text-danger">Terlambat</div>
                                                <div class="metric-icon-box bg-danger text-white"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                            </div>
                                            <div>
                                                <div class="text-danger mb-1" style="font-size: 1.75rem; font-weight: 800;"><?php echo $total_menit_terlambat_dash; ?> <span class="fs-6 text-muted fw-normal">Mnt</span></div>
                                                <span class="badge bg-danger-subtle text-danger fw-bold rounded-pill px-2.5 py-1" style="font-size: 0.68rem;">Akumulasi Menit</span>
                                            </div>
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
                </div>

                <!-- Salary Executive Hero Card (Col 5) -->
                <div class="col-lg-5">
                    <div class="salary-exec-card h-100">
                        <div>
                            <!-- Top Card Header Row -->
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="salary-chip-icon"><i class="fa-solid fa-credit-card"></i></div>
                                    <span class="salary-badge-pill"><i class="fa-solid fa-wallet text-warning me-1.5"></i>Info Gaji Bulan Ini</span>
                                </div>
                                <span class="small text-white-50 font-mono fw-bold"><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['periode']); ?></span>
                            </div>

                            <!-- Net Salary Amount -->
                            <div class="my-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="small text-emerald-200 fw-bold text-uppercase letter-spacing-1" style="font-size: 0.75rem;">Estimasi Gaji Bersih (Take Home Pay)</div>
                                    <button type="button" class="btn-toggle-salary-eye" id="toggleSalaryEye" title="Sembunyikan / Tampilkan Nominal Gaji">
                                        <i class="fa-solid fa-eye" id="salaryEyeIcon"></i>
                                    </button>
                                </div>
                                <div class="fw-extrabold text-white" id="salaryDisplayAmount" style="font-size: 2.15rem; letter-spacing: -1px; text-shadow: 0 4px 15px rgba(0,0,0,0.3);" data-real-amount="<?php echo htmlspecialchars($info_gaji_bulan_ini_dash['gaji_bersih_rp']); ?>">
                                    <?php echo htmlspecialchars($info_gaji_bulan_ini_dash['gaji_bersih_rp']); ?>
                                </div>
                            </div>

                            <!-- Salary Details Mini Breakdown -->
                            <div class="salary-breakdown-row">
                                <div>
                                    <div class="small text-white-50 fw-semibold" style="font-size: 0.7rem;">STATUS GAJI</div>
                                    <div class="fw-bold text-emerald-300 small"><i class="fa-solid fa-circle-notch fa-spin me-1"></i>Proses Akumulasi</div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-white-50 fw-semibold" style="font-size: 0.7rem;">GAJI POKOK</div>
                                    <div class="fw-bold text-white small" id="salaryPokokAmount" data-real-amount="Rp <?php echo number_format($kar['gaji_pokok'] ?? 5500000, 0, ',', '.'); ?>">Rp <?php echo number_format($kar['gaji_pokok'] ?? 5500000, 0, ',', '.'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Payment Date & CTA -->
                        <div class="pt-2 border-top border-white border-opacity-20 d-flex align-items-center justify-content-between mt-2">
                            <div>
                                <div class="small text-white-50" style="font-size: 0.72rem;">Estimasi Tanggal Bayar</div>
                                <div class="fw-bold text-white small"><i class="fa-regular fa-calendar-check me-1.5 text-emerald-300"></i><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['tanggal_bayar']); ?></div>
                            </div>
                            <a href="<?php echo $link_ke_riwayat_gaji_dash; ?>" class="btn-slip-gaji">
                                <span>Detail Slip Gaji</span>
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. QUICK ACTION MENU GRID -->
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

        document.addEventListener("DOMContentLoaded", function() {
            const eyeBtn = document.getElementById("toggleSalaryEye");
            const eyeIcon = document.getElementById("salaryEyeIcon");
            const salaryDisplay = document.getElementById("salaryDisplayAmount");
            const salaryPokok = document.getElementById("salaryPokokAmount");

            if (eyeBtn && salaryDisplay) {
                let isHidden = localStorage.getItem("gravitti_salary_hidden") === "true";

                function updateSalaryVisibility() {
                    if (isHidden) {
                        salaryDisplay.textContent = "Rp •••••••••";
                        if (salaryPokok) salaryPokok.textContent = "Rp •••••••••";
                        eyeIcon.className = "fa-solid fa-eye-slash text-warning";
                        eyeBtn.setAttribute("title", "Tampilkan Nominal Gaji");
                    } else {
                        salaryDisplay.textContent = salaryDisplay.getAttribute("data-real-amount");
                        if (salaryPokok) salaryPokok.textContent = salaryPokok.getAttribute("data-real-amount");
                        eyeIcon.className = "fa-solid fa-eye";
                        eyeBtn.setAttribute("title", "Sembunyikan Nominal Gaji");
                    }
                }

                updateSalaryVisibility();

                eyeBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    isHidden = !isHidden;
                    localStorage.setItem("gravitti_salary_hidden", isHidden);
                    updateSalaryVisibility();
                });
            }
        });
    </script>
</body>

</html>