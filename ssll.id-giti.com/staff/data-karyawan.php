<?php
session_start();

// Cek keamanan: Hanya superadmin yang bisa akses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// --- PERUBAHAN: Fungsi diubah menjadi Soft Delete ---
function deleteKaryawan($conn, $nip) {
    // 1. Set timezone ke Jakarta
    date_default_timezone_set('Asia/Jakarta');
    // 2. Dapatkan waktu saat ini dalam format yang sesuai untuk MySQL
    $deleted_at_time = date('Y-m-d H:i:s');

    // 3. Siapkan query UPDATE untuk soft delete
    // Selain mengisi deleted_at, kita juga set status_karyawan menjadi 'tidak aktif' untuk konsistensi
    $query = "UPDATE karyawan SET deleted_at = ?, status_karyawan = 'tidak aktif' WHERE nip = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        // 4. Bind parameter waktu dan NIP
        $stmt->bind_param("ss", $deleted_at_time, $nip);

        if ($stmt->execute()) {
            // Cek apakah ada baris yang terpengaruh
            if ($stmt->affected_rows > 0) {
                $message = "Data karyawan dengan NIP $nip telah berhasil dinonaktifkan dan diarsipkan.";
            } else {
                $message = "Tidak ada data karyawan yang ditemukan dengan NIP $nip.";
            }
            echo "<script>alert('$message'); window.location.href = 'data-karyawan.php';</script>";
        } else {
            $message = "Terjadi kesalahan saat mengarsipkan data: " . $stmt->error;
            echo "<script>alert('$message'); window.location.href = 'data-karyawan.php';</script>";
        }
        $stmt->close();
    } else {
        $message = "Gagal mempersiapkan query: " . $conn->error;
        echo "<script>alert('$message'); window.location.href = 'data-karyawan.php';</script>";
    }
}

// Memeriksa apakah ada permintaan hapus
if (isset($_GET['deleteNIP'])) {
    deleteKaryawan($conn, $_GET['deleteNIP']);
    exit(); // Hentikan eksekusi setelah redirect
}

// --- Ambil data karyawan ---
$query = "SELECT nik, nama, jabatan, tanggal_masuk, nomor_handphone, alamat, status_karyawan, nip, gaji_pokok FROM karyawan WHERE deleted_at IS NULL ORDER BY nama ASC";
$result = $conn->query($query);
if (!$result) {
    die("Query gagal dieksekusi: " . $conn->error);
}
$karyawanData = $result->fetch_all(MYSQLI_ASSOC);

// Cek apakah ada karyawan dengan gaji 0
$query_check_zero_gaji = "SELECT COUNT(*) AS count FROM karyawan WHERE gaji_pokok = 0 AND nip NOT IN ('001', '70326') AND deleted_at IS NULL";
$result_check_zero_gaji = $conn->query($query_check_zero_gaji);
$zero_gaji_count = 0;
if ($result_check_zero_gaji) {
    $zero_gaji_count = $result_check_zero_gaji->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan - Superadmin - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    
    <style>
        /* Style untuk highlight baris karyawan yang gajinya 0 */
        .table-hover .highlight-gaji-nol td,
        .table-hover .highlight-gaji-nol:hover td {
            background-color: #fff8e1; /* Warna kuning muda */
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; // Menggunakan sidebar modern yang konsisten ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Data Karyawan</h1>
                <p>Kelola seluruh data karyawan yang terdaftar di sistem.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <?php if ($zero_gaji_count > 0): ?>
                <div class="alert alert-warning d-flex align-items-center justify-content-between" role="alert">
                    <div>
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Terdapat <strong><?php echo $zero_gaji_count; ?> karyawan</strong> yang belum memiliki gaji pokok.
                    </div>
                    <a href="input-gaji.php" class="btn btn-sm btn-dark">Input Gaji Sekarang</a>
                </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-users title-icon"></i>Daftar Karyawan Aktif & Non-Aktif</h5>
                        <a href="data-karyawan-baru.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-2"></i>Tambah Karyawan</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Tgl. Masuk</th>
                                        <th>No. Handphone</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($karyawanData)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-4">Belum ada data karyawan.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($karyawanData as $karyawan) :
                                        if($karyawan['nip'] != '001' && $karyawan['nip'] != '70326') :
                                            // Menambahkan class jika gaji_pokok 0
                                            $highlightClass = ($karyawan['gaji_pokok'] == '0.00' || $karyawan['gaji_pokok'] == 0) ? 'highlight-gaji-nol' : '';
                                    ?>
                                        <tr class="<?php echo $highlightClass; ?>">
                                            <td><?php echo htmlspecialchars($karyawan['nik']); ?></td>
                                            <td style="text-transform:capitalize;"><?php echo htmlspecialchars($karyawan['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($karyawan['jabatan']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($karyawan['tanggal_masuk'])); ?></td>
                                            <td>
                                                <?php
                                                $nomorHandphone = $karyawan['nomor_handphone'];
                                                $waLink = 'https://api.whatsapp.com/send?phone=' . (substr($nomorHandphone, 0, 1) === '0' ? '62' . substr($nomorHandphone, 1) : $nomorHandphone);
                                                ?>
                                                <a href="<?php echo $waLink; ?>" target="_blank"><?php echo htmlspecialchars($karyawan['nomor_handphone']); ?></a>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input" type="checkbox" role="switch" 
                                                           onchange="updateStatus('<?php echo $karyawan['nip']; ?>', this)" 
                                                           <?php if ($karyawan['status_karyawan'] === 'aktif') echo 'checked'; ?>>
                                                    <label class="form-check-label" style="text-transform:capitalize;"><?php echo htmlspecialchars($karyawan['status_karyawan']); ?></label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="view-profile-karyawan.php?nip=<?php echo $karyawan['nip']; ?>" class="btn btn-info btn-sm" title="Lihat Profil">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <button onclick="deleteKaryawan('<?php echo $karyawan['nip']; ?>')" class="btn btn-danger btn-sm" title="Hapus Karyawan">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php 
                                        endif;
                                    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // FUNGSI INI TETAP SAMA KARENA PROSESNYA DIMINTA TIDAK BERUBAH
    function deleteKaryawan(nip) {
        if (confirm("Apakah Anda yakin ingin menghapus seluruh data karyawan dengan NIP " + nip + "?\n\nTindakan ini tidak dapat diurungkan!")) {
            window.location.href = "data-karyawan.php?deleteNIP=" + nip;
        }
    }

    // FUNGSI INI TETAP SAMA KARENA PROSESNYA DIMINTA TIDAK BERUBAH
    function updateStatus(nip, checkbox) {
        var status = checkbox.checked ? 'aktif' : 'tidak aktif';
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                // Proses berhasil, refresh halaman untuk melihat perubahan
                location.reload(); 
            }
        };
        // Asumsi ada file sa-update-status-karyawan.php untuk memproses ini
        xhttp.open("GET", "update-status-karyawan.php?nip=" + nip + "&status=" + status, true);
        xhttp.send();
    }
    </script>
</body>
</html>