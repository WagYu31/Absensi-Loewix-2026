<?php
session_start();

// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// Ambil semua data cashbon & gabungkan dengan karyawan
$query_cashbon = "SELECT cb.*, k.nama, k.nik FROM cashbon cb JOIN karyawan k ON cb.nip = k.nip ORDER BY cb.tanggal DESC";
$result_cashbon = $conn->query($query_cashbon);
$cashbon_list = $result_cashbon->fetch_all(MYSQLI_ASSOC);

// --- OPTIMALISASI: Ambil semua data pembayaran dalam satu query ---
$pembayaran_terakumulasi = [];
if (!empty($cashbon_list)) {
    // Kumpulkan semua id_cashbon yang ada
    $ids_cashbon = array_column($cashbon_list, 'id_cashbon');
    $id_placeholders = implode(',', array_fill(0, count($ids_cashbon), '?'));
    
    // Query untuk mengambil total pembayaran untuk semua cashbon yang ditampilkan
    $sql_pembayaran = "SELECT id_cashbon, SUM(bayar) AS total_bayar FROM bayar_cashbon WHERE id_cashbon IN ($id_placeholders) GROUP BY id_cashbon";
    $stmt_pembayaran = $conn->prepare($sql_pembayaran);
    
    // Dynamically bind parameters
    $types = str_repeat('i', count($ids_cashbon));
    $stmt_pembayaran->bind_param($types, ...$ids_cashbon);
    $stmt_pembayaran->execute();
    $result_pembayaran = $stmt_pembayaran->get_result();
    
    while($row = $result_pembayaran->fetch_assoc()){
        $pembayaran_terakumulasi[$row['id_cashbon']] = $row['total_bayar'];
    }
    $stmt_pembayaran->close();
}

// Ambil data karyawan untuk dropdown "Tambah Cashbon"
$query_kar = "SELECT nip, nama FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND nip NOT IN ('001', '70326') ORDER BY nama ASC";
$result_kar = $conn->query($query_kar);
$karyawan_list = $result_kar->fetch_all(MYSQLI_ASSOC);

// Ambil data bulan/tahun yang terkunci untuk validasi JS
$query_locked = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
$result_locked = $conn->query($query_locked);
$locked_dates = [];
if ($result_locked->num_rows > 0) {
    while ($row_locked = $result_locked->fetch_assoc()) {
        $locked_dates[] = $row_locked['tahun'] . '-' . str_pad($row_locked['bulan'], 2, '0', STR_PAD_LEFT);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Cashbon Karyawan - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Kelola Cashbon Karyawan</h1>
                <p>Tambah data cashbon baru dan lihat riwayat pinjaman karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <div class="accordion mb-4 no-print" id="accordionTambahCashbon">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fa-solid fa-plus me-2"></i> Klik untuk Tambah Cashbon Baru
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionTambahCashbon">
                            <div class="accordion-body">
                                <form action="sa-proses-cashbon.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="nip-denda" class="form-label">Nama Karyawan</label>
                                            <select class="form-select" id="nip-denda" name="nip_denda" required>
                                                <option value="" disabled selected>-- Pilih Karyawan --</option>
                                                <?php foreach ($karyawan_list as $karyawan): ?>
                                                    <option value="<?php echo htmlspecialchars($karyawan['nip']); ?>"><?php echo htmlspecialchars($karyawan['nama']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-denda" class="form-label">Tanggal Ambil Cashbon</label>
                                            <input type="date" class="form-control" id="tanggal-denda" name="tanggal_denda" onchange="checkLockedDates('tanggal-denda')" required>
                                        </div>
                                         <div class="col-md-6">
                                            <label for="jumlah-denda" class="form-label">Jumlah (Rp)</label>
                                            <input type="number" class="form-control" id="jumlah-denda" name="jumlah_denda" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bayar" class="form-label">Pembayaran (Berapa kali cicil?)</label>
                                            <input type="number" class="form-control" id="bayar" name="bayar" placeholder="Contoh: 3" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-mulai" class="form-label">Tanggal Mulai Pembayaran</label>
                                            <input type="date" class="form-control" id="tanggal-mulai" name="tanggal_mulai" onchange="checkLockedDates('tanggal-mulai')" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="keterangan-denda" class="form-label">Keterangan</label>
                                            <textarea class="form-control" id="keterangan-denda" name="keterangan_denda" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary">Tambah Cashbon</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-hand-holding-dollar title-icon"></i>Daftar Cashbon Karyawan</h5>
                        <div class="btn-group btn-group-sm no-print" role="group" aria-label="Filter Status">
                            <button type="button" class="btn btn-outline-primary active" id="btn-show-unpaid">Belum Lunas</button>
                            <button type="button" class="btn btn-outline-primary" id="btn-show-paid">Lunas</button>
                            <button type="button" class="btn btn-outline-primary" id="btn-show-all">Semua</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" id="cashbon-table" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Tanggal Ambil</th>
                                        <th>Total Pinjaman</th>
                                        <th>Cicilan / Bulan</th>
                                        <th class="text-end">Sisa</th>
                                        <th class="text-center no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cashbon_list)): ?>
                                        <tr><td colspan="8" class="text-center p-5 text-muted">Belum ada data cashbon.</td></tr>
                                    <?php endif; ?>
                                    <?php $no = 1; foreach ($cashbon_list as $cashbon):
                                        $cicilan = ($cashbon['cicil'] > 0) ? ($cashbon['jumlah'] / $cashbon['cicil']) : 0;
                                        $akumulasiBayar = $pembayaran_terakumulasi[$cashbon['id_cashbon']] ?? 0;
                                        $sisa = $cashbon['jumlah'] - $akumulasiBayar;
                                        $status_lunas = ($sisa <= 10) ? 'lunas' : 'belum-lunas';
                                    ?>
                                    <tr data-status="<?php echo $status_lunas; ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($cashbon['nik']); ?></td>
                                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars($cashbon['nama']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($cashbon['tanggal'])); ?></td>
                                        <td>Rp <?php echo number_format($cashbon['jumlah'], 0, ',', '.'); ?></td>
                                        <td>Rp <?php echo number_format($cicilan, 0, ',', '.'); ?> (<?php echo $cashbon['cicil']; ?>x)</td>
                                        <td class="text-end">
                                            <?php if ($status_lunas === 'lunas'): ?>
                                                <span class="badge bg-success">LUNAS</span>
                                            <?php else: ?>
                                                <strong>Rp <?php echo number_format($sisa, 0, ',', '.'); ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center no-print">
                                            <button onclick="viewDetails('<?php echo $cashbon['id_cashbon']; ?>')" class="btn btn-info btn-sm" title="Lihat Detail Pembayaran"><i class="fa-solid fa-eye"></i></button>
                                            <button onclick="confirmDelete('<?php echo $cashbon['id_cashbon']; ?>')" class="btn btn-danger btn-sm" title="Hapus Cashbon"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // FUNGSI-FUNGSI JAVASCRIPT INTI (TETAP SAMA)
        function confirmDelete(id_cashbon) {
            if (confirm("Apakah Anda yakin ingin menghapus data cashbon ini?")) {
                window.location.href = "sa-proses-hapus-cashbon.php?id_denda=" + id_cashbon;
            }
        }
        
        function viewDetails(id_cashbon) {
            window.location.href = "view-detail-cashbon.php?id_cashbon=" + id_cashbon;
        }
        
        // PERBAIKAN BUG: checkLockedDates kini menerima ID elemen yang akan dicek
        function checkLockedDates(elementId) {
            const tanggalInput = document.getElementById(elementId);
            const selectedDate = tanggalInput.value;
            const lockedDates = <?php echo json_encode($locked_dates); ?>;
            
            if (selectedDate) {
                const selectedYearMonth = selectedDate.substring(0, 7);
                if (lockedDates.includes(selectedYearMonth)) {
                    alert("Periode gaji untuk bulan dan tahun yang dipilih sudah terkunci dan tidak dapat diubah.");
                    tanggalInput.value = "";
                }
            }
        }

        $(document).ready(function() {
            // Inisialisasi Select2
            $('#nip-denda').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#collapseOne')
            });

            // Logika filter tabel (TETAP SAMA)
            const filterButtons = {
                'unpaid': $('#btn-show-unpaid'),
                'paid': $('#btn-show-paid'),
                'all': $('#btn-show-all')
            };

            function filterTable(status) {
                $('#cashbon-table tbody tr').each(function() {
                    const rowStatus = $(this).data('status');
                    if (status === 'all' || rowStatus === status) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            filterButtons.unpaid.on('click', function() { filterTable('belum-lunas'); setActiveButton($(this)); });
            filterButtons.paid.on('click', function() { filterTable('lunas'); setActiveButton($(this)); });
            filterButtons.all.on('click', function() { filterTable('all'); setActiveButton($(this)); });
            
            function setActiveButton($button) {
                $('.btn-group .btn').removeClass('active');
                $button.addClass('active');
            }

            // Filter default saat halaman dimuat
            filterTable('belum-lunas');
        });
    </script>
</body>
</html>