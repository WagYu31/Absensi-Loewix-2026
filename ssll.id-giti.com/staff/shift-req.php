<?php
session_start();

// Cek keamanan: Hanya superadmin yang bisa akses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

$role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    $bulan = date('m');
    $tahun = date('Y');
}

// Query untuk mengambil data rincian gaji berdasarkan bulan dan tahun yang dipilih
$query = "SELECT shift_req.*, karyawan.nama, karyawan.pin_absen AS pin, karyawan.nik
        FROM shift_req 
        JOIN karyawan ON karyawan.pin_absen = shift_req.nip";

// Tambahkan kondisi filter berdasarkan bulan dan tahun jika telah dipilih
if (!empty($bulan) && !empty($tahun)) {
    $query .= " WHERE MONTH(shift_req.tgl_mulai) = ? AND YEAR(shift_req.tgl_mulai) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $bulan, $tahun);
} else {
    // Jika tidak ada filter bulan dan tahun, ambil semua data (sesuai query awal)
    // Atau jika Anda ingin default ke bulan/tahun saat ini jika tidak ada filter dari POST
    $current_bulan = date('m');
    $current_tahun = date('Y');
    $query .= " WHERE MONTH(shift_req.tgl_mulai) = ? AND YEAR(shift_req.tgl_mulai) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $current_bulan, $current_tahun);
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
                <h1>Request Shifting Karyawan</h1>
                <p>Kelola dan ajukan permintaan perubahan shift karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                Ajukan Permintaan Shifting Baru
                            </div>
                            <div class="card-body">
                                <form method="post" action="update_req_shift.php">
                                    <div class="mb-3">
                                        <label for="nama_karyawan" class="form-label">Nama</label>
                                        <select class="form-select" id="nama_karyawan" name="pin" required>
                                            <option value="">Pilih Nama</option>
                                            <?php
                                            include '../conn.php'; // Included again just in case previous script closed it
                                            $queryNK = "SELECT pin_absen, nama FROM karyawan WHERE pin_absen IS NOT NULL ORDER BY nama ASC";
                                            $resultNK = $conn->query($queryNK);
                                            if ($resultNK && $resultNK->num_rows > 0) { // Check if result is valid
                                                while ($rowNK = $resultNK->fetch_assoc()) {
                                                    echo '<option value="' . $rowNK['pin_absen'] . '">' . htmlspecialchars($rowNK['nama']) . '</option>';
                                                }
                                            }
                                            // $conn->close(); // Don't close here, as it might be needed for the table display below
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                                        <div class="form-text text-muted">* Isi dengan tanggal yang sama jika hanya 1 hari</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="shift" class="form-label">Shift</label>
                                        <select class="form-select" id="shift" name="shift" required>
                                            <option value="P">Shift 1 (07.00 s/d 16.00)</option>
                                            <option value="M">Shift 2 (08.30 s/d 17.30)</option>
                                            <option value="N">Shift 3 (09.00 s/d 18.00)</option>
                                            <option value="S">Shift 4 (09.30 s/d 18.30)</option>
                                            <option value="T">Shift Harco (09.10 s/d 18.00)</option>
                                            <option value="W">Sabtu (8.30 s/d 13.00)</option>
                                            <option value="TW">Harco Sabtu (9.00 s/d 13.00)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card shadow-sm mb-4 no-print">
                            <div class="card-header">
                                Filter Data Shifting
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
                                Data Shifting Karyawan - Periode <?php echo $bulanNames[$bulan] . ' ' . $tahun; ?>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm mb-0 text-sm" id="tabel-shift-req">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Tanggal Mulai</th>
                                                <th>Tanggal Selesai</th>
                                                <th>Shifting</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $nomor_urut = 1;
                                        if (empty($dataa)) {
                                            echo '<tr><td colspan="6" class="text-center p-5 text-muted">Tidak ada data shifting untuk periode ini.</td></tr>';
                                        } else {
                                            // Daftar singkatan bulan Indonesia
                                            $bulan_indo = [
                                                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                                                'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agu',
                                                'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
                                            ];
                                    
                                            foreach ($dataa as $data) {
                                                // Mengambil tanggal dan mengganti nama bulan ke Indonesia
                                                $time_mulai = strtotime($data['tgl_mulai']);
                                                $time_selesai = strtotime($data['tgl_selesai']);
                                    
                                                $tgl_mulai_en = date('d M Y', $time_mulai);
                                                $tgl_selesai_en = date('d M Y', $time_selesai);
                                    
                                                // Proses translasi bulan ke Indonesia
                                                $tanggal_mulai_format = strtr($tgl_mulai_en, $bulan_indo);
                                                $tanggal_selesai_format = strtr($tgl_selesai_en, $bulan_indo);
                                    
                                                $shifting_display = $data['shifting'];
                                                switch ($data['shifting']) {
                                                    case 'P': $shifting_display = "Shift 1 (07.00 s/d 16.00)"; break;
                                                    case 'M': $shifting_display = "Shift 2 (08.30 s/d 17.30)"; break;
                                                    case 'N': $shifting_display = "Shift 3 (09.00 s/d 18.00)"; break;
                                                    case 'S': $shifting_display = "Shift 4 (09.30 s/d 18.30)"; break;
                                                    case 'T': $shifting_display = "Shift Harco (09.10 s/d 18.00)"; break;
                                                    case 'W': $shifting_display = "Sabtu (8.30 s/d 13.00)"; break;
                                                    case 'TW': $shifting_display = "Harco Sabtu (9.00 s/d 13.00)"; break;
                                                }
                                    
                                                echo "<tr>";
                                                echo "<td class='text-center'>" . $nomor_urut++ . "</td>";
                                                echo "<td style='text-align:left; text-transform:capitalize;'>" . htmlspecialchars($data['nama']) . "</td>";
                                                echo "<td>" . $tanggal_mulai_format . "</td>";
                                                echo "<td>" . $tanggal_selesai_format . "</td>";
                                                echo "<td>" . htmlspecialchars($shifting_display) . "</td>";
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
                window.location.href = "proses-delete-req-shift.php?id=" + id;
            }
        }
        function toggleSidebar() {
            // This function is for sidebar, typically used with a Bootstrap collapse
            // If gn-menu.js handles sidebar, this might not be needed.
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) { // Check if element exists
                sidebar.classList.toggle('active');
            }
        }
    </script>
</body>
</html>