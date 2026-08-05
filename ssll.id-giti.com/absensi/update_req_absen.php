<?php
include '../conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $pin_absen = $_POST['pin'];
    $date = $_POST['tanggal'];
    $dateY = DateTime::createFromFormat('Y-m-d', $date);
    $tanggal = $dateY->format('d-m-Y');
    $jam = $_POST['jam'];
    $tglJam = $tanggal . " " . $jam;
    $kantor = "Ya";
    
    // Periksa apakah semua field yang diperlukan ada
    if (!empty($pin_absen) && !empty($date) && !empty($jam)) {
        // Ambil data karyawan berdasarkan pin_absen
        $query = "SELECT nama, nik FROM karyawan WHERE pin_absen = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('s', $pin_absen);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $data = $res->fetch_assoc();
                $namaKar = $data['nama'];
                $nikKar = $data['nik'];
                
                // Insert data ke tabel absen
                $sql = "INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama, kantor) VALUES (?, ?, ?, ?, ?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param('sssssss', $tglJam, $tanggal, $jam, $pin_absen, $nikKar, $namaKar, $kantor);

                    if ($stmt->execute()) {
                        header("Location: req_absen.php");
                        exit();
                    } else {
                        echo "Error: " . $stmt->error;
                    }

                    $stmt->close();
                } else {
                    echo "Error: " . $conn->error;
                }
            } else {
                echo "Karyawan dengan PIN tersebut tidak ditemukan.";
            }
            $stmt->close();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Semua field harus diisi.";
    }

    $conn->close();
} else {
    echo "Metode request tidak valid.";
}
?>
