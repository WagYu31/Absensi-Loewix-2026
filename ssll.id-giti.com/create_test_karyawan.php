<?php
include 'conn.php';

$nip = 'TEST001';
$username = 'testkaryawan';
$password = 'test123456';
$nama = 'Karyawan Testing';
$nik = '999001';
$pin_absen = 'TEST001';
$jabatan = 'Staff Testing';
$role = 'karyawan';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 1. Insert/Update Karyawan
$stmtK = $conn->prepare("INSERT INTO karyawan (nip, nik, nama, status_karyawan, pin_absen, jabatan) VALUES (?, ?, ?, 'aktif', ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama), status_karyawan = 'aktif'");
$stmtK->bind_param("sssss", $nip, $nik, $nama, $pin_absen, $jabatan);
$stmtK->execute();
$stmtK->close();

// 2. Insert/Update Users
$stmtU = $conn->prepare("INSERT INTO users (nip, username, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)");
$stmtU->bind_param("ssss", $nip, $username, $hashedPassword, $role);
$stmtU->execute();
$stmtU->close();

// 3. Assign 24-Hour Shift Testing in shift_req
$tgl_mulai = date('Y-01-01');
$tgl_selesai = date('Y-12-31');
$shifting = 'TEST';

// Delete previous test shift req if exists
$conn->query("DELETE FROM shift_req WHERE nip = '$nip'");

$stmtS = $conn->prepare("INSERT INTO shift_req (nip, tgl_mulai, tgl_selesai, shifting) VALUES (?, ?, ?, ?)");
$stmtS->bind_param("ssss", $nip, $tgl_mulai, $tgl_selesai, $shifting);
$stmtS->execute();
$stmtS->close();

echo "<div style='font-family: Arial, sans-serif; padding: 30px; background: #0f172a; color: #fff; border-radius: 12px; max-width: 500px; margin: 50px auto; text-align: center;'>";
echo "<h2 style='color: #3b82f6;'>Akun Karyawan Testing Berhasil Dibuat!</h2>";
echo "<p style='color: #94a3b8; font-size: 0.9rem;'>Sudah otomatis terpasang Shift Testing (Bisa Masuk & Pulang 24 Jam Non-stop)</p>";
echo "<hr style='border-color: rgba(255,255,255,0.1); margin: 20px 0;'>";
echo "<p style='font-size: 1.1rem;'><strong>Nama:</strong> $nama</p>";
echo "<p style='font-size: 1.1rem;'><strong>Username:</strong> <span style='color: #60a5fa;'>$username</span></p>";
echo "<p style='font-size: 1.1rem;'><strong>Password:</strong> <span style='color: #34d399;'>$password</span></p>";
echo "<p style='font-size: 1.1rem;'><strong>NIP / PIN:</strong> $nip</p>";
echo "<a href='index.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Buka Halaman Login</a>";
echo "</div>";

$conn->close();
?>
