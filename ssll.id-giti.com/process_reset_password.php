<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $otp = $_POST["otp"];
    $newPassword = $_POST["new-password"];
    $confirmPassword = $_POST["confirm-password"];

    // Query untuk memeriksa apakah username dan otp cocok di tabel reset_pw
    $checkQuery = "SELECT * FROM reset_pw WHERE username = '$username' AND otp = '$otp' AND sukses = 'tidak'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if ($checkResult->num_rows === 1) {
        $resetData = mysqli_fetch_assoc($checkResult);
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE username = '$username'";
            
            if (mysqli_query($conn, $updateQuery)) {
                // Update status sukses pada tabel reset_pw
                $updateStatusQuery = "UPDATE reset_pw SET sukses = 'ya' WHERE otp = " . $resetData['otp'];
                mysqli_query($conn, $updateStatusQuery);
                
                $message = "Password berhasil di perbaharui!";
                echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
                exit();
            } else {
                $message = "Password gagal diperbaharui!!";
                echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
                exit();
            }
        } else {
            $message = "Password tidak sama!";
            echo "<script>alert('$message'); window.location.href = 'reset_password.php?username=$username&otp=$otp';</script>";
            exit();
        }
    } else {
        $message = "OTP tidak valid!";
        echo "<script>alert('$message'); window.location.href = 'reset_password.php?username=$username&otp=$otp';</script>";
        exit();
    }
}
?>
