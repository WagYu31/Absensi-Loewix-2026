        <table class="table table-responsive" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>No</th>
                    <!-- <th>PIN</th>
                    <th>NIP</th>
                    <th>Nama</th> -->
                    <th>Hari / Tanggal</th>
                    <th>Shifting</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Terlambat</th>
                    <th>Jam Kerja</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $sql = "SELECT 
                            MIN(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_scan, 
                            a.nip, 
                            a.pin, 
                            k.nik, 
                            k.nama AS nama_karyawan, 
                            k.shifting
                        FROM 
                            absen a
                        JOIN 
                            karyawan k ON a.nip = k.nik
                        WHERE
                            k.nik = '$nik' AND
                            MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = $bulan AND
                            YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = $tahun
                        GROUP BY 
                            a.nip, DATE_FORMAT(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s'), '%m-%d')";
                $result = $conn->query($sql);

                $jumlah_telat = 0;
                $no = 1;
                    $denda_absen = 0;
                    $jumlah_terlambat = 0;
                    $jumlah_tidak_absen_masuk = 0;
                    $jumlah_tidak_absen_pulang = 0;
                    $jumlah_izin_jam_kerja = 0;
                    $jam_scan = "";
                    $jam_out = "";
                    $shifting = "";
                    $hari_scan1 = "";
                    $totalJam = 0;
                    $totalMenit = 0;
                    $totalDetik = 0;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $tgl_scan = date('d-m-Y H:i:s', strtotime($row['tgl_scan']));
                        $waktu_scan = date('H:i', strtotime($row['tgl_scan']));
                        $jam_scan = date('H:i', strtotime($row['tgl_scan']));
                        $tgl_only = date('d-m-Y', strtotime($tgl_scan));
                        $cek_tgl = date('Y-m-d', strtotime($tgl_scan));
                        $query = "SELECT MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out
                            FROM absen 
                            WHERE nip = '" . $row['nip'] . "' AND DATE_FORMAT(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'), '%d-%m-%Y') = '" . $tgl_only . "'";
                        $res = $conn->query($query);
                        $data = $res->fetch_assoc();
                        $tgl_out = date('d-m-Y H:i:s', strtotime($data['tgl_out']));
                        $waktu_out = date('H:i', strtotime($data['tgl_out']));
                        $jam_out = date('H:i', strtotime($data['tgl_out']));
                        $shifting = $row["shifting"];

                        $hari_scan = date('l', strtotime($tgl_scan));
                        setlocale(LC_TIME, 'id_ID.UTF-8');

                        if($hari_scan == "Monday"){
                            $hari_scan1 = "Senin";
                        }
                        elseif($hari_scan == "Tuesday"){
                            $hari_scan1 = "Selasa";
                        }
                        elseif($hari_scan == "Wednesday"){
                            $hari_scan1 = "Rabu";
                        }
                        elseif($hari_scan == "Thursday"){
                            $hari_scan1 = "Kamis";
                        }
                        elseif($hari_scan == "Friday"){
                            $hari_scan1 = "Jumat";
                        }
                        elseif($hari_scan == "Saturday"){
                            $hari_scan1 = "Sabtu";
                        }
                        elseif($hari_scan == "Sunday"){
                            $hari_scan1 = "Minggu";
                        }
                        else{
                            $hari_scan1 = $hari_scan;
                        }

                        $shifting = $row["shifting"];
                        $pinAbsen = $row["pin"];

                        include "req_shift_db.php";

                        if ($hari_scan == "Saturday" && $shifting != "T") {
                            $shifting = "W";
                        } elseif ($hari_scan == "Saturday" && $shifting == "T") {
                            $shifting = "TW";
                        }
                        
                        if ($waktu_scan == $waktu_out && strtotime($waktu_scan) > strtotime("12:00")) {
                            $jam_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $tgl_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $jumlah_tidak_absen_masuk++;
                        } elseif ($waktu_scan != $waktu_out && strtotime($waktu_scan) > strtotime("12:00")) {
                            $jam_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $tgl_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $jumlah_tidak_absen_masuk++;
                        }

                        if ($waktu_scan == $waktu_out && strtotime($waktu_out) < strtotime("11:00")) {
                            $jam_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $tgl_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $jumlah_tidak_absen_pulang++;
                        } elseif ($waktu_scan != $waktu_out && strtotime($waktu_out) < strtotime("11:00")) {
                            $jam_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $tgl_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $jumlah_tidak_absen_pulang++;
                        }
                        echo "<tr>";
                        echo "<td>" . $no . "</td>";
                        // echo "<td>" . $pinAbsen . "</td>";
                        // echo "<td>" . $row["nip"] . "</td>";
                        // echo "<td>" . $row["nama_karyawan"] . "</td>";
                        echo "<td style='text-align:left;'>" . $hari_scan1 . ", " . $tgl_only . "</td>";

                        if ($shifting == "P") {
                            $shifting_1 = "Shift 1 (07.00 s/d 16.00)";
                        } elseif ($shifting == "M") {
                            $shifting_1 = "Shift 2 (08.30 s/d 17.30)";
                        } elseif ($shifting == "S") {
                            $shifting_1 = "Shift 3 (09.30 s/d 18.30)";
                        } elseif ($shifting == "T") {
                            $shifting_1 = "Harco (09.10 s/d 18.00)";
                        } elseif ($shifting == "W") {
                            $shifting_1 = "Sabtu (8.30 s/d 13.00)";
                        } elseif ($shifting == "TW") {
                            $shifting_1 = "Harco Sabtu (9.10 s/d 14.00)";
                        } else {
                            $shifting_1 = $shifting;
                        }

                        echo "<td style='text-align:left;'>" . $shifting_1 . "</td>";

                        echo "<td>" . $jam_scan . "</td>";
                        echo "<td>" . $jam_out . "</td>";

                        $waktu_masuk_unix = "";
                        if ($shifting == "P") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 07:00:00");
                        } elseif ($shifting == "M") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 08:30:00");
                        } elseif ($shifting == "S") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 09:30:00");
                        } elseif ($shifting == "T") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 09:10:00");
                        } elseif ($shifting == "W") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 08:30:00");
                        } elseif ($shifting == "TW") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 09:10:00");
                        }

                        $tgl_scan_unix = strtotime(date('d-m-Y') . " " . $waktu_scan);

                        $keterlambatan_detik = $tgl_scan_unix - $waktu_masuk_unix;

                        $keterlambatan_menit = floor($keterlambatan_detik / 60);
                        if ($keterlambatan_menit < 0) {
                            $keterlambatan_menit = 0;
                        } elseif ($tgl_scan == "<span class='text-danger'>Tidak Absen Masuk</span>") {
                            $keterlambatan_menit = 0;
                        }

                        $ket_izin = "";
                        include "izin_jam_kerja.php";

                        echo "<td>" . $keterlambatan_menit . " menit" . "</td>";

                        $jumlah_terlambat += $keterlambatan_menit;
                        $tgl_scan_unix = strtotime($tgl_scan);
                        $tgl_out_unix = strtotime($tgl_out);
                        $selisih_detik = $tgl_out_unix - $tgl_scan_unix;

                        $jam = floor($selisih_detik / (60 * 60));
                        $menit = floor(($selisih_detik - ($jam * 60 * 60)) / 60);
                        $detik = $selisih_detik - ($jam * 60 * 60) - ($menit * 60);

                        if ($tgl_scan == "<span class='text-danger'>Tidak Absen Masuk</span>" || $tgl_out == "<span class='text-danger'>Tidak Absen Pulang</span>") {
                            echo "<td>-</td>";
                            $jam = 0;
                            $menit = 0;
                            $detik = 0;
                        } else {
                            echo "<td>" . $jam . " jam " . $menit . " menit " . $detik . " detik" . "</td>";
                        }
                        
                        $totalJam += $jam;
                        $totalMenit += $menit;
                        $totalDetik += $detik;
                        
                        // Cek kembali totalDetik dan totalMenit agar sesuai dengan 60 detik per menit dan 60 menit per jam
                        if ($totalDetik >= 60) {
                            $totalMenit += floor($totalDetik / 60);
                            $totalDetik = $totalDetik % 60;
                        }
                        
                        if ($totalMenit >= 60) {
                            $totalJam += floor($totalMenit / 60);
                            $totalMenit = $totalMenit % 60;
                        }

                        echo "<td>" . $ket_izin . "</td>";

                        echo "</tr>";
                        $no++;
                    }
                    
                    echo "<tr>";
                    echo "<td colspan='6'><b>TOTAL</b></td>";
                    echo "<td><b>" . $totalJam . " jam " . $totalMenit . " menit " . $totalDetik . " detik" . "</b></td>";
                    echo "<td></td>";
                    echo "</tr>";
                } else {
                    echo "<tr><td colspan='4'>Tidak ada data absen</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
        <?php
        echo "<div class='row mb-5'>";
        
        echo "<div class='col-md-6'>";
        echo "<div class='card card-custom shadow-sm'>";
        echo "<div class='card-body card-body-custom d-flex flex-column justify-content-center align-items-center text-center'>";
        echo "<p class='card-text card-text-custom'>Total Terlambat</p><p>" . $jumlah_terlambat . " menit</p>";
        $jumlah_denda = "";
        if ($jumlah_terlambat <= 20) {
            $jumlah_denda = 0;
        } elseif ($jumlah_terlambat > 20 && $jumlah_terlambat <= 80) {
            $jumlah_denda = ($jumlah_terlambat - 20) * 300;
        } elseif ($jumlah_terlambat > 80 && $jumlah_terlambat <= 140) {
            $jumlah_denda = (60 * 300) + (($jumlah_terlambat - 80) * 600);
        }elseif ($jumlah_terlambat > 140) {
            $jumlah_denda = (60 * 300) + (60 * 600) + (($jumlah_terlambat - 140) * 2000);
        }
        $jumlah_denda_rupiah = number_format($jumlah_denda, 0, ',', '.');
        echo "<p class='font-weight-bold'>Rp " . $jumlah_denda_rupiah . "</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        echo "<div class='col-md-6'>";
        echo "<div class='card card-custom shadow-sm'>";
        echo "<div class='card-body card-body-custom text-center d-flex flex-column justify-content-center align-items-center'>";
        $jumlah_tidak_absen = $jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang;
        echo "<p class='card-text card-text-custom'>Total Tidak Absen</p><p>" . $jumlah_tidak_absen . " x Rp 25,000</p>";
        $jumlah_tidak_absen_nominal = $jumlah_tidak_absen * 25000;
        $tidak_absen_rupiah = number_format($jumlah_tidak_absen_nominal, 0, ',', '.');
        echo "<p class='font-weight-bold'>" . $tidak_absen_rupiah . " </p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        // echo "<div class='col-md-4'>";
        // echo "<div class='card card-custom shadow-sm'>";
        // echo "<div class='card-body card-body-custom text-center d-flex flex-column justify-content-center align-items-center'>";
        // echo "<p class='card-text card-text-custom'>Total Izin Tidak Hadir</p><p>" . $jumlah_izin_jam_kerja . "</p>";
        // if($jumlah_izin_jam_kerja > 6 && $jumlah_izin_jam_kerja < 10){
        //     $jumlah_izin_jam_kerja_nominal = ($jumlah_izin_jam_kerja - 6) * 400000;
        //     $jumlah_izin_jam_kerja_rupiah = number_format($jumlah_izin_jam_kerja_nominal, 0, ',', '.');
        // } elseif($jumlah_izin_jam_kerja > 9){
        //     $jumlah_izin_jam_kerja_nominal = (($jumlah_izin_jam_kerja - 9) * 800000) + 1200000;
        //     $jumlah_izin_jam_kerja_rupiah = number_format($jumlah_izin_jam_kerja_nominal, 0, ',', '.');
        // }
        // $jumlah_izin_jam_kerja_nominal = $jumlah_izin_jam_kerja;
        // $jumlah_izin_jam_kerja_rupiah = number_format($jumlah_izin_jam_kerja_nominal, 0, ',', '.');
        // echo "<p class='font-weight-bold'>" . $jumlah_izin_jam_kerja . " </p>";
        // echo "</div>";
        // echo "</div>";
        // echo "</div>";

        echo "</div>";

        // $total_all = $jumlah_denda + $jumlah_tidak_absen_nominal + $jumlah_izin_jam_kerja_nominal;
        $total_all = $jumlah_denda + $jumlah_tidak_absen_nominal;
        $total_all_total = number_format($total_all, 0, ',', '.');

        echo "<div class='row mb-5' style='margin-top: 4vh;'>";

        echo "<div class='col-md-5'>";
        echo "<div class='card card-custom shadow-sm'>";
        echo "<div class='card-body card-body-custom d-flex flex-column justify-content-center align-items-start text-start'>";
        echo "<p class='card-text font-weight-bold'>Keterangan Perhitungan Denda Terlambat</p>";
        echo "<p>20 Menit pertama = Rp 0,- (Free)<br>";
        echo "60 Menit selanjutnya = Rp 300,-/menit<br>";
        echo "60 Menit selanjutnya = Rp 600,-/menit<br>";
        echo "Selanjutnya = Rp 2,000,-/menit</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        echo "<div class='col-md-7'>";
        echo "<div class='card card-custom shadow-sm'>";
        echo "<div class='card-body card-body-custom d-flex flex-column justify-content-center align-items-center text-center'>";
        echo "<p class='card-text font-weight-bold'>TOTAL DENDA</p>";
        echo "<p class='card-text font-weight-bold total-denda'>Rp " . $total_all_total . "</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        echo "</div>";

        ?>