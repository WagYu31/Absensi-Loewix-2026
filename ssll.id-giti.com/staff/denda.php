<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';

// Logika untuk filter bulan, tahun, dan karyawan
$bulan = $_REQUEST['bulan'] ?? date('m');
$tahun = $_REQUEST['tahun'] ?? date('Y');
$selected_karyawan = $_REQUEST['karyawan'] ?? '';

$bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];

// Ambil data denda berdasarkan filter
$query_denda = "SELECT denda.*, karyawan.nama, karyawan.nik
                FROM denda 
                JOIN karyawan ON karyawan.nip = denda.nip
                WHERE MONTH(denda.tanggal) = ? AND YEAR(denda.tanggal) = ? AND denda.ket1 = 'Denda' ";

if (!empty($selected_karyawan)) {
    $query_denda .= " AND (karyawan.nik = '$selected_karyawan' OR karyawan.nip = '$selected_karyawan') ";
}

$query_denda .= " ORDER BY denda.tanggal DESC, karyawan.nama ASC";

$stmt_denda = $conn->prepare($query_denda);
$stmt_denda->bind_param("ss", $bulan, $tahun);
$stmt_denda->execute();
$data_denda = $stmt_denda->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_denda->close();

// Metrics Summary
$total_denda_rp = array_sum(array_column($data_denda, 'jumlah'));
$total_kejadian_denda = count($data_denda);
$unique_employees_denda = count(array_unique(array_column($data_denda, 'nip')));

// Ambil data karyawan aktif untuk dropdown
$query_kar = "SELECT nip, nik, nama FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND nip NOT IN ('001', '70326') ORDER BY nama ASC";
$result_kar = $conn->query($query_kar);
$karyawan_list = $result_kar->fetch_all(MYSQLI_ASSOC);

// Ambil data bulan/tahun yang terkunci
$query_locked = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
$result_locked = $conn->query($query_locked);
$locked_dates = [];
if ($result_locked && $result_locked->num_rows > 0) {
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
    <title>Kelola Denda Karyawan - Gravitti Tech</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
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

        /* Top Summary Stat Widgets */
        .stat-widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-widget-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease-out;
        }

        .stat-widget-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.07);
        }

        .widget-val {
            font-weight: 800;
            font-size: 1.65rem;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .widget-lbl {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .widget-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .widget-icon-box.rose { background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%); color: #ffffff; }
        .widget-icon-box.blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #ffffff; }
        .widget-icon-box.amber { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); color: #ffffff; }

        /* Accordion & Cards */
        .card-denda-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .emp-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #334155;
            font-weight: 800;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
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
                <h1>Kelola Denda Karyawan</h1>
                <p class="small opacity-80 mb-0">Tambah denda manual, tinjau potongan keterlambatan, dan cetak laporan denda bulanan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <!-- KPI Summary Stat Widgets -->
                <div class="stat-widget-grid">
                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val text-danger">Rp <?php echo number_format($total_denda_rp, 0, ',', '.'); ?></div>
                            <div class="widget-lbl">Total Denda Periode Ini</div>
                        </div>
                        <div class="widget-icon-box rose"><i class="fa-solid fa-money-bill-wave"></i></div>
                    </div>

                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val"><?php echo $unique_employees_denda; ?> <span class="fs-6 fw-normal text-muted">Orang</span></div>
                            <div class="widget-lbl">Karyawan Terkena Denda</div>
                        </div>
                        <div class="widget-icon-box blue"><i class="fa-solid fa-user-minus"></i></div>
                    </div>

                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val"><?php echo $total_kejadian_denda; ?> <span class="fs-6 fw-normal text-muted">Kejadian</span></div>
                            <div class="widget-lbl">Total Record Denda</div>
                        </div>
                        <div class="widget-icon-box amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                </div>

                <!-- Accordion Input Denda Manual -->
                <div class="accordion mb-4 no-print" id="accordionTambahDenda">
                    <div class="accordion-item border-0 shadow-sm rounded-4 overflow-hidden">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fa-solid fa-circle-plus text-primary me-2 fs-5"></i> Tambah Denda Manual Karyawan
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionTambahDenda">
                            <div class="accordion-body bg-white p-4">
                                <form action="proses-tambah-data-denda-karyawan.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="nip-denda" class="form-label fw-bold text-secondary small">Pilih Karyawan</label>
                                            <select class="form-select" id="nip-denda" name="nip_denda" required>
                                                <option value="" disabled selected>-- Pilih Karyawan --</option>
                                                <?php foreach ($karyawan_list as $karyawan): ?>
                                                    <option value="<?php echo htmlspecialchars($karyawan['nip']); ?>"><?php echo htmlspecialchars($karyawan['nama']); ?> (NIK: <?php echo htmlspecialchars($karyawan['nik']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-denda" class="form-label fw-bold text-secondary small">Tanggal Denda</label>
                                            <input type="date" class="form-control rounded-3" id="tanggal-denda" name="tanggal_denda" onchange="checkLockedDates()" required>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="jumlah-denda" class="form-label fw-bold text-secondary small">Jumlah Denda (Rp)</label>
                                            <input type="number" class="form-control rounded-3" id="jumlah-denda" name="jumlah_denda" placeholder="Contoh: 50000" required>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="keterangan-denda" class="form-label fw-bold text-secondary small">Keterangan Alasan Denda</label>
                                            <input type="text" class="form-control rounded-3" id="keterangan-denda" name="keterangan_denda" placeholder="Contoh: Denda terlambat atau tidak scan" required>
                                        </div>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4 py-2 shadow-sm"><i class="fa-solid fa-save me-1.5"></i>Simpan Denda</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Data Card with Expanded Filters & Search -->
                <div class="card-denda-main">
                    <div class="card-header bg-white border-bottom p-3">
                        <form method="GET" action="denda.php" class="row g-2.5 align-items-center">
                            
                            <!-- Bulan -->
                            <div class="col-6 col-md-3">
                                <label for="bulan" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar me-1 text-primary"></i> Bulan</label>
                                <select id="bulan" name="bulan" class="form-select rounded-3">
                                    <?php foreach ($bulanNames as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php if($num == $bulan) echo 'selected'; ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tahun -->
                            <div class="col-6 col-md-2">
                                <label for="tahun" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar-days me-1 text-primary"></i> Tahun</label>
                                <select id="tahun" name="tahun" class="form-select rounded-3">
                                    <?php $currentYear = date('Y');
                                    for ($i = $currentYear; $i >= $currentYear - 10; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php if($i == $tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Karyawan -->
                            <div class="col-12 col-md-3">
                                <label for="karyawan" class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-user me-1 text-primary"></i> Karyawan</label>
                                <select id="karyawan" name="karyawan" class="form-select rounded-3">
                                    <option value="">-- Semua Karyawan --</option>
                                    <?php foreach ($karyawan_list as $kar): ?>
                                        <option value="<?php echo htmlspecialchars($kar['nik']); ?>" <?php if($kar['nik'] == $selected_karyawan || $kar['nip'] == $selected_karyawan) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($kar['nama']); ?> (NIK: <?php echo htmlspecialchars($kar['nik']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter & Print Buttons -->
                            <div class="col-12 col-md-4 d-flex gap-2 mt-md-4">
                                <button type="submit" class="btn btn-primary flex-grow-1 rounded-3 fw-bold py-2"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                                <a href="denda.php" class="btn btn-outline-secondary rounded-3 px-3 py-2" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                                <button type="button" onclick="printData()" class="btn btn-warning rounded-3 px-3 py-2 text-dark font-semibold" title="Cetak Laporan Denda">
                                    <i class="fa-solid fa-print me-1"></i> Cetak
                                </button>
                            </div>

                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0">Riwayat Record Denda Periode <span class="text-primary"><?php echo $bulanNames[$bulan] . ' ' . $tahun; ?></span></h6>
                            
                            <!-- Instant Live Search Bar -->
                            <div class="input-group input-group-sm" style="max-width: 260px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="searchDendaInput" class="form-control border-start-0 bg-light" placeholder="Cari nama karyawan / NIK...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="dendaTable" style="font-size: 0.88rem;">
                                <thead class="table-custom-head">
                                    <tr>
                                        <th class="ps-3" width="60">No</th>
                                        <th width="90">NIK</th>
                                        <th>Nama Karyawan</th>
                                        <th>Tanggal Denda</th>
                                        <th>Keterangan Alasan</th>
                                        <th class="text-end">Jumlah Denda</th>
                                        <th class="text-center no-print" width="90">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data_denda)): ?>
                                        <tr><td colspan="7" class="text-center p-5 text-muted">Tidak ada data denda untuk kriteria periode ini.</td></tr>
                                    <?php endif; ?>

                                    <?php $no = 1; foreach ($data_denda as $data): 
                                        $words = explode(' ', trim($data['nama']));
                                        $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                    ?>
                                    <tr class="denda-row">
                                        <td class="ps-3 text-secondary fw-semibold"><?php echo $no++; ?></td>
                                        <td class="fw-semibold text-secondary"><?php echo htmlspecialchars($data['nik']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="emp-avatar"><?php echo $init; ?></span>
                                                <span class="fw-bold text-dark" style="text-transform:capitalize;"><?php echo htmlspecialchars($data['nama']); ?></span>
                                            </div>
                                        </td>
                                        <td class="fw-medium text-secondary">
                                            <span class="badge bg-light text-dark border px-2.5 py-1.5"><i class="fa-solid fa-calendar me-1 text-primary"></i><?php echo date('d M Y', strtotime($data['tanggal'])); ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($data['keterangan']); ?></span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6 px-3 py-1.5">Rp <?php echo number_format($data['jumlah'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="text-center no-print">
                                            <button onclick="confirmDelete('<?php echo $data['id_denda']; ?>')" class="btn btn-outline-danger btn-sm rounded-3 px-2.5 py-1" title="Hapus Denda">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
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
        $(document).ready(function() {
            // Live Search Bar
            $('#searchDendaInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#dendaTable tbody tr.denda-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Select2 Karyawan
            $('#nip-denda').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#collapseOne')
            });
        });

        function confirmDelete(id_denda) {
            if (confirm("Apakah Anda yakin ingin menghapus data denda ini?")) {
                window.location.href = "proses-hapus-data-denda-karyawan.php?id_denda=" + id_denda;
            }
        }

        function checkLockedDates() {
            const tanggalInput = document.getElementById("tanggal-denda");
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

        function printData() {
            const bulan = document.getElementById("bulan").value;
            const tahun = document.getElementById("tahun").value;
            const url = "print-denda.php?bulan=" + bulan + "&tahun=" + tahun;
            window.open(url, "_blank");
        }
    </script>
</body>
</html>