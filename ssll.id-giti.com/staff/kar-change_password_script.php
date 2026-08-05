<?php
session_start();
include '../conn.php';

if(isset($_POST['nip']) && isset($_POST['newPassword'])) {
    $nip = $_POST['nip'];
    $newPassword = $_POST['newPassword']; // Mengambil password baru yang dikirim JS

    // Enkripsi password menggunakan standar keamanan PHP (Bcrypt)
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE nip = ?");
    $stmt->bind_param("ss", $hashedPassword, $nip);
    
    if($stmt->execute()){
        echo "Password berhasil diubah!";
    } else {
        echo "Gagal mengubah password.";
    }
    $stmt->close();
}
$conn->close();
?>