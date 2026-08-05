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

        // Cek apakah ada karyawan yang berulang tahun hari ini
        $today_md = date('m-d');
        $has_birthday = false;
        $sql_bday = "SELECT 1 FROM karyawan WHERE DATE_FORMAT(tanggal_lahir, '%m-%d') = '$today_md' AND status_karyawan = 'aktif' AND deleted_at IS NULL LIMIT 1";
        $res_bday = $conn->query($sql_bday);
        if ($res_bday && $res_bday->num_rows > 0) {
            $has_birthday = true;
        }

        // Pengalihan Otomatis Berdasarkan Ulang Tahun
        if ($has_birthday) {
            if ($role == "karyawan") {
                header("Location: karyawan/kalender_kerja.php");
            } else {
                header("Location: staff/kalender_kerja.php");
            }
            exit();
        }

        // Pengalihan Normal Jika Tidak Ada Yang Ulang Tahun
        if ($role == "admin") {
            header("Location: staff/kalender_kerja.php");
        } elseif ($role == "karyawan") {
            header("Location: karyawan/profile.php");
        } elseif ($role == "superadmin") {
            header("Location: staff/penggajian.php");
        }
        exit();
    } else {
        $message = "Login failed. Please double-check your username and password.";
        echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
        exit();
    }
}

$conn->close();
?>
