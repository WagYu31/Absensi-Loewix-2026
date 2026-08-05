<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai karyawan
if (!isset($_SESSION['nip']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
// get-kar-login-data.php menyediakan $nip (session NIP), $nama, $jabatan, $nik (database NIK)
include 'get-kar-login-data.php';

$loggedInUserNip = $_SESSION['nip'];
$pesan_sukses = '';
$pesan_error = '';

// --- Proses Form Pengajuan Cuti ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajukan_cuti'])) {
    $tgl_mulai = $_POST['tgl_mulai'] ?? '';
    $tgl_selesai = $_POST['tgl_selesai'] ?? '';
    $jenis_cuti = $_POST['jenis_cuti'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nama_file_bukti = null;

    // Validasi dasar
    if (empty($tgl_mulai) || empty($tgl_selesai) || empty($jenis_cuti) || empty($keterangan)) {
        $pesan_error = "Semua field yang wajib diisi (tanggal mulai, tanggal selesai, jenis cuti, keterangan) harus diisi.";
    } elseif (strtotime($tgl_selesai) < strtotime($tgl_mulai)) {
        $pesan_error = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
    } else {
        // Handle upload file bukti jika ada
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['bukti']['tmp_name'];
            $file_name_original = $_FILES['bukti']['name'];
            $file_size = $_FILES['bukti']['size'];
            $file_ext_arr = explode('.', $file_name_original);
            $file_ext = strtolower(end($file_ext_arr));

            $extensions_allowed = array("jpeg", "jpg", "png", "pdf", "doc", "docx");
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (in_array($file_ext, $extensions_allowed)) {
                if ($file_size <= $max_file_size) {
                    $nama_file_bukti = "bukti_cuti_" . $loggedInUserNip . "_" . time() . "." . $file_ext;
                    $target_dir = "../uploads/bukti_cuti/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0775, true);
                    }
                    $target_file = $target_dir . $nama_file_bukti;

                    if (!move_uploaded_file($file_tmp_name, $target_file)) {
                        $pesan_error = "Gagal mengunggah file bukti. Silakan coba lagi.";
                        $nama_file_bukti = null; // Set kembali ke null jika gagal upload
                    }
                } else {
                    $pesan_error = "Ukuran file bukti terlalu besar (maksimal 5MB).";
                }
            } else {
                $pesan_error = "Jenis file bukti tidak diizinkan (hanya JPG, PNG, PDF, DOC, DOCX).";
            }
        } elseif (isset($_FILES['bukti']) && $_FILES['bukti']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Ada error lain selain tidak ada file yang diupload
            $pesan_error = "Terjadi kesalahan saat mengupload file bukti. Kode Error: " . $_FILES['bukti']['error'];
        }


        // Lanjutkan insert ke database jika tidak ada error dari validasi atau file upload
        if (empty($pesan_error)) {
            $stmt_insert_cuti = $conn->prepare("INSERT INTO cuti (nip, tgl_mulai, tgl_selesai, jenis, keterangan, bukti, verif, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            if ($stmt_insert_cuti) {
                $stmt_insert_cuti->bind_param("ssssss", $loggedInUserNip, $tgl_mulai, $tgl_selesai, $jenis_cuti, $keterangan, $nama_file_bukti);
                if ($stmt_insert_cuti->execute()) {
                    $_SESSION['pesan_sukses_cuti'] = "Pengajuan cuti Anda telah berhasil dikirim dan sedang menunggu verifikasi.";
                    // Alihkan ke halaman ini sendiri (pengajuan-cuti.php) atau ke cuti.php jika itu halaman yang berbeda
                    // Untuk contoh, kita redirect ke halaman ini sendiri (PRG Pattern)
                    header("Location: cuti.php");
                    exit(); // Pastikan exit setelah header redirect
                } else {
                    $pesan_error = "Gagal menyimpan pengajuan cuti ke database: " . $stmt_insert_cuti->error;
                    if ($nama_file_bukti && file_exists($target_file)) { // Hapus file jika DB gagal
                        unlink($target_file);
                    }
                }
                $stmt_insert_cuti->close();
            } else {
                $pesan_error = "Gagal menyiapkan statement database: " . $conn->error;
                if ($nama_file_bukti && file_exists($target_file)) { // Hapus file jika DB gagal
                    unlink($target_file);
                }
            }
        }
    }
}

// --- Ambil Riwayat Cuti untuk ditampilkan ---
$riwayat_cuti_list = [];
$limit_riwayat = 5; // Jumlah item per halaman
$page_riwayat = isset($_GET['page_cuti']) ? (int)$_GET['page_cuti'] : 1;
$page_riwayat = max($page_riwayat, 1);
$offset_riwayat = ($page_riwayat - 1) * $limit_riwayat;

// Hitung total data untuk pagination
$totalResultCuti = $conn->query("SELECT COUNT(id) as total FROM cuti WHERE nip='$loggedInUserNip' AND deleted_at IS NULL");
$totalRowCuti = $totalResultCuti->fetch_assoc();
$totalDataCuti = $totalRowCuti['total'] ?? 0;
$totalPagesCuti = ceil($totalDataCuti / $limit_riwayat);


$stmt_riwayat = $conn->prepare("SELECT id, tgl_mulai, tgl_selesai, jenis, keterangan, bukti, verif, created_at FROM cuti WHERE nip = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?");
if ($stmt_riwayat) {
    $stmt_riwayat->bind_param("sii", $loggedInUserNip, $limit_riwayat, $offset_riwayat);
    $stmt_riwayat->execute();
    $result_riwayat = $stmt_riwayat->get_result();
    while ($row_cuti = $result_riwayat->fetch_assoc()) {
        $riwayat_cuti_list[] = $row_cuti;
    }
    $stmt_riwayat->close();
} else {
    $pesan_error = "Gagal mengambil riwayat cuti: " . $conn->error;
}


// Fungsi untuk mapping jenis cuti dan status verifikasi ke teks yang mudah dibaca
function formatJenisCuti($jenis)
{
    switch (strtolower($jenis)) {
        case 'dipotong':
            return 'Cuti Dipotong Gaji';
        case 'tidak_dipotong':
            return 'Cuti Khusus (Tidak Dipotong)';
        case 'hak':
            return 'Cuti Tahunan (Hak)';
        default:
            return ucfirst($jenis);
    }
}

function formatStatusVerif($status)
{
    switch (ucfirst(strtolower($status))) {
        case 'Pending':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
        case 'Disetujui':
            return '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="fa-solid fa-check-circle me-1"></i>Disetujui</span>';
        case 'Ditolak':
            return '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="fa-solid fa-times-circle me-1"></i>Ditolak</span>';
        case 'Dibatalkan':
            return '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle"><i class="fa-solid fa-ban me-1"></i>Dibatalkan</span>';
        default:
            return '<span class="badge bg-light text-dark border">' . htmlspecialchars($status) . '</span>';
    }
}

function hitungDurasiCuti($tgl_mulai, $tgl_selesai)
{
    try {
        $mulai = new DateTime($tgl_mulai);
        $selesai = new DateTime($tgl_selesai);
        if ($mulai > $selesai) return 0; // Handle jika tanggal selesai sebelum mulai
        $durasi = $selesai->diff($mulai)->days + 1; // Termasuk hari mulai dan selesai
        return $durasi;
    } catch (Exception $e) {
        return 0; // Atau handle error sesuai kebutuhan
    }
}

$current_page_basename = basename($_SERVER['PHP_SELF']);
$nama_karyawan_login = $nama; // $nama dari get-kar-login-data.php
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti - <?php echo htmlspecialchars($nama_karyawan_login); ?> - Grav-Tech</title>
    <meta name="description" content="Halaman pengajuan dan riwayat cuti karyawan Grav-Tech" />
    <meta name="keywords" content="cuti, pengajuan cuti, leave request, gravitti technology" />
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
    <link rel="stylesheet" href="../assets/css/pengajuan-cuti-styles.css">
</head>

<body>

    <?php include 'nav/sidebar.php'; // Include navigasi samping 
    ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Pengajuan Cuti Karyawan</h1>
                <p>Ajukan cuti Anda dan lihat riwayat pengajuan di sini.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4 px-0">

                <div class="card mb-0 pb-0" id="pengajuan-cuti-card">
                    <div class="card-header text-end justify-content-end align-items-end pb-0">
                        <div class="text-end mb-4 no-print"> <button type="button" class="btn btn-success text-light" data-bs-toggle="modal" data-bs-target="#modalSyaratKetentuanCuti">
                                <i class="fa-solid fa-book-open me-2"></i>Syarat & Ketentuan
                            </button>
                        </div>

                        <div class="card form-pengajuan-cuti-card shadow-sm">
                        </div>
                    </div>
                </div>

                <?php if (!empty($pesan_sukses)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-check-circle me-2"></i><?php echo htmlspecialchars($pesan_sukses); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($pesan_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($pesan_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card form-pengajuan-cuti-card mb-4 shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-paper-plane title-icon"></i>Formulir Pengajuan Cuti Baru</h5>
                    </div>
                    <div class="card-body p-lg-4">
                        <form action="pengajuan-cuti.php" method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tgl_mulai" class="form-label">Tanggal Mulai Cuti <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" id="tgl_mulai" name="tgl_mulai" required
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); // Minimal besok 
                                                ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="tgl_selesai" class="form-label">Tanggal Selesai Cuti <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" id="tgl_selesai" name="tgl_selesai" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="jenis_cuti" class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="jenis_cuti" name="jenis_cuti" required>
                                        <option value="">-- Pilih Jenis Cuti --</option>
                                        <!--<option value="hak">Cuti Hak</option>-->
                                        <option value="khusus">Cuti Khusus</option>
                                        <option value="dipotong">Cuti Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="keterangan" class="form-label">Keterangan/Alasan Cuti <span class="text-danger">*</span></label>
                                    <textarea class="form-control form-control-sm" id="keterangan" name="keterangan" rows="4" required placeholder="Jelaskan alasan pengajuan cuti Anda..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="bukti" class="form-label">Lampiran Bukti (Opsional - JPG, PNG, PDF, DOCX, maks 5MB)</label>
                                    <input type="file" class="form-control form-control-sm" id="bukti" name="bukti" accept=".jpg, .jpeg, .png, .pdf, .doc, .docx">
                                    <small class="form-text text-muted">Misalnya: surat dokter untuk cuti sakit, surat undangan untuk cuti penting.</small>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" name="ajukan_cuti" class="btn btn-primary">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Ajukan Cuti
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <div class="footer no-print">
                Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                <br><small>Version 1.1.0</small>
            </div>
        </div>

        <div class="modal fade" id="modalSyaratKetentuanCuti" tabindex="-1" aria-labelledby="modalSyaratKetentuanCutiLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered p-3">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSyaratKetentuanCutiLabel">
                            <i class="fa-solid fa-circle-info me-2 text-primary"></i>Informasi dan Kebijakan Cuti
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="info-cuti-intro text-justify">Sebelum mengajukan cuti, mohon perhatikan jenis-jenis cuti beserta ketentuannya di bawah ini. Pastikan Anda memilih jenis cuti yang paling sesuai.</p>

                        <?php
                        // Anda tetap memerlukan logika untuk $sisa_cuti_tahunan_karyawan di bagian atas file PHP
                        if (isset($sisa_cuti_tahunan_karyawan) && is_numeric($sisa_cuti_tahunan_karyawan)):
                        ?>
                            <div class="alert alert-info saldo-cuti-alert d-flex align-items-center" role="alert">
                                <i class="fa-solid fa-plane-departure fa-lg me-3"></i>
                                <div>
                                    Sisa hak cuti tahunan Anda saat ini: <strong><?php echo $sisa_cuti_tahunan_karyawan; ?> hari</strong>.
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="leave-policy-section mt-3">
                            <div class="leave-type-item">
                                <div class="leave-type-header">
                                    <i class="fa-solid fa-shield-heart icon-cuti text-success"></i>
                                    <h6 class="leave-type-title">Cuti Hak (Kejadian Khusus)</h6>
                                </div>
                                <p class="leave-type-description text-justify">Diberikan untuk peristiwa khusus sesuai kebijakan perusahaan.</p>
                                <p class="leave-type-examples"><small><strong>Contoh:</strong> Pernikahan karyawan, cuti melahirkan/mendampingi istri melahirkan, duka cita keluarga inti.</small></p>
                                <p class="leave-type-implication"><small><i class="fa-solid fa-check-circle text-success me-1"></i><strong>Ketentuan:</strong> Umumnya tidak memotong kuota hak cuti tahunan dan tidak ada potongan gaji (sesuai S&K berlaku).</small></p>
                            </div>

                            <div class="leave-type-item">
                                <div class="leave-type-header">
                                    <i class="fa-solid fa-notes-medical icon-cuti text-info"></i>
                                    <h6 class="leave-type-title">Cuti Khusus (Dengan Pertimbangan)</h6>
                                </div>
                                <p class="leave-type-description text-justify">Untuk keperluan tertentu yang memerlukan justifikasi dan/atau bukti pendukung.</p>
                                <p class="leave-type-examples"><small><strong>Contoh:</strong> Sakit (dengan surat dokter), keperluan pendidikan/pelatihan yang relevan.</small></p>
                                <p class="leave-type-implication"><small><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i><strong>Ketentuan:</strong> Berpotensi memotong hak cuti tahunan atau dikenakan potongan gaji (prorata) jika melebihi batas yang ditetapkan atau tanpa bukti yang memadai.</small></p>
                            </div>

                            <div class="leave-type-item">
                                <div class="leave-type-header">
                                    <i class="fa-solid fa-umbrella-beach icon-cuti text-primary"></i>
                                    <h6 class="leave-type-title">Cuti Lainnya (Menggunakan Hak Cuti / Tidak Dibayar)</h6>
                                </div>
                                <p class="leave-type-description text-justify">Untuk keperluan pribadi yang tidak termasuk dalam kategori Cuti Hak atau Cuti Khusus.</p>
                                <p class="leave-type-examples"><small><strong>Contoh:</strong> Keperluan pribadi, istirahat tambahan, liburan (jika menggunakan hak cuti tahunan).</small></p>
                                <p class="leave-type-implication"><small><i class="fa-solid fa-scissors text-danger me-1"></i><strong>Ketentuan:</strong> Akan memotong kuota hak cuti tahunan Anda. Jika hak cuti habis, pengajuan akan dianggap sebagai cuti tidak dibayar dan berpotensi dikenakan potongan gaji (prorata).</small></p>
                            </div>
                        </div>

                        <hr class="my-3">
                        <p class="small text-muted footer-info-cuti mb-1 text-justify"><i class="fa-solid fa-paperclip me-2"></i>Lampirkan dokumen pendukung (jika ada/diperlukan) pada formulir pengajuan untuk mempercepat proses.</p>
                        <p class="small text-muted footer-info-cuti text-justify"><i class="fa-solid fa-user-check me-2"></i>Setiap pengajuan cuti akan ditinjau dan diverifikasi oleh HRD (Ibu Chika Retno A.).</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php include 'nav/bottom-nav.php'; // Include navigasi bawah mobile 
    ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Validasi tanggal selesai tidak boleh sebelum tanggal mulai
            $('#tgl_mulai, #tgl_selesai').on('change', function() {
                const tglMulai = $('#tgl_mulai').val();
                const tglSelesai = $('#tgl_selesai').val();
                if (tglMulai && tglSelesai && (new Date(tglSelesai) < new Date(tglMulai))) {
                    alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                    $('#tgl_selesai').val(tglMulai); // Set tanggal selesai sama dengan mulai atau kosongkan
                }
                // Set min untuk tgl_selesai berdasarkan tgl_mulai
                if (tglMulai) {
                    $('#tgl_selesai').attr('min', tglMulai);
                }
            });

            // Set min untuk tgl_mulai agar tidak bisa memilih tanggal yang sudah lewat (H+1)
            var today = new Date();
            today.setDate(today.getDate() + 1); // Besok
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
            var yyyy = today.getFullYear();
            var minDate = yyyy + '-' + mm + '-' + dd;
            $('#tgl_mulai').attr('min', minDate);


            // Skrip untuk menu aktif
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
            if (currentPath === "pengajuan-cuti.php" && !$('.sidebar-menu a[href="pengajuan-cuti.php"]').hasClass('active')) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="pengajuan-cuti.php"]').addClass('active');
            }

            // Untuk Navigasi Bawah Mobile (Pengajuan Cuti tidak ada di bottom nav utama)
            $('.custom-nav__link.active').removeClass('active'); // Non-aktifkan semua dulu
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) {
                // FAB is visually distinct
            } else if (currentPath === "dashboard_karyawan.php") {
                $('.custom-nav__link[href="dashboard_karyawan.php"]').addClass('active');
            } else if (currentPath === "profile.php") {
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // function batalkanCuti(idCuti) {
        //     if (confirm("Apakah Anda yakin ingin membatalkan pengajuan cuti ini?")) {
        //         // Kirim request AJAX untuk membatalkan (UPDATE deleted_at atau status)
        //         // $.post('batalkan_cuti.php', {id: idCuti}, function(response){ ... });
        //         alert("Fitur pembatalan belum diimplementasikan. ID Cuti: " + idCuti);
        //     }
        // }
    </script>
</body>

</html>