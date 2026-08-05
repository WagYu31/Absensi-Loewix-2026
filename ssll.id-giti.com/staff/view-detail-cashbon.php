<?php
session_start();

// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// Validasi ID dari URL
if (!isset($_GET['id_cashbon']) || !ctype_digit($_GET['id_cashbon'])) {
    die("Error: ID Cashbon tidak valid.");
}
$id_cashbon = $_GET['id_cashbon'];

// 1. Ambil data utama cashbon (Gunakan Prepared Statement)
$query_cashbon = "SELECT cb.*, k.nik, k.nama, k.jabatan
                  FROM cashbon cb
                  JOIN karyawan k ON cb.nip = k.nip
                  WHERE cb.id_cashbon = ?";
$stmt_cashbon = $conn->prepare($query_cashbon);
$stmt_cashbon->bind_param("i", $id_cashbon);
$stmt_cashbon->execute();
$result_cashbon = $stmt_cashbon->get_result();

if ($result_cashbon->num_rows === 0) {
    die("Data cashbon tidak ditemukan.");
}
$data_cashbon = $result_cashbon->fetch_assoc();
$stmt_cashbon->close();


// 2. Ambil riwayat pembayaran (Gunakan Prepared Statement)
$query_pembayaran = "SELECT * FROM bayar_cashbon WHERE id_cashbon = ? ORDER BY cicilan ASC";
$stmt_pembayaran = $conn->prepare($query_pembayaran);
$stmt_pembayaran->bind_param("i", $id_cashbon);
$stmt_pembayaran->execute();
$riwayat_pembayaran = $stmt_pembayaran->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pembayaran->close();


// 3. Lakukan semua kalkulasi di sini agar kode HTML bersih
$total_pinjaman = $data_cashbon['jumlah'];
$jumlah_cicilan = $data_cashbon['cicil'];
$cicilan_per_bulan = ($jumlah_cicilan > 0) ? $total_pinjaman / $jumlah_cicilan : 0;

$total_dibayar = 0;
foreach ($riwayat_pembayaran as $bayar) {
    $total_dibayar += $bayar['bayar'];
}

$sisa_pinjaman = $total_pinjaman - $total_dibayar;
$status_lunas = ($sisa_pinjaman <= 10); // Toleransi sisa kecil dianggap lunas
$persentase_lunas = ($total_pinjaman > 0) ? ($total_dibayar / $total_pinjaman) * 100 : 0;
$persentase_lunas = min($persentase_lunas, 100); // Pastikan tidak lebih dari 100%

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Cashbon: <?php echo htmlspecialchars($data_cashbon['nama']); ?> - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Detail Cashbon</h1>
                <p>Rincian pinjaman untuk karyawan: <?php echo htmlspecialchars($data_cashbon['nama']); ?></p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <div class="d-flex justify-content-end mb-3 no-print">
                    <a href="cashbon.php" class="btn btn-secondary me-2"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a>
                    <button onclick="window.print()" class="btn btn-info"><i class="fa-solid fa-print me-2"></i>Cetak</button>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Total Pinjaman</h6>
                                <h4 class="card-title">Rp <?php echo number_format($total_pinjaman, 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Sisa Pinjaman</h6>
                                <h4 class="card-title text-danger">Rp <?php echo number_format($sisa_pinjaman, 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                     <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Status</h6>
                                <?php if($status_lunas): ?>
                                    <h4 class="card-title text-success">LUNAS</h4>
                                <?php else: ?>
                                    <h4 class="card-title">Belum Lunas</h4>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $persentase_lunas; ?>%;" aria-valuenow="<?php echo $persentase_lunas; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-5 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header"><h5 class="mb-0">Rincian Pinjaman</h5></div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between"><strong>NIK:</strong> <span><?php echo htmlspecialchars($data_cashbon['nik']); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Nama:</strong> <span><?php echo htmlspecialchars($data_cashbon['nama']); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Jabatan:</strong> <span><?php echo htmlspecialchars($data_cashbon['jabatan']); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Tanggal Ambil:</strong> <span><?php echo date('d F Y', strtotime($data_cashbon['tanggal'])); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Keterangan:</strong> <span class="text-end"><?php echo htmlspecialchars($data_cashbon['keterangan']); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Rencana Cicilan:</strong> <span><?php echo $jumlah_cicilan; ?> kali</span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Cicilan per Bulan:</strong> <span>Rp <?php echo number_format($cicilan_per_bulan, 0, ',', '.'); ?></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Mulai Bayar:</strong> <span><?php echo date('d F Y', strtotime($data_cashbon['mulai'])); ?></span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-7 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header"><h5 class="mb-0">Riwayat Pembayaran</h5></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Cicilan Ke-</th>
                                                <th>Tanggal Bayar</th>
                                                <th class="text-end">Jumlah Dibayar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($riwayat_pembayaran)): ?>
                                                <tr><td colspan="3" class="text-center p-4 text-muted">Belum ada pembayaran.</td></tr>
                                            <?php else: ?>
                                                <?php foreach($riwayat_pembayaran as $bayar): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($bayar['cicilan']); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($bayar['tanggal'])); ?></td>
                                                    <td class="text-end">Rp <?php echo number_format($bayar['bayar'], 0, ',', '.'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-group-divider">
                                            <tr class="fw-bold">
                                                <td colspan="2" class="text-end">Total Telah Dibayar:</td>
                                                <td class="text-end">Rp <?php echo number_format($total_dibayar, 0, ',', '.'); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>