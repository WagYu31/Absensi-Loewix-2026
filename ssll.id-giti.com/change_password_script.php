<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}

include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = $_POST["nip"];
    $newPassword = base64_decode($_POST["newPassword"]); // Decode base64-encoded password

    // Hash the new password before storing it in the database
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the user's password in the database
    $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE nip = '$nip'";
    $updateResult = mysqli_query($conn, $updateQuery);

    if ($updateResult) {
        echo "Password updated successfully.";
    } else {
        echo "An error occurred while updating the password.";
    }
} else {
    echo "Invalid request.";
}
?>
