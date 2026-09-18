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

// Query Akun Login dari tabel users
$stmt_usr = $conn->prepare("SELECT username, role FROM users WHERE nip = ?");
$stmt_usr->bind_param("s", $nip);
$stmt_usr->execute();
$res_usr = $stmt_usr->get_result();
$userData = ($res_usr->num_rows > 0) ? $res_usr->fetch_assoc() : null;
$stmt_usr->close();

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
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($data['jabatan'] ?? '-'); ?></p>
                                <p class="text-muted small">NIP: <?php echo htmlspecialchars($data['nip'] ?? '-'); ?></p>
                                
                                <hr>
                                <div class="text-start small">
                                    <p class="mb-2"><i class="fa-solid fa-phone fa-fw me-2"></i>
                                        <?php if (!empty($data['nomor_handphone'])): ?>
                                            <a href="https://wa.me/<?php echo '62' . substr($data['nomor_handphone'], 1); ?>" target="_blank">
                                                <?php echo htmlspecialchars($data['nomor_handphone']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-0"><i class="fa-solid fa-envelope fa-fw me-2"></i>
                                        <?php if (!empty($data['email'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($data['email']); ?>">
                                                <?php echo htmlspecialchars($data['email']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
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
                                <li class="list-group-item d-flex justify-content-between"><strong>NIK / PIN Absen</strong> <span><?php echo htmlspecialchars($data['nik'] ?? '-'); ?> / <?php echo htmlspecialchars($data['pin_absen'] ?? '-'); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Nomor KTP</strong> 
                                    <?php if (!empty($data['nomor_ktp'])): ?>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#ktpModal" data-img-src="../uploads/<?php echo htmlspecialchars($data['gambar_ktp'] ?? 'default.png'); ?>">
                                            <?php echo htmlspecialchars($data['nomor_ktp']); ?> <i class="fa-solid fa-image fa-fw"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Alamat</strong> <span style="text-transform:capitalize;"><?php echo htmlspecialchars($data['alamat'] ?? '-'); ?></span></li>
                            </ul>
                        </div>
                        
                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h6 class="mb-0">Informasi Kepegawaian</h6></div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Status Karyawan</strong> <div><span class="badge bg-<?php echo ($data['status_karyawan'] ?? '') === 'aktif' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars(ucfirst($data['status_karyawan'] ?? 'aktif')); ?></span></div></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Tanggal Masuk</strong> <span><?php echo (!empty($data['tanggal_masuk']) && $data['tanggal_masuk'] !== '0000-00-00') ? date('d F Y', strtotime($data['tanggal_masuk'])) : '-'; ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Jabatan</strong> <span><?php echo htmlspecialchars($data['jabatan'] ?? '-'); ?></span></li>
                            </ul>
                        </div>
                        
                        <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h6 class="mb-0"><i class="fa-solid fa-lock me-2"></i>Informasi Finansial (Superadmin)</h6></div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <li class="list-group-item d-flex justify-content-between"><strong>Gaji Pokok</strong> <span>Rp <?php echo number_format($data['gaji_pokok'] ?? 0, 0, ',', '.'); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Tunjangan Jabatan</strong> <span>Rp <?php echo number_format($data['tunjangan'] ?? 0, 0, ',', '.'); ?></span></li>
                                <?php include 'get-tunjangan-masa-kerja.php';?>
                                <li class="list-group-item d-flex justify-content-between"><strong>Tunjangan Masa Kerja</strong> <span>Rp <?php echo isset($dataTMK) ? number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.') : '0'; ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Jenis Pembayaran</strong> <span style="text-transform:capitalize;"><?php echo ($data['jenis_gaji'] ?? '') === 'mingguan' ? 'Mingguan - Gaji 1: Rp ' . number_format($data['gaji_1'] ?? 0, 0, ',', '.') : htmlspecialchars(ucfirst($data['jenis_gaji'] ?? '-')); ?></span></li>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="card shadow-sm mb-4">
                            <div class="card-header"><h6 class="mb-0">Akun Bank</h6></div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <?php include 'get-nama-bank.php';?>
                                <li class="list-group-item d-flex justify-content-between"><strong>Nama Bank</strong> <span><?php echo isset($nmbank) ? htmlspecialchars($nmbank) : 'Bank Tidak Terdaftar'; ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Nomor Rekening</strong> <span><?php echo htmlspecialchars($data['nomor_rekening'] ?? '-'); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Atas Nama</strong> <span><?php echo htmlspecialchars($data['nama_pemilik_rekening'] ?? '-'); ?></span></li>
                            </ul>
                        </div>

                        <div class="card shadow-sm mb-4 border-0 rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08) !important;">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-key me-2 text-warning"></i>Akun Login & Keamanan</h6>
                                <?php if ($userData): ?>
                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="fa-solid fa-check me-1"></i>Akun Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Belum Ada Akun</span>
                                <?php endif; ?>
                            </div>
                            <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <strong>Username Login</strong> 
                                    <span class="fw-bold font-monospace text-primary fs-6"><?php echo htmlspecialchars($userData['username'] ?? '-'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <strong>Role Hak Akses</strong> 
                                    <span class="badge bg-light text-dark border fw-bold text-capitalize px-2 py-1"><?php echo htmlspecialchars($userData['role'] ?? 'karyawan'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-3 bg-light">
                                    <strong>Aksi Akun</strong>
                                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3 rounded-3" onclick="openAccountModal('<?php echo $nip; ?>', '<?php echo htmlspecialchars(addslashes($data['nama'])); ?>')">
                                        <i class="fa-solid fa-key me-1"></i> <?php echo $userData ? 'Reset Password / Ubah Akun' : 'Buat Akun Login'; ?>
                                    </button>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Kelola Akun Login & Reset Password -->
    <div class="modal fade" id="modalKelolaAkun" tabindex="-1" aria-labelledby="modalKelolaAkunLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e293b, #0f172a); padding: 1.25rem 1.5rem;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(245, 158, 11, 0.2); color: #f59e0b;">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalKelolaAkunLabel" style="font-size: 1.1rem;">Kelola Akun Login</h5>
                            <small class="text-white-50" id="modalSubtitle">Atur Username & Password Karyawan</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background: #f8fafc;">
                    <div id="accountLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0 small">Mengambil data akun...</p>
                    </div>

                    <form id="formKelolaAkun" style="display: none;" onsubmit="saveUserAccount(event)">
                        <input type="hidden" id="accNip" name="nip" value="<?php echo htmlspecialchars($nip); ?>">

                        <div class="alert alert-info py-2 px-3 rounded-3 small mb-3 border-0" style="background: #e0f2fe; color: #0369a1;">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Nama: <strong id="accNama"><?php echo htmlspecialchars($data['nama']); ?></strong></span>
                                <span>NIK: <strong id="accNik"><?php echo htmlspecialchars($data['nik']); ?></strong></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>NIP: <strong id="accNipDisplay"><?php echo htmlspecialchars($nip); ?></strong></span>
                                <span id="accStatusBadge">-</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">USERNAME</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0 fw-bold" id="accUsername" name="username" required placeholder="Masukkan username login">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small text-secondary mb-0">PASSWORD BARU</label>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 rounded-2" style="font-size: 0.72rem;" onclick="$('#accPassword').val('12345678')">Preset: 12345678</button>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0 fw-bold" id="accPassword" name="password" placeholder="Kosongkan jika tidak ingin ubah password">
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;" id="passHelpText">Isi password baru untuk mereset kata sandi akun ini.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">ROLE HAK AKSES</label>
                            <select class="form-select fw-semibold" id="accRole" name="role">
                                <option value="karyawan">Karyawan (Akses Portal Absensi Karyawan)</option>
                                <option value="admin">Admin (Akses Dashboard Staff / Admin)</option>
                            </select>
                        </div>

                        <div id="accAlert" class="alert d-none py-2 small mb-3"></div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-light fw-bold px-3" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" id="btnSaveAccount" class="btn btn-primary fw-bold px-4" style="border-radius: 10px;">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
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
    if (ktpModal) {
        ktpModal.addEventListener('show.bs.modal', event => {
          const button = event.relatedTarget;
          const imgSrc = button.getAttribute('data-img-src');
          const modalImage = ktpModal.querySelector('#modalKtpImage');
          modalImage.src = imgSrc;
        });
    }

    function openAccountModal(nip, nama) {
        $('#accAlert').addClass('d-none').removeClass('alert-success alert-danger');
        $('#accountLoading').show();
        $('#formKelolaAkun').hide();
        $('#modalKelolaAkun').modal('show');

        $.ajax({
            url: 'api_manage_user_account.php',
            type: 'GET',
            data: { action: 'get_account', nip: nip },
            dataType: 'json',
            success: function(res) {
                $('#accountLoading').hide();
                if (res.status === 'success') {
                    const data = res.data;
                    $('#accNip').val(data.nip);
                    $('#accNipDisplay').text(data.nip);
                    $('#accNama').text(data.nama);
                    $('#accNik').text(data.nik);
                    $('#accUsername').val(data.username || data.nama.toLowerCase().replace(/[^a-z0-9]/g, ''));
                    $('#accPassword').val('');
                    $('#accRole').val(data.role || 'karyawan');

                    if (data.has_account) {
                        $('#accStatusBadge').html('<span class="badge bg-success-subtle text-success border border-success">Akun Aktif</span>');
                        $('#passHelpText').text('Kosongkan jika hanya ingin melihat/mengubah username tanpa ganti password.');
                    } else {
                        $('#accStatusBadge').html('<span class="badge bg-warning-subtle text-warning border border-warning">Belum Ada Akun</span>');
                        $('#passHelpText').text('Karyawan ini belum memiliki akun. Masukkan password untuk membuatnya.');
                        $('#accPassword').val('12345678');
                    }
                    $('#formKelolaAkun').show();
                } else {
                    alert('Gagal mengambil data: ' + res.message);
                    $('#modalKelolaAkun').modal('hide');
                }
            },
            error: function() {
                $('#accountLoading').hide();
                alert('Terjadi kesalahan koneksi saat mengambil data akun.');
                $('#modalKelolaAkun').modal('hide');
            }
        });
    }

    function saveUserAccount(e) {
        e.preventDefault();
        const btn = $('#btnSaveAccount');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');
        $('#accAlert').addClass('d-none');

        const formData = {
            action: 'save_account',
            nip: $('#accNip').val(),
            username: $('#accUsername').val(),
            password: $('#accPassword').val(),
            role: $('#accRole').val()
        };

        $.ajax({
            url: 'api_manage_user_account.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan');
                if (res.status === 'success') {
                    $('#accAlert').removeClass('d-none alert-danger').addClass('alert-success').html('<i class="fa-solid fa-circle-check me-1"></i> ' + res.message);
                    $('#accStatusBadge').html('<span class="badge bg-success-subtle text-success border border-success">Akun Aktif</span>');
                    setTimeout(function() {
                        location.reload();
                    }, 1200);
                } else {
                    $('#accAlert').removeClass('d-none alert-success').addClass('alert-danger').html('<i class="fa-solid fa-circle-exclamation me-1"></i> ' + res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan');
                $('#accAlert').removeClass('d-none alert-success').addClass('alert-danger').html('<i class="fa-solid fa-circle-exclamation me-1"></i> Terjadi kesalahan koneksi server.');
            }
        });
    }
    </script>
</body>
</html>