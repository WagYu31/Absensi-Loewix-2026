<?php
session_start();

if (!isset($_SESSION['nip'])) {
    echo "error: unauthorized";
    exit();
}

include '../conn.php';

$nip = $_SESSION['nip'];
$oldPassword = $_POST['oldPassword'] ?? '';

// Validate input
if (empty($oldPassword)) {
    echo "error: empty_password";
    exit();
}

// Get hashed password from database
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
$hashedPassword = $user['password'];

// Verify password (assuming passwords are hashed with password_hash())
if (password_verify($oldPassword, $hashedPassword)) {
    echo "success";
} else {
    echo "error: wrong_password";
}

$stmt->close();
$conn->close();
