<?php
include 'conn.php';

$nip = 'SA999';
$username = 'superadmin';
$password = 'superadmin123';
$nama = 'Super Admin';
$nik = '999999999';
$role = 'superadmin';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 1. Insert/Update Karyawan
$stmtK = $conn->prepare("INSERT INTO karyawan (nip, nik, nama, status_karyawan, pin_absen, jabatan, tanggal_masuk) VALUES (?, ?, ?, 'aktif', ?, 'Super Admin', CURDATE()) ON DUPLICATE KEY UPDATE nama = VALUES(nama), status_karyawan = 'aktif', jabatan = 'Super Admin', tanggal_masuk = IFNULL(tanggal_masuk, CURDATE())");
$stmtK->bind_param("ssss", $nip, $nik, $nama, $nip);
$stmtK->execute();
$stmtK->close();

// 2. Insert/Update Users
$stmtU = $conn->prepare("INSERT INTO users (nip, username, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)");
$stmtU->bind_param("ssss", $nip, $username, $hashedPassword, $role);
if ($stmtU->execute()) {
    echo "<div style='font-family: Arial, sans-serif; padding: 30px; background: #0f172a; color: #fff; border-radius: 12px; max-width: 500px; margin: 50px auto; text-align: center;'>";
    echo "<h2 style='color: #10b981;'>Akun Super Admin Berhasil Dibuat!</h2>";
    echo "<hr style='border-color: rgba(255,255,255,0.1); margin: 20px 0;'>";
    echo "<p style='font-size: 1.1rem;'><strong>Username:</strong> <span style='color: #60a5fa;'>$username</span></p>";
    echo "<p style='font-size: 1.1rem;'><strong>Password:</strong> <span style='color: #34d399;'>$password</span></p>";
    echo "<p style='font-size: 1.1rem;'><strong>Role:</strong> $role</p>";
    echo "<p style='font-size: 1.1rem;'><strong>NIP:</strong> $nip</p>";
    echo "<a href='index.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Buka Halaman Login</a>";
    echo "</div>";
} else {
    echo "<h1>Gagal membuat akun: " . $conn->error . "</h1>";
}
$stmtU->close();
$conn->close();
?>
