<?php
$query_shift_req = "SELECT * FROM shift_req WHERE nip = ?";

// Prepare statement
if ($stmt = $conn->prepare($query_shift_req)) {
    // Bind parameter
    $stmt->bind_param('s', $pinAbsen);

    // Execute statement
    if ($stmt->execute()) {
        // Ambil hasil query
        $result_shift_req = $stmt->get_result();

        // Periksa apakah ada baris yang ditemukan
        if ($result_shift_req->num_rows > 0) {
            // Ambil data shift yang ditemukan
            while ($row_shift_req = $result_shift_req->fetch_assoc()) {

                $tgl_mulai_shift_req = date('Y-m-d', strtotime($row_shift_req['tgl_mulai']));
                $tgl_selesai_shift_req = date('Y-m-d', strtotime($row_shift_req['tgl_selesai']));


                // Periksa apakah tanggal scan berada di antara tanggal mulai dan tanggal selesai
                if ($cek_tgl >= $tgl_mulai_shift_req && $cek_tgl <= $tgl_selesai_shift_req) {
                    // Jika ya, ambil nilai shifting
                    $shifting = $row_shift_req['shifting'];
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
