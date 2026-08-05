<?php

$query_izin_jam = "SELECT * FROM izin_jam_kerja WHERE nip = ?";

// Prepare statement
if ($stmt = $conn->prepare($query_izin_jam)) {
    // Bind parameter
    $stmt->bind_param('s', $pinAbsen);

    // Execute statement
    if ($stmt->execute()) {
        // Ambil hasil query
        $result_izin_jam = $stmt->get_result();

        // Periksa apakah ada baris yang ditemukan
        if ($result_izin_jam->num_rows > 0) {
            // Ambil data shift yang ditemukan
            while ($row_izin_jam = $result_izin_jam->fetch_assoc()) {

                $tgl_req_izin_jam = date('Y-m-d', strtotime($row_izin_jam['tgl_izin']));
                $pada = $row_izin_jam['pada'];


                // Periksa apakah tanggal scan berada di antara tanggal mulai dan tanggal selesai
                if ($cek_tgl == $tgl_req_izin_jam) {
                    // Jika ya, ambil nilai shifting
                    $keterlambatan_menit = 0;
                    $jumlah_izin_jam_kerja++;
                    if ($pada == "1") {
                        $pada = "Masuk";
                    } else {
                        $pada = "Keluar";
                    }
                    $ket_izin = "Izin Setengah Hari (Pada Jam " . $pada . " Kerja)";
                }
            }
        } else {
        }
    } else {
        echo "Error: " . $stmt->error;
    }

    // Tutup statement
    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}
