<?php
session_start();

// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// Variabel untuk menampung pesan notifikasi
$pesan_notifikasi = null;

// =========================================================================
// PROSES UPLOAD DAN IMPORT FILE (Logika Inti Tetap Sama)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"])) {
    if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0775, true);
        }
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validasi tipe file
        if ($fileType != "xls" && $fileType != "xlsx") {
            $pesan_notifikasi = ['tipe' => 'danger', 'pesan' => 'Upload Gagal! Hanya file Excel (.xls, .xlsx) yang diizinkan.'];
        } else {
            // Pindahkan file yang diunggah
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                
                // Proses pembacaan file Excel dan impor ke database
                // Menggunakan library PHPExcel yang sudah Anda gunakan
                require_once 'PHPExcel/Classes/PHPExcel.php';
                
                try {
                    $objPHPExcel = PHPExcel_IOFactory::load($target_file);
                    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                    
                    // PERBAIKAN: Menggunakan prepared statement untuk keamanan
                    $stmt = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama, jabatan, departemen, kantor, verifikasi, io, workcode, sn, mesin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $rowCount = 0;
                    $importedCount = 0;
                    foreach ($sheetData as $row) {
                        $rowCount++;
                        if ($rowCount <= 2) continue; // Lewati 2 baris header

                        // Bind parameter ke statement
                        $stmt->bind_param("ssssssssssssss", $row['A'], $row['B'], $row['C'], $row['D'], $row['E'], $row['F'], $row['G'], $row['H'], $row['I'], $row['J'], $row['K'], $row['L'], $row['M'], $row['N']);
                        
                        if ($stmt->execute()) {
                            $importedCount++;
                        }
                    }
                    $stmt->close();
                    $pesan_notifikasi = ['tipe' => 'success', 'pesan' => "Upload Berhasil! File " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " telah diproses. Sebanyak " . $importedCount . " baris data berhasil diimpor."];

                } catch (Exception $e) {
                    $pesan_notifikasi = ['tipe' => 'danger', 'pesan' => 'Terjadi error saat membaca file Excel: ' . $e->getMessage()];
                }
            } else {
                $pesan_notifikasi = ['tipe' => 'danger', 'pesan' => 'Maaf, terjadi kesalahan saat mengunggah file.'];
            }
        }
    } else {
        $pesan_notifikasi = ['tipe' => 'warning', 'pesan' => 'Silakan pilih file untuk diunggah terlebih dahulu.'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unggah Absensi - Grav-Tech</title>
    
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
                <h1>Unggah File Absensi</h1>
                <p>Impor data absensi karyawan dari file Excel mesin absensi.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-lg-8">

                        <?php if ($pesan_notifikasi): ?>
                        <div class="alert alert-<?php echo $pesan_notifikasi['tipe']; ?> alert-dismissible fade show" role="alert">
                            <?php echo $pesan_notifikasi['pesan']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fa-solid fa-circle-info title-icon"></i> Petunjuk Pengunggahan</h5>
                            </div>
                            <div class="card-body">
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Pastikan file data absensi dari mesin berformat Excel (**.xls** atau **.xlsx**).</li>
                                    <li class="list-group-item">Jika file masih berformat `.xls`, buka file tersebut lalu simpan ulang sebagai `.xlsx` untuk kompatibilitas terbaik.</li>
                                    <li class="list-group-item">Beri nama file yang jelas, contoh: **Absen-Juni-2025.xlsx**.</li>
                                    <li class="list-group-item">Pilih file pada form di bawah ini lalu klik tombol "Unggah File".</li>
                                </ol>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                             <div class="card-header">
                                <h5 class="mb-0"><i class="fa-solid fa-upload title-icon"></i>Formulir Unggah</h5>
                             </div>
                             <div class="card-body">
                                 <form action="upload-absen.php" method="post" enctype="multipart/form-data">
                                     <div class="mb-3">
                                         <label for="fileToUpload" class="form-label">Pilih file Excel Absensi:</label>
                                         <input class="form-control" type="file" name="fileToUpload" id="fileToUpload" required>
                                     </div>
                                     <div class="d-grid">
                                        <button type="submit" value="Unggah File" name="submit" class="btn btn-primary btn-lg">Unggah File</button>
                                     </div>
                                 </form>
                             </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>