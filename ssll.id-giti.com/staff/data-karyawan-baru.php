<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan Baru - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Tambah Karyawan Baru</h1>
                <p>Isi formulir di bawah ini untuk mendaftarkan karyawan baru ke dalam sistem.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-lg-4">
                        
                        <?php if (isset($_SESSION['pesan_flash'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['pesan_flash']['tipe']; ?> alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['pesan_flash']['pesan']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['pesan_flash']); ?>
                        <?php endif; ?>

                        <form action="proses-tambah-data-karyawan.php" method="POST" enctype="multipart/form-data">
                            
                            <h6 class="mb-3">Data Pribadi & Identitas</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="nik" class="form-label">NIK (Nomor Induk Karyawan) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nik" name="nik" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tempatLahir" class="form-label">Tempat Lahir</label>
                                    <input type="text" class="form-control" id="tempatLahir" name="tempat_lahir">
                                </div>
                                <div class="col-md-6">
                                    <label for="tanggalLahir" class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="tanggalLahir" name="tanggal_lahir">
                                </div>
                                <div class="col-12">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                                </div>
                                 <div class="col-md-6">
                                    <label for="nomorKTP" class="form-label">Nomor KTP</label>
                                    <input type="text" class="form-control" id="nomorKTP" name="nomor_ktp">
                                </div>
                            </div>

                            <hr class="my-4">
                            
                            <h6 class="mb-3">Informasi Kontak</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nomorHP" class="form-label">Nomor Handphone</label>
                                    <input type="text" class="form-control" id="nomorHP" name="nomor_handphone">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                                <div class="col-md-6">
                                    <label for="nomorTelepon" class="form-label">Nomor Telepon (Rumah/Lainnya)</label>
                                    <input type="text" class="form-control" id="nomorTelepon" name="nomor_telepon">
                                </div>
                            </div>
                            
                            <hr class="my-4">

                            <h6 class="mb-3">Informasi Kepegawaian</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="idJabatan" class="form-label">Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="idJabatan" name="id_jabatan" required placeholder="Contoh: Staff IT">
                                </div>
                                 <div class="col-md-6">
                                    <label for="pin" class="form-label">PIN Absen <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="pin" name="pin" required placeholder="PIN dari mesin absensi">
                                </div>
                                <div class="col-md-6">
                                    <label for="tanggalMasuk" class="form-label">Tanggal Masuk</label>
                                    <input type="date" class="form-control" id="tanggalMasuk" name="tanggal_masuk">
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="mb-3">Informasi Bank</h6>
                             <div class="row g-3">
                                 <div class="col-md-4">
                                     <label for="namaBank" class="form-label">Nama Bank</label>
                                     <select class="form-select" id="namaBank" name="nama_bank">
                                        <option value="bca">Bank Central Asia (BCA)</option>
                                        <option value="mandiri">Bank Mandiri</option>
                                        <option value="bri">Bank Rakyat Indonesia (BRI)</option>
                                        <option value="bni">Bank Negara Indonesia (BNI)</option>
                                        <option value="btn">Bank Tabungan Negara (BTN)</option>
                                        <option value="cimb">CIMB Niaga</option>
                                        <option value="bsi">Bank Syariah Indonesia (BSI)</option>
                                        <option value="ocbc">OCBC NISP</option>
                                        <option value="panin">Bank Panin</option>
                                        <option value="danamon">Bank Danamon</option>
                                        <option value="bcablue">Blu by BCA</option>
                                        <option value="gopay">Gopay</option>
                                        <option value="ovo">OVO</option>
                                        <option value="link">Link Aja</option>
                                        <option value="dana">Dana</option>
                                     </select>
                                 </div>
                                 <div class="col-md-4">
                                     <label for="nomorRekening" class="form-label">Nomor Rekening</label>
                                     <input type="text" class="form-control" id="nomorRekening" name="nomor_rekening">
                                 </div>
                                 <div class="col-md-4">
                                     <label for="namaPemilikRekening" class="form-label">Atas Nama</label>
                                     <input type="text" class="form-control" id="namaPemilikRekening" name="nama_pemilik_rekening">
                                 </div>
                             </div>

                             <hr class="my-4">

                            <h6 class="mb-3">Unggah Dokumen</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="pas_photo" class="form-label">Pas Photo</label>
                                    <div class="mb-2 text-center">
                                        <img id="photo-preview" src="../assets/img/placeholder-avatar.png" alt="Preview Pas Photo" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                    <input class="form-control form-control-sm" type="file" id="pas_photo" name="pas_photo" accept="image/*">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="gambarKTP" class="form-label">Gambar KTP</label>
                                    <div class="mb-2 text-center">
                                        <img id="ktp-preview" src="../assets/img/placeholder-id.png" alt="Preview KTP" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                    <input class="form-control form-control-sm" type="file" id="gambarKTP" name="gambar_ktp" accept="image/*">
                                </div>
                            </div>


                            <div class="mt-4 pt-3 text-end border-top">
                                <a href="data-karyawan.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Tambah Karyawan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setupImagePreview(inputId, previewId) {
            const inputElement = document.getElementById(inputId);
            const previewElement = document.getElementById(previewId);

            inputElement.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    previewElement.src = URL.createObjectURL(file);
                    previewElement.onload = () => URL.revokeObjectURL(previewElement.src);
                }
            });
        }

        setupImagePreview('pas_photo', 'photo-preview');
        setupImagePreview('gambarKTP', 'ktp-preview');
    </script>
</body>
</html>