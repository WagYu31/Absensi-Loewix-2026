<?php
session_start();

if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Karyawan - Grav-Tech Salary</title>
    <meta name="description" content="Halaman profil karyawan Grav-Tech Salary" />
    <meta name="keywords" content="profile, karyawan, salary, gaji, gravitti technology" />
    <meta name="author" content="Irviani" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background-color: white;
            padding: 25px;
            border-radius: var(--card-border-radius, 0.5rem);
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .popup-content .close-popup-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #777;
            line-height: 1;
        }

        .popup-content .close-popup-btn:hover {
            color: #333;
        }

        .popup-content h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-weight: 600;
            color: var(--dark-color, #212529);
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="header-banner profile-page-header">
            <div class="container-fluid px-lg-4">
                <h1>Profil Saya</h1>
                <p>Lihat dan kelola informasi pribadi Anda.</p>
            </div>
        </div>

        <div class="dashboard-content profile-page-content">
            <div class="container-fluid px-lg-4 px-0">
                <div class="card profile-header-card mb-4">
                    <div class="card-body text-center">
                        <?php
                        $base_upload_path_profile = '../uploads/';
                        $universal_default_image_profile = $base_upload_path_profile . 'default_avatar.png';
                        $image_source_profile = '';

                        if (!empty($photo)) { 
                            $image_source_profile = htmlspecialchars($base_upload_path_profile . $photo);
                        } else {
                            $initial_profile = !empty($nama) ? strtoupper(substr($nama, 0, 1)) : 'U';
                            $image_source_profile = 'https://via.placeholder.com/120/2979ff/ffffff?Text=' . $initial_profile;
                        }
                        ?>
                        <img src="<?php echo $image_source_profile; ?>"
                            alt="Foto Profil"
                            class="profile-photo-main"
                            id="photoButton"
                            onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($universal_default_image_profile); ?>';">

                        <h5 class="profile-name-main mt-3"><?php echo htmlspecialchars($nama); ?></h5>
                        <p class="profile-position-main text-muted"><?php echo htmlspecialchars($jabatan); ?></p>

                        <div class="profile-buttons-main mt-3">
                            <button type="button" class="btn btn-primary rounded-pill me-md-2 me-0" onclick="changePasswordPrompt('<?php echo htmlspecialchars($nip); ?>')">
                                <i class="fas fa-key me-1"></i> Ganti Password
                            </button>
                            <a href="edit-profile.php" class="btn btn-outline-primary rounded-pill ms-0">
                                <i class="fas fa-user-edit me-1"></i> Edit Profil
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card profile-details-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-circle-info title-icon"></i>Informasi Detail Karyawan</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table profile-table-custom mb-0">
                                <tbody>
                                    <tr>
                                        <th colspan="2" class="table-section-header">Profil Dasar</th>
                                    </tr>
                                    <tr>
                                        <td width="35%">NIK (Nomor Induk Karyawan)</td>
                                        <td><?php echo htmlspecialchars($nik); ?></td>
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
                                        <th colspan="2" class="table-section-header">Informasi Kontak</th>
                                    </tr>
                                    <tr>
                                        <td>No Handphone</td>
                                        <td><?php echo htmlspecialchars($nomorHP); ?></td>
                                    </tr>
                                    <tr>
                                        <td>No Telepon</td>
                                        <td><?php echo htmlspecialchars($nomorTelepon ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Email</td>
                                        <td class="text-wrap text-break"><?php echo htmlspecialchars($email); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header">Informasi Kepegawaian</th>
                                    </tr>
                                    <tr>
                                        <td>Jabatan</td>
                                        <td><?php echo htmlspecialchars($jabatan); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Status Karyawan</td>
                                        <td><?php echo htmlspecialchars($statusKaryawan); ?></td>
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
                                        <th colspan="2" class="table-section-header">Informasi Finansial</th>
                                    </tr>
                                    <tr>
                                        <td>Gaji Pokok</td>
                                        <td><?php echo $gajiPokok;
                                            ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Jabatan</td>
                                        <td><?php echo $tunjangan; 
                                            ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Masa Kerja</td>
                                        <td><?php 
                                        include 'get-tmk.php';
                                        echo "Rp " . number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header">Informasi Bank</th>
                                    </tr>
                                    <tr>
                                        <td>Nama Bank</td>
                                        <td><?php include '../get-nama-bank.php';
                                            echo $nmbank ?? htmlspecialchars($namaBank);
                                            ?></td>
                                    </tr>
                                    <tr>
                                        <td>Nomor Rekening</td>
                                        <td><?php echo htmlspecialchars($nomorRekening); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Pemilik Rekening</td>
                                        <td><?php echo htmlspecialchars($namaPemilikRekening); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="table-section-header">Dokumen Identitas</th>
                                    </tr>
                                    <tr>
                                        <td>Nomor KTP</td>
                                        <td><?php echo htmlspecialchars($nomorKTP); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Scan KTP</td>
                                        <td>
                                            <?php if (!empty($gambarKTP)): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($gambarKTP); ?>" alt="Scan KTP" class="img-fluid profile-document-img">
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>

            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>
    </div>

    <div id="uploadPopup" class="popup">
        <div class="popup-content">
            <span class="close-popup-btn" id="cancelBtn">&times;</span>
            <h2>Upload Foto Profil Baru</h2>
            <form action="../upload-photo-kar.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="newPhotoInput" class="form-label">Pilih file gambar (JPG/PNG):</label>
                    <input type="file" class="form-control" id="newPhotoInput" name="newPhoto" accept="image/jpeg, image/png" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Upload Foto</button>
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

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
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