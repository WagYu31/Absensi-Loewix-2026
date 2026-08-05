<?php
session_start();

// 1. Validasi & Keamanan Awal
// =============================================================================
// Cek apakah pengguna telah login dan memiliki peran yang sesuai
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    // Pengguna tidak sah, redirect ke halaman login
    header('Location: index.php');
    exit();
}

// Cek apakah request datang dari form (metode POST) dan semua data ada
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['nip_denda'], $_POST['tanggal_denda'], $_POST['jumlah_denda'], $_POST['keterangan_denda'])) {
    // Jika tidak, redirect ke halaman input denda
    header('Location: denda.php');
    exit();
}

include '../conn.php';

// Ambil dan bersihkan data dari form
$nipDenda = $_POST['nip_denda'];
$tanggalDenda = $_POST['tanggal_denda'];
$jumlahDenda = (int) $_POST['jumlah_denda']; // Pastikan jumlah adalah integer
$keteranganDenda = trim($_POST['keterangan_denda']);

$pesan_notifikasi = "Terjadi kesalahan yang tidak diketahui.";
$redirect_page = "denda.php";

// 2. Memulai "Mode Aman" (Transaksi Database)
// =============================================================================
// Semua proses di bawah ini akan dijalankan. Jika ada 1 saja yang gagal, semua akan dibatalkan.
$conn->begin_transaction();

try {
    // LANGKAH 1: Simpan catatan denda ke tabel `denda`
    // Menambahkan 'ket1' secara default sesuai logika di halaman sebelumnya
    $stmt_denda = $conn->prepare("INSERT INTO denda (nip, tanggal, jumlah, keterangan, ket1) VALUES (?, ?, ?, ?, 'Denda')");
    $stmt_denda->bind_param("ssis", $nipDenda, $tanggalDenda, $jumlahDenda, $keteranganDenda);
    $stmt_denda->execute();
    $stmt_denda->close();

    // LANGKAH 2: Cek apakah sudah ada rincian gaji untuk NIP ini di bulan & tahun yang sama
    $bulan_denda = date('m', strtotime($tanggalDenda));
    $tahun_denda = date('Y', strtotime($tanggalDenda));

    $stmt_cek = $conn->prepare("SELECT id_rincian_gaji, denda FROM rincian_gaji WHERE nip = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ? LIMIT 1");
    $stmt_cek->bind_param("sss", $nipDenda, $bulan_denda, $tahun_denda);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();
    
    if ($result_cek->num_rows > 0) {
        // JIKA SUDAH ADA: Update denda yang sudah ada dengan menambahkan jumlah baru
        $row = $result_cek->fetch_assoc();
        $denda_baru = $row['denda'] + $jumlahDenda;
        $id_rincian = $row['id_rincian_gaji'];
        
        $stmt_update = $conn->prepare("UPDATE rincian_gaji SET denda = ? WHERE id_rincian_gaji = ?");
        $stmt_update->bind_param("ii", $denda_baru, $id_rincian);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // JIKA BELUM ADA: Insert baris baru ke rincian_gaji
        $stmt_insert = $conn->prepare("INSERT INTO rincian_gaji (nip, tanggal, denda) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("ssi", $nipDenda, $tanggalDenda, $jumlahDenda);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_cek->close();

    // 3. Finalisasi: Jika semua langkah di atas berhasil, simpan perubahan secara permanen
    // =============================================================================
    $conn->commit();
    $pesan_notifikasi = "Sukses! Denda berhasil ditambahkan.";
    
} catch (mysqli_sql_exception $exception) {
    // 4. Penanganan Error: Jika ada satu saja error, batalkan semua perubahan
    // =============================================================================
    $conn->rollback();
    // Ambil pesan error dari database untuk debugging (opsional, bisa dicatat ke log)
    $pesan_notifikasi = "Gagal! Terjadi kesalahan pada database. Perubahan dibatalkan. Error: " . $exception->getMessage();
    
} finally {
    // Selalu tutup koneksi
    $conn->close();
}

// 5. Tampilkan Notifikasi dan Redirect Pengguna
// =============================================================================
// Mekanisme notifikasi ini sama persis dengan output kode lama Anda
echo "<script>
        alert('" . addslashes($pesan_notifikasi) . "');
        window.location.href = '" . $redirect_page . "';
      </script>";
exit();
?>