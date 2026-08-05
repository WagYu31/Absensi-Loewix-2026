<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php'; 

$pesan_sukses = '';
$pesan_error = '';

$karyawan_list = [];
$result_karyawan = $conn->query("SELECT nip, nama FROM karyawan WHERE status_karyawan = 'aktif' ORDER BY nama ASC");
if ($result_karyawan) {
    while ($row = $result_karyawan->fetch_assoc()) {
        $karyawan_list[] = $row;
    }
} else {
    $pesan_error = "Gagal mengambil daftar karyawan: " . $conn->error;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_cuti_manual'])) {
    
    $nip_karyawan = $_POST['nip_karyawan'] ?? '';
    $tgl_mulai = $_POST['tgl_mulai'] ?? '';
    $tgl_selesai = $_POST['tgl_selesai'] ?? '';
    $jenis_cuti = $_POST['jenis_cuti'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nama_file_bukti = null;

    $potong_gaji = 0; 
    if ($jenis_cuti === 'hak') {
        $potong_gaji = 0;
    } elseif ($jenis_cuti === 'dipotong') {
        $potong_gaji = 1;
    } elseif ($jenis_cuti === 'khusus') {
        if (isset($_POST['potong_gaji'])) {
            $potong_gaji = (int)$_POST['potong_gaji'];
        }
    }

    if (empty($nip_karyawan) || empty($tgl_mulai) || empty($tgl_selesai) || empty($jenis_cuti) || empty($keterangan)) {
        $pesan_error = "Semua field bertanda (*) wajib diisi.";
    } elseif ($jenis_cuti === 'khusus' && !isset($_POST['potong_gaji'])) {
        $pesan_error = "Untuk Cuti Khusus, Anda wajib memilih 'Potong Gaji (Ya/Tidak)'.";
    } elseif (strtotime($tgl_selesai) < strtotime($tgl_mulai)) {
        $pesan_error = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
    } else {
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['bukti']['tmp_name'];
            $file_name_original = $_FILES['bukti']['name'];
            $file_size = $_FILES['bukti']['size'];
            $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));

            $extensions_allowed = ["jpeg", "jpg", "png", "pdf", "doc", "docx"];
            $max_file_size = 5 * 1024 * 1024; 

            if (in_array($file_ext, $extensions_allowed) && $file_size <= $max_file_size) {
                $nama_file_bukti = "bukti_cuti_" . $nip_karyawan . "_" . time() . "." . $file_ext;
                $target_dir = "../uploads/bukti_cuti/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0775, true);
                }
                $target_file = $target_dir . $nama_file_bukti;

                if (!move_uploaded_file($file_tmp_name, $target_file)) {
                    $pesan_error = "Gagal mengunggah file bukti.";
                    $nama_file_bukti = null;
                }
            } else {
                $pesan_error = "File tidak valid (Format: jpg, png, pdf, docx | Ukuran Maks: 5MB).";
            }
        }

        if (empty($pesan_error)) {
            $stmt = $conn->prepare("INSERT INTO cuti (nip, tgl_mulai, tgl_selesai, jenis, keterangan, bukti, verif, potong_gaji, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Disetujui', ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("ssssssi", $nip_karyawan, $tgl_mulai, $tgl_selesai, $jenis_cuti, $keterangan, $nama_file_bukti, $potong_gaji);
                if ($stmt->execute()) {
                    $_SESSION['pesan_sukses_flash'] = "Data cuti untuk karyawan berhasil ditambahkan.";
                    header("Location: " . $_SERVER['PHP_SELF']); 
                    exit();
                } else {
                    $pesan_error = "Gagal menyimpan data ke database: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $pesan_error = "Gagal menyiapkan statement database: " . $conn->error;
            }
        }
    }
}

if (isset($_SESSION['pesan_sukses_flash'])) {
    $pesan_sukses = $_SESSION['pesan_sukses_flash'];
    unset($_SESSION['pesan_sukses_flash']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Cuti Manual - Admin - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/pengajuan-cuti-styles.css">
    <style>
        .form-sm-custom .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        .form-sm-custom .form-control,
        .form-sm-custom .form-select {
            font-size: 0.9rem;
        }
        .form-sm-custom .form-check-label {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Input Cuti Manual</h1>
                <p>Tambahkan data cuti karyawan yang disetujui di luar sistem.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-lg-8">

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

                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-plus-circle title-icon"></i>Formulir Cuti Manual</h5>
                                <div class="text-end no-print"> <button type="button" class="btn btn-success text-light" data-bs-toggle="modal" data-bs-target="#modalSyaratKetentuanCuti">
                                    <i class="fa-solid fa-book-open me-2"></i>Syarat & Ketentuan
                                        </button>
                                </div>
                            </div>
                            <div class="card-body p-lg-4">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data" class="form-sm-custom">
                                    <div class="row g-3">

                                        <div class="col-12">
                                            <label for="nip_karyawan" class="form-label">Pilih Karyawan <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="nip_karyawan" name="nip_karyawan" required>
                                                <option value="" disabled selected>-- Cari dan Pilih Nama Karyawan --</option>
                                                <?php foreach ($karyawan_list as $karyawan): ?>
                                                    <option value="<?php echo htmlspecialchars($karyawan['nip']); ?>">
                                                        <?php echo htmlspecialchars($karyawan['nama']) . ' (' . htmlspecialchars($karyawan['nip']) . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="tgl_mulai" class="form-label">Tanggal Mulai Cuti <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control form-control-sm" id="tgl_mulai" name="tgl_mulai" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tgl_selesai" class="form-label">Tanggal Selesai Cuti <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control form-control-sm" id="tgl_selesai" name="tgl_selesai" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="jenis_cuti" class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="jenis_cuti" name="jenis_cuti" required>
                                                <option value="">-- Pilih Jenis Cuti --</option>
                                                <option value="hak">Cuti Hak</option>
                                                <option value="khusus">Cuti Khusus</option>
                                                <option value="dipotong">Cuti Lainnya</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6" id="potongGajiWrapper" style="display: none;">
                                            <label class="form-label">Potong Gaji? <span class="text-danger">*</span></label>
                                            <div class="mt-1">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="potong_gaji" id="potongGajiYa" value="1">
                                                    <label class="form-check-label" for="potongGajiYa">Ya</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="potong_gaji" id="potongGajiTidak" value="0">
                                                    <label class="form-check-label" for="potongGajiTidak">Tidak</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="keterangan" class="form-label">Keterangan/Alasan Cuti <span class="text-danger">*</span></label>
                                            <textarea class="form-control form-control-sm" id="keterangan" name="keterangan" rows="4" required placeholder="Jelaskan alasan cuti atau berikan catatan..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="bukti" class="form-label">Lampiran Bukti (Opsional)</label>
                                            <input type="file" class="form-control form-control-sm" id="bukti" name="bukti" accept=".jpg, .jpeg, .png, .pdf, .doc, .docx">
                                        </div>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit" name="simpan_cuti_manual" class="btn btn-primary">
                                            <i class="fa-solid fa-save me-2"></i>Simpan Data Cuti
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            
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

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <script>
    $(document).ready(function() {
        $('#nip_karyawan').select2({
            theme: 'bootstrap-5',
            placeholder: $(this).data('placeholder'),
        });

        $('#tgl_mulai, #tgl_selesai').on('change', function() {
            const tglMulai = $('#tgl_mulai').val();
            const tglSelesai = $('#tgl_selesai').val();
            if (tglMulai) {
                $('#tgl_selesai').attr('min', tglMulai);
            }
            if (tglMulai && tglSelesai && (new Date(tglSelesai) < new Date(tglMulai))) {
                alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                $('#tgl_selesai').val(tglMulai);
            }
        });

        $('#jenis_cuti').on('change', function() {
            var jenis = $(this).val();
            var wrapper = $('#potongGajiWrapper');
            var radioYa = $('#potongGajiYa');
            var radioTidak = $('#potongGajiTidak');

            if (jenis === 'khusus') {
                wrapper.show();
                radioYa.prop('required', true);
                radioTidak.prop('required', true);
                radioYa.prop('checked', false);
                radioTidak.prop('checked', false);
            } else if (jenis === 'hak') {
                wrapper.hide();
                radioYa.prop('required', false);
                radioTidak.prop('required', false);
                radioTidak.prop('checked', true); 
            } else if (jenis === 'dipotong') {
                wrapper.hide();
                radioYa.prop('required', false);
                radioTidak.prop('required', false);
                radioYa.prop('checked', true); 
            } else {
                wrapper.hide();
                radioYa.prop('required', false);
                radioTidak.prop('required', false);
                radioTidak.prop('checked', false);
            }
        });
    });
    </script>
    <script>
        $(document).ready(function() {
            var currentPath = "<?php echo basename($_SERVER['PHP_SELF']); ?>";

            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                }
            });
            
            if (currentPath === "input_cuti_manual.php" && !$('.sidebar-menu a[href="input_cuti_manual.php"]').hasClass('active')) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="input_cuti_manual.php"]').addClass('active');
            }

            $('.custom-nav__link.active').removeClass('active');
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) {
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
    </script>
</body>
</html>