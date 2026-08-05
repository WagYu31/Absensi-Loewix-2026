<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai karyawan
if (!isset($_SESSION['nip']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

$loggedInUserNip = $_SESSION['nip'];
$nama_karyawan_login = $nama ?? $_SESSION['nama'] ?? 'Karyawan';
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
                        $nama_file_bukti = null;
                    }
                } else {
                    $pesan_error = "Ukuran file bukti terlalu besar (maksimal 5MB).";
                }
            } else {
                $pesan_error = "Jenis file bukti tidak diizinkan (hanya JPG, PNG, PDF, DOC, DOCX).";
            }
        } elseif (isset($_FILES['bukti']) && $_FILES['bukti']['error'] !== UPLOAD_ERR_NO_FILE) {
            $pesan_error = "Terjadi kesalahan saat mengupload file bukti. Kode Error: " . $_FILES['bukti']['error'];
        }

        if (empty($pesan_error)) {
            $stmt_insert_cuti = $conn->prepare("INSERT INTO cuti (nip, tgl_mulai, tgl_selesai, jenis, keterangan, bukti, verif, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            if ($stmt_insert_cuti) {
                $stmt_insert_cuti->bind_param("ssssss", $loggedInUserNip, $tgl_mulai, $tgl_selesai, $jenis_cuti, $keterangan, $nama_file_bukti);
                if ($stmt_insert_cuti->execute()) {
                    $_SESSION['pesan_sukses_cuti'] = "Pengajuan cuti Anda telah berhasil dikirim dan sedang menunggu verifikasi.";
                    header("Location: cuti.php");
                    exit();
                } else {
                    $pesan_error = "Gagal menyimpan pengajuan cuti ke database: " . $stmt_insert_cuti->error;
                    if ($nama_file_bukti && file_exists($target_file)) {
                        unlink($target_file);
                    }
                }
                $stmt_insert_cuti->close();
            } else {
                $pesan_error = "Gagal menyiapkan statement database: " . $conn->error;
                if ($nama_file_bukti && file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        }
    }
}

$current_page_basename = basename($_SERVER['PHP_SELF']);
$asset_version = time();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pengajuan Cuti 3D - Gravitti Tech</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/footer.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/pengajuan-cuti-styles.css?v=<?php echo $asset_version; ?>">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            background: #f1f5f9 !important;
        }
    </style>
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-paper-plane me-2 text-primary-light"></i>Pengajuan Cuti Karyawan</h1>
                <p class="small mb-0 opacity-80">Isi formulir pengajuan cuti kerja baru secara praktis dan transparan.</p>
            </div>
        </div>

        <div class="dashboard-content px-0 pt-2">
            <div class="container-fluid px-lg-4">

                <!-- Syarat & Ketentuan Bar -->
                <div class="d-flex justify-content-end mb-3 no-print">
                    <button type="button" class="btn btn-syarat-3d" data-bs-toggle="modal" data-bs-target="#modalSyaratKetentuanCuti">
                        <i class="fa-solid fa-book-open"></i>Syarat & Ketentuan Cuti
                    </button>
                </div>

                <?php if (!empty($pesan_sukses)): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($pesan_sukses); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($pesan_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($pesan_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- 3D Form Card -->
                <div class="form-pengajuan-cuti-card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-file-pen title-icon"></i>Formulir Pengajuan Cuti Baru</h5>
                    </div>
                    <div class="card-body p-3 p-lg-4">
                        <form action="pengajuan-cuti.php" method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tgl_mulai" class="form-label">Tanggal Mulai Cuti <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" required
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="tgl_selesai" class="form-label">Tanggal Selesai Cuti <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tgl_selesai" name="tgl_selesai" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="jenis_cuti" class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jenis_cuti" name="jenis_cuti" required>
                                        <option value="">-- Pilih Jenis Cuti --</option>
                                        <option value="khusus">Cuti Khusus</option>
                                        <option value="dipotong">Cuti Lainnya (Potong Gaji)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="keterangan" class="form-label">Keterangan/Alasan Cuti <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="keterangan" name="keterangan" rows="4" required placeholder="Jelaskan secara detail alasan pengajuan cuti Anda..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="bukti" class="form-label">Lampiran Bukti (Opsional - JPG, PNG, PDF, DOCX, maks 5MB)</label>
                                    <input type="file" class="form-control" id="bukti" name="bukti" accept=".jpg, .jpeg, .png, .pdf, .doc, .docx">
                                    <small class="text-muted d-block mt-1 fw-semibold"><i class="fa-solid fa-circle-info me-1 text-primary"></i>Misalnya: surat dokter untuk cuti sakit, surat undangan untuk izin kepentingan keluarga.</small>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" name="ajukan_cuti" class="btn btn-submit-cuti-3d">
                                    <i class="fa-solid fa-paper-plane"></i>Kirim Pengajuan Cuti
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

    <!-- Modal Syarat Ketentuan 3D -->
    <div class="modal fade" id="modalSyaratKetentuanCuti" tabindex="-1" aria-labelledby="modalSyaratKetentuanCutiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered p-3">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #0f172a, #1e1b4b) !important;">
                    <h5 class="modal-title fw-bold" id="modalSyaratKetentuanCutiLabel">
                        <i class="fa-solid fa-circle-info me-2 text-warning"></i>Informasi & Kebijakan Cuti
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary fw-semibold">Sebelum mengajukan cuti, mohon perhatikan jenis-jenis cuti beserta ketentuannya di bawah ini:</p>
                    
                    <div class="card mb-3 border-0 bg-light-subtle shadow-sm rounded-3 p-3 border-start border-4 border-primary">
                        <h6 class="fw-bold text-primary mb-1"><i class="fa-solid fa-calendar-check me-2"></i>1. Cuti Hak (Cuti Tahunan)</h6>
                        <p class="small text-secondary mb-0">Cuti tahunan yang diperoleh karyawan setelah masa kerja 6 bulan secara berkelanjutan.</p>
                    </div>

                    <div class="card mb-3 border-0 bg-light-subtle shadow-sm rounded-3 p-3 border-start border-4 border-warning">
                        <h6 class="fw-bold text-warning-emphasis mb-1"><i class="fa-solid fa-star me-2"></i>2. Cuti Khusus</h6>
                        <p class="small text-secondary mb-0">Cuti dengan alasan khusus seperti pernikahan, kemalangan, atau ibadah keagamaan sesuai peraturan perusahaan.</p>
                    </div>

                    <div class="card border-0 bg-light-subtle shadow-sm rounded-3 p-3 border-start border-4 border-danger">
                        <h6 class="fw-bold text-danger mb-1"><i class="fa-solid fa-coins me-2"></i>3. Cuti Lainnya (Potong Gaji)</h6>
                        <p class="small text-secondary mb-0">Cuti yang diambil jika kuota cuti tahunan telah habis atau untuk keperluan di luar ketentuan di atas.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tgl_mulai, #tgl_selesai').on('change', function() {
                const tglMulai = $('#tgl_mulai').val();
                const tglSelesai = $('#tgl_selesai').val();
                if (tglMulai && tglSelesai && (new Date(tglSelesai) < new Date(tglMulai))) {
                    alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                    $('#tgl_selesai').val(tglMulai);
                }
                if (tglMulai) {
                    $('#tgl_selesai').attr('min', tglMulai);
                }
            });
        });
    </script>
</body>
</html>