<?php
// Koneksi ke database
include 'conn.php';

// Mengambil data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $nip = $_POST["nip"];
    $username = $_POST["new-username"];
    $newPassword = $_POST["new-password"];

    // Query untuk memeriksa apakah NIP sudah terdaftar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM karyawan WHERE nip = ?");
    $stmt->bind_param("s", $nip);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    // Query untuk memeriksa apakah username sudah ada
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($usernameCount);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0 && $usernameCount === 0) {
        // Jika NIP sudah terdaftar dan username belum ada, lakukan proses sign up
        // Use password_hash() to securely hash the password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role, nip) VALUES (?, ?, 'karyawan', ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $nip);
        $stmt->execute();
        $stmt->close();

        $message = "Success";
        echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
        exit();

    } else {
        $message = "Username sudah pernah digunakan!";
        echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
        exit();
    }
}

$conn->close();
?>
