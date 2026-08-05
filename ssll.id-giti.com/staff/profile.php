<?php
session_start();
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
$current_page_basename = basename($_SERVER['PHP_SELF']);
$asset_version = time();

$nip_login = $_SESSION['nip'];
$stmt = $conn->prepare("SELECT * FROM karyawan WHERE nip = ?");
$stmt->bind_param("s", $nip_login);
$stmt->execute();
$res = $stmt->get_result();
$kar = $res->fetch_assoc();
$stmt->close();

$nik = $kar['nik'] ?? '-';
$nama = $kar['nama'] ?? '-';
$tempatLahir = $kar['tempat_lahir'] ?? '-';
$tanggalLahir = $kar['tanggal_lahir'] ?? '';
$alamat = $kar['alamat'] ?? '-';
$nomorHP = $kar['nomor_handphone'] ?? '-';
$nomorTelepon = $kar['nomor_telepon'] ?? '-';
$email = $kar['email'] ?? '-';
$jabatan = $kar['jabatan'] ?? '-';
$statusKaryawan = $kar['status_karyawan'] ?? '-';
$tanggalMasuk = $kar['tanggal_masuk'] ?? '';
$photo = $kar['pas_photo'] ?? '';
$nip = $kar['nip'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Staff 3D - Gravitti Tech</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">
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
            <div class="container-fluid px-lg-4 d-flex align-items-center justify-content-between">
                <div>
                    <h1><i class="fa-solid fa-user-gear me-2 text-primary-light"></i>Profil Saya (Staff/Admin)</h1>
                    <p class="small mb-0 opacity-80">Lihat dan kelola informasi akun administrator Anda.</p>
                </div>
                <a href="../logout.php" class="btn btn-3d-danger btn-sm rounded-pill px-3 py-1.5 fs-7 text-white shadow-sm" onclick="return confirm('Apakah Anda yakin ingin keluar (Log Out)?');">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Log Out
                </a>
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
                        <span class="badge bg-success-subtle text-success fw-bold rounded-pill px-3 py-1"><i class="fa-solid fa-shield-halved me-1"></i><?php echo htmlspecialchars(strtoupper($_SESSION['role'])); ?></span>
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

            </div>
        </div>
    </div>

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