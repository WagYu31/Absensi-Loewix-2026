<?php
session_start();

if (!isset($_SESSION['nip'])) {
    echo "error: unauthorized";
    exit();
}

include '../conn.php';

$nip = $_SESSION['nip'];
$newPassword = $_POST['newPassword'] ?? '';

// Validate input
if (empty($newPassword)) {
    echo "error: empty_password";
    exit();
}

// Additional password strength validation
if (strlen($newPassword) < 8) {
    echo "error: password_too_short";
    exit();
}

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password in database
$query = "UPDATE users SET password = ? WHERE nip = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $hashedPassword, $nip);

if ($stmt->execute()) {
    // Logout user after password change (optional)
    session_unset();
    session_destroy();
    echo "success";
} else {
    echo "error: update_failed";
}

$stmt->close();
$conn->close();
