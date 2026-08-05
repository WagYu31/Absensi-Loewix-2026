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
    $bulan = date('m');
    $tahun = date('Y');
}


// if ($_SERVER["REQUEST_METHOD"] === "POST") {
//     $bulan = $_POST["bulan"];
//     $tahun = $_POST["tahun"];
// } else {
//     $currentMonth = date('m');
//     $currentYear = date('Y');

//     if ($currentMonth == '01') {
//         $bulan = '12';
//         $tahun = $currentYear - 1;
//     } else {
//         $bulan = str_pad($currentMonth - 1, 2, '0', STR_PAD_LEFT);
//         $tahun = $currentYear;
//     }
// }


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
    $stmt = $conn->prepare($query);
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
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--<meta http-equiv='cache-control' content='no-cache'>-->
    <!--<meta http-equiv='expires' content='0'>-->
    <!--<meta http-equiv='pragma' content='no-cache'>-->
    <title>Grav-Tech Salary</title>
    <meta name="description" content="Website Penghitung Gaji Karyawan Grav-Tech" />
    <meta name="keywords" content="salary, gaji, gravitti technology, gravitti, grav-tech" />
    <meta name="author" content="Irviani" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <script src="../js/script.js" defer></script>
    <script type="text/javascript" src="../tableExport.js"></script>
    <script type="text/javascript" src="../jquery.base64.js"></script>
    <script type="text/javascript" src="../html2canvas.js"></script>
    <script type="text/javascript" src="../jspdf/libs/sprintf.js"></script>
    <script type="text/javascript" src="../jspdf/jspdf.js"></script>
    <script type="text/javascript" src="../jspdf/libs/base64.js"></script>
    <script type="text/javascript" src="../js/script-download-all.js?rev=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- <link rel="stylesheet" type="text/css" href="css/style-menu-bar.css"> -->
    <link rel="stylesheet" type="text/css" href="../css/style-data-karyawan.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="../css/foot.css?rev=<?php echo time(); ?>">


    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="stylesheet" type="text/css" href="../css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="../css/demo.css" />
    <link rel="stylesheet" type="text/css" href="../css/component.css" />
    <script src="../js/modernizr.custom.js"></script>

    <style>
        .highlight td {
            background-color: #ffff66;
        }

        .form-container,
        .form-container-2 {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            margin-top: 15vh;
        }

        .form-container-2 {
            margin-top: 5vh;
            margin-bottom: 5vh;
        }
        #denda{
            margin-top: 2vh;
        }
    </style>

</head>

<body>
    <div class="container no-print">

        <?php include "gn-menu.php"; ?>
    </div><!-- /container -->

    <div class="container mt-5">
        <div class="row">
            <div class="col-6 form-container">
                <h2>Request Shifting</h2>
                <form method="post" action="update_req_shift.php">
                    <div class="form-group">
                        <label for="nama_karyawan">Nama</label>
                        <select class="form-control" id="nama_karyawan" name="pin" required>
                            <option value="">Pilih Nama</option>
                            <?php
                            include '../conn.php';
                            $queryNK = "SELECT pin_absen, nama FROM karyawan WHERE pin_absen IS NOT NULL ORDER BY nama ASC";
                            $resultNK = $conn->query($queryNK);
                            if ($resultNK->num_rows > 0) {
                                while ($rowNK = $resultNK->fetch_assoc()) {
                                    echo '<option value="' . $rowNK['pin_absen'] . '">' . $rowNK['nama'] . '</option>';
                                }
                            }
                            $conn->close();
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                        <label style="font-size:12px;text-decoration:italic;margin-left:5px;">* Isi dengan tanggal yang sama jika hanya 1 hari</label>
                    </div>
                    <div class="form-group">
                        <label for="shift">Shift</label>
                        <select class="form-control" id="shift" name="shift" required>
                            <option value="P">Shift 1 (07.00 s/d 16.00)</option>
                            <option value="M">Shift 2 (08.30 s/d 17.30)</option>
                            <option value="S">Shift 3 (09.30 s/d 18.30)</option>
                            <option value="T">Shift Harco (09.10 s/d 18.00)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

            <div class="col-6 form-container-2">
                <div class="pilih no-print">

                    <form method="post" class="bt p-4 shadow rounded bg-white">
                        <div class="form-group mb-3">
                            <label for="bulan" class="form-label">Bulan:</label>
                            <select id="bulan" name="bulan" class="form-control">
                                <?php
                                $bulanNames = array(
                                    '01' => 'Januari',
                                    '02' => 'Februari',
                                    '03' => 'Maret',
                                    '04' => 'April',
                                    '05' => 'Mei',
                                    '06' => 'Juni',
                                    '07' => 'Juli',
                                    '08' => 'Agustus',
                                    '09' => 'September',
                                    '10' => 'Oktober',
                                    '11' => 'November',
                                    '12' => 'Desember'
                                );

                                foreach ($bulanNames as $bulanNum => $bulanName) {
                                    $selected = ($bulanNum == $bulan) ? 'selected' : '';
                                    echo "<option class='bln' value='$bulanNum' $selected>$bulanName</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="tahun" class="form-label">Tahun:</label>
                            <select id="tahun" name="tahun" class="form-control">
                                <?php
                                $tahunSekarang = date('Y');
                                for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                                    $selected = ($i == $tahun) ? 'selected' : '';
                                    echo "<option class='thn' value='$i' $selected>$i</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <button type="submit" class="btn btn-primary" id="bs">Tampilkan Data</button>
                            <!-- <a href="#" onclick="printData()" class="btn btn-secondary"><i class="fas fa-print"></i> PRINT</a> -->
                        </div>
                    </form>


                </div>
                <table id="denda">
                    <tr>
                        <th onclick="sortTable(0)">No</th>
                        <th onclick="sortTable(1)">Nama</th>
                        <th onclick="sortTable(2)">Tanggal Mulai</th>
                        <th onclick="sortTable(3)">Tanggal Selesai</th>
                        <th onclick="sortTable(4)">Shifting</th>
                        <th width="10%">Action</th>
                    </tr>
                    <?php
                    $nomor_urut = 1;
                    $shifting_1 = "";
                    foreach ($dataa as $data) {
                        echo "<tr>";
                        echo "<td>" . $nomor_urut . "</td>";
                        echo "<td style='text-align:left;'>" . $data['nama'] . "</td>";
                        setlocale(LC_TIME, 'id_ID'); // Setel lokal ke Bahasa Indonesia

                        $tanggal = $data['tgl_mulai'];
                        $tanggal_diubah_format = strftime('%d %b %Y', strtotime($tanggal));

                        echo "<td>" . $tanggal_diubah_format . "</td>";

                        $tanggal_selesai = $data['tgl_selesai'];
                        $tanggal_selesai_format = strftime('%d %b %Y', strtotime($tanggal_selesai));

                        echo "<td>" . $tanggal_selesai_format . "</td>";

                        $shifting = $data['shifting'];
                        if ($shifting == "P") {
                            $shifting_1 = "Shift 1 (07.00 s/d 16.00)";
                        } elseif ($shifting == "M") {
                            $shifting_1 = "Shift 2 (08.30 s/d 17.30)";
                        } elseif ($shifting == "S") {
                            $shifting_1 = "Shift 3 (09.30 s/d 18.30)";
                        } elseif ($shifting == "T") {
                            $shifting_1 = "Harco (09.00 s/d 18.00)";
                        } elseif ($shifting == "W") {
                            $shifting_1 = "Sabtu (8.30 s/d 13.00)";
                        } elseif ($shifting == "TW") {
                            $shifting_1 = "Harco Sabtu (9.00 s/d 13.00)";
                        } else {
                            $shifting_1 = $shifting;
                        }

                        echo "<td class='hide-m' style='text-align:left;'>" . $shifting_1 . "</td>";
                        echo "<td>";
                        // $queryLockedDates2 = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
                        // $resultLockedDates2 = $conn->query($queryLockedDates2);

                        // $lockedDates2 = array();

                        echo "<button class='btn btn-danger' onclick=\"confirmDelete('" . $data['id'] . "')\">Delete</button>";
                        // }


                        echo "</td>";
                        echo "</tr>";

                        $nomor_urut++;
                    }
                    ?>
                </table>
            </div>
        </div>

    </div>
    <div class="footer">
        Copyrights © Gravitti Technology 2023<br>All Rights Reserved
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/classie.js"></script>
    <script src="../js/gnmenu.js"></script>
    <script>
        new gnMenu(document.getElementById('gn-menu'));
    </script>
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this data?")) {
                // Redirect ke halaman proses-hapus-data-denda-karyawan.php dengan mengirimkan id data yang akan dihapus melalui parameter GET
                window.location.href = "proses-delete-req-shift.php?id=" + id; // Change 'id' to 'id_denda'
            }
        }
        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>

</html>