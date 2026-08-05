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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya 3D - Gravitti Tech</title>
    <meta name="description" content="Halaman profil karyawan Gravitti Tech" />

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

        /* 3D Glassmorphic Profile Card */
        .profile-3d-card {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 12px 24px -12px rgba(15, 23, 42, 0.08) !important;
            padding: 2rem !important;
            margin-bottom: 1.5rem !important;
        }

        .avatar-3d-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        .avatar-3d-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.25), 0 0 0 4px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
        }

        .avatar-3d-wrapper:hover .avatar-3d-img {
            transform: scale(1.04);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.35), 0 0 0 6px rgba(37, 99, 235, 0.5);
        }

        .avatar-camera-badge {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 38px;
            height: 38px;
            background: var(--primary-3d);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2.5px solid #ffffff;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.4);
            font-size: 0.9rem;
        }

        /* 3D Buttons */
        .btn-3d-primary {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 700 !important;
            border-radius: 14px !important;
            padding: 10px 24px !important;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-3d-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.45), 0 4px 0 #1e40af !important;
            color: #ffffff !important;
        }

        .btn-3d-outline {
            background: #ffffff !important;
            color: #2563eb !important;
            border: 1.5px solid #cbd5e1 !important;
            font-weight: 700 !important;
            border-radius: 14px !important;
            padding: 10px 24px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04), 0 2px 0 #cbd5e1 !important;
            transition: all 0.15s ease-out !important;
        }

        .btn-3d-outline:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08), 0 3px 0 #94a3b8 !important;
            color: #1d4ed8 !important;
        }

        .btn-3d-danger {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #b91c1c 100%) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            border-radius: 14px !important;
            padding: 10px 20px !important;
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35), 0 3px 0 #991b1b !important;
            transition: all 0.15s ease-out !important;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
        }

        .btn-3d-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(220, 38, 38, 0.45), 0 4px 0 #7f1d1d !important;
            color: #ffffff !important;
        }

        /* Table Styling */
        .profile-table-3d {
            margin-bottom: 0 !important;
        }

        .profile-table-3d th.table-section-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            color: #1e293b !important;
            font-weight: 800 !important;
            font-size: 0.9rem !important;
            letter-spacing: 0.3px;
            padding: 1rem 1.25rem !important;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #cbd5e1 !important;
        }

        .profile-table-3d td {
            padding: 0.9rem 1.25rem !important;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .profile-table-3d td:first-child {
            font-weight: 700;
            color: #64748b;
            width: 35%;
        }

        .profile-table-3d td:last-child {
            font-weight: 600;
            color: #0f172a;
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

        .popup-content-3d .close-popup-btn:hover {
            color: #1e293b;
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-id-card me-2 text-primary-light"></i>Profil Saya</h1>
                <p class="small mb-0 opacity-80">Lihat dan kelola informasi pribadi serta kepegawaian Anda.</p>
            </div>
        </div>

        <div class="dashboard-content px-0">
            <div class="container-fluid px-lg-4">

                <!-- 3D Header Profile Card -->
                <div class="profile-3d-card text-center position-relative">
                    <?php
                    $base_upload_path_profile = '../uploads/';
                    $universal_default_image_profile = $base_upload_path_profile . 'default_avatar.png';
                    $image_source_profile = '';

                    if (!empty($photo)) { 
                        $image_source_profile = htmlspecialchars($base_upload_path_profile . $photo);
                    } else {
                        $initial_profile = !empty($nama) ? strtoupper(substr($nama, 0, 1)) : 'U';
                        $image_source_profile = 'https://via.placeholder.com/130/2563eb/ffffff?Text=' . $initial_profile;
                    }
                    ?>
                    
                    <div class="avatar-3d-wrapper" id="photoButton">
                        <img src="<?php echo $image_source_profile; ?>"
                            alt="Foto Profil"
                            class="avatar-3d-img"
                            onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($universal_default_image_profile); ?>';">
                        <div class="avatar-camera-badge">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                    </div>

                    <h4 class="fw-extrabold text-dark mb-1" style="letter-spacing: -0.5px;"><?php echo htmlspecialchars($nama); ?></h4>
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary fw-bold rounded-pill px-3 py-1"><?php echo htmlspecialchars($jabatan); ?></span>
                        <span class="badge bg-success-subtle text-success fw-bold rounded-pill px-3 py-1"><i class="fa-solid fa-circle-check me-1"></i><?php echo htmlspecialchars($statusKaryawan); ?></span>
                    </div>

                    <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mt-3">
                        <button type="button" class="btn btn-3d-primary" onclick="triggerPWAInstall()">
                            <i class="fa-solid fa-mobile-screen-button me-1.5"></i>Install HP
                        </button>
                        <button type="button" class="btn btn-3d-outline" onclick="changePasswordPrompt('<?php echo htmlspecialchars($nip); ?>')">
                            <i class="fas fa-key me-1.5"></i>Password
                        </button>
                        <a href="edit-profile.php" class="btn btn-3d-outline">
                            <i class="fas fa-user-edit me-1.5"></i>Edit Profil
                        </a>
                        <a href="../logout.php" class="btn btn-3d-danger" onclick="return confirm('Apakah Anda yakin ingin keluar (Log Out) dari akun ini?');">
                            <i class="fa-solid fa-arrow-right-from-bracket me-1.5"></i>Log Out
                        </a>
                    </div>
                </div>

                <!-- 3D Details Card -->
                <div class="profile-3d-card p-0" style="overflow: hidden;">
                    <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-dark mb-0 fs-6"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Informasi Detail Karyawan</h6>
                    </div>
                    <div class="p-0">
                        <div class="table-responsive">
                            <table class="table profile-table-3d">
                                <tbody>
                                    <tr>
                                        <th colspan="2" class="table-section-header"><i class="fa-solid fa-user me-2 text-primary"></i>Profil Dasar</th>
                                    </tr>
                                    <tr>
                                        <td>NIK (Nomor Induk Karyawan)</td>
                                        <td><span class="badge bg-light text-dark border font-mono px-2 py-1"><?php echo htmlspecialchars($nik); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Nama Lengkap</td>
                                        <td><?php echo htmlspecialchars($nama); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tempat Lahir</td>
                                        <td><?php echo htmlspecialchars($tempatLahir); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Lahir</td>
                                        <td><?php echo !empty($tanggalLahir) ? date('d F Y', strtotime($tanggalLahir)) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td><?php echo nl2br(htmlspecialchars($alamat)); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header"><i class="fa-solid fa-address-book me-2 text-primary"></i>Informasi Kontak</th>
                                    </tr>
                                    <tr>
                                        <td>No Handphone</td>
                                        <td><a href="tel:<?php echo htmlspecialchars($nomorHP); ?>" class="text-decoration-none fw-bold text-primary"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($nomorHP); ?></a></td>
                                    </tr>
                                    <tr>
                                        <td>No Telepon</td>
                                        <td><?php echo htmlspecialchars($nomorTelepon ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Email</td>
                                        <td class="text-wrap text-break"><a href="mailto:<?php echo htmlspecialchars($email); ?>" class="text-decoration-none text-dark"><i class="fa-solid fa-envelope me-1 text-secondary"></i><?php echo htmlspecialchars($email); ?></a></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header"><i class="fa-solid fa-briefcase me-2 text-primary"></i>Informasi Kepegawaian</th>
                                    </tr>
                                    <tr>
                                        <td>Jabatan</td>
                                        <td><span class="badge bg-primary rounded-pill px-3 py-1"><?php echo htmlspecialchars($jabatan); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Status Karyawan</td>
                                        <td><span class="badge bg-success rounded-pill px-3 py-1"><?php echo htmlspecialchars($statusKaryawan); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Masuk</td>
                                        <td><?php echo !empty($tanggalMasuk) ? date('d F Y', strtotime($tanggalMasuk)) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Shifting</td>
                                        <td><?php echo htmlspecialchars($shifting ? 'Ya' : 'Tidak'); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header"><i class="fa-solid fa-wallet me-2 text-primary"></i>Informasi Finansial</th>
                                    </tr>
                                    <tr>
                                        <td>Gaji Pokok</td>
                                        <td><span class="fw-extrabold text-success"><?php echo $gajiPokok; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Jabatan</td>
                                        <td><span class="fw-bold text-dark"><?php echo $tunjangan; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Masa Kerja</td>
                                        <td><span class="fw-bold text-dark"><?php 
                                         include 'get-tmk.php';
                                         echo "Rp " . number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.'); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header"><i class="fa-solid fa-building-columns me-2 text-primary"></i>Informasi Bank</th>
                                    </tr>
                                    <tr>
                                        <td>Nama Bank</td>
                                        <td><?php include '../get-nama-bank.php';
                                             echo $nmbank ?? htmlspecialchars($namaBank);
                                             ?></td>
                                    </tr>
                                    <tr>
                                        <td>Nomor Rekening</td>
                                        <td><span class="font-mono fw-bold text-dark"><?php echo htmlspecialchars($nomorRekening); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Pemilik Rekening</td>
                                        <td><?php echo htmlspecialchars($namaPemilikRekening); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header"><i class="fa-solid fa-file-contract me-2 text-primary"></i>Dokumen Identitas</th>
                                    </tr>
                                    <tr>
                                        <td>Nomor KTP</td>
                                        <td><span class="font-mono text-dark"><?php echo htmlspecialchars($nomorKTP); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Scan KTP</td>
                                        <td>
                                            <?php if (!empty($gambarKTP)): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($gambarKTP); ?>" alt="Scan KTP" class="img-fluid rounded-3 border shadow-sm" style="max-height: 140px;">
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Belum diupload</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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

    <!-- Popup Upload Photo 3D -->
    <div id="uploadPopup" class="popup">
        <div class="popup-content-3d text-center">
            <span class="close-popup-btn" id="cancelBtn">&times;</span>
            <div class="avatar-3d-wrapper mb-3">
                <img src="<?php echo $image_source_profile; ?>" class="avatar-3d-img" style="width: 90px; height: 90px;">
            </div>
            <h5 class="fw-bold text-dark mb-3">Upload Foto Profil Baru</h5>
            <form action="../upload-photo-kar.php" method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <input type="file" class="form-control rounded-3 p-2" id="newPhotoInput" name="newPhoto" accept="image/jpeg, image/png" required>
                </div>
                <button type="submit" class="btn btn-3d-primary w-100"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Foto Sekarang</button>
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

            var currentPath = "<?php echo $current_page_basename; ?>";

            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
            
            if (currentPath === "profile.php" && !$('.sidebar-menu a[href="profile.php"]').hasClass('active')) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="profile.php"]').addClass('active');
            }

            $('.custom-nav__link').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.custom-nav__link.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
            if (currentPath === "profile.php" && !$('.custom-nav__link[href="profile.php"]').hasClass('active')) {
                $('.custom-nav__link.active').removeClass('active');
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }
        });

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