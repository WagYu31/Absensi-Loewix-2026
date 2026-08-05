<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

$loggedInUserNip = $_SESSION['nip'];
$nama_karyawan_login = $nama ?? 'Karyawan';
$current_page_basename = basename($_SERVER['PHP_SELF']);
$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan 3D - Gravitti Tech</title>
    
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
    <link rel="stylesheet" href="../assets/css/bantuan-styles.css?v=<?php echo $asset_version; ?>">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            background: #f1f5f9 !important;
        }

        .btn-call-action {
            background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border-radius: 12px !important;
            padding: 6px 14px !important;
            font-size: 0.8rem !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .btn-call-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.45) !important;
            color: #ffffff !important;
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header bantuan-header-banner no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-headset me-2 text-primary-light"></i>Pusat Bantuan</h1>
                <p class="small mb-0 opacity-80">Temukan informasi kontak penting, nomor ekstensi, dan tim bantuan di Gravitti Technology.</p>
            </div>
        </div>

        <div class="dashboard-content px-0 pt-2">
            <div class="container-fluid px-lg-4">

                <div class="row g-3">
                    <!-- HRD Card -->
                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm h-100 border-0">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-users title-icon"></i>Human Resources (HRD)</h5>
                                <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold fs-7">Ext. 812</span>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-user-tie kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Narahubung Utama:</span>
                                        <span class="kontak-info-value">Ibu Chika Retno A.</span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-envelope kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Email HRD:</span>
                                        <span class="kontak-info-value"><a href="mailto:info@grav-tech.com">info@grav-tech.com</a></span>
                                    </div>
                                    <a href="mailto:info@grav-tech.com" class="btn-call-action"><i class="fa-solid fa-paper-plane me-1"></i>Email</a>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-phone-volume kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Ekstensi Telepon:</span>
                                        <span class="kontak-info-value">812</span>
                                    </div>
                                    <a href="tel:812" class="btn-call-action"><i class="fa-solid fa-phone me-1"></i>Panggil</a>
                                </div>
                                <hr class="my-3 opacity-25">
                                <strong class="kontak-info-label d-block mb-2 text-primary"><i class="fa-solid fa-list-check me-1"></i>Area Tanggung Jawab:</strong>
                                <ul class="tanggung-jawab-list">
                                    <li>Pengelolaan Gaji dan Tunjangan Karyawan</li>
                                    <li>Pengelolaan Absensi & Kehadiran</li>
                                    <li>Proses Pengajuan Cuti Tahunan & Khusus</li>
                                    <li>Rekrutmen dan Pengembangan Karyawan</li>
                                    <li>Keluhan dan Konsultasi Karyawan</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- IT Support Card -->
                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm h-100 border-0">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-laptop-code title-icon"></i>IT Support & System</h5>
                                <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold fs-7">Ext. 814</span>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-headset kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Tim Bantuan Teknis:</span>
                                        <span class="kontak-info-value">IT Helpdesk & System Admin</span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-envelope kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Email IT Helpdesk:</span>
                                        <span class="kontak-info-value"><a href="mailto:it.support@grav-tech.com">it.support@grav-tech.com</a></span>
                                    </div>
                                    <a href="mailto:it.support@grav-tech.com" class="btn-call-action"><i class="fa-solid fa-paper-plane me-1"></i>Email</a>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-phone-volume kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Ekstensi Telepon:</span>
                                        <span class="kontak-info-value">814</span>
                                    </div>
                                    <a href="tel:814" class="btn-call-action"><i class="fa-solid fa-phone me-1"></i>Panggil</a>
                                </div>
                                <hr class="my-3 opacity-25">
                                <strong class="kontak-info-label d-block mb-2 text-primary"><i class="fa-solid fa-list-check me-1"></i>Area Tanggung Jawab:</strong>
                                <ul class="tanggung-jawab-list">
                                    <li>Masalah Perangkat Keras (Komputer, Laptop, Printer)</li>
                                    <li>Gangguan Jaringan Internet & Intranet Office</li>
                                    <li>Akses dan Masalah Sistem Aplikasi Absensi Internal</li>
                                    <li>Keamanan Data dan Reset Akun Pengguna</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- General Affair Card -->
                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm h-100 border-0">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-building-user title-icon"></i>Bagian Umum (GA)</h5>
                                <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold fs-7">Ext. 813</span>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-user-tie kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Narahubung GA:</span>
                                        <span class="kontak-info-value">Rosalina Megawati</span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-phone-volume kontak-icon"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Ekstensi Telepon:</span>
                                        <span class="kontak-info-value">813</span>
                                    </div>
                                    <a href="tel:813" class="btn-call-action"><i class="fa-solid fa-phone me-1"></i>Panggil</a>
                                </div>
                                <hr class="my-3 opacity-25">
                                <strong class="kontak-info-label d-block mb-2 text-primary"><i class="fa-solid fa-list-check me-1"></i>Area Tanggung Jawab:</strong>
                                <ul class="tanggung-jawab-list">
                                    <li>Fasilitas dan Pemeliharaan Gedung Kantor</li>
                                    <li>Pengadaan Alat Tulis Kantor (ATK)</li>
                                    <li>Peminjaman Perangkat & Barang Kantor</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact Card -->
                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm h-100 border-0">
                            <div class="card-header bg-gradient d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #7f1d1d, #991b1b) !important; color:#fff !important;">
                                <h5 class="card-title mb-0 text-white"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Kontak Darurat Internal</h5>
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold fs-7">24 Jam</span>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-shield-halved kontak-icon" style="background: linear-gradient(135deg, #dc2626, #b91c1c) !important;"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Informasi Keamanan Gedung (24 Jam):</span>
                                        <span class="kontak-info-value text-danger">Ext. 810</span>
                                    </div>
                                    <a href="tel:810" class="btn-call-action" style="background: linear-gradient(135deg, #dc2626, #b91c1c) !important;"><i class="fa-solid fa-phone me-1"></i>Panggil</a>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-briefcase-medical kontak-icon" style="background: linear-gradient(135deg, #dc2626, #b91c1c) !important;"></i>
                                    <div class="flex-grow-1">
                                        <span class="kontak-info-label">Petugas P3K / Klinik Internal:</span>
                                        <span class="kontak-info-value text-danger">Ext. 813</span>
                                    </div>
                                    <a href="tel:813" class="btn-call-action" style="background: linear-gradient(135deg, #dc2626, #b91c1c) !important;"><i class="fa-solid fa-phone me-1"></i>Panggil</a>
                                </div>
                                <hr class="my-3 opacity-25">
                                <p class="small text-danger mb-0 fw-semibold"><i class="fa-solid fa-circle-info me-1"></i>Segera hubungi ekstensi di atas jika terjadi situasi darurat di lingkungan kantor.</p>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="footer mt-4">
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