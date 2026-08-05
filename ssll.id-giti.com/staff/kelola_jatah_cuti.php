<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

$pesan_notifikasi = null;
$data_untuk_diedit = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = $_POST['id'] ?? null;
    $tahun = filter_input(INPUT_POST, 'tahun', FILTER_SANITIZE_NUMBER_INT);
    $jumlah = filter_input(INPUT_POST, 'jumlah', FILTER_SANITIZE_NUMBER_INT);

    if (!empty($tahun) && !empty($jumlah)) {
        try {
            if (empty($id)) {
                $stmt = $conn->prepare("INSERT INTO jatah_cuti_tahunan (tahun, jumlah) VALUES (?, ?)");
                $stmt->bind_param("ii", $tahun, $jumlah);
                $stmt->execute();
                $_SESSION['pesan_flash'] = ['tipe' => 'success', 'pesan' => 'Jatah cuti untuk tahun ' . $tahun . ' berhasil ditambahkan.'];
            } else {
                $stmt = $conn->prepare("UPDATE jatah_cuti_tahunan SET tahun = ?, jumlah = ? WHERE id = ?");
                $stmt->bind_param("iii", $tahun, $jumlah, $id);
                $stmt->execute();
                $_SESSION['pesan_flash'] = ['tipe' => 'success', 'pesan' => 'Jatah cuti untuk tahun ' . $tahun . ' berhasil diperbarui.'];
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Error: Jatah cuti untuk tahun ' . $tahun . ' sudah ada.'];
            } else {
                $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Terjadi kesalahan pada database.'];
            }
        } finally {
            if(isset($stmt)) $stmt->close();
        }
    } else {
        $_SESSION['pesan_flash'] = ['tipe' => 'warning', 'pesan' => 'Tahun dan Jumlah tidak boleh kosong.'];
    }
    
    header("Location: kelola_jatah_cuti.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM jatah_cuti_tahunan WHERE id = ?");
    $stmt->bind_param("i", $id_hapus);
    if ($stmt->execute()) {
        $_SESSION['pesan_flash'] = ['tipe' => 'success', 'pesan' => 'Data jatah cuti berhasil dihapus.'];
    } else {
        $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal menghapus data.'];
    }
    $stmt->close();
    header("Location: kelola_jatah_cuti.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM jatah_cuti_tahunan WHERE id = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_untuk_diedit = $result->fetch_assoc();
    $stmt->close();
}

$jatah_cuti_list = $conn->query("SELECT * FROM jatah_cuti_tahunan ORDER BY tahun DESC")->fetch_all(MYSQLI_ASSOC);

if (isset($_SESSION['pesan_flash'])) {
    $pesan_notifikasi = $_SESSION['pesan_flash'];
    unset($_SESSION['pesan_flash']);
}

function hitungDetailHari($tahun, $conn) {
    $isLeap = (($tahun % 4 == 0) && ($tahun % 100 != 0)) || ($tahun % 400 == 0);
    $totalHari = $isLeap ? 366 : 365;

    $minggu = 0;
    $date = new DateTime("$tahun-01-01");
    while ($date->format('Y') == $tahun) {
        if ($date->format('N') == 7) {
            $minggu++;
        }
        $date->modify('+1 day');
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total_libur FROM kalender_kerja WHERE YEAR(tanggal_merah) = ? AND libur = 'yes' AND DAYOFWEEK(tanggal_merah) != 1 AND deleted_at IS NULL");
    $stmt->bind_param("i", $tahun);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $liburNasional = (int)$res['total_libur'];
    $stmt->close();

    $efektif = $totalHari - $minggu - $liburNasional;

    return [
        'efektif' => $efektif,
        'libur' => $liburNasional
    ];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jatah Cuti Tahunan - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .table-sm-custom { font-size: 0.9rem; }
        .bg-info-light { background-color: #eef7ff !important; }
        .bg-warning-light { background-color: #fff9e6 !important; }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Pengaturan Jatah Cuti</h1>
                <p>Kelola kuota cuti tahunan dan tinjau estimasi hari kerja.</p>
            </div>
        </div>
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <?php if ($pesan_notifikasi): ?>
                <div class="alert alert-<?php echo $pesan_notifikasi['tipe']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($pesan_notifikasi['pesan']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa-solid fa-plus-circle title-icon"></i> <?php echo $data_untuk_diedit ? 'Edit Jatah Cuti Tahun ' . htmlspecialchars($data_untuk_diedit['tahun']) : 'Tambah Jatah Cuti Baru'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="kelola_jatah_cuti.php" method="POST">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?php echo $data_untuk_diedit['id'] ?? ''; ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="tahun" class="form-label">Tahun</label>
                                    <input type="number" class="form-control" id="tahun" name="tahun" placeholder="Contoh: 2025" value="<?php echo $data_untuk_diedit['tahun'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label for="jumlah" class="form-label">Jumlah Hari Cuti</label>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah" placeholder="Contoh: 12" value="<?php echo $data_untuk_diedit['jumlah'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100"><?php echo $data_untuk_diedit ? 'Update' : 'Simpan'; ?></button>
                                    <?php if ($data_untuk_diedit): ?>
                                        <a href="kelola_jatah_cuti.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa-solid fa-list-ul title-icon"></i> Daftar Jatah Cuti & Estimasi Kalender</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 table-sm-custom">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th>Tahun</th>
                                        <th>Jatah Cuti</th>
                                        <th class="bg-warning-light">Hari Libur (Tidak Termasuk Minggu)</th>
                                        <th class="bg-info-light">Estimasi Hari Kerja</th>
                                        <th style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($jatah_cuti_list)): ?>
                                        <tr><td colspan="6" class="text-center p-4 text-muted">Belum ada data jatah cuti.</td></tr>
                                    <?php endif; ?>
                                    <?php $no = 1; foreach ($jatah_cuti_list as $item): 
                                        $detail = hitungDetailHari($item['tahun'], $conn);
                                    ?>
                                    <tr class="text-center">
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['tahun']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['jumlah']); ?> Hari</td>
                                        <td class="bg-warning-light">
                                            <span class="text-danger fw-bold"><?php echo $detail['libur']; ?> Hari</span>
                                        </td>
                                        <td class="bg-info-light">
                                            <span class="text-primary fw-bold"><?php echo $detail['efektif']; ?> Hari</span>
                                        </td>
                                        <td>
                                            <a href="kelola_jatah_cuti.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i></a>
                                            <button onclick="konfirmasiHapus(<?php echo $item['id']; ?>)" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">
                            <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                            <strong>Peringatan:</strong> Kolom "Perkiraan Hari Kerja Efektif" bersifat estimasi. 
                            Hasil dihitung berdasarkan total hari dalam setahun dikurangi jumlah hari Minggu dan hari libur yang diinput pada Kalender Kerja. 
                            Pastikan data Kalender Kerja untuk tahun tersebut sudah lengkap agar angka lebih akurat.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function konfirmasiHapus(id) {
            if (confirm("Apakah Anda yakin ingin menghapus data jatah cuti ini?")) {
                window.location.href = 'kelola_jatah_cuti.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>