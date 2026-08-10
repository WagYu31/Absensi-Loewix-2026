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
$gajiPokokVal = $kar['gaji_pokok'] ?? 0;
$tunjanganVal = $kar['tunjangan'] ?? 0;
$namaBank = $kar['nama_bank'] ?? '-';
$nomorRekening = $kar['nomor_rekening'] ?? '-';
$namaPemilikRekening = $kar['nama_pemilik_rekening'] ?? '-';
$nomorKTP = $kar['nomor_ktp'] ?? '-';
$gambarKTP = $kar['gambar_ktp'] ?? '';
$shifting = $kar['shifting'] ?? '';
$nip = $kar['nip'];

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

        /* Detail Item Row */
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
        <!-- Header Banner -->
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Profil Saya</h1>
                <p class="small opacity-80 mb-0">Kelola data informasi akun administrator dan kepegawaian Anda.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <div class="row g-4">
                    
                    <!-- Left Column: Hero Card & Actions (col-lg-4) -->
                    <div class="col-lg-4">
                        <div class="profile-card-main text-center p-4">
                            <div class="avatar-container" id="photoButton" title="Klik untuk ubah foto profil">
                                <?php if (!empty($photo) && file_exists('../uploads/' . $photo)): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="Foto Profil" class="avatar-circle-large">
                                <?php else: ?>
                                    <div class="avatar-circle-large"><?php echo $initials; ?></div>
                                <?php endif; ?>
                                <div class="avatar-cam-badge">
                                    <i class="fa-solid fa-camera"></i>
                                </div>
                            </div>

                            <h4 class="fw-extrabold text-dark mb-1"><?php echo htmlspecialchars($nama); ?></h4>
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold rounded-pill px-3 py-1"><?php echo htmlspecialchars($jabatan); ?></span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold rounded-pill px-3 py-1"><i class="fa-solid fa-shield-halved me-1"></i><?php echo htmlspecialchars(strtoupper($_SESSION['role'])); ?></span>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="button" class="btn btn-primary rounded-3 fw-bold py-2.5 shadow-sm" onclick="triggerPWAInstall()">
                                    <i class="fa-solid fa-mobile-screen-button me-2"></i>Install Aplikasi HP
                                </button>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-secondary rounded-3 w-100 fw-bold py-2" onclick="changePasswordPrompt('<?php echo htmlspecialchars($nip); ?>')">
                                            <i class="fa-solid fa-key me-1.5"></i>Password
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <a href="edit-profile.php" class="btn btn-outline-primary rounded-3 w-100 fw-bold py-2">
                                            <i class="fa-solid fa-pen-to-square me-1.5"></i>Edit Profil
                                        </a>
                                    </div>
                                </div>
                                <a href="../logout.php" class="btn btn-outline-danger rounded-3 fw-bold py-2 mt-1" onclick="return confirm('Apakah Anda yakin ingin keluar (Log Out) dari akun ini?');">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Log Out
                                </a>
                            </div>
                        </div>

                        <!-- Financial Overview Summary Card -->
                        <div class="profile-card-main p-4">
                            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="fa-solid fa-wallet text-success me-2"></i>Ringkasan Finansial</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted fw-bold d-flex align-items-center">
                                    GAJI POKOK
                                    <i class="fa-solid fa-eye text-success ms-1.5" id="iconToggleGajiStaff" onclick="toggleGajiPokokStaff()" title="Sembunyikan / Tampilkan Gaji" style="cursor: pointer; font-size: 0.95rem;"></i>
                                </span>
                                <span class="fw-bold text-success fs-6" id="valGajiPokokStaff" data-original="Rp <?php echo number_format($gajiPokokVal, 0, ',', '.'); ?>">Rp <?php echo number_format($gajiPokokVal, 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted fw-bold">TUNJANGAN JABATAN</span>
                                <span class="fw-semibold text-dark">Rp <?php echo number_format($tunjanganVal, 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted fw-bold">TUNJANGAN MASA KERJA</span>
                                <span class="fw-semibold text-dark">
                                    <?php 
                                    $dataTMK = ['tunjangan_masa_kerja' => 0];
                                    if (file_exists('get-tunjangan-masa-kerja.php')) {
                                        $temp_karyawan_for_tmk = $kar;
                                        include 'get-tunjangan-masa-kerja.php';
                                        unset($temp_karyawan_for_tmk);
                                    }
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
                                            <div class="detail-value fw-bold text-dark" style="width: 50%;"><?php echo htmlspecialchars($namaBank); ?></div>
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

                </div>

            </div>
        </div>
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

        function toggleGajiPokokStaff() {
            var valElem = document.getElementById("valGajiPokokStaff");
            var iconElem = document.getElementById("iconToggleGajiStaff");
            if (!valElem || !iconElem) return;
            
            if (valElem.getAttribute("data-hidden") === "true") {
                valElem.innerText = valElem.getAttribute("data-original");
                valElem.setAttribute("data-hidden", "false");
                iconElem.className = "fa-solid fa-eye text-success ms-1.5";
            } else {
                valElem.innerText = "Rp ••••••••";
                valElem.setAttribute("data-hidden", "true");
                iconElem.className = "fa-solid fa-eye-slash text-muted ms-1.5";
            }
        }
    </script>
</body>
</html>