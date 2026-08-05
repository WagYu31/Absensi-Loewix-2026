<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}

include 'conn.php';

if (isset($_GET['nip']) && isset($_GET['status'])) {
    $nip = $_GET['nip'];
    $status = $_GET['status'];

    $queryUpdate = "UPDATE karyawan SET status_karyawan = '$status' WHERE nip = '$nip'";
    if ($conn->query($queryUpdate) === TRUE) {
        echo "Status updated successfully!";
    } else {
        echo "Error occurred while updating status: " . $conn->error;
    }
}
?>
