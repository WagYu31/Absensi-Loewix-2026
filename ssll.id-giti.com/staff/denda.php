<?php
session_start();

// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// Logika untuk filter bulan dan tahun (TETAP SAMA)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulan"])) {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    // Default ke bulan dan tahun saat ini jika tidak ada filter
    $bulan = date('m');
    $tahun = date('Y');
}

// Ambil data untuk tabel denda (DITINGKATKAN dengan prepared statements)
$query_denda = "SELECT denda.*, karyawan.nama, karyawan.nik
                FROM denda 
                JOIN karyawan ON karyawan.nip = denda.nip
                WHERE MONTH(denda.tanggal) = ? AND YEAR(denda.tanggal) = ? AND denda.ket1 = 'Denda'
                ORDER BY karyawan.nama ASC, denda.tanggal DESC";
$stmt_denda = $conn->prepare($query_denda);
$stmt_denda->bind_param("ss", $bulan, $tahun);
$stmt_denda->execute();
$data_denda = $stmt_denda->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_denda->close();


// Ambil data karyawan untuk dropdown "Tambah Denda" (TETAP SAMA)
$query_kar = "SELECT nip, nama FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND nip NOT IN ('001', '70326') ORDER BY nama ASC";
$result_kar = $conn->query($query_kar);
$karyawan_list = $result_kar->fetch_all(MYSQLI_ASSOC);

// Ambil data bulan/tahun yang terkunci untuk validasi JS (TETAP SAMA)
$query_locked = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
$result_locked = $conn->query($query_locked);
$locked_dates = [];
if ($result_locked->num_rows > 0) {
    while ($row_locked = $result_locked->fetch_assoc()) {
        $locked_dates[] = $row_locked['tahun'] . '-' . str_pad($row_locked['bulan'], 2, '0', STR_PAD_LEFT);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Denda Karyawan - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Kelola Denda Karyawan</h1>
                <p>Tambah denda manual atau lihat riwayat denda berdasarkan periode.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <div class="accordion mb-4 no-print" id="accordionTambahDenda">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fa-solid fa-plus me-2"></i> Klik untuk Tambah Denda Baru
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionTambahDenda">
                            <div class="accordion-body">
                                <form action="proses-tambah-data-denda-karyawan.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="nip-denda" class="form-label">Nama Karyawan</label>
                                            <select class="form-select" id="nip-denda" name="nip_denda" required>
                                                <option value="" disabled selected>-- Pilih Karyawan --</option>
                                                <?php foreach ($karyawan_list as $karyawan): ?>
                                                    <option value="<?php echo htmlspecialchars($karyawan['nip']); ?>"><?php echo htmlspecialchars($karyawan['nama']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-denda" class="form-label">Tanggal Denda</label>
                                            <input type="date" class="form-control" id="tanggal-denda" name="tanggal_denda" onchange="checkLockedDates()" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="jumlah-denda" class="form-label">Jumlah (Rp)</label>
                                            <input type="number" class="form-control" id="jumlah-denda" name="jumlah_denda" placeholder="Contoh: 50000" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="keterangan-denda" class="form-label">Keterangan</label>
                                            <textarea class="form-control" id="keterangan-denda" name="keterangan_denda" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary">Tambah Denda</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <form method="POST" class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <label for="bulan" class="form-label visually-hidden">Bulan</label>
                                <select id="bulan" name="bulan" class="form-select">
                                    <?php $bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                    foreach ($bulanNames as $num => $name) {
                                        echo "<option value='$num' " . ($num == $bulan ? 'selected' : '') . ">$name</option>";
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="tahun" class="form-label visually-hidden">Tahun</label>
                                <select id="tahun" name="tahun" class="form-select">
                                    <?php $currentYear = date('Y');
                                    for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                                        echo "<option value='$i' " . ($i == $tahun ? 'selected' : '') . ">$i</option>";
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex">
                                <button type="submit" class="btn btn-primary w-100 me-2">Filter</button>
                                <button type="button" onclick="printData()" class="btn btn-info w-100" title="Cetak Laporan Denda">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Jumlah</th>
                                        <th class="text-center no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data_denda)): ?>
                                        <tr><td colspan="7" class="text-center p-5 text-muted">Tidak ada data denda untuk periode ini.</td></tr>
                                    <?php endif; ?>
                                    <?php $no = 1; foreach ($data_denda as $data): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($data['nik']); ?></td>
                                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars($data['nama']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                                        <td><?php echo htmlspecialchars($data['keterangan']); ?></td>
                                        <td class="text-end">Rp <?php echo number_format($data['jumlah'], 0, ',', '.'); ?></td>
                                        <td class="text-center no-print">
                                            <button onclick="confirmDelete('<?php echo $data['id_denda']; ?>')" class="btn btn-danger btn-sm" title="Hapus Denda">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // FUNGSI-FUNGSI JAVASCRIPT INTI TETAP SAMA
        function confirmDelete(id_denda) {
            if (confirm("Apakah Anda yakin ingin menghapus data denda ini?")) {
                window.location.href = "proses-hapus-data-denda-karyawan.php?id_denda=" + id_denda;
            }
        }

        function checkLockedDates() {
            const tanggalInput = document.getElementById("tanggal-denda");
            const selectedDate = tanggalInput.value;
            // Data tanggal terkunci dari PHP
            const lockedDates = <?php echo json_encode($locked_dates); ?>;
            
            if (selectedDate) {
                const selectedYearMonth = selectedDate.substring(0, 7);
                if (lockedDates.includes(selectedYearMonth)) {
                    alert("Periode gaji untuk bulan dan tahun yang dipilih sudah terkunci dan tidak dapat diubah.");
                    tanggalInput.value = "";
                }
            }
        }

        function printData() {
            const bulan = document.getElementById("bulan").value;
            const tahun = document.getElementById("tahun").value;
            const url = "print-denda.php?bulan=" + bulan + "&tahun=" + tahun;
            window.open(url, "_blank");
        }

        // Inisialisasi Select2 untuk dropdown karyawan
        $(document).ready(function() {
            $('#nip-denda').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#collapseOne') // Penting: agar dropdown muncul di atas elemen lain
            });
        });
    </script>
</body>
</html>