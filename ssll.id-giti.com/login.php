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
    $stmt->close();

    // Verifikasi password menggunakan password_verify()
    if ($hashedPassword && password_verify($password, $hashedPassword)) {
        $_SESSION["nip"] = $nip;
        $_SESSION["role"] = $role;

        // Set 30-day persistent cookie to prevent unexpected session timeout during attendance
        $secret_token = md5($nip . 'SALT_SECRET_LOEWIX_2026' . $role);
        setcookie('absensi_nip', $nip, time() + (86400 * 30), '/', '', false, true);
        setcookie('absensi_role', $role, time() + (86400 * 30), '/', '', false, true);
        setcookie('absensi_token', $secret_token, time() + (86400 * 30), '/', '', false, true);

        // Pengalihan Otomatis Setelah Login Berhasil -> Langsung ke Dashboard Utama
        if ($role == "karyawan") {
            header("Location: karyawan/home.php");
        } else {
            header("Location: staff/grafik-kinerja.php");
        }
        exit();
    } else {
        $message = "Login gagal! Silakan periksa kembali Username dan Password Anda.";
        echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
        exit();
    }
}

$conn->close();
?>
