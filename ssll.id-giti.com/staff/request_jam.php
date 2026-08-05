<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai admin
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin atau superadmin, arahkan ke halaman login atau halaman lainnya
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    // Default ke bulan dan tahun saat ini jika tidak ada filter dari POST
    $bulan = date('m');
    $tahun = date('Y');
}

// Query untuk mengambil data absensi berdasarkan bulan dan tahun yang dipilih
$query = "SELECT absen.*, karyawan.nama, karyawan.pin_absen AS pin, karyawan.nik
        FROM absen 
        JOIN karyawan ON karyawan.pin_absen = absen.pin";

// Tambahkan kondisi filter berdasarkan bulan dan tahun jika telah dipilih
if (!empty($bulan) && !empty($tahun)) {
    $query .= " WHERE MONTH(STR_TO_DATE(absen.tanggal, '%d-%m-%Y')) = ? 
                 AND YEAR(STR_TO_DATE(absen.tanggal, '%d-%m-%Y')) = ?
                 AND absen.kantor = 'Ya'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $bulan, $tahun);
} else {
    // Jika tidak ada filter yang disubmit, gunakan bulan dan tahun default (saat ini)
    $current_bulan = date('m');
    $current_tahun = date('Y');
    $query .= " WHERE MONTH(STR_TO_DATE(absen.tanggal, '%d-%m-%Y')) = ? 
                 AND YEAR(STR_TO_DATE(absen.tanggal, '%d-%m-%Y')) = ?
                 AND absen.kantor = 'Ya'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $current_bulan, $current_tahun);
    $bulan = $current_bulan; // Update $bulan dan $tahun untuk tampilan select box
    $tahun = $current_tahun;
}

$result = $stmt->execute();

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

$dataa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Shifting - Superadmin - Grav-Tech</title>
    
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
        table tr th, table tr td{
            font-size: 11pt !important;
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; // Menggunakan sidebar modern yang konsisten ?>
    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Request Jam Absen</h1>
                <p>Ajukan permintaan absen manual untuk karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                Ajukan Request Jam Absen Baru
                            </div>
                            <div class="card-body">
                                <form method="post" action="update_req_absen.php">
                                    <div class="mb-3">
                                        <label for="nama_karyawan" class="form-label">Nama</label>
                                        <select class="form-select" id="nama_karyawan" name="pin" required>
                                            <option value="">Pilih Nama</option>
                                            <?php
                                            // Re-include conn.php if needed, or ensure $conn is still open
                                            include '../conn.php'; 
                                            $queryNK = "SELECT pin_absen, nama FROM karyawan WHERE pin_absen IS NOT NULL ORDER BY nama ASC";
                                            $resultNK = $conn->query($queryNK);
                                            if ($resultNK && $resultNK->num_rows > 0) { 
                                                while ($rowNK = $resultNK->fetch_assoc()) {
                                                    echo '<option value="' . htmlspecialchars($rowNK['pin_absen']) . '">' . htmlspecialchars($rowNK['nama']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tanggal" class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="jam" class="form-label">Absen Manual di Jam</label>
                                        <select class="form-select" id="jam" name="jam" required>
                                            <option value="07:00:00">Masuk Shift Pagi (07:00)</option>
                                            <option value="08:30:00">Masuk Shift Tengah (08:30)</option>
                                            <option value="09:00:00">Masuk Shift Siang (09:00)</option>
                                            <option value="09:30:00">Masuk Shift Siang (09:30)</option>
                                            <option value="09:10:00">Masuk Shift Harco (09:10)</option>
                                            <option value="08:30:00">Masuk Weekend (08:30)</option>
                                            <option value="16:00:00">Pulang Shift Pagi (16:00)</option>
                                            <option value="17:30:00">Pulang Shift Tengah (17:30)</option>
                                            <option value="18:30:00">Pulang Shift Siang (18:30)</option>
                                            <option value="18:00:00">Pulang Shift Kantor dan Harco (18:00)</option>
                                            <option value="13:00:00">Pulang Weekend (13:00)</option>
                                            <option value="14:00:00">Pulang Shift Harco Weekend (14:00)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4 no-print">
                            <div class="card-header">
                                Filter Data Absen Manual
                            </div>
                            <div class="card-body">
                                <form method="post" class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label for="bulan" class="form-label">Bulan:</label>
                                        <select id="bulan" name="bulan" class="form-select">
                                            <?php
                                            $bulanNames = array(
                                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                            );
                                            foreach ($bulanNames as $bulanNum => $bulanName) {
                                                $selected = ($bulanNum == $bulan) ? 'selected' : '';
                                                echo "<option value='$bulanNum' $selected>$bulanName</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="tahun" class="form-label">Tahun:</label>
                                        <select id="tahun" name="tahun" class="form-select">
                                            <?php
                                            $tahunSekarang = date('Y');
                                            for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                                                $selected = ($i == $tahun) ? 'selected' : '';
                                                echo "<option value='$i' $selected>$i</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Show</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-header">
                                Data Absen Manual Karyawan - Periode <?php echo $bulanNames[$bulan] . ' ' . $tahun; ?>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm mb-0" id="tabel-request-jam">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center">No</th>
                                                <th>Nama</th>
                                                <th>Tanggal</th>
                                                <th>Jam</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $nomor_urut = 1;
                                            if (empty($dataa)) {
                                                echo '<tr><td colspan="5" class="text-center p-5 text-muted">Tidak ada data absen manual untuk periode ini.</td></tr>';
                                            } else {
                                                $fmt = new IntlDateFormatter(
                                                    'id_ID',
                                                    IntlDateFormatter::MEDIUM,
                                                    IntlDateFormatter::NONE,
                                                    date_default_timezone_get(),
                                                    IntlDateFormatter::GREGORIAN,
                                                    'dd MMM yyyy'
                                                );
                                                foreach ($dataa as $data) {
                                                    $dt = new DateTime($data['tanggal']);
                                                    $tanggal_format = $fmt->format($dt);
                                                    
                                                    echo "<tr>";
                                                    echo "<td class='text-center'>" . $nomor_urut++ . "</td>";
                                                    echo "<td style='text-align:left; text-transform:capitalize;'>" . htmlspecialchars($data['nama']) . "</td>";
                                                    echo "<td>" . $tanggal_format . "</td>";
                                                    echo "<td>" . htmlspecialchars($data['jam']) . "</td>";
                                                    echo "<td class='text-center'>";
                                                    echo "<button class='btn btn-danger btn-sm' onclick=\"confirmDelete('" . htmlspecialchars($data['id']) . "')\"><i class='fas fa-trash'></i></button>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="footer">
        Copyrights © Gravitti Technology 2023<br>All Rights Reserved
    </div>


    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        new gnMenu(document.getElementById('gn-menu'));
    </script>
    <script>
        function confirmDelete(id) {
            if (confirm("Apakah Anda yakin ingin menghapus data ini?")) {
                window.location.href = "proses-delete-req-absen.php?id=" + id;
            }
        }
        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        }
    </script>
</body>
</html>