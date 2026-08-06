<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';

$selected_karyawan = $_GET['karyawan'] ?? '';

// Ambil semua data cashbon & gabungkan dengan karyawan
$query_cashbon = "SELECT cb.*, k.nama, k.nik FROM cashbon cb JOIN karyawan k ON cb.nip = k.nip ";
if (!empty($selected_karyawan)) {
    $query_cashbon .= " WHERE (k.nik = '$selected_karyawan' OR k.nip = '$selected_karyawan') ";
}
$query_cashbon .= " ORDER BY cb.tanggal DESC";
$result_cashbon = $conn->query($query_cashbon);
$cashbon_list = $result_cashbon->fetch_all(MYSQLI_ASSOC);

// Bulk fetch pembayaran cashbon
$pembayaran_terakumulasi = [];
if (!empty($cashbon_list)) {
    $ids_cashbon = array_column($cashbon_list, 'id_cashbon');
    $id_placeholders = implode(',', array_fill(0, count($ids_cashbon), '?'));
    
    $sql_pembayaran = "SELECT id_cashbon, SUM(bayar) AS total_bayar FROM bayar_cashbon WHERE id_cashbon IN ($id_placeholders) GROUP BY id_cashbon";
    $stmt_pembayaran = $conn->prepare($sql_pembayaran);
    
    $types = str_repeat('i', count($ids_cashbon));
    $stmt_pembayaran->bind_param($types, ...$ids_cashbon);
    $stmt_pembayaran->execute();
    $result_pembayaran = $stmt_pembayaran->get_result();
    
    while($row = $result_pembayaran->fetch_assoc()){
        $pembayaran_terakumulasi[$row['id_cashbon']] = $row['total_bayar'];
    }
    $stmt_pembayaran->close();
}

// Calculate summary KPI metrics
$total_pinjaman_awal = array_sum(array_column($cashbon_list, 'jumlah'));
$total_sisa_pinjaman = 0;
$count_unpaid_borrowers = 0;

foreach ($cashbon_list as $cb_item) {
    $bayar = $pembayaran_terakumulasi[$cb_item['id_cashbon']] ?? 0;
    $sisa_cb = $cb_item['jumlah'] - $bayar;
    if ($sisa_cb > 10) {
        $total_sisa_pinjaman += $sisa_cb;
        $count_unpaid_borrowers++;
    }
}

// Ambil data karyawan untuk dropdown
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
    <title>Kelola Cashbon Karyawan - Gravitti Tech</title>
    
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
        .widget-icon-box.emerald { background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #ffffff; }

        /* Accordion & Cards */
        .card-cashbon-main {
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
                <h1>Kelola Cashbon Karyawan</h1>
                <p class="small opacity-80 mb-0">Tambah pinjaman baru, tinjau cicilan bulanan, dan kelola histori pelunasan cashbon.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <!-- KPI Summary Stat Widgets -->
                <div class="stat-widget-grid">
                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val text-danger">Rp <?php echo number_format($total_sisa_pinjaman, 0, ',', '.'); ?></div>
                            <div class="widget-lbl">Total Belum Lunas (Sisa)</div>
                        </div>
                        <div class="widget-icon-box rose"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    </div>

                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val"><?php echo $count_unpaid_borrowers; ?> <span class="fs-6 fw-normal text-muted">Orang</span></div>
                            <div class="widget-lbl">Karyawan Berhutang</div>
                        </div>
                        <div class="widget-icon-box blue"><i class="fa-solid fa-users"></i></div>
                    </div>

                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val">Rp <?php echo number_format($total_pinjaman_awal, 0, ',', '.'); ?></div>
                            <div class="widget-lbl">Total Pinjaman Awal</div>
                        </div>
                        <div class="widget-icon-box emerald"><i class="fa-solid fa-wallet"></i></div>
                    </div>
                </div>

                <!-- Accordion Input Cashbon Manual -->
                <div class="accordion mb-4 no-print" id="accordionTambahCashbon">
                    <div class="accordion-item border-0 shadow-sm rounded-4 overflow-hidden">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fa-solid fa-circle-plus text-primary me-2 fs-5"></i> Tambah Data Cashbon Baru
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionTambahCashbon">
                            <div class="accordion-body bg-white p-4">
                                <form action="sa-proses-cashbon.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="nip-denda" class="form-label fw-bold text-secondary small">Nama Karyawan</label>
                                            <select class="form-select" id="nip-denda" name="nip_denda" required>
                                                <option value="" disabled selected>-- Pilih Karyawan --</option>
                                                <?php foreach ($karyawan_list as $karyawan): ?>
                                                    <option value="<?php echo htmlspecialchars($karyawan['nip']); ?>"><?php echo htmlspecialchars($karyawan['nama']); ?> (NIK: <?php echo htmlspecialchars($karyawan['nik']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-denda" class="form-label fw-bold text-secondary small">Tanggal Ambil Cashbon</label>
                                            <input type="date" class="form-control rounded-3" id="tanggal-denda" name="tanggal_denda" onchange="checkLockedDates('tanggal-denda')" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="jumlah-denda" class="form-label fw-bold text-secondary small">Jumlah Pinjaman (Rp)</label>
                                            <input type="number" class="form-control rounded-3" id="jumlah-denda" name="jumlah_denda" placeholder="Contoh: 1000000" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bayar" class="form-label fw-bold text-secondary small">Berapa Kali Cicil? (Tenor Bulan)</label>
                                            <input type="number" class="form-control rounded-3" id="bayar" name="bayar" placeholder="Contoh: 3" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal-mulai" class="form-label fw-bold text-secondary small">Tanggal Mulai Pembayaran</label>
                                            <input type="date" class="form-control rounded-3" id="tanggal-mulai" name="tanggal_mulai" onchange="checkLockedDates('tanggal-mulai')" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="keterangan-denda" class="form-label fw-bold text-secondary small">Keterangan Alasan Pinjaman</label>
                                            <textarea class="form-control rounded-3" id="keterangan-denda" name="keterangan_denda" rows="2" placeholder="Contoh: Pinjaman renovasi rumah atau keperluan medis" required></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4 py-2 shadow-sm"><i class="fa-solid fa-save me-1.5"></i>Simpan Cashbon</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Card with Filter Bar & Table -->
                <div class="card-cashbon-main">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        
                        <!-- Left Filter Bar -->
                        <form method="GET" action="cashbon.php" class="d-flex flex-wrap align-items-center gap-2">
                            <div style="min-width: 220px;">
                                <select id="karyawan" name="karyawan" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                                    <option value="">-- Semua Karyawan --</option>
                                    <?php foreach ($karyawan_list as $kar): ?>
                                        <option value="<?php echo htmlspecialchars($kar['nik']); ?>" <?php if($kar['nik'] == $selected_karyawan || $kar['nip'] == $selected_karyawan) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($kar['nama']); ?> (NIK: <?php echo htmlspecialchars($kar['nik']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if($selected_karyawan): ?>
                                <a href="cashbon.php" class="btn btn-outline-secondary btn-sm rounded-3"><i class="fa-solid fa-rotate-left me-1"></i>Reset</a>
                            <?php endif; ?>
                        </form>

                        <!-- Status Filter Segmented Buttons -->
                        <div class="btn-group btn-group-sm no-print" role="group">
                            <button type="button" class="btn btn-outline-primary active rounded-start-3 fw-bold px-3 py-1.5" id="btn-show-unpaid">Belum Lunas</button>
                            <button type="button" class="btn btn-outline-primary fw-bold px-3 py-1.5" id="btn-show-paid">Lunas</button>
                            <button type="button" class="btn btn-outline-primary rounded-end-3 fw-bold px-3 py-1.5" id="btn-show-all">Semua</button>
                        </div>

                        <!-- Live Search Bar -->
                        <div class="input-group input-group-sm" style="max-width: 240px;">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="searchCashbonInput" class="form-control border-start-0 bg-light" placeholder="Cari nama karyawan / NIK...">
                        </div>

                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="cashbon-table" style="font-size: 0.88rem;">
                                <thead class="table-custom-head">
                                    <tr>
                                        <th class="ps-3" width="60">No</th>
                                        <th width="90">NIK</th>
                                        <th>Nama Karyawan</th>
                                        <th>Tanggal Ambil</th>
                                        <th>Total Pinjaman</th>
                                        <th>Cicilan / Bulan</th>
                                        <th class="text-end">Sisa Belum Lunas</th>
                                        <th class="text-center no-print" width="100">Aksi</th>
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
                                        
                                        $words = explode(' ', trim($cashbon['nama']));
                                        $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                    ?>
                                    <tr class="cashbon-row" data-status="<?php echo $status_lunas; ?>">
                                        <td class="ps-3 text-secondary fw-semibold"><?php echo $no++; ?></td>
                                        <td class="fw-semibold text-secondary"><?php echo htmlspecialchars($cashbon['nik']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="emp-avatar"><?php echo $init; ?></span>
                                                <span class="fw-bold text-dark" style="text-transform:capitalize;"><?php echo htmlspecialchars($cashbon['nama']); ?></span>
                                            </div>
                                        </td>
                                        <td class="fw-medium text-secondary">
                                            <span class="badge bg-light text-dark border px-2.5 py-1.5"><i class="fa-solid fa-calendar me-1 text-primary"></i><?php echo date('d M Y', strtotime($cashbon['tanggal'])); ?></span>
                                        </td>
                                        <td class="fw-bold text-dark">Rp <?php echo number_format($cashbon['jumlah'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-medium">Rp <?php echo number_format($cicilan, 0, ',', '.'); ?> / bln</span>
                                            <small class="text-muted ms-1">(<?php echo $cashbon['cicil']; ?>x)</small>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($status_lunas === 'lunas'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-3 py-1.5"><i class="fa-solid fa-circle-check me-1"></i>LUNAS</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6 px-3 py-1.5">Rp <?php echo number_format($sisa, 0, ',', '.'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center no-print">
                                            <button onclick="viewDetails('<?php echo $cashbon['id_cashbon']; ?>')" class="btn btn-outline-primary btn-sm rounded-3 px-2 py-1 me-1" title="Lihat Detail Pembayaran"><i class="fa-solid fa-eye"></i></button>
                                            <button onclick="confirmDelete('<?php echo $cashbon['id_cashbon']; ?>')" class="btn btn-outline-danger btn-sm rounded-3 px-2 py-1" title="Hapus Cashbon"><i class="fa-solid fa-trash"></i></button>
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
        function confirmDelete(id_cashbon) {
            if (confirm("Apakah Anda yakin ingin menghapus data cashbon ini?")) {
                window.location.href = "sa-proses-hapus-cashbon.php?id_denda=" + id_cashbon;
            }
        }
        
        function viewDetails(id_cashbon) {
            window.location.href = "view-detail-cashbon.php?id_cashbon=" + id_cashbon;
        }
        
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
            // Select2 Karyawan
            $('#nip-denda').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#collapseOne')
            });

            // Live Instant Table Search
            $('#searchCashbonInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#cashbon-table tbody tr.cashbon-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Status Filter Buttons
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
                
                $.each(filterButtons, function(key, button) {
                    button.removeClass('active');
                });
                if(filterButtons[status]) {
                    filterButtons[status].addClass('active');
                }
            }

            filterButtons['unpaid'].click(function() { filterTable('belum-lunas'); });
            filterButtons['paid'].click(function() { filterTable('lunas'); });
            filterButtons['all'].click(function() { filterTable('all'); });
            
            // Initial filter
            filterTable('belum-lunas');
        });
    </script>
</body>
</html>