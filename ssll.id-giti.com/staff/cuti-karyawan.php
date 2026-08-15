<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include '../get-kar-login-data.php'; 

$holidays = [];
$sql_holidays = "SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND deleted_at IS NULL";
$result_holidays = $conn->query($sql_holidays);
if ($result_holidays) {
    while ($row = $result_holidays->fetch_assoc()) {
        if (!empty($row['tanggal_merah'])) {
            $holidays[$row['tanggal_merah']] = true;
        }
    }
    $result_holidays->close();
}

function formatJenisCuti($jenis) {
    switch (strtolower($jenis)) {
        case 'dipotong': return 'Cuti Lainnya';
        case 'khusus': return 'Cuti Khusus';
        case 'hak': return 'Cuti Hak';
        default: return ucfirst($jenis);
    }
}

function formatStatusVerif($status) {
    switch (ucfirst(strtolower($status))) {
        case 'Pending': return '<span class="badge bg-warning-subtle text-dark border border-warning fw-bold px-2.5 py-1.5"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
        case 'Disetujui': return '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2.5 py-1.5"><i class="fa-solid fa-check-circle me-1"></i>Disetujui</span>';
        case 'Ditolak': return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2.5 py-1.5"><i class="fa-solid fa-times-circle me-1"></i>Ditolak</span>';
        default: return '<span class="badge bg-light text-dark border px-2.5 py-1.5">' . htmlspecialchars($status) . '</span>';
    }
}

function hitungDurasiCuti($tgl_mulai, $tgl_selesai, $holidays_array = []) {
    if (empty($tgl_mulai) || empty($tgl_selesai) || $tgl_mulai == '0000-00-00' || $tgl_selesai == '0000-00-00') {
        return 0;
    }
    try {
        $start = new DateTime($tgl_mulai);
        $end = new DateTime($tgl_selesai);
        if ($start > $end) return 0;
        
        $end->modify('+1 day');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        $duration = 0;
        
        foreach ($period as $date) {
            $dayOfWeek = $date->format('N'); 
            $dateString = $date->format('Y-m-d');
            
            if ($dayOfWeek != 7 && !isset($holidays_array[$dateString])) {
                $duration++;
            }
        }
        return $duration;
    } catch (Exception $e) {
        return 0;
    }
}

$pengajuan_cuti_list = [];
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_term = isset($_GET['search']) && !empty(trim($_GET['search'])) ? trim($_GET['search']) : null;
$query_params = []; 

$where_clauses = ["c.deleted_at IS NULL"];
$params = [];
$types = "";

if ($search_term) {
    $where_clauses[] = "(k.nama LIKE ? OR k.nip LIKE ? OR c.keterangan LIKE ?)";
    $search_like = "%" . $search_term . "%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "sss";
    $query_params['search'] = $search_term;
}

$sql_where = "WHERE " . implode(" AND ", $where_clauses);

$sql = "SELECT c.id, c.nip, k.nama, c.tgl_mulai, c.tgl_selesai, c.jenis, c.keterangan, c.bukti, c.verif, c.potong_gaji, c.reason, c.created_at
        FROM cuti c
        JOIN karyawan k ON c.nip = k.nip
        $sql_where
        ORDER BY c.verif = 'Pending' DESC, c.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$types .= "i";
$params[] = $offset;
$types .= "i";

$stmt = $conn->prepare($sql);

if (!empty($types)) {
    $bind_args = [$types];
    foreach ($params as $key => &$value) {
        $bind_args[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_args);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pengajuan_cuti_list[] = $row;
}
$stmt->close();

$sql_total = "SELECT COUNT(c.id) as total 
              FROM cuti c 
              JOIN karyawan k ON c.nip = k.nip 
              $sql_where";
$stmt_total = $conn->prepare($sql_total);

$count_params = [];
$count_types = "";
if ($search_term) {
    $count_params[] = $search_like;
    $count_params[] = $search_like;
    $count_params[] = $search_like;
    $count_types .= "sss";
}

if (!empty($count_types)) {
    $bind_args_total = [$count_types];
    foreach ($count_params as $key => &$value) {
        $bind_args_total[] = &$value;
    }
    call_user_func_array([$stmt_total, 'bind_param'], $bind_args_total);
}

$stmt_total->execute();
$totalResult = $stmt_total->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalData = $totalRow['total'] ?? 0;
$totalPages = ceil($totalData / $limit);
$stmt_total->close();

$current_page_basename = basename($_SERVER['PHP_SELF']);
$query_string = http_build_query($query_params);
$base_url = $current_page_basename . '?' . $query_string;

$today = new DateTime();
$today->setTime(0, 0, 0);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengajuan Cuti - Gravitti Tech</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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

        /* Main Table Card */
        .card-cuti-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .emp-avatar {
            width: 34px;
            height: 34px;
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
                <h1>Kelola Pengajuan Cuti Karyawan</h1>
                <p class="small opacity-80 mb-0">Tinjau, verifikasi, dan kelola histori pengajuan cuti seluruh karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <!-- Main Card -->
                <div class="card-cuti-main">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        
                        <div class="d-flex align-items-center gap-2">
                            <a href="input_cuti_manual.php" class="btn btn-primary rounded-3 fw-bold px-3 py-2 shadow-sm">
                                <i class="fa-solid fa-circle-plus me-1.5"></i> Input Cuti Manual
                            </a>
                            <a href="rekap_cuti.php" class="btn btn-warning rounded-3 fw-bold px-3 py-2 shadow-sm text-dark">
                                <i class="fa-solid fa-chart-pie me-1.5"></i> Rekap Cuti
                            </a>
                        </div>

                        <!-- Live Search Bar -->
                        <form action="<?php echo $current_page_basename; ?>" method="GET" class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="max-width: 280px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="searchInput" name="search" class="form-control border-start-0 bg-light" placeholder="Cari Nama/NIP/Keterangan..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm rounded-3 px-3 fw-bold">Cari</button>
                            <?php if($search_term): ?>
                                <a href="<?php echo $current_page_basename; ?>" class="btn btn-outline-secondary btn-sm rounded-3 px-2.5">Reset</a>
                            <?php endif; ?>
                        </form>

                        <!-- Category Badges Legend -->
                        <div class="d-flex align-items-center gap-3 small">
                            <span class="badge bg-warning-subtle text-dark border border-warning fw-bold px-2 py-1"><i class="fa-solid fa-circle me-1 text-warning"></i> Cuti Khusus</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1"><i class="fa-solid fa-circle me-1 text-danger"></i> Cuti Lainnya</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1"><i class="fa-solid fa-circle me-1 text-success"></i> Cuti Hak</span>
                        </div>

                    </div>

                    <div class="card-body p-0">
                        <?php if (empty($pengajuan_cuti_list)): ?>
                            <div class="text-center p-5 text-muted">Belum ada pengajuan cuti<?php echo $search_term ? ' dengan kriteria "' . htmlspecialchars($search_term) . '"' : ''; ?>.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="cutiTable" style="font-size: 0.88rem;">
                                    <thead class="table-custom-head">
                                        <tr>
                                            <th class="ps-3" width="60">No.</th>
                                            <th>Nama Karyawan</th>
                                            <th>Tgl. Pengajuan</th>
                                            <th>Jenis Cuti</th>
                                            <th class="text-center">Potong Cuti</th>
                                            <th class="text-center">Durasi Hari</th>
                                            <th>Keterangan Alasan</th>
                                            <th class="text-center">Bukti</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" width="130">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = $offset + 1; foreach ($pengajuan_cuti_list as $cuti): 
                                            $tgl_mulai_cuti = new DateTime($cuti['tgl_mulai']);
                                            $is_editable = $cuti['verif'] == "Pending";
                                            $words = explode(' ', trim($cuti['nama']));
                                            $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                            $durasi = hitungDurasiCuti($cuti['tgl_mulai'], $cuti['tgl_selesai'], $holidays);
                                        ?>
                                            <tr id="cuti-row-<?php echo $cuti['id']; ?>" class="cuti-row">
                                                <td class="ps-3 text-secondary fw-semibold"><?php echo $no++; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="emp-avatar"><?php echo $init; ?></span>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($cuti['nama']); ?></div>
                                                            <div class="small text-muted">NIP: <?php echo htmlspecialchars($cuti['nip']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-medium text-secondary"><?php echo date('d M Y', strtotime($cuti['created_at'])); ?></td>
                                                <td class="jenis-cell">
                                                    <?php
                                                    $jenis_cuti_lower = strtolower($cuti['jenis']);
                                                    if ($jenis_cuti_lower == 'hak') {
                                                        echo '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2.5 py-1">Cuti Hak</span>';
                                                    } elseif ($jenis_cuti_lower == 'khusus') {
                                                        echo '<span class="badge bg-warning-subtle text-dark border border-warning fw-bold px-2.5 py-1">Cuti Khusus</span>';
                                                    } elseif ($jenis_cuti_lower == 'dipotong') {
                                                        echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2.5 py-1">Cuti Lainnya</span>';
                                                    } else {
                                                        echo '<span class="badge bg-light text-dark border px-2.5 py-1">' . formatJenisCuti($cuti['jenis']) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo ($cuti['potong_gaji'] == 1) ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">Ya</span>' : '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">Tidak</span>'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-purple-subtle fw-bold px-2.5 py-1" style="background: rgba(139, 92, 246, 0.12); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.2);"><?php echo $durasi; ?> Hari</span>
                                                    <div class="small text-muted mt-1"><?php echo date('d M', strtotime($cuti['tgl_mulai'])); ?> - <?php echo date('d M Y', strtotime($cuti['tgl_selesai'])); ?></div>
                                                </td>
                                                <td class="text-wrap">
                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($cuti['keterangan']); ?>" class="fw-medium text-dark">
                                                        <?php echo htmlspecialchars(substr($cuti['keterangan'], 0, 45)) . (strlen($cuti['keterangan']) > 45 ? '...' : ''); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (!empty($cuti['bukti'])): ?>
                                                        <a href="../uploads/bukti_cuti/<?php echo htmlspecialchars($cuti['bukti']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1" title="Lihat Lampiran Bukti"><i class="fa-solid fa-paperclip me-1"></i>Bukti</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="status-cell text-center"><?php echo formatStatusVerif($cuti['verif']); ?></td>
                                                <td class="action-cell text-center" data-editable="<?php echo $is_editable ? 'true' : 'false'; ?>">
                                                    <?php if ($is_editable): ?>
                                                        <button class="btn btn-success btn-sm rounded-3 px-2 py-1 btn-terima me-1" data-id="<?php echo $cuti['id']; ?>" data-jenis="<?php echo $cuti['jenis']; ?>" data-nama="<?php echo htmlspecialchars($cuti['nama']); ?>" title="Setujui Pengajuan"><i class="fa-solid fa-check me-1"></i>Setujui</button>
                                                        <button class="btn btn-danger btn-sm rounded-3 px-2 py-1 btn-tolak me-1" data-id="<?php echo $cuti['id']; ?>" data-jenis="<?php echo $cuti['jenis']; ?>" data-nama="<?php echo htmlspecialchars($cuti['nama']); ?>" title="Tolak Pengajuan"><i class="fa-solid fa-times me-1"></i>Tolak</button>
                                                    <?php else: ?>
                                                        <span class="text-muted small me-2" title="Pengajuan telah diverifikasi"><i class="fa-solid fa-lock"></i> Kunci</span>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-danger btn-sm rounded-3 px-2 py-1 btn-delete" data-id="<?php echo $cuti['id']; ?>" data-nama="<?php echo htmlspecialchars($cuti['nama']); ?>" title="Hapus Data"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $base_url . (empty($query_string) ? '' : '&') . 'page=' . ($page - 1); ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $base_url . (empty($query_string) ? '' : '&') . 'page=' . $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $base_url . (empty($query_string) ? '' : '&') . 'page=' . ($page + 1); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="modalTerima" tabindex="-1" aria-labelledby="modalTerimaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalTerimaLabel"><i class="fa-solid fa-check-circle me-2"></i>Terima Pengajuan Cuti</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTerima">
                    <div class="modal-body">
                        <input type="hidden" name="cuti_id" id="cutiIdTerima">
                        <input type="hidden" name="action" value="terima">
                        <div class="mb-3">
                            <label for="jenisCuti" class="form-label fw-bold small text-secondary">Jenis Cuti</label>
                            <select class="form-select rounded-3" id="jenisCuti" name="jenis_cuti" required>
                                <option value="hak">Cuti Hak</option>
                                <option value="khusus">Cuti Khusus</option>
                                <option value="dipotong">Cuti Lainnya</option>
                            </select>
                        </div>
                        <p class="mb-0 text-muted small">Konfirmasi persetujuan pengajuan cuti untuk karyawan <strong id="namaKaryawanTerima"></strong>.</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success rounded-3 fw-bold px-4">Setujui Cuti</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTolak" tabindex="-1" aria-labelledby="modalTolakLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="modalTolakLabel"><i class="fa-solid fa-times-circle me-2"></i>Tolak Pengajuan Cuti</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTolak">
                    <div class="modal-body">
                        <input type="hidden" name="cuti_id" id="cutiIdTolak">
                        <input type="hidden" name="action" value="tolak">
                        <p class="mb-2">Anda yakin ingin menolak pengajuan cuti dari <strong id="namaKaryawanTolak"></strong>?</p>
                        <div class="mb-3">
                            <label for="alasanPenolakan" class="form-label fw-bold small text-secondary">Alasan Penolakan (Opsional)</label>
                            <textarea class="form-control rounded-3" id="alasanPenolakan" name="reason" rows="3" placeholder="Contoh: Kuota cuti tidak mencukupi..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-3 fw-bold px-4">Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            $('.btn-terima').on('click', function() {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var jenis = $(this).data('jenis');
                
                $('#cutiIdTerima').val(id);
                $('#namaKaryawanTerima').text(nama);
                $('#jenisCuti').val(jenis);
                
                var modal = new bootstrap.Modal(document.getElementById('modalTerima'));
                modal.show();
            });

            $('.btn-tolak').on('click', function() {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                
                $('#cutiIdTolak').val(id);
                $('#namaKaryawanTolak').text(nama);
                
                var modal = new bootstrap.Modal(document.getElementById('modalTolak'));
                modal.show();
            });

            $('#formTerima').on('submit', function(e) {
                e.preventDefault();
                var modalEl = document.getElementById('modalTerima');
                var modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();

                $.ajax({
                    url: 'process_cuti.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Setujui Berhasil',
                                text: 'Pengajuan cuti berhasil disetujui.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: response.message
                            });
                        }
                    }
                });
            });

            $('#formTolak').on('submit', function(e) {
                e.preventDefault();
                var modalEl = document.getElementById('modalTolak');
                var modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();

                $.ajax({
                    url: 'process_cuti.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Tolak Berhasil',
                                text: 'Pengajuan cuti berhasil ditolak.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: response.message
                            });
                        }
                    }
                });
            });

            $('.btn-delete').on('click', function() {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                
                Swal.fire({
                    title: 'Hapus Data Pengajuan Cuti',
                    text: 'Apakah Anda yakin ingin menghapus data pengajuan cuti dari ' + nama + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'process_cuti.php',
                            type: 'POST',
                            data: { action: 'delete', cuti_id: id },
                            dataType: 'json',
                            success: function(response) {
                                if(response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Hapus Berhasil',
                                        text: 'Data pengajuan cuti berhasil dihapus.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(function() {
                                        $('#cuti-row-' + id).fadeOut();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal',
                                        text: response.message
                                    });
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>