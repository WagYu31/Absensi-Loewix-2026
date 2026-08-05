<?php
// Ambil data karyawan dari database
// $host = "localhost";
// $user = "root";
// $password = "";
// $database = "salary";

// $host = "localhost";
// $user = "u836263092_rootSalary";
// $password = "Eddie@18";
// $database = "u836263092_salary";

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
?>