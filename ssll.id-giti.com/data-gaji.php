<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

// Filter berdasarkan parameter yang diterima dari URL
$filter = $_GET['filter'] ?? 'all';

// Query untuk mengambil riwayat gaji karyawan berdasarkan filter
$query = "SELECT rincian_gaji.id_rincian_gaji, karyawan.nip, karyawan.nama, rincian_gaji.tanggal, rincian_gaji.gaji_pokok, rincian_gaji.tunjangan_jabatan, rincian_gaji.tunjangan_masa_kerja, rincian_gaji.tunjangan_lainnya, rincian_gaji.denda, (rincian_gaji.gaji_pokok + rincian_gaji.tunjangan_jabatan + rincian_gaji.tunjangan_masa_kerja + rincian_gaji.tunjangan_lainnya - rincian_gaji.denda) AS total_gaji
          FROM karyawan
          INNER JOIN rincian_gaji ON karyawan.nip = rincian_gaji.nip";

// Tambahkan filter berdasarkan kondisi tanggal
if ($filter === 'month') {
    $query .= " WHERE MONTH(rincian_gaji.tanggal) = MONTH(CURRENT_DATE())";
} elseif ($filter === 'year') {
    $query .= " WHERE YEAR(rincian_gaji.tanggal) = YEAR(CURRENT_DATE())";
}

$query .= " ORDER BY rincian_gaji.tanggal DESC";

$result = $conn->query($query);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Gaji Karyawan - Admin</title>
    <link rel="stylesheet" type="text/css" href="css/style-sidebar-menu.css">
    <link rel="stylesheet" type="text/css" href="css/style-admin-gaji.css">
</head>
<body>
    <!-- Sidebar Menu -->
    <div class="sidebar" style="font-size:15px;">
        <div class="top-side">
            <img src="img/payment.png" width="100"></img>
            <p class="cm">NAMA PERUSAHAAN</p>
            <p>Motto Perusahaan</p>
        </div>
        <a href="admin-profile.php"><img src="img/resume.png" width="30" style="float:left; margin-top:-6px; margin-right:20px;"></img>Profil</a>
        <a href="data-karyawan.php"><img src="img/4975755.png" width="30" style="float:left; margin-top:-6px; margin-right:20px;"></img>Data Karyawan</a>
        <a href="tunjangan-karyawan.php"><img src="img/payment.png" width="30" style="float:left; margin-top:-6px; margin-right:20px;"></img>Pembayaran Pengganti</a>
        <a href="denda-karyawan.php"><img src="img/tax.png" width="30" style="float:left; margin-top:-6px; margin-right:20px;"></img>Denda / Cashbon</a>
        <a href="gaji-karyawan.php"><img src="img/monitor.png" width="30" style="float:left; margin-top:-6px; margin-right:20px;"></img>Generate Gaji</a>
        <a href="data-gaji.php"><img src="img/salary.png" width="30" style="float:left; margin-top:-6px; margin-right:20px;"></img>Data Gaji</a>
        <a href="logout.php"><img src="img/padlock.png" width="30" style="float:left; margin-top:-6px; margin-right:20px;"></img>Keluar</a>
    </div>
    <div class="container">
    <h1>GAJI KARYAWAN</h1>

    <form id="printForm">
        <label for="startDate">Mulai :</label>
        <input type="date" id="startDate" name="startDate" required>
        <label for="endDate">Akhir :</label>
        <input type="date" id="endDate" name="endDate" required>
        <button type="submit" id="printByDate">Print</button>
        <button type="button" id="printAll">Print All</button>
    </form>

    <div class="report">
        <a href="data-gaji.php?filter=all"><button<?php if ($filter === 'all') echo ' class="active"'; ?>>Semua</button></a>
        <a href="data-gaji.php?filter=month"><button<?php if ($filter === 'month') echo ' class="active"'; ?>>Bulan Ini</button></a>
        <a href="data-gaji.php?filter=year"><button<?php if ($filter === 'year') echo ' class="active"'; ?>>Tahun Ini</button></a>
    </div>

    <table id="gaji">
        <tr>
            <th onclick="sortTable(0)">Tanggal</th>
            <th onclick="sortTable(1)">NIP</th>
            <th onclick="sortTable(2)">Nama</th>
            <th onclick="sortTable(3)">Gaji Pokok</th>
            <th onclick="sortTable(4)">Tunjangan Jabatan</th>
            <th onclick="sortTable(5)">Tunjangan Masa Kerja</th>
            <th onclick="sortTable(6)">Pembayaran Pengganti</th>
            <th onclick="sortTable(7)">Denda  / Cashbon</th>
            <th onclick="sortTable(8)">Total</th>
            <th>Aksi</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tanggal = $row['tanggal'];
                $nip = $row['nip'];
                $nama = $row['nama'];
                $gajiPokok = "Rp " . number_format($row['gaji_pokok'], 0, ',', '.');
                $tunjanganJabatan = "Rp " . number_format($row['tunjangan_jabatan'], 0, ',', '.');
                $tunjanganMasaKerja = "Rp " . number_format($row['tunjangan_masa_kerja'], 0, ',', '.');
                $tunjanganLainnya = "Rp " . number_format($row['tunjangan_lainnya'], 0, ',', '.');
                $denda = "Rp " . number_format($row['denda'], 0, ',', '.');
                $total = $row['gaji_pokok'] + $row['tunjangan_jabatan'] + $row['tunjangan_masa_kerja'] + $row['tunjangan_lainnya'] - $row['denda'];
                $totalGaji = "Rp " . number_format($total, 0, ',', '.');
                ?>
                <tr>
                    <td><?php echo $tanggal; ?></td>
                    <td><?php echo $nip; ?></td>
                    <td><?php echo $nama; ?></td>
                    <td><?php echo $gajiPokok; ?></td>
                    <td><?php echo $tunjanganJabatan; ?></td>
                    <td><?php echo $tunjanganMasaKerja; ?></td>
                    <td><?php echo $tunjanganLainnya; ?></td>
                    <td><?php echo $denda; ?></td>
                    <td><?php echo $totalGaji; ?></td>
                    <td>
                        <a href="view-gaji.php?nip=<?php echo $nip; ?>&tanggal=<?php echo $tanggal; ?>"><img src="img/magnifier.png"></img></a>
                        <button onclick="deleteRow(this, '<?php echo $row['id_rincian_gaji']; ?>')"><img src="img/dustbin.png"></img></button>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='10'>There is no data for employee salary history</td></tr>";
        }
        ?>
    </table>
</div>

<script>
    function sortTable(n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("gaji");
        switching = true;
        //Set the sorting direction to ascending:
        dir = "asc";
        /*Make a loop that will continue until
        no switching has been done:*/
        while (switching) {
            //start by saying: no switching is done:
            switching = false;
            rows = table.rows;
            /*Loop through all table rows (except the
            first, which contains table headers):*/
            for (i = 1; i < (rows.length - 1); i++) {
                //start by saying there should be no switching:
                shouldSwitch = false;
                /*Get the two elements you want to compare,
                one from current row and one from the next:*/
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                /*check if the two rows should switch place,
                based on the direction, asc or desc:*/
                if (dir == "asc") {
                    if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                        //if so, mark as a switch and break the loop:
                        shouldSwitch = true;
                        break;
                    }
                } else if (dir == "desc") {
                    if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                        //if so, mark as a switch and break the loop:
                        shouldSwitch = true;
                        break;
                    }
                }
            }
            if (shouldSwitch) {
                /*If a switch has been marked, make the switch
                and mark that a switch has been done:*/
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                //Each time a switch is done, increase this count by 1:
                switchcount++;
            } else {
                /*If no switching has been done AND the direction is "asc",
                set the direction to "desc" and run the while loop again.*/
                if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }
    }

    function deleteRow(button, idRincianGaji) {
    // Confirmation dialog
    var confirmation = confirm("Are you sure you want to delete this data?");
    if (confirmation) {
        // Find the row element
        var row = button.parentNode.parentNode;
        // Remove the row from the table
        row.parentNode.removeChild(row);

        // Send AJAX request to delete the row from the server
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "delete-gaji.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status === "success") {
                    // Display success message
                    alert("Data has been successfully deleted.");
                } else {
                    // Display error message
                    alert("Failed to delete the data.");
                }
            }
        };
        // Send the id_rincian_gaji as parameter
        var params = "idRincianGaji=" + idRincianGaji;
        xhr.send(params);
    }
}


document.getElementById("printForm").addEventListener("submit", function(event) {
    event.preventDefault();
    var startDate = document.getElementById("startDate").value;
    var endDate = document.getElementById("endDate").value;
    window.open("print-gaji.php?startDate=" + startDate + "&endDate=" + endDate);
});

document.getElementById("printAll").addEventListener("click", function() {
    window.open("print-gaji-all.php");
});

</script>

</body>
</html>
