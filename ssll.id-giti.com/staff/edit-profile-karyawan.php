<?php
session_start();

// --- PERUBAHAN HAK AKSES: Memperbolehkan admin dan superadmin ---
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    die("Error: NIP karyawan tidak ditemukan.");
}
$nip = $_GET['nip'];

// --- PERUBAHAN HAK AKSES: Buat variabel untuk mengecek peran ---
$is_superadmin = ($_SESSION['role'] === 'superadmin');

// --- PROSES UPDATE DATA (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fields = [
        'nama', 'nik', 'jabatan', 'gaji_pokok', 'jenis_gaji', 'gaji_1', 'tunjangan', 
        'tempat_lahir', 'tanggal_lahir', 'alamat', 'tanggal_masuk', 'email', 
        'nomor_handphone', 'nomor_telepon', 'nomor_ktp', 'nama_bank', 
        'nomor_rekening', 'nama_pemilik_rekening'
    ];

    // --- PERUBAHAN HAK AKSES: Definisikan field yang hanya bisa diubah superadmin ---
    $restricted_fields = ['gaji_pokok', 'jenis_gaji', 'gaji_1', 'tunjangan'];
    
    $sql_parts = [];
    $params = [];
    $types = '';

    foreach ($fields as $field) {
        // --- PERUBAHAN HAK AKSES: Lewati field terlarang jika bukan superadmin ---
        if (!$is_superadmin && in_array($field, $restricted_fields)) {
            continue; // Lanjut ke field berikutnya
        }
        
        $sql_parts[] = "$field = ?";
        $post_key = ($field === 'gaji_1') ? 'gaji_1' : $field;
        $params[] = $_POST[$post_key];
        $types .= 's';
    }

    $new_ktp_filename = null;
    if (isset($_FILES['gambar_ktp']) && $_FILES['gambar_ktp']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        $file_ext = strtolower(pathinfo($_FILES['gambar_ktp']['name'], PATHINFO_EXTENSION));
        $new_ktp_filename = "ktp_" . $nip . "_" . time() . "." . $file_ext;
        
        if (move_uploaded_file($_FILES['gambar_ktp']['tmp_name'], $target_dir . $new_ktp_filename)) {
            $sql_parts[] = "gambar_ktp = ?";
            $params[] = $new_ktp_filename;
            $types .= 's';
        }
    }

    if (!empty($sql_parts)) {
        $query = "UPDATE karyawan SET " . implode(', ', $sql_parts) . " WHERE nip = ?";
        $params[] = $nip;
        $types .= 's';
        
        $stmt_update = $conn->prepare($query);
        if ($stmt_update) {
            $stmt_update->bind_param($types, ...$params);
            
            if ($stmt_update->execute()) {
                $message = "Perubahan berhasil disimpan.";
                // --- PERUBAHAN: Redirect ke halaman profil yang sudah modern ---
                echo "<script>alert('$message'); window.location.href = 'view-profile-karyawan.php?nip=$nip';</script>";
                exit();
            } else { $message = "Update Gagal! Terjadi kesalahan database."; }
        } else { $message = "Update Gagal! Gagal mempersiapkan query."; }
    } else { $message = "Tidak ada data untuk diupdate."; }
    
    echo "<script>alert('$message'); window.location.href = 'edit-profile-karyawan.php?nip=$nip';</script>";
    exit();
}

// --- AMBIL DATA UNTUK DITAMPILKAN DI FORM (GET REQUEST) ---
$query_get = "SELECT * FROM karyawan WHERE nip = ?";
$stmt_get = $conn->prepare($query_get);
$stmt_get->bind_param("s", $nip);
$stmt_get->execute();
$result = $stmt_get->get_result();

if ($result->num_rows === 0) {
    die("Data karyawan dengan NIP tersebut tidak ditemukan.");
}
$karyawan = $result->fetch_assoc();
$stmt_get->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil: <?php echo htmlspecialchars($karyawan['nama']); ?> - Grav-Tech</title>
    
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
                <h1>Edit Profil Karyawan</h1>
                <p>Mengubah data untuk <?php echo htmlspecialchars($karyawan['nama']); ?></p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-lg-4">
                        <form action="edit-profile-karyawan.php?nip=<?php echo htmlspecialchars($nip); ?>" method="POST" enctype="multipart/form-data">
                            
                            <h6 class="mb-3">Data Pribadi</h6>
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($karyawan['nama']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nik" class="form-label">NIK (Nomor Induk Karyawan)</label>
                                    <input type="text" class="form-control" id="nik" name="nik" value="<?php echo htmlspecialchars($karyawan['nik']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" value="<?php echo htmlspecialchars($karyawan['tempat_lahir']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo htmlspecialchars($karyawan['tanggal_lahir']); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3"><?php echo htmlspecialchars($karyawan['alamat']); ?></textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="mb-3">Informasi Kontak & Identitas</h6>
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="nomor_handphone" class="form-label">Nomor Handphone</label>
                                    <input type="text" class="form-control" id="nomor_handphone" name="nomor_handphone" value="<?php echo htmlspecialchars($karyawan['nomor_handphone']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Alamat Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($karyawan['email']); ?>">
                                </div>
                                 <div class="col-md-6 mb-3">
                                    <label for="nomor_ktp" class="form-label">Nomor KTP</label>
                                    <input type="text" class="form-control" id="nomor_ktp" name="nomor_ktp" value="<?php echo htmlspecialchars($karyawan['nomor_ktp']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gambar_ktp" class="form-label">Upload Gambar KTP Baru</label>
                                    
                                    <div class="mb-2">
                                        <?php if (!empty($karyawan['gambar_ktp'])): ?>
                                            <img id="ktp-preview" src="../uploads/<?php echo htmlspecialchars($karyawan['gambar_ktp']); ?>" alt="KTP Saat Ini" class="img-thumbnail" style="max-height: 150px;">
                                        <?php else: ?>
                                            <img id="ktp-preview" src="../assets/img/placeholder-id.png" alt="Preview KTP" class="img-thumbnail" style="max-height: 150px; filter: grayscale(80%);">
                                        <?php endif; ?>
                                    </div>
                                
                                    <input class="form-control form-control-sm" type="file" id="gambar_ktp" name="gambar_ktp" accept="image/*">
                                    <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah gambar KTP saat ini.</small>
                                </div>
                            </div>

                            <hr class="my-4">

                             <h6 class="mb-3">Informasi Kepegawaian & Finansial</h6>
                            <?php if (!$is_superadmin): ?>
                                <div class="alert alert-warning small p-2"><i class="fa-solid fa-lock me-2"></i>Beberapa field di bawah ini hanya bisa diubah oleh Superadmin.</div>
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="jabatan" class="form-label">Jabatan</label>
                                    <input type="text" class="form-control" id="jabatan" name="jabatan" value="<?php echo htmlspecialchars($karyawan['jabatan']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" value="<?php echo htmlspecialchars($karyawan['tanggal_masuk']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gaji_pokok" class="form-label">Gaji Pokok</label>
                                    <input type="number" class="form-control" id="gaji_pokok" name="gaji_pokok" value="<?php echo htmlspecialchars($karyawan['gaji_pokok']); ?>" <?php echo !$is_superadmin ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tunjangan" class="form-label">Tunjangan Jabatan</label>
                                    <input type="number" class="form-control" id="tunjangan" name="tunjangan" value="<?php echo htmlspecialchars($karyawan['tunjangan']); ?>" <?php echo !$is_superadmin ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="jenis_gaji" class="form-label">Jenis Pembayaran</label>
                                    <select class="form-select" id="jenis_gaji" name="jenis_gaji" <?php echo !$is_superadmin ? 'disabled' : ''; ?>>
                                        <option value="bulanan" <?php if ($karyawan['jenis_gaji'] === 'bulanan') echo 'selected'; ?>>Bulanan</option>
                                        <option value="mingguan" <?php if ($karyawan['jenis_gaji'] === 'mingguan') echo 'selected'; ?>>Mingguan</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="gaji_mingguan_field" style="display: <?php echo $karyawan['jenis_gaji'] === 'mingguan' ? 'block' : 'none'; ?>;">
                                    <label for="gaji_1" class="form-label">Gaji Minggu ke-2</label>
                                    <input type="number" class="form-control" id="gaji_1" name="gaji_1" value="<?php echo htmlspecialchars($karyawan['gaji_1']); ?>" <?php echo !$is_superadmin ? 'disabled' : ''; ?>>
                                    <small class="form-text text-muted">* Isi 0 jika pembayaran bulanan.</small>
                                </div>
                            </div>
                            
                            <hr class="my-4">

                            <h6 class="mb-3">Akun Bank</h6>
                             <div class="row g-3">
                                 <div class="col-md-4 mb-3">
                                     <label for="nama_bank" class="form-label">Nama Bank</label>
                                     <select class="form-select" id="nama_bank" name="nama_bank">
                                         <option value="bca" <?php if ($karyawan['nama_bank'] === 'bca') echo 'selected'; ?>>BCA</option>
                                         <option value="mandiri" <?php if ($karyawan['nama_bank'] === 'mandiri') echo 'selected'; ?>>Mandiri</option>
                                         <option value="bri" <?php if ($karyawan['nama_bank'] === 'bri') echo 'selected'; ?>>BRI</option>
                                         <option value="bni" <?php if ($karyawan['nama_bank'] === 'bni') echo 'selected'; ?>>BNI</option>
                                         </select>
                                 </div>
                                 <div class="col-md-4 mb-3">
                                     <label for="nomor_rekening" class="form-label">Nomor Rekening</label>
                                     <input type="text" class="form-control" id="nomor_rekening" name="nomor_rekening" value="<?php echo htmlspecialchars($karyawan['nomor_rekening']); ?>">
                                 </div>
                                 <div class="col-md-4 mb-3">
                                     <label for="nama_pemilik_rekening" class="form-label">Atas Nama</label>
                                     <input type="text" class="form-control" id="nama_pemilik_rekening" name="nama_pemilik_rekening" value="<?php echo htmlspecialchars($karyawan['nama_pemilik_rekening']); ?>">
                                 </div>
                             </div>

                            <div class="mt-4 text-end">
                                <a href="profil_karyawan.php?nip=<?php echo htmlspecialchars($nip); ?>" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i>Simpan Perubahan</button>
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
        // Script untuk menampilkan/menyembunyikan field gaji mingguan
        document.getElementById('jenis_gaji').addEventListener('change', function() {
            var gajiMingguanField = document.getElementById('gaji_mingguan_field');
            if (this.value === 'mingguan') {
                gajiMingguanField.style.display = 'block';
            } else {
                gajiMingguanField.style.display = 'none';
                document.getElementById('gaji_1').value = 0; // Set ke 0 saat pilihan bulanan
            }
        });

        // --- PERBAIKAN: Script baru untuk live preview gambar KTP ---
        document.getElementById('gambar_ktp').addEventListener('change', function(event) {
            const previewImage = document.getElementById('ktp-preview');
            const file = event.target.files[0];
    
            if (file) {
                // Buat URL sementara untuk file yang baru dipilih
                previewImage.src = URL.createObjectURL(file);
                
                // Hapus filter grayscale jika ada
                previewImage.style.filter = 'none';
    
                // Optional: Hapus object URL dari memori browser setelah gambar dimuat
                previewImage.onload = function() {
                    URL.revokeObjectURL(previewImage.src);
                }
            }
        });
    </script>
</body>
</html>