<?php
require '../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];

    // Query untuk memeriksa apakah username dan email cocok di antara tabel users dan karyawan
    $checkQuery = "SELECT u.nip FROM users u JOIN karyawan k ON u.nip = k.nip WHERE u.username = '$username' AND k.email = '$email'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if ($checkResult->num_rows > 0) {
            
        $checkExistingQuery = "SELECT * FROM reset_pw WHERE username = '$username'";
        $existingResult = mysqli_query($conn, $checkExistingQuery);

        if ($existingResult && mysqli_num_rows($existingResult) > 0) {
            $deleteQuery = "DELETE FROM reset_pw WHERE username = '$username'";
            $deleteResult = mysqli_query($conn, $deleteQuery);

            if (!$deleteResult) {
                header("Location: index.php?message=failed");
                exit();
            }
        }
        // Generate OTP (contoh sederhana, seharusnya menggunakan metode yang lebih aman)
        $otp = rand(100000, 999999);

        // Instantiation and passing true enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'account-info@grav-tech.com';
            $mail->Password   = '?D3v3L0p3R';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;

            //Recipients
            $mail->setFrom('account-info@grav-tech.com', 'GITI Account Information');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = <<<EOT
            <html>
            <head>
                <style>
                    body {
                        font-family: 'Lato', sans-serif;
                    }
                    .container {
                        background-color: white;
                        padding: 20px;
                        border-radius: 5px;
                    }
                    .otp{
                        width: 180px;
                        text-align: center;
                        padding: 10px;
                        background-color: #228daa;
                        font-size: 20px;
                        color: white;
                    }
                    .logo {
                        max-width: 150px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <img src="https://drive.google.com/uc?export=view&id=1NXcjvQWotjvmdSNiGmRGI3pqFeS3UFu1" alt="Company Logo" class="logo">
                    <h2>Hello $username,</h2>
                    <p>Your OTP code is: </p>
                    <p><div class="otp">$otp</div></p>
                    <p>Please use this code to verify your account.</p>
                    <p>If you did not request this OTP, please ignore this email.</p>
                </div>
            </body>
            </html>
            EOT;
            $mail->AltBody = "Hello $username,\n\nYour OTP code is: $otp";

            $mail->send();


            // Insert data ke tabel reset_pw
            $insertQuery = "INSERT INTO reset_pw (username, otp, sukses) VALUES ('$username', '$otp', 'tidak')";
            $insertResult = mysqli_query($conn, $insertQuery);

            if ($insertResult) {
                header("Location: verify_otp.php?username=$username");
                exit();
            } else {
                header("Location: index.php?message=failed");
                exit();
            }

        } catch (Exception $e) {
            header("Location: index.php?message=error");
            exit();
        }
    } else {
        $message = "Username dan Email tidak sesuai!";
        echo "<script>alert('$message'); window.location.href = 'lupa.php';</script>";
        exit();
    }
}
?>