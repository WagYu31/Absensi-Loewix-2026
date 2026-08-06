<?php
$host = "localhost";
$user = "u836263092_ssll";
$password = "WagyuA531052002.";
$database = "u836263092_ssll";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection to the database failed. " . $conn->connect_error);
}

date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

// Auto-restore session from persistent cookie if PHP session expired
if (session_status() === PHP_SESSION_ACTIVE || session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        @session_start();
    }
    if (empty($_SESSION['nip']) && !empty($_COOKIE['absensi_nip']) && !empty($_COOKIE['absensi_token'])) {
        $c_nip = $_COOKIE['absensi_nip'];
        $c_role = $_COOKIE['absensi_role'] ?? '';
        $c_token = $_COOKIE['absensi_token'];
        
        if ($c_token === md5($c_nip . 'SALT_SECRET_LOEWIX_2026' . $c_role)) {
            $_SESSION['nip'] = $c_nip;
            $_SESSION['role'] = $c_role;
        }
    }
}
?>