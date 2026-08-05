<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = $_POST["nip"];
    $enteredOldPassword = $_POST["oldPassword"];

    // Fetch the hashed old password from the database for the given NIP
    $sql = "SELECT password FROM users WHERE nip = '$nip'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $hashedOldPasswordFromDB = $row["password"];

        // Verify if the entered old password matches the stored hash
        if (password_verify($enteredOldPassword, $hashedOldPasswordFromDB)) {
            // Password matches, return success response
            echo "success";
        } else {
            // Password doesn't match, return error response
            echo "error";
        }
    } else {
        // User not found, return error response
        echo "error";
    }
} else {
    // Invalid request method
    echo "Invalid request method.";
}
?>
