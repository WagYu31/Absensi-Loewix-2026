<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../login.html');
    exit();
}

include '../conn.php';

$role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulan"])) {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    $bulan = date('m');
    $tahun = date('Y');
}

$query = "SELECT tunjangan_lainnya.*, karyawan.nama, karyawan.nip AS nipk, karyawan.nik, karyawan.status_karyawan
        FROM tunjangan_lainnya 
        JOIN karyawan ON karyawan.nip = tunjangan_lainnya.nip
        WHERE MONTH(tunjangan_lainnya.tanggal) = ? AND YEAR(tunjangan_lainnya.tanggal) = ? AND tunjangan_lainnya.ket1 = 'ganti'
        ORDER BY karyawan.nama ASC, tunjangan_lainnya.tanggal DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $bulan, $tahun);
$stmt->execute();
$dataa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$queryKar = "SELECT * FROM karyawan WHERE status_karyawan = 'aktif' AND nip NOT IN ('001', '70326') ORDER BY nama ASC";
$resultKar = $conn->query($queryKar);
$karyawan_list = $resultKar->fetch_all(MYSQLI_ASSOC);

$queryLockedDates = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
$resultLockedDates = $conn->query($queryLockedDates);
$lockedDates = array();
if ($resultLockedDates->num_rows > 0) {
    while ($rowLockedDate = $resultLockedDates->fetch_assoc()) {
        $lockedDates[] = $rowLockedDate['tahun'] . '-' . str_pad($rowLockedDate['bulan'], 2, '0', STR_PAD_LEFT);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biaya Pengganti - Grav-Tech</title>
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
                <h1>Biaya Pengganti</h1>
                <p>Kelola data biaya pengganti karyawan (Tunjangan Lainnya).</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <div class="accordion mb-4 no-print" id="accordionTunjangan">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fa-solid fa-plus me-2"></i> Klik untuk Tambah Biaya Pengganti Baru
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionTunjangan">
                            <div class="accordion-body">
                                <form action="../proses-tambah-data-tunjangan-lainnya-karyawan.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="nip-tunjangan" class="form-label">Nama Karyawan</label>
                                            <select class="form-select" id="nip-tunjangan" name="nip_tunjangan" required>
                                                <option value="" disabled selected>-- Pilih Karyawan --</option>
                                                <?php foreach ($karyawan_list as $kar): ?>
                                                    <option value="<?php echo htmlspecialchars($kar['nip']); ?>"><?php echo htmlspecialchars($kar['nama']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-tunjangan" class="form-label">Tanggal</label>
                                            <input type="date" class="form-control" id="tanggal-tunjangan" name="tanggal_tunjangan" onchange="checkLockedDates()" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="jumlah-tunjangan" class="form-label">Jumlah (Rp)</label>
                                            <input type="number" class="form-control" id="jumlah-tunjangan" name="jumlah_tunjangan" placeholder="Contoh: 100000" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="keterangan-tunjangan" class="form-label">Keterangan</label>
                                            <textarea class="form-control" id="keterangan-tunjangan" name="keterangan_tunjangan" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary">Tambah Data</button>
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
                                <select id="bulan" name="bulan" class="form-select">
                                    <?php 
                                    $bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                    foreach ($bulanNames as $num => $name) {
                                        echo "<option value='$num' " . ($num == $bulan ? 'selected' : '') . ">$name</option>";
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select id="tahun" name="tahun" class="form-select">
                                    <?php 
                                    $tahunSekarang = date('Y');
                                    for ($i = $tahunSekarang; $i >= $tahunSekarang - 10; $i--) {
                                        echo "<option value='$i' " . ($i == $tahun ? 'selected' : '') . ">$i</option>";
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex">
                                <button type="submit" class="btn btn-primary w-100 me-2">Filter</button>
                                <button type="button" onclick="printData()" class="btn btn-info w-100">
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
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Jumlah</th>
                                        <th class="text-center no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dataa)): ?>
                                        <tr><td colspan="6" class="text-center p-5 text-muted">Tidak ada data biaya pengganti untuk periode ini.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($dataa as $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['nik']); ?></td>
                                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars($data['nama']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                                        <td><?php echo htmlspecialchars($data['keterangan']); ?></td>
                                        <td class="text-end">Rp <?php echo number_format($data['jumlah'], 0, ',', '.'); ?></td>
                                        <td class="text-center no-print">
                                            <button onclick="deleteTunjangan('<?php echo $data['id_tunjangan_lain']; ?>')" class="btn btn-danger btn-sm">
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
        function deleteTunjangan(idTunjangan) {
            if (confirm("Apakah Anda yakin ingin menghapus data ini?")) {
                window.location.href = "../proses-hapus-data-tunjangan.php?id_tunjangan_lain=" + idTunjangan;
            }
        }

        function checkLockedDates() {
            const tanggalInput = document.getElementById("tanggal-tunjangan");
            const selectedDate = tanggalInput.value;
            const lockedDates = <?php echo json_encode($lockedDates); ?>;
            
            if (selectedDate) {
                const selectedYearMonth = selectedDate.substring(0, 7);
                if (lockedDates.includes(selectedYearMonth)) {
                    alert("Tanggal pada bulan dan tahun yang terkunci tidak dapat dipilih.");
                    tanggalInput.value = "";
                }
            }
        }

        function printData() {
            const bulan = document.getElementById("bulan").value;
            const tahun = document.getElementById("tahun").value;
            const url = "../print-tunjangan.php?bulan=" + bulan + "&tahun=" + tahun;
            window.open(url, "_blank");
        }

        $(document).ready(function() {
            $('#nip-tunjangan').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#collapseOne')
            });
        });
    </script>
</body>
</html>