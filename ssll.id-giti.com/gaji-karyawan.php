<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

include 'conn.php';

$query = "SELECT * FROM karyawan";
$result = $conn->query($query);
$karyawan = array();
while ($row = $result->fetch_assoc()) {
    $karyawan[] = $row;
}
$conn->close();

?>

<!DOCTYPE html>
<html>
<head>
  <title>Form Gaji Karyawan</title>
  <link rel="stylesheet" type="text/css" href="css/style-payroll.css">
  <link rel="stylesheet" type="text/css" href="css/style-sidebar-menu.css">

  <script>
    function loadGajiPokokJabatan() {
      var nip = document.getElementById("nip").value;
      var gajiPokok = document.getElementById("gajiPokok");
      var tunjanganJabatan = document.getElementById("tunjanganJabatan");
      var tunjanganMasaKerja = document.getElementById("tunjanganMasaKerja");

      // Kirim permintaan AJAX untuk mendapatkan data gaji pokok, tunjangan jabatan, dan tunjangan masa kerja
      var xhr = new XMLHttpRequest();
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var data = JSON.parse(xhr.responseText);
          gajiPokok.value = data.gaji_pokok;
          tunjanganJabatan.value = data.tunjangan;
          tunjanganMasaKerja.value = data.tunjangan_masa_kerja;
        }
      };
      xhr.open("GET", "get-gaji-jabatan.php?nip=" + nip, true);
      xhr.send();

      // Memanggil fungsi untuk mengisi data tunjangan lainnya dan denda
      generateTunjanganLainnyaInput();
      generateDendaInput();
    }

    function generateTunjanganLainnyaInput() {
      var tunjanganLainnyaContainer = document.getElementById("tunjanganLainnyaContainer");
      var nip = document.getElementById("nip").value;

      // Kirim permintaan AJAX untuk mendapatkan data tunjangan lainnya
      var xhr = new XMLHttpRequest();
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var data = JSON.parse(xhr.responseText);
          var html = "<h3>Tunjangan Lainnya:</h3>";
          html += "<ul>";
          for (var i = 0; i < data.length; i++) {
            html += "<li>Tanggal: " + data[i].tanggal + ", Keterangan: " + data[i].keterangan + ", Jumlah: " + data[i].jumlah + "</li>";
          }
          html += "</ul>";
          tunjanganLainnyaContainer.innerHTML = html;
        }
      };
      xhr.open("GET", "get-tunjangan-lainnya.php?nip=" + nip, true);
      xhr.send();
    }

    function generateDendaInput() {
      var dendaContainer = document.getElementById("dendaContainer");
      var nip = document.getElementById("nip").value;

      // Kirim permintaan AJAX untuk mendapatkan data denda
      var xhr = new XMLHttpRequest();
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var data = JSON.parse(xhr.responseText);
          var html = "<h3>Penalty :</h3>";
          html += "<ul>";
          for (var i = 0; i < data.length; i++) {
            html += "<li>Date : " + data[i].tanggal + ", Description : " + data[i].keterangan + ", Amount : " + data[i].jumlah + "</li>";
          }
          html += "</ul>";
          dendaContainer.innerHTML = html;
        }
      };
      xhr.open("GET", "get-denda.php?nip=" + nip, true);
      xhr.send();
    }
  </script>
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
    <h2>Generate Gaji</h2>
    <form action="proses-input-gaji.php" method="POST">
      <label for="nip">Nama :</label>
      <select id="nip" name="nip" required onchange="loadGajiPokokJabatan()">
        <?php
        foreach ($karyawan as $data) {
          echo '<option value="' . $data['nip'] . '">' . $data['nama'] . '</option>';
        }
        ?>
      </select>

      <label for="tanggal">Tanggal :</label>
      <input type="date" id="tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>

      <label for="gajiPokok">Gaji : </label>
      <input type="number" id="gajiPokok" name="gaji_pokok" readonly>

      <label for="tunjanganJabatan">Tunjangan Jabatan :</label>
      <input type="number" id="tunjanganJabatan" name="tunjangan_jabatan" readonly>

      <label for="tunjanganMasaKerja">Tunjangan Masa Kerja :</label>
      <input type="number" id="tunjanganMasaKerja" name="tunjangan_masa_kerja" readonly>

      <div id="tunjanganLainnyaContainer"></div> <!-- Container untuk tunjangan lainnya -->
      <div id="dendaContainer"></div> <!-- Container untuk denda -->

      <input type="submit" value="Process">
    </form>
  </div>
</body>
</html>
