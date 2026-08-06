<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit();
}

include '../conn.php';

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

// Optimized Instant Day Counter
function hitungDetailHari($tahun, $conn) {
    $isLeap = (($tahun % 4 == 0) && ($tahun % 100 != 0)) || ($tahun % 400 == 0);
    $totalHari = $isLeap ? 366 : 365;

    // Mathematical Sunday count (Instant 0ms)
    $startJan1Day = date('N', strtotime("$tahun-01-01"));
    $minggu = 52;
    if (($isLeap && ($startJan1Day == 6 || $startJan1Day == 7)) || (!$isLeap && $startJan1Day == 7)) {
        $minggu = 53;
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
    <title>Kelola Jatah Cuti Tahunan - Gravitti Tech</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    
    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0284c7 100%);
            --card-radius-lg: 24px;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #f1f5f9 !important;
            color: #0f172a;
        }

        .main-content-wrapper {
            background: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%) !important;
            min-height: 100vh;
        }

        /* Hero Header Banner */
        .page-specific-header {
            background: var(--header-gradient) !important;
            color: #ffffff;
            padding: 2.25rem 0 4.5rem 0 !important;
            margin-bottom: -50px !important;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-specific-header h1 {
            font-weight: 800 !important;
            font-size: 1.65rem !important;
            letter-spacing: -0.5px;
            color: #ffffff !important;
        }

        /* Form & Table Cards */
        .card-cuti-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .table-custom-head {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom-head th {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 2px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <!-- Header Banner -->
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Pengaturan Jatah Cuti Tahunan</h1>
                <p class="small opacity-80 mb-0">Kelola kuota cuti tahunan dan tinjau estimasi hari kerja efektif karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <?php if ($pesan_notifikasi): ?>
                <div class="alert alert-<?php echo $pesan_notifikasi['tipe']; ?> alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($pesan_notifikasi['pesan']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Form Card -->
                <div class="card-cuti-main">
                    <div class="card-header bg-white border-bottom p-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-plus text-primary"></i> 
                            <?php echo $data_untuk_diedit ? 'Edit Jatah Cuti Tahun ' . htmlspecialchars($data_untuk_diedit['tahun']) : 'Tambah Jatah Cuti Baru'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="kelola_jatah_cuti.php" method="POST">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?php echo $data_untuk_diedit['id'] ?? ''; ?>">
                            
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="tahun" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar-days me-1 text-primary"></i>Tahun Cuti</label>
                                    <input type="number" class="form-control rounded-3" id="tahun" name="tahun" placeholder="Contoh: 2026" value="<?php echo $data_untuk_diedit['tahun'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label for="jumlah" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-umbrella-beach me-1 text-primary"></i>Kuota Jatah Hari Cuti</label>
                                    <input type="number" class="form-control rounded-3" id="jumlah" name="jumlah" placeholder="Contoh: 12" value="<?php echo $data_untuk_diedit['jumlah'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm">
                                        <i class="fa-solid fa-save me-1.5"></i><?php echo $data_untuk_diedit ? 'Update' : 'Simpan'; ?>
                                    </button>
                                    <?php if ($data_untuk_diedit): ?>
                                        <a href="kelola_jatah_cuti.php" class="btn btn-outline-secondary w-100 rounded-3 mt-2 py-1.5">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Card with Live Search Bar -->
                <div class="card-cuti-main">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-list-ul text-primary"></i> 
                            Daftar Jatah Cuti & Estimasi Kalender
                        </h5>

                        <!-- Instant Live Search Bar -->
                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="searchCutiInput" class="form-control border-start-0 bg-light" placeholder="Cari tahun...">
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-center mb-0" id="cutiTable" style="font-size: 0.88rem;">
                                <thead class="table-custom-head">
                                    <tr>
                                        <th width="70">No</th>
                                        <th>Tahun</th>
                                        <th>Jatah Cuti</th>
                                        <th>Hari Libur (Tidak Termasuk Minggu)</th>
                                        <th>Estimasi Hari Kerja Efektif</th>
                                        <th width="140">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($jatah_cuti_list)): ?>
                                        <tr><td colspan="6" class="text-center p-5 text-muted">Belum ada data jatah cuti.</td></tr>
                                    <?php endif; ?>

                                    <?php $no = 1; foreach ($jatah_cuti_list as $item): 
                                        $detail = hitungDetailHari($item['tahun'], $conn);
                                    ?>
                                    <tr class="cuti-row">
                                        <td class="text-secondary fw-semibold"><?php echo $no++; ?></td>
                                        <td class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($item['tahun']); ?></td>
                                        <td>
                                            <span class="badge bg-purple-subtle fw-bold fs-6 px-3 py-1.5" style="background: rgba(139, 92, 246, 0.12); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.2);"><?php echo htmlspecialchars($item['jumlah']); ?> Hari</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-danger border border-warning-subtle fw-bold px-3 py-1.5"><?php echo $detail['libur']; ?> Hari Libur</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-3 py-1.5"><?php echo $detail['efektif']; ?> Hari Kerja</span>
                                        </td>
                                        <td>
                                            <a href="kelola_jatah_cuti.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary rounded-3 px-2.5 py-1 me-1" title="Edit Jatah Cuti"><i class="fa-solid fa-pencil"></i></a>
                                            <button onclick="konfirmasiHapus(<?php echo $item['id']; ?>)" class="btn btn-sm btn-outline-danger rounded-3 px-2.5 py-1" title="Hapus Jatah Cuti"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-light p-3">
                        <small class="text-muted">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            <strong>Peringatan:</strong> Kolom "Perkiraan Hari Kerja Efektif" bersifat estimasi. 
                            Hasil dihitung berdasarkan total hari dalam setahun dikurangi jumlah hari Minggu dan hari libur nasional pada Kalender Kerja.
                        </small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Live Instant Search
            $('#searchCutiInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#cutiTable tbody tr.cuti-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });

        function konfirmasiHapus(id) {
            if (confirm("Apakah Anda yakin ingin menghapus data jatah cuti ini?")) {
                window.location.href = 'kelola_jatah_cuti.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>