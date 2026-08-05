<?php
// Fungsi untuk menghapus data dari tabel tertentu berdasarkan kondisi
function deleteFromTable($conn, $table, $condition) {
    $query = "DELETE FROM $table WHERE $condition;";
    if ($conn->query($query) === TRUE) {
        return true;
    } else {
        return false;
    }
}

// Memeriksa apakah parameter NIP untuk penghapusan data karyawan telah diterima
if (isset($_GET['deleteNIP'])) {
    $deleteNIP = $_GET['deleteNIP'];

    $tablesToDeleteFrom = [
        "rincian_gaji",
        "denda",
        "tunjangan_lainnya",
        "users",
        "karyawan"
    ];

    $successfulDeletions = 0;

    foreach ($tablesToDeleteFrom as $table) {
        $condition = "nip = '$deleteNIP'";
        if (deleteFromTable($conn, $table, $condition)) {
            $successfulDeletions++;
        }
    }

    if ($successfulDeletions === count($tablesToDeleteFrom)) {
        $message = "Employee data with NIP $deleteNIP has been successfully deleted.";
    } else {
        $message = "Error occurred while deleting employee data.";
    }

    echo "<script>alert('$message'); window.location.href = 'sa-data-karyawan.php';</script>";
}
?>
