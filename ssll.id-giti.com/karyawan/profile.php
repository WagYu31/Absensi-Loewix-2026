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
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Gravitti Tech</title>
    
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

        /* Taste-Driven Modern Executive Profile Styles */
        .dashboard-content-profile {
            padding-top: 85px !important; /* Prevents top header navbar overlap on mobile */
            padding-bottom: 100px !important; /* Clears floating bottom navbar on mobile */
        }

        @media (min-width: 992px) {
            .dashboard-content-profile {
                padding-top: 24px !important;
            }
        }

        .profile-hero-card {
            background: #ffffff !important;
            border-radius: 22px !important;
            padding: 1.5rem 1.2rem 1.35rem 1.2rem !important;
            color: #0f172a;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 32px -4px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.03) !important;
            border: 1px solid #e2e8f0 !important;
            margin-bottom: 1rem !important;
        }

        .profile-hero-avatar-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-bottom: 0.85rem !important;
        }

        .profile-hero-avatar {
            width: 90px !important;
            height: 90px !important;
            border-radius: 50%;
            object-fit: cover;
            border: 3.5px solid #ffffff !important;
            box-shadow: 0 10px 24px -4px rgba(37, 99, 235, 0.28) !important;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 1.95rem !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-hero-cam-badge {
            position: absolute;
            bottom: 0px;
            right: 0px;
            width: 30px !important;
            height: 30px !important;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2.5px solid #ffffff !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
            font-size: 0.78rem !important;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .profile-hero-avatar-wrapper:hover .profile-hero-cam-badge {
            transform: scale(1.15);
        }

        .profile-badge-pill {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #2563eb;
            font-weight: 700;
            border-radius: 50px;
            padding: 5px 14px !important;
            font-size: 0.78rem !important;
        }

        .profile-badge-pill-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
        }

        /* 3D Compact Action Buttons Grid */
        .btn-3d-action-primary {
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%) !important;
            color: #ffffff !important;
            border-bottom: 3.5px solid #1e40af !important;
            border-radius: 14px !important;
            font-weight: 800 !important;
            font-size: 0.88rem !important;
            height: 44px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px -4px rgba(37, 99, 235, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            border: none !important;
        }

        .btn-3d-action-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(37, 99, 235, 0.6) !important;
            color: #ffffff !important;
        }

        .btn-3d-action-primary:active {
            transform: translateY(1px);
            border-bottom-width: 1px !important;
        }

        .btn-3d-action-glass {
            background: #f8fafc !important;
            color: #334155 !important;
            border: 1.2px solid #e2e8f0 !important;
            border-bottom: 3px solid #cbd5e1 !important;
            border-radius: 12px !important;
            font-weight: 800 !important;
            font-size: 0.82rem !important;
            height: 40px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 8px rgba(15, 23, 42, 0.04) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .btn-3d-action-glass:hover {
            transform: translateY(-2px);
            border-color: #93c5fd !important;
            border-bottom-color: #2563eb !important;
            color: #2563eb !important;
        }

        .btn-3d-action-glass:active {
            transform: translateY(1px);
            border-bottom-width: 1px !important;
        }

        .btn-3d-action-danger {
            background: #fff1f2 !important;
            color: #e11d48 !important;
            border: 1.2px solid #fecdd3 !important;
            border-bottom: 3px solid #fda4af !important;
            border-radius: 12px !important;
            font-weight: 800 !important;
            font-size: 0.82rem !important;
            height: 40px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 8px rgba(225, 29, 72, 0.06) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .btn-3d-action-danger:hover {
            transform: translateY(-2px);
            background: #ffe4e6 !important;
            color: #be123c !important;
            border-bottom-color: #e11d48 !important;
        }

        .btn-3d-action-danger:active {
            transform: translateY(1px);
            border-bottom-width: 1px !important;
        }

        /* Profile Cards */
        .profile-card-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        .avatar-circle-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.25);
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-cam-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 36px;
            height: 36px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2.5px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            font-size: 0.85rem;
        }

        .detail-item-row {
            padding: 12px 16px;
            border-bottom: 1px dashed #e2e8f0;
            display: flex;
            flex-direction: column;
        }

        @media (min-width: 576px) {
            .detail-item-row {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .detail-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        @media (min-width: 576px) {
            .detail-label {
                margin-bottom: 0;
                width: 40%;
            }
        }

        .detail-value {
            font-size: 0.92rem;
            font-weight: 600;
            color: #0f172a;
        }

        @media (min-width: 576px) {
            .detail-value {
                width: 60%;
                text-align: right;
            }
        }

        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .popup-content-3d {
            background-color: white;
            padding: 30px;
            border-radius: var(--card-radius-lg);
            width: 90%;
            max-width: 480px;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .popup-content-3d .close-popup-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="dashboard-content dashboard-content-profile">
            <div class="container-fluid px-lg-4">

                <div class="row g-4">
                    
                    <!-- Left Column: Hero Card & Actions (col-lg-4) -->
                    <div class="col-lg-4">
                        
                        <!-- Integrated Executive Hero Card -->
                        <div class="profile-hero-card">
                            <div class="profile-hero-avatar-wrapper" id="photoButton" title="Klik untuk ubah foto profil">
                                <?php if (!empty($photo) && file_exists('../uploads/' . $photo)): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="Foto Profil" class="profile-hero-avatar">
                                <?php else: ?>
                                    <div class="profile-hero-avatar"><?php echo $initials; ?></div>
                                <?php endif; ?>
                                <div class="profile-hero-cam-badge">
                                    <i class="fa-solid fa-camera"></i>
                                </div>
                            </div>

                            <h4 class="fw-extrabold text-dark mb-2 fs-5" style="letter-spacing: -0.4px;"><?php echo htmlspecialchars($nama); ?></h4>
                            <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-3">
                                <span class="profile-badge-pill"><i class="fa-solid fa-briefcase me-1.5 opacity-75"></i><?php echo htmlspecialchars($jabatan); ?></span>
                                <span class="profile-badge-pill profile-badge-pill-success"><i class="fa-solid fa-circle-check me-1.5"></i><?php echo htmlspecialchars($statusKaryawan); ?></span>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="d-grid gap-2 text-start">
                                <button type="button" class="btn btn-3d-action-primary w-100 mb-1" onclick="triggerPWAInstall()">
                                    <i class="fa-solid fa-mobile-screen-button me-2 fs-5"></i>Install Aplikasi HP
                                </button>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-3d-action-glass w-100" onclick="changePasswordPrompt('<?php echo htmlspecialchars($nip); ?>')">
                                            <i class="fa-solid fa-key me-1.5 text-warning"></i>Password
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <a href="edit-profile.php" class="btn btn-3d-action-glass w-100">
                                            <i class="fa-solid fa-pen-to-square me-1.5 text-primary"></i>Edit Profil
                                        </a>
                                    </div>
                                </div>
                                <a href="../logout.php" class="btn btn-3d-action-danger w-100 mt-1" onclick="return confirm('Apakah Anda yakin ingin keluar (Log Out) dari akun ini?');">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Log Out (Keluar)
                                </a>
                            </div>
                        </div>

                        <!-- Financial Overview Summary Card -->
                        <div class="profile-card-main p-4">
                            <h6 class="fw-extrabold text-dark mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                                <span><i class="fa-solid fa-wallet text-emerald-600 me-2 fs-5"></i>Ringkasan Finansial</span>
                                <span class="badge bg-emerald-subtle text-emerald-700 rounded-pill px-2.5 py-1 style='font-size: 0.72rem;'"><i class="fa-solid fa-shield-check me-1"></i>Official</span>
                            </h6>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-dashed">
                                <span class="small text-muted fw-bold">GAJI POKOK</span>
                                <span class="fw-extrabold text-emerald-600 font-mono fs-6"><?php echo $gajiPokok; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-dashed">
                                <span class="small text-muted fw-bold">TUNJANGAN JABATAN</span>
                                <span class="fw-bold text-dark font-mono"><?php echo $tunjangan; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <span class="small text-muted fw-bold">TUNJANGAN MASA KERJA</span>
                                <span class="fw-bold text-dark font-mono">
                                    <?php 
                                    include 'get-tmk.php';
                                    echo "Rp " . number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Details Grid (col-lg-8) -->
                    <div class="col-lg-8">
                        
                        <!-- 1. Informasi Dasar & Kontak -->
                        <div class="profile-card-main">
                            <div class="card-header bg-white border-bottom p-3">
                                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user me-2 text-primary"></i>Informasi Dasar & Kontak</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="detail-item-row">
                                    <div class="detail-label">NIK (Nomor Induk Karyawan)</div>
                                    <div class="detail-value"><span class="badge bg-light text-dark border fw-bold font-mono px-2.5 py-1"><?php echo htmlspecialchars($nik); ?></span></div>
                                </div>
                                <div class="detail-item-row">
                                    <div class="detail-label">Nama Lengkap</div>
                                    <div class="detail-value fw-bold text-dark"><?php echo htmlspecialchars($nama); ?></div>
                                </div>
                                <div class="detail-item-row">
                                    <div class="detail-label">Tempat & Tanggal Lahir</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($tempatLahir); ?>, <?php echo !empty($tanggalLahir) ? date('d F Y', strtotime($tanggalLahir)) : '-'; ?></div>
                                </div>
                                <div class="detail-item-row">
                                    <div class="detail-label">Alamat Lengkap</div>
                                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($alamat)); ?></div>
                                </div>
                                <div class="detail-item-row">
                                    <div class="detail-label">No. Handphone</div>
                                    <div class="detail-value"><a href="tel:<?php echo htmlspecialchars($nomorHP); ?>" class="text-decoration-none fw-bold text-primary"><i class="fa-solid fa-phone me-1.5"></i><?php echo htmlspecialchars($nomorHP); ?></a></div>
                                </div>
                                <div class="detail-item-row border-bottom-0">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value"><a href="mailto:<?php echo htmlspecialchars($email); ?>" class="text-decoration-none text-dark fw-medium"><i class="fa-solid fa-envelope me-1.5 text-secondary"></i><?php echo htmlspecialchars($email); ?></a></div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Informasi Kepegawaian & Bank -->
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="profile-card-main h-100">
                                    <div class="card-header bg-white border-bottom p-3">
                                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-briefcase me-2 text-primary"></i>Informasi Kepegawaian</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="detail-item-row">
                                            <div class="detail-label" style="width: 50%;">Jabatan</div>
                                            <div class="detail-value" style="width: 50%;"><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold rounded-pill px-3 py-1"><?php echo htmlspecialchars($jabatan); ?></span></div>
                                        </div>
                                        <div class="detail-item-row">
                                            <div class="detail-label" style="width: 50%;">Status Karyawan</div>
                                            <div class="detail-value" style="width: 50%;"><span class="badge bg-success-subtle text-success border border-success-subtle fw-bold rounded-pill px-3 py-1"><?php echo htmlspecialchars($statusKaryawan); ?></span></div>
                                        </div>
                                        <div class="detail-item-row">
                                            <div class="detail-label" style="width: 50%;">Tanggal Masuk</div>
                                            <div class="detail-value" style="width: 50%;"><?php echo !empty($tanggalMasuk) ? date('d M Y', strtotime($tanggalMasuk)) : '-'; ?></div>
                                        </div>
                                        <div class="detail-item-row border-bottom-0">
                                            <div class="detail-label" style="width: 50%;">Status Shifting</div>
                                            <div class="detail-value" style="width: 50%;"><?php echo htmlspecialchars($shifting ? 'Aktif (Ya)' : 'Tidak'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="profile-card-main h-100">
                                    <div class="card-header bg-white border-bottom p-3">
                                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-building-columns me-2 text-primary"></i>Informasi Bank</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="detail-item-row">
                                            <div class="detail-label" style="width: 50%;">Nama Bank</div>
                                            <div class="detail-value fw-bold text-dark" style="width: 50%;"><?php include '../get-nama-bank.php'; echo $nmbank ?? htmlspecialchars($namaBank); ?></div>
                                        </div>
                                        <div class="detail-item-row">
                                            <div class="detail-label" style="width: 50%;">No. Rekening</div>
                                            <div class="detail-value font-mono fw-bold text-primary" style="width: 50%;"><?php echo htmlspecialchars($nomorRekening); ?></div>
                                        </div>
                                        <div class="detail-item-row border-bottom-0">
                                            <div class="detail-label" style="width: 50%;">Pemilik Rekening</div>
                                            <div class="detail-value fw-medium" style="width: 50%;"><?php echo htmlspecialchars($namaPemilikRekening); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Dokumen Identitas (KTP) -->
                        <div class="profile-card-main mt-4">
                            <div class="card-header bg-white border-bottom p-3">
                                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-contract me-2 text-primary"></i>Dokumen Identitas KTP</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-6">
                                        <div class="small text-muted fw-bold uppercase mb-1">NOMOR KTP</div>
                                        <div class="fs-5 fw-bold text-dark font-mono"><?php echo htmlspecialchars($nomorKTP); ?></div>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <?php if (!empty($gambarKTP) && file_exists('../uploads/' . $gambarKTP)): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($gambarKTP); ?>" alt="Scan KTP" class="img-fluid rounded-3 border shadow-sm" style="max-height: 130px;">
                                        <?php else: ?>
                                            <span class="text-muted fst-italic small"><i class="fa-solid fa-image me-1"></i>Scan KTP belum diupload</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                <div class="footer mt-4 mb-1 text-center">
                    <small class="text-muted fw-medium">Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>.<br>Version 1.2.0</small>
                </div>
            </div>
        </div>
        <?php include 'nav/bottom-nav.php'; ?>
    </div>

    <!-- Popup Upload Photo -->
    <div id="uploadPopup" class="popup">
        <div class="popup-content-3d text-center">
            <span class="close-popup-btn" id="cancelBtn">&times;</span>
            <div class="avatar-container mb-3">
                <?php if (!empty($photo) && file_exists('../uploads/' . $photo)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="Foto Profil" class="avatar-circle-large" style="width:90px; height:90px;">
                <?php else: ?>
                    <div class="avatar-circle-large" style="width:90px; height:90px; font-size:1.8rem;"><?php echo $initials; ?></div>
                <?php endif; ?>
            </div>
            <h5 class="fw-bold text-dark mb-3">Upload Foto Profil Baru</h5>
            <form action="../upload-photo-kar.php" method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <input type="file" class="form-control rounded-3 p-2" id="newPhotoInput" name="newPhoto" accept="image/jpeg, image/png" required>
                </div>
                <button type="submit" class="btn btn-primary rounded-3 w-100 fw-bold py-2"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Foto Sekarang</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $("#photoButton").click(function() {
                $("#uploadPopup").css("display", "flex");
            });

            $("#cancelBtn").click(function() {
                $("#uploadPopup").css("display", "none");
            });

            $(window).click(function(event) {
                if (event.target == document.getElementById('uploadPopup')) {
                    $("#uploadPopup").css("display", "none");
                }
            });
        });

        function triggerPWAInstall() {
            alert("Aplikasi web siap diinstall di HP Anda. Silakan gunakan menu 'Add to Home Screen' pada browser Anda.");
        }

        function changePasswordPrompt(nip) {
            var oldPassword = prompt("Masukkan Password Lama:");
            if (oldPassword === null) return;

            var newPassword = prompt("Masukkan Password Baru:");
            if (newPassword === null) return;

            var confirmPassword = prompt("Konfirmasi Password Baru:");
            if (confirmPassword === null) return;

            if (newPassword.length < 6) { 
                alert("Password baru minimal harus 6 karakter.");
                return;
            }
            if (newPassword !== confirmPassword) {
                alert("Konfirmasi Password Baru tidak sesuai dengan Password Baru.");
                return;
            }

            $.ajax({
                type: "POST",
                url: "../kar-validate_old_password.php",
                data: {
                    nip: nip,
                    oldPassword: oldPassword
                },
                success: function(response) {
                    if (response.trim() === "success") {
                        var hashedNewPassword = btoa(newPassword);

                        $.ajax({
                            type: "POST",
                            url: "../kar-change_password_script.php",
                            data: {
                                nip: nip,
                                newPassword: hashedNewPassword
                            },
                            success: function(changeResponse) {
                                alert(changeResponse);
                            },
                            error: function() {
                                alert("Terjadi kesalahan saat mengubah password.");
                            }
                        });
                    } else {
                        alert("Password Lama yang dimasukkan salah.");
                    }
                },
                error: function() {
                    alert("Terjadi kesalahan saat memvalidasi password lama.");
                }
            });
        }
    </script>
</body>
</html>