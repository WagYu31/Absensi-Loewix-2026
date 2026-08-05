<?php
session_start();

// Koneksi ke database
include 'conn.php';

// Mengambil data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Query untuk memeriksa username dan hashed password
    $stmt = $conn->prepare("SELECT nip, role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($nip, $role, $hashedPassword);
    $stmt->fetch();

    // Verifikasi password menggunakan password_verify()
    if (password_verify($password, $hashedPassword)) {
        $_SESSION["nip"] = $nip;
        $_SESSION["role"] = $role;

        if ($role == "admin") {
            header("Location: staff/kalender_kerja.php");
        } elseif ($role == "karyawan") {
            header("Location: karyawan/profile.php");
        } elseif ($role == "superadmin") {
            header("Location: staff/penggajian.php");
        }
    } else {
        $message = "Login failed. Please double-check your username and password.";
        echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
        exit();
    }

    $stmt->close();
}

$conn->close();

?>
