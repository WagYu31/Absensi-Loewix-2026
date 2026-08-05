<?php

include "conn.php";

if (isset($_POST["submit"])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Cek apakah file adalah file Excel
    if ($fileType != "xls" && $fileType != "xlsx") {
        echo "Maaf, hanya file Excel yang diizinkan.";
        $uploadOk = 0;
    }

    // Cek apakah file sudah ada
    if (file_exists($target_file)) {
        echo "Maaf, file sudah ada.";
        $uploadOk = 0;
    }

    // Jika upload gagal
    if ($uploadOk == 0) {
        echo "Maaf, file tidak terunggah.";
    } else {
        // Jika upload berhasil
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "File " . basename($_FILES["fileToUpload"]["name"]) . " berhasil terunggah.";

            // Baca file Excel dan impor data ke database
            require_once 'PHPExcel/Classes/PHPExcel.php';
            $objPHPExcel = PHPExcel_IOFactory::load($target_file);
            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

            // Initialize a row counter
            $rowIndex = 0;

            foreach ($sheetData as $row) {
                $rowIndex++;

                // Skip the first two rows
                if ($rowIndex <= 2) {
                    continue;
                }

                // Insert data into the database
                $sql = "INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama, jabatan, departemen, kantor, verifikasi, io, workcode, sn, mesin) VALUES ('" . $row['A'] . "', '" . $row['B'] . "', '" . $row['C'] . "', '" . $row['D'] . "', '" . $row['E'] . "', '" . $row['F'] . "', '" . $row['G'] . "', '" . $row['H'] . "', '" . $row['I'] . "', '" . $row['J'] . "', '" . $row['K'] . "', '" . $row['L'] . "', '" . $row['M'] . "', '" . $row['N'] . "')";

                if ($conn->query($sql) === TRUE) {
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }
            header("Location: data-absen.php");
            exit();
        } else {
            echo "Maaf, terjadi kesalahan saat mengunggah file.";
        }
    }
}

$conn->close();
