<?php
session_start();

// 1. Validasi & Keamanan Awal
// =============================================================================

// PERBAIKAN: Logika session check diperbaiki menggunakan !in_array
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    // Arahkan ke halaman login jika tidak memiliki akses
    header('Location: index.php');
    exit();
}

// Periksa apakah ID Denda dikirimkan melalui metode GET
if (!isset($_GET['id_denda']) || !ctype_digit($_GET['id_denda'])) {
    // Jika parameter id tidak valid atau tidak ada, arahkan kembali
    header('Location: kelola_denda.php');
    exit();
}

$id_denda = $_GET['id_denda'];

include '../conn.php';

// Siapkan variabel untuk notifikasi dan halaman redirect
$pesan_notifikasi = "Terjadi kesalahan yang tidak diketahui.";
$redirect_page = "denda.php";


// 2. Memulai "Mode Aman" (Transaksi Database)
// =============================================================================
$conn->begin_transaction();

try {
    // LANGKAH 1: Ambil detail denda yang akan dihapus.
    // Kunci baris ini (`FOR UPDATE`) untuk mencegah proses lain mengubahnya saat kita bekerja.
    $stmt_get = $conn->prepare("SELECT nip, tanggal, jumlah FROM denda WHERE id_denda = ? FOR UPDATE");
    $stmt_get->bind_param("i", $id_denda);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();

    if ($result_get->num_rows === 0) {
        // Jika denda dengan ID tersebut tidak ditemukan, lempar error.
        throw new Exception("Data denda tidak ditemukan.");
    }
    
    // Simpan detail denda ke variabel
    $dendaData = $result_get->fetch_assoc();
    $nipDenda = $dendaData['nip'];
    $tanggalDenda = $dendaData['tanggal'];
    $jumlahDenda = $dendaData['jumlah'];
    $stmt_get->close();

    // LANGKAH 2: Hapus data dari tabel `denda`
    $stmt_delete = $conn->prepare("DELETE FROM denda WHERE id_denda = ?");
    $stmt_delete->bind_param("i", $id_denda);
    $stmt_delete->execute();
    $stmt_delete->close();
    
    // LANGKAH 3: Kurangi jumlah denda di tabel `rincian_gaji`
    // Menggunakan GREATEST(0, ...) untuk memastikan nilai denda tidak menjadi minus
    $bulan_denda = date('m', strtotime($tanggalDenda));
    $tahun_denda = date('Y', strtotime($tanggalDenda));

    $stmt_update = $conn->prepare("UPDATE rincian_gaji SET denda = GREATEST(0, denda - ?) WHERE nip = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $stmt_update->bind_param("isss", $jumlahDenda, $nipDenda, $bulan_denda, $tahun_denda);
    $stmt_update->execute();
    $stmt_update->close();

    // 4. Finalisasi: Jika semua langkah berhasil, simpan perubahan
    // =============================================================================
    $conn->commit();
    $pesan_notifikasi = "Sukses! Data denda telah berhasil dihapus.";

} catch (Exception $e) {
    // 5. Penanganan Error: Jika ada satu saja error, batalkan semua perubahan
    // =============================================================================
    $conn->rollback();
    $pesan_notifikasi = "Gagal! Terjadi kesalahan. Perubahan dibatalkan. (" . $e->getMessage() . ")";

} finally {
    // Selalu tutup koneksi database
    $conn->close();
}

// 6. Tampilkan Notifikasi dan Redirect Pengguna
// =============================================================================
echo "<script>
        alert('" . addslashes($pesan_notifikasi) . "');
        window.location.href = '" . $redirect_page . "';
      </script>";
exit();
?>