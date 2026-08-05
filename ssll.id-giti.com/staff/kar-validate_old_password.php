<?php
session_start();

if (!isset($_SESSION['nip'])) {
    echo "error: unauthorized";
    exit();
}

// Pastikan path conn.php sesuai dengan lokasi file ini
include '../conn.php'; 

$nip = $_SESSION['nip'];
$oldPassword = $_POST['oldPassword'] ?? '';

// Validate input
if (empty($oldPassword)) {
    echo "error: empty_password";
    exit();
}

// Get password dari database
$query = "SELECT password FROM users WHERE nip = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $nip);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "error: user_not_found";
    exit();
}

$user = $result->fetch_assoc();
$dbPassword = $user['password'];

// Validasi Multi-Format
$is_valid = false;

if (password_verify($oldPassword, $dbPassword)) {
    $is_valid = true; // Jika format di DB adalah Bcrypt (Standar PHP)
} elseif ($dbPassword === $oldPassword) {
    $is_valid = true; // Jika format di DB adalah Teks Biasa (Plain Text)
} elseif ($dbPassword === base64_encode($oldPassword)) {
    $is_valid = true; // Jika format di DB adalah Base64 (Efek Javascript btoa)
} elseif ($dbPassword === md5($oldPassword)) {
    $is_valid = true; // Jika format di DB adalah MD5
}

if ($is_valid) {
    echo "success";
} else {
    echo "error: wrong_password";
}

$stmt->close();
$conn->close();
?>