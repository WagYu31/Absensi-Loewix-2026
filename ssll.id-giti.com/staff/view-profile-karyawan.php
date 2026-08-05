<?php
session_start();

// PERUBAHAN: Memperbolehkan 'superadmin' dan 'admin' untuk mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// Validasi NIP dari URL
if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    die("Error: NIP karyawan tidak ditemukan.");
}
$nip = $_GET['nip'];

// PERUBAHAN: Menggunakan prepared statement untuk keamanan (mencegah SQL Injection)
$query = "SELECT * FROM karyawan WHERE nip = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $nip);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data karyawan dengan NIP tersebut tidak ditemukan.");
}
$data = $result->fetch_assoc();
$stmt->close();

// Include file helper setelah data karyawan didapatkan
// Ini asumsi file-file ini memerlukan variabel dari $data
if ($_SESSION['role'] === 'superadmin') {
    include 'get-tunjangan-masa-kerja.php'; // Asumsi file ini memerlukan $data
}
$namaBank = $data['nama_bank'];
include 'get-nama-bank.php'; // Asumsi file ini memerlukan $namaBank

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Karyawan: <?php echo htmlspecialchars($data['nama']); ?> - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    
    <style>
        .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #dee2e6;
        }
        .list-group-item strong {
            flex-basis: 40%; /* Memberi ruang yang konsisten untuk label */
        }
        .list-group-item span {
            text-align: right;
            flex-basis: 60%;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Profil Karyawan</h1>
                <p>Detail lengkap untuk <?php echo htmlspecialchars($data['nama']); ?></p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="row">
                    <div class="col-lg-4 col-md-5 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body text-center">
                                <img src="../uploads/<?php echo !empty($data['pas_photo']) ? htmlspecialchars($data['pas_photo']) : 'default-avatar.png'; ?>" 
                                     alt="Pas Photo" class="profile-pic mb-3">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($data['nama']); ?></h5>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($data['jabatan']); ?></p>
                                <p class="text-muted small">NIP: <?php echo htmlspecialchars($data['nip']); ?></p>
                                
                                <hr>
                                <div class="text-start small">
                                    <p class="mb-2"><i class="fa-solid fa-phone fa-fw me-2"></i>
                                        <a href="https://wa.me/<?php echo '62' . substr($data['nomor_handphone'], 1); ?>" target="_blank">
                                            <?php echo htmlspecialchars($data['nomor_handphone']); ?>
                                        </a>
                                    </p>
                                    <p class="mb-0"><i class="fa-solid fa-envelope fa-fw me-2"></i>
                                        <a href="mailto:<?php echo htmlspecialchars($data['email']); ?>">
                                            <?php echo htmlspecialchars($data['email']); ?>
                                        </a>
                                    </p>
                                </div>
                                <hr>
                                <?php 
                                // if ($_SESSION['role'] === 'superadmin'): 
                                ?>
                                    <a href="edit-profile-karyawan.php?nip=<?php echo htmlspecialchars($nip); ?>" class="btn btn-primary w-100">
                                        <i class="fa-solid fa-pencil me-2"></i>Edit Profil
                                    </a>
                                <?php 
                                // endif; 
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 col-md-7">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h6 class="mb-0">Data Diri</h6></div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <li class="list-group-item d-flex justify-content-between"><strong>NIK / PIN Absen</strong> <span><?php echo htmlspecialchars($data['nik']); ?> / <?php echo htmlspecialchars($data['pin_absen']); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Nomor KTP</strong> 
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#ktpModal" data-img-src="../uploads/<?php echo htmlspecialchars($data['gambar_ktp']); ?>">
                                        <?php echo htmlspecialchars($data['nomor_ktp']); ?> <i class="fa-solid fa-image fa-fw"></i>
                                    </a>
                                </li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Alamat</strong> <span style="text-transform:capitalize;"><?php echo htmlspecialchars($data['alamat']); ?></span></li>
                                <!--<li class="list-group-item d-flex justify-content-between"><strong>Email</strong> <span><?php echo htmlspecialchars($data['email']); ?></span></li>-->
                                <!--<li class="list-group-item d-flex justify-content-between"><strong>No. Telepon</strong> <span><?php echo htmlspecialchars($data['nomor_telepon']); ?></span></li>-->
                            </ul>
                        </div>
                        
                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h6 class="mb-0">Informasi Kepegawaian</h6></div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Status Karyawan</strong> <div><span class="badge bg-<?php echo $data['status_karyawan'] === 'aktif' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars(ucfirst($data['status_karyawan'])); ?></span></div></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Tanggal Masuk</strong> <span><?php echo date('d F Y', strtotime($data['tanggal_masuk'])); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Jabatan</strong> <span><?php echo htmlspecialchars($data['jabatan']); ?></span></li>
                            </ul>
                        </div>
                        
                        <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h6 class="mb-0"><i class="fa-solid fa-lock me-2"></i>Informasi Finansial (Superadmin)</h6></div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <li class="list-group-item d-flex justify-content-between"><strong>Gaji Pokok</strong> <span>Rp <?php echo number_format($data['gaji_pokok'], 0, ',', '.'); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Tunjangan Jabatan</strong> <span>Rp <?php echo number_format($data['tunjangan'], 0, ',', '.'); ?></span></li>
                                <?php include 'get-tunjangan-masa-kerja.php';?>
                                <li class="list-group-item d-flex justify-content-between"><strong>Tunjangan Masa Kerja</strong> <span>Rp <?php echo isset($dataTMK) ? number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.') : 'N/A'; ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Jenis Pembayaran</strong> <span style="text-transform:capitalize;"><?php echo $data['jenis_gaji'] === 'mingguan' ? 'Mingguan - Gaji 1: Rp ' . number_format($data['gaji_1'], 0, ',', '.') : htmlspecialchars(ucfirst($data['jenis_gaji'])); ?></span></li>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h6 class="mb-0">Akun Bank</h6></div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <?php include 'get-nama-bank.php';?>
                                <li class="list-group-item d-flex justify-content-between"><strong>Nama Bank</strong> <span><?php echo isset($nmbank) ? htmlspecialchars($nmbank) : 'N/A'; ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Nomor Rekening</strong> <span><?php echo htmlspecialchars($data['nomor_rekening']); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Atas Nama</strong> <span><?php echo htmlspecialchars($data['nama_pemilik_rekening']); ?></span></li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ktpModal" tabindex="-1" aria-labelledby="ktpModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ktpModalLabel">Gambar KTP</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <img id="modalKtpImage" src="" class="img-fluid" alt="Gambar KTP">
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Script untuk menampilkan gambar di modal
    const ktpModal = document.getElementById('ktpModal');
    ktpModal.addEventListener('show.bs.modal', event => {
      // Tombol yang memicu modal
      const button = event.relatedTarget;
      // Ekstrak info dari atribut data-img-src
      const imgSrc = button.getAttribute('data-img-src');
      // Update konten modal
      const modalImage = ktpModal.querySelector('#modalKtpImage');
      modalImage.src = imgSrc;
    });
    </script>
</body>
</html>