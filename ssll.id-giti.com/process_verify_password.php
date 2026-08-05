<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $otp = $_POST["otp"];

    // Query untuk memeriksa apakah username dan otp cocok di tabel reset_pw
    $checkQuery = "SELECT * FROM reset_pw WHERE username = '$username' AND otp = '$otp' AND sukses = 'tidak'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if ($checkResult->num_rows === 1) {
        header("Location: reset_password.php?username=$username&otp=$otp&message=OK");
        exit();
    } else {
        $message = "Invalid OTP!";
        echo "<script>alert('$message'); window.location.href = 'verify_otp.php?username=$username&otp=$otp';</script>";
        exit();
    }
}
?>
