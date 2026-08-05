<?php
session_start();

// Cek keamanan: Hanya admin dan superadmin yang bisa akses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// Query yang lebih efisien: hanya ambil karyawan yang gajinya 0 atau NULL
// dan belum di-soft-delete, serta bukan akun sistem.
$query = "SELECT nip, nama, jabatan 
          FROM karyawan 
          WHERE (gaji_pokok = 0 OR gaji_pokok IS NULL) 
            AND deleted_at IS NULL 
            AND nip NOT IN ('001', '70326') 
          ORDER BY nama ASC";

$result = $conn->query($query);
if (!$result) {
    die("Query gagal dieksekusi: " . $conn->error);
}
$karyawan_tanpa_gaji = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Gaji Pokok Massal - Grav-Tech</title>
    
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
                <h1>Input Gaji Pokok Massal</h1>
                <p>Masukkan gaji pokok untuk karyawan yang datanya belum lengkap.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-file-invoice-dollar title-icon"></i>Daftar Karyawan Tanpa Gaji Pokok</h5>
                        <a href="sa-data-karyawan.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($karyawan_tanpa_gaji)): ?>
                            <div class="alert alert-success text-center">
                                <i class="fa-solid fa-check-circle fa-2x mb-2"></i><br>
                                Kerja Bagus! Semua karyawan sudah memiliki data gaji pokok.
                            </div>
                        <?php else: ?>
                            <form action="proses_input_gaji.php" method="POST">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Nama Karyawan</th>
                                                <th scope="col">Jabatan</th>
                                                <th scope="col" style="width: 35%;">Input Gaji Pokok</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($karyawan_tanpa_gaji as $karyawan): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($karyawan['nama']); ?>
                                                    <input type="hidden" name="nip[]" value="<?php echo htmlspecialchars($karyawan['nip']); ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($karyawan['jabatan']); ?></td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control currency-input" name="gaji_pokok[]" placeholder="0" autocomplete="off">
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i>Simpan Semua Gaji</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fungsi untuk format mata uang saat mengetik
            $('.currency-input').on('keyup', function(event) {
                var selection = window.getSelection().toString();
                if (selection !== '') {
                    return;
                }
                if ($.inArray(event.keyCode, [38, 40, 37, 39]) !== -1) {
                    return;
                }
                var $this = $(this);
                var input = $this.val();
                var input = input.replace(/[\D\s\._\-]+/g, "");
                input = input ? parseInt(input, 10) : 0;
                $this.val(function() {
                    return (input === 0) ? "" : input.toLocaleString("id-ID");
                });
            });
        });
    </script>
</body>
</html>