<?php
session_start();

// Validasi sesi dan peran
if (!isset($_SESSION["nip"]) || (isset($_SESSION["role"]) && $_SESSION["role"] !== "karyawan")) {
    // Jika peran tidak diset, atau peran bukan karyawan, redirect.
    // Pertimbangkan untuk selalu mengecek $_SESSION["role"] jika itu bagian dari otorisasi Anda.
    header("Location: ../index.php"); // Arahkan ke index jika akses tidak sah
    exit();
}

$nip_session = $_SESSION['nip']; // Gunakan variabel yang jelas untuk NIP dari sesi

include '../conn.php';

// Ambil data karyawan yang akan diedit
$query_karyawan = "SELECT * FROM karyawan WHERE nip = ?";
$stmt_karyawan = $conn->prepare($query_karyawan);
if (!$stmt_karyawan) {
    die("Prepare statement failed: " . $conn->error);
}
$stmt_karyawan->bind_param("s", $nip_session);
$stmt_karyawan->execute();
$result_karyawan = $stmt_karyawan->get_result();

if ($result_karyawan && $result_karyawan->num_rows > 0) {
    $row = $result_karyawan->fetch_assoc();

    // Proses update form jika metode POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Ambil data dari form
        $updatedNama = $_POST['nama'] ?? $row['nama'];
        // $updatedNik = $_POST['nik']; // NIK tidak boleh diubah, jadi ambil dari $row
        $updatedTempatLahir = $_POST['tempat_lahir'] ?? $row['tempat_lahir'];
        $updatedTanggalLahir = $_POST['tanggal_lahir'] ?? $row['tanggal_lahir'];
        $updatedAlamat = $_POST['alamat'] ?? $row['alamat'];
        $updatedNomorHandphone = $_POST['nomor_handphone'] ?? $row['nomor_handphone'];
        $updatedNomorTelepon = $_POST['nomor_telepon'] ?? $row['nomor_telepon'];
        $updatedEmail = $_POST['email'] ?? $row['email'];
        $updatedNomorKTP = $_POST['nomor_ktp'] ?? $row['nomor_ktp'];
        $updatedNamaBank = $_POST['nama_bank'] ?? $row['nama_bank'];
        $updatedNomorRekening = $_POST['nomor_rekening'] ?? $row['nomor_rekening'];
        $updatedNamaPemilikRekening = $_POST['nama_pemilik_rekening'] ?? $row['nama_pemilik_rekening'];

        $gambar_ktp_db = $row['gambar_ktp']; // Simpan nama file KTP lama

        // Handle upload gambar KTP baru
        if (isset($_FILES['gambar_ktp']) && $_FILES['gambar_ktp']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['gambar_ktp']['tmp_name'];
            $file_name = $_FILES['gambar_ktp']['name'];
            $file_size = $_FILES['gambar_ktp']['size'];
            $file_type = $_FILES['gambar_ktp']['type'];
            $file_ext_arr = explode('.', $file_name);
            $file_ext = strtolower(end($file_ext_arr));

            $extensions_allowed = array("jpeg", "jpg", "png");

            if (in_array($file_ext, $extensions_allowed)) {
                if ($file_size < 2000000) { // Batas ukuran file 2MB
                    // Buat nama file unik untuk menghindari overwrite
                    $gambar_ktp_db = "ktp_" . $nip_session . "_" . time() . "." . $file_ext;
                    $target_dir = "../uploads/"; // Pastikan direktori ini ada dan bisa ditulis
                    $target_file = $target_dir . $gambar_ktp_db;

                    if (move_uploaded_file($file_tmp_name, $target_file)) {
                        // File berhasil diupload, nama file baru adalah $gambar_ktp_db
                    } else {
                        $message = "Gagal memindahkan file KTP yang diunggah.";
                        echo "<script>alert('" . addslashes($message) . "');</script>";
                        $gambar_ktp_db = $row['gambar_ktp']; // Gunakan gambar lama jika upload gagal
                    }
                } else {
                    $message = "Ukuran file KTP terlalu besar (maks 2MB).";
                    echo "<script>alert('" . addslashes($message) . "');</script>";
                    $gambar_ktp_db = $row['gambar_ktp'];
                }
            } else {
                $message = "Ekstensi file KTP tidak diizinkan (hanya JPG, JPEG, PNG).";
                echo "<script>alert('" . addslashes($message) . "');</script>";
                $gambar_ktp_db = $row['gambar_ktp'];
            }
        }
        // Jika tidak ada file baru diupload, $gambar_ktp_db akan tetap berisi nama file lama.

        // Query Update menggunakan prepared statement untuk keamanan
        $update_query = "UPDATE karyawan SET 
                            nama=?, tempat_lahir=?, tanggal_lahir=?, alamat=?, email=?, 
                            nomor_handphone=?, nomor_telepon=?, nomor_ktp=?, gambar_ktp=?, 
                            nama_bank=?, nomor_rekening=?, nama_pemilik_rekening=? 
                         WHERE nip=?";

        $stmt_update = $conn->prepare($update_query);
        if (!$stmt_update) {
            die("Prepare statement failed for update: " . $conn->error);
        }
        // s = string, d = double, i = integer, b = blob
        $stmt_update->bind_param(
            "sssssssssssss",
            $updatedNama,
            $updatedTempatLahir,
            $updatedTanggalLahir,
            $updatedAlamat,
            $updatedEmail,
            $updatedNomorHandphone,
            $updatedNomorTelepon,
            $updatedNomorKTP,
            $gambar_ktp_db,
            $updatedNamaBank,
            $updatedNomorRekening,
            $updatedNamaPemilikRekening,
            $nip_session
        );

        if ($stmt_update->execute()) {
            // Update session jika nama berubah, agar tampilan di halaman lain juga update
            if ($_SESSION['nama_lengkap'] != $updatedNama) { // Asumsi session nama_lengkap juga ada
                $_SESSION['nama_lengkap'] = $updatedNama;
            }

            $message = "Profil berhasil diperbarui.";
            echo "<script>alert('" . addslashes($message) . "'); window.location.href = 'profile.php';</script>"; // Arahkan ke profile.php
            exit();
        } else {
            $message = "Gagal memperbarui profil: " . $stmt_update->error;
            error_log("Update profile error: " . $stmt_update->error); // Log error
            echo "<script>alert('" . addslashes($message) . "');</script>";
        }
        $stmt_update->close();
    }
} else {
    // Jika data karyawan tidak ditemukan berdasarkan NIP sesi
    $_SESSION['error_message'] = "Data karyawan tidak ditemukan.";
    header("Location: profile.php"); // Arahkan kembali ke profile.php atau halaman error
    exit();
}
$stmt_karyawan->close();
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - <?php echo htmlspecialchars($row['nama'] ?? 'Karyawan'); ?> - Grav-Tech</title>
    <meta name="description" content="Halaman edit profil karyawan Grav-Tech Salary" />
    <meta name="keywords" content="edit profil, karyawan, salary, gaji, gravitti technology" />
    <meta name="author" content="Irviani & AI Assistant" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/edit-profile-styles.css">
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="header-banner edit-profile-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Edit Profil Saya</h1>
                <p>Perbarui informasi data diri, kontak, dan rekening bank Anda.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4 px-0">
                <div class="card edit-profile-form-card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-user-edit me-2 title-icon"></i>Formulir Edit Profil</h5>
                    </div>
                    <div class="card-body p-lg-4">
                        <form action="edit-profile.php" method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <h6 class="form-section-title"><i class="fas fa-id-card me-2"></i>Informasi Pribadi</h6>
                                    <div class="mb-3">
                                        <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="nama" name="nama" value="<?php echo htmlspecialchars($row['nama']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nik_display" class="form-label">NIK (Nomor Induk Karyawan)</label>
                                        <input type="text" class="form-control form-control-sm" id="nik_display" name="nik_display" value="<?php echo htmlspecialchars($row['nik']); ?>" disabled readonly>
                                        <input type="hidden" name="nik" value="<?php echo htmlspecialchars($row['nik']); ?>">
                                    </div>
                                    <div class="row gx-3">
                                        <div class="col-md-6 mb-3">
                                            <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                                            <input type="text" class="form-control form-control-sm" id="tempat_lahir" name="tempat_lahir" value="<?php echo htmlspecialchars($row['tempat_lahir']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                                            <input type="date" class="form-control form-control-sm" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo htmlspecialchars($row['tanggal_lahir']); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nomor_ktp" class="form-label">Nomor KTP</label>
                                        <input type="text" class="form-control form-control-sm" id="nomor_ktp" name="nomor_ktp" value="<?php echo htmlspecialchars($row['nomor_ktp']); ?>" pattern="\d{16}" title="Nomor KTP harus 16 digit angka">
                                    </div>
                                    <div class="mb-3">
                                        <label for="gambar_ktp" class="form-label">Upload Scan KTP Baru (JPG/PNG, maks 2MB)</label>
                                        <input type="file" class="form-control form-control-sm" id="gambar_ktp" name="gambar_ktp" accept="image/jpeg, image/png">
                                        <?php if (!empty($row['gambar_ktp'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted d-block mb-1">Scan KTP Saat Ini:</small>
                                                <img src="../uploads/<?php echo htmlspecialchars($row['gambar_ktp']); ?>" alt="KTP Saat Ini" class="ktp-preview img-fluid">
                                            </div>
                                        <?php endif; ?>
                                        <div id="ktpPreviewContainer" class="mt-2"></div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <h6 class="form-section-title"><i class="fas fa-address-book me-2"></i>Kontak dan Alamat</h6>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control form-control-sm" id="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                                    </div>
                                    <div class="row gx-3">
                                        <div class="col-md-6 mb-3">
                                            <label for="nomor_handphone" class="form-label">Nomor Handphone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control form-control-sm" id="nomor_handphone" name="nomor_handphone" value="<?php echo htmlspecialchars($row['nomor_handphone']); ?>" required pattern="08\d{8,11}" title="Format: 08xxxxxxxxx (10-13 digit)">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nomor_telepon" class="form-label">Nomor Telepon (Opsional)</label>
                                            <input type="tel" class="form-control form-control-sm" id="nomor_telepon" name="nomor_telepon" value="<?php echo htmlspecialchars($row['nomor_telepon']); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="alamat" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" id="alamat" name="alamat" rows="4" required><?php echo htmlspecialchars($row['alamat']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <h6 class="form-section-title"><i class="fas fa-university me-2"></i>Informasi Rekening Bank</h6>
                                    <div class="mb-3">
                                        <label for="nama_bank" class="form-label">Nama Bank</label>
                                        <select class="form-select form-select-sm" id="nama_bank" name="nama_bank">
                                            <option value="">-- Pilih Bank --</option>
                                            <option value="bca" <?php if ($row['nama_bank'] === 'bca') echo 'selected'; ?>>Bank Central Asia (BCA)</option>
                                            <option value="mandiri" <?php if ($row['nama_bank'] === 'mandiri') echo 'selected'; ?>>Bank Mandiri</option>
                                            <option value="bri" <?php if ($row['nama_bank'] === 'bri') echo 'selected'; ?>>Bank Rakyat Indonesia (BRI)</option>
                                            <option value="bni" <?php if ($row['nama_bank'] === 'bni') echo 'selected'; ?>>Bank Negara Indonesia (BNI)</option>
                                            <option value="bsi" <?php if ($row['nama_bank'] === 'bsi') echo 'selected'; ?>>Bank Syariah Indonesia (BSI)</option>
                                            <option value="cimb" <?php if ($row['nama_bank'] === 'cimb') echo 'selected'; ?>>CIMB Niaga</option>
                                            <option value="btn" <?php if ($row['nama_bank'] === 'btn') echo 'selected'; ?>>Bank Tabungan Negara (BTN)</option>
                                            <option value="ocbc" <?php if ($row['nama_bank'] === 'ocbc') echo 'selected'; ?>>OCBC NISP</option>
                                            <option value="panin" <?php if ($row['nama_bank'] === 'panin') echo 'selected'; ?>>Bank Panin</option>
                                            <option value="danamon" <?php if ($row['nama_bank'] === 'danamon') echo 'selected'; ?>>Bank Danamon</option>
                                            <option value="permata" <?php if ($row['nama_bank'] === 'permata') echo 'selected'; ?>>Bank Permata</option>
                                            <option value="bcadigital" <?php if ($row['nama_bank'] === 'bcadigital') echo 'selected'; ?>>Blu by BCA Digital</option>
                                            <option value="jago" <?php if ($row['nama_bank'] === 'jago') echo 'selected'; ?>>Bank Jago</option>
                                            <option value="seabank" <?php if ($row['nama_bank'] === 'seabank') echo 'selected'; ?>>SeaBank</option>
                                            <option value="gopay" <?php if ($row['nama_bank'] === 'gopay') echo 'selected'; ?>>GoPay</option>
                                            <option value="ovo" <?php if ($row['nama_bank'] === 'ovo') echo 'selected'; ?>>OVO</option>
                                            <option value="linkaja" <?php if ($row['nama_bank'] === 'linkaja') echo 'selected'; ?>>LinkAja</option>
                                            <option value="dana" <?php if ($row['nama_bank'] === 'dana') echo 'selected'; ?>>DANA</option>
                                            <option value="lainnya" <?php if ($row['nama_bank'] === 'lainnya') echo 'selected'; ?>>Lainnya</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nomor_rekening" class="form-label">Nomor Rekening</label>
                                        <input type="text" class="form-control form-control-sm" id="nomor_rekening" name="nomor_rekening" value="<?php echo htmlspecialchars($row['nomor_rekening']); ?>" pattern="\d*">
                                    </div>
                                    <div class="mb-3">
                                        <label for="nama_pemilik_rekening" class="form-label">Atas Nama Rekening</label>
                                        <input type="text" class="form-control form-control-sm" id="nama_pemilik_rekening" name="nama_pemilik_rekening" value="<?php echo htmlspecialchars($row['nama_pemilik_rekening']); ?>">
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <h6 class="form-section-title"><i class="fas fa-info-circle me-2"></i>Informasi Perusahaan (Read-only)</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Jabatan</label>
                                        <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['jabatan']); ?>" disabled readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Masuk</label>
                                        <input type="text" class="form-control form-control-sm" value="<?php echo !empty($row['tanggal_masuk']) ? date('d F Y', strtotime($row['tanggal_masuk'])) : '-'; ?>" disabled readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status Karyawan</label>
                                        <input type="text" class="form-control form-control-sm text-capitalize" value="<?php echo htmlspecialchars($row['status_karyawan']); ?>" disabled readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions text-end mt-4 pt-4">
                                <a href="profile.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-times me-1"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="footer no-print">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>

            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Preview KTP image before upload
            $('#gambar_ktp').on('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let previewContainer = $('#ktpPreviewContainer');
                        previewContainer.empty(); // Hapus preview lama jika ada
                        $('<img>', {
                            src: e.target.result,
                            class: 'ktp-preview img-fluid', // Tambahkan img-fluid agar responsif
                            alt: 'Preview KTP Baru'
                        }).appendTo(previewContainer);
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Skrip untuk menu aktif
            var currentPath = "<?php echo $current_page_basename; ?>";
            var profilePageName = "profile.php"; // Nama file halaman profil utama

            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                // Aktifkan "Profile" jika di halaman profile.php atau edit-profile.php
                if (linkHref === profilePageName && (currentPath === profilePageName || currentPath === "edit-profile.php")) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else if (linkHref === currentPath && currentPath !== profilePageName && currentPath !== "edit-profile.php") {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else if (linkHref !== profilePageName && linkHref !== currentPath) {
                    $(this).removeClass('active');
                }
            });


            $('.custom-nav__link').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                // Aktifkan "Profile" jika di halaman profile.php atau edit-profile.php
                if (linkHref === profilePageName && (currentPath === profilePageName || currentPath === "edit-profile.php")) {
                    $('.custom-nav__link.active').removeClass('active');
                    $(this).addClass('active');
                } else if (linkHref === currentPath && currentPath !== profilePageName && currentPath !== "edit-profile.php") {
                    $('.custom-nav__link.active').removeClass('active');
                    $(this).addClass('active');
                } else if (linkHref !== profilePageName && linkHref !== currentPath) {
                    $(this).removeClass('active');
                }
            });
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) { // Jika di halaman absen, non-aktifkan item lain di bottom nav
                $('.custom-nav__link.active').removeClass('active');
            }


            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>