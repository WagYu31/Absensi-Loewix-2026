<?php
session_start();

// Cek keamanan: Hanya admin dan superadmin yang bisa menjalankan proses ini
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Akses ditolak.'];
    header('Location: data-karyawan.php');
    exit();
}

include '../conn.php';

// Pastikan request adalah POST dan data yang diterima adalah array
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['nip']) || !is_array($_POST['nip'])) {
    header('Location: input-gaji.php');
    exit();
}

$nips = $_POST['nip'];
$gaji_pokoks = $_POST['gaji_pokok'];
$updated_count = 0;
$error_count = 0;

// Siapkan query UPDATE di luar loop untuk efisiensi
$query = "UPDATE karyawan SET gaji_pokok = ? WHERE nip = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    foreach ($nips as $index => $nip) {
        $gaji_str = $gaji_pokoks[$index] ?? '0';
        
        // Hanya proses jika input gaji tidak kosong
        if (!empty($gaji_str)) {
            // Bersihkan format Rupiah (misal: "1.500.000" menjadi 1500000)
            $gaji_numeric = (int) preg_replace('/[^\d]/', '', $gaji_str);

            // Bind parameter dan eksekusi
            $stmt->bind_param("is", $gaji_numeric, $nip);
            if ($stmt->execute()) {
                $updated_count++;
            } else {
                $error_count++;
            }
        }
    }
    $stmt->close();
} else {
    // Gagal mempersiapkan statement
    $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Terjadi kesalahan pada database.'];
    header('Location: data-karyawan.php');
    exit();
}

// Siapkan pesan notifikasi berdasarkan hasil proses
if ($updated_count > 0) {
    $_SESSION['pesan_flash'] = [
        'tipe' => 'success', 
        'pesan' => "Sukses! $updated_count data gaji karyawan telah berhasil diperbarui."
    ];
} else {
    $_SESSION['pesan_flash'] = [
        'tipe' => 'warning', 
        'pesan' => 'Tidak ada data gaji yang diperbarui. Pastikan Anda mengisi kolom gaji.'
    ];
}

$conn->close();

// Redirect kembali ke halaman data karyawan untuk melihat hasilnya
header('Location: data-karyawan.php');
exit();
?>