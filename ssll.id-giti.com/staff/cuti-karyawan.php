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
        case 'Pending': return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
        case 'Disetujui': return '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="fa-solid fa-check-circle me-1"></i>Disetujui</span>';
        case 'Ditolak': return '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="fa-solid fa-times-circle me-1"></i>Ditolak</span>';
        default: return '<span class="badge bg-light text-dark border">' . htmlspecialchars($status) . '</span>';
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
    $where_clauses[] = "(k.nama LIKE ? OR k.nip LIKE ?)";
    $search_like = "%" . $search_term . "%";
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "ss";
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
    $count_types .= "ss";
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
    <title>Kelola Pengajuan Cuti - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
        .table-sm-custom {
            font-size: 0.9rem; 
        }
        .table-sm-custom th,
        .table-sm-custom td {
            padding: 0.5rem; 
            vertical-align: middle;
        }
        .table-sm-custom .btn {
            padding: 0.1rem 0.4rem; 
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Kelola Pengajuan Cuti</h1>
                <p>Verifikasi pengajuan cuti dari seluruh karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4 px-0">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4" style="width:100%;">
                            <a href="input_cuti_manual.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i> Input Cuti</a>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <form action="<?php echo $current_page_basename; ?>" method="GET" class="d-flex gap-2">
                                    <input type="text" id="searchInput" name="search" class="form-control" placeholder="Cari Nama/NIP..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>" style="width: auto;">
                                    <button type="submit" class="btn btn-info">Cari</button>
                                    <a href="<?php echo $current_page_basename; ?>" class="btn btn-secondary">Reset</a>
                                </form>
                            </div>
                            
                            <div>
                                <span class="badge bg-warning text-dark"> </span>
                                <small class="text-muted ms-1 me-3">Cuti Khusus</small>
                                
                                <span class="badge bg-danger"> </span>
                                <small class="text-muted ms-1 me-3">Cuti Lainnya</small>
                                
                                <span class="badge bg-success"> </span>
                                <small class="text-muted ms-1">Cuti Hak</small>
                            </div>
                            
                            <div>
                                <a href="rekap_cuti.php" class="btn btn-warning">Rekap Cuti</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pengajuan_cuti_list)): ?>
                            <div class="text-center p-4 text-muted">Belum ada pengajuan cuti<?php echo $search_term ? ' dengan kriteria "' . htmlspecialchars($search_term) . '"' : ''; ?>.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 table-sm-custom" id="cutiTable">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama Karyawan</th>
                                            <th>Tgl. Pengajuan</th>
                                            <th>Jenis Cuti</th>
                                            <th>Potong Cuti</th>
                                            <th>Durasi</th>
                                            <th>Keterangan</th>
                                            <th>Bukti</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = $offset + 1; foreach ($pengajuan_cuti_list as $cuti): ?>
                                            <?php
                                                $tgl_mulai_cuti = new DateTime($cuti['tgl_mulai']);
                                                // $is_editable = $tgl_mulai_cuti >= $today;
                                                $is_editable = $cuti['verif'] != "Disetujui";
                                            ?>
                                            <tr id="cuti-row-<?php echo $cuti['id']; ?>">
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($cuti['nama']); ?><br><small class="text-muted"><?php echo htmlspecialchars($cuti['nip']); ?></small></td>
                                                <td><?php echo date('d M Y', strtotime($cuti['created_at'])); ?></td>
                                                <td class="jenis-cell">
                                                    <?php
                                                    $jenis_cuti_lower = strtolower($cuti['jenis']);
                                                    $badge_class = '';
                                                    if ($jenis_cuti_lower == 'hak') {
                                                        $badge_class = 'bg-success';
                                                    } elseif ($jenis_cuti_lower == 'khusus') {
                                                        $badge_class = 'bg-warning text-dark';
                                                    } elseif ($jenis_cuti_lower == 'dipotong') {
                                                        $badge_class = 'bg-danger';
                                                    } else {
                                                        $badge_class = 'bg-secondary';
                                                    }
                                                    echo '<span class="badge ' . $badge_class . '">' . formatJenisCuti($cuti['jenis']) . '</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo ($cuti['potong_gaji'] == 1) ? '<span class="text-danger">Ya</span>' : 'Tidak'; ?>
                                                </td>
                                                <td>
                                                    <?php echo hitungDurasiCuti($cuti['tgl_mulai'], $cuti['tgl_selesai'], $holidays); ?> hari
                                                    <br><small class="text-muted"><?php echo date('d M y', strtotime($cuti['tgl_mulai'])); ?> - <?php echo date('d M y', strtotime($cuti['tgl_selesai'])); ?></small>
                                                </td>
                                                <td class="text-wrap">
                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($cuti['keterangan']); ?>">
                                                        <?php echo htmlspecialchars(substr($cuti['keterangan'], 0, 40)) . (strlen($cuti['keterangan']) > 40 ? '...' : ''); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($cuti['bukti'])): ?>
                                                        <a href="../uploads/bukti_cuti/<?php echo htmlspecialchars($cuti['bukti']); ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1"><i class="fa-solid fa-paperclip"></i></a>
                                                    <?php else: echo '-'; endif; ?>
                                                </td>
                                                <td class="status-cell"><?php echo formatStatusVerif($cuti['verif']); ?></td>
                                                <td class="action-cell text-center" data-editable="<?php echo $is_editable ? 'true' : 'false'; ?>">
                                                    <?php if ($is_editable): ?>
                                                        <button class="btn btn-success btn-sm btn-terima" data-id="<?php echo $cuti['id']; ?>" data-jenis="<?php echo $cuti['jenis']; ?>" data-nama="<?php echo htmlspecialchars($cuti['nama']); ?>" title="Setujui"><i class="fa-solid fa-check"></i></button>
                                                        <button class="btn btn-danger btn-sm btn-tolak" data-id="<?php echo $cuti['id']; ?>" data-jenis="<?php echo $cuti['jenis']; ?>" data-nama="<?php echo htmlspecialchars($cuti['nama']); ?>" title="Tolak"><i class="fa-solid fa-times"></i></button>
                                                    <?php else: ?>
                                                        <span class="text-muted" title="Tanggal cuti sudah lewat"><i class="fa-solid fa-lock"></i></span>
                                                    <?php endif; ?>
                                                    <button class="btn btn-danger btn-sm btn-delete ms-2" data-id="<?php echo $cuti['id']; ?>" data-nama="<?php echo htmlspecialchars($cuti['nama']); ?>" title="Hapus Data"><i class="fa-solid fa-trash"></i></button>
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

    <div class="modal fade" id="modalTerima" tabindex="-1" aria-labelledby="modalTerimaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTerimaLabel">Terima Pengajuan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTerima">
                    <div class="modal-body">
                        <input type="hidden" name="cuti_id" id="cutiIdTerima">
                        <input type="hidden" name="action" value="terima">
                        <div class="mb-3">
                            <label for="jenisCuti" class="form-label">Jenis Cuti</label>
                            <select class="form-select" id="jenisCuti" name="jenis_cuti" required>
                                <option value="hak">Cuti Hak</option>
                                <option value="khusus">Cuti Khusus</option>
                                <option value="dipotong">Cuti Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Potong Gaji?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="potong_gaji" id="potongGajiYa" value="1" required>
                                <label class="form-check-label" for="potongGajiYa">Ya, potong gaji</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="potong_gaji" id="potongGajiTidak" value="0" checked>
                                <label class="form-check-label" for="potongGajiTidak">Tidak, jangan potong gaji</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Persetujuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTolak" tabindex="-1" aria-labelledby="modalTolakLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTolakLabel">Tolak Pengajuan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTolak">
                    <div class="modal-body">
                        <input type="hidden" name="cuti_id" id="cutiIdTolak">
                        <input type="hidden" name="action" value="tolak">
                        <div class="mb-3">
                            <label for="alasanPenolakan" class="form-label">Alasan Penolakan</label>
                            <textarea class="form-control" id="alasanPenolakan" name="alasan" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDelete" tabindex="-1" aria-labelledby="modalDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDeleteLabel">Hapus Pengajuan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formDelete">
                    <div class="modal-body">
                        <input type="hidden" name="cuti_id" id="cutiIdDelete">
                        <input type="hidden" name="action" value="delete">
                        <p>Apakah Anda yakin ingin menghapus pengajuan cuti untuk <strong id="namaKaryawanDelete"></strong>?</p>
                        <p class="text-danger small">Tindakan ini tidak dapat dibatalkan melalui halaman ini.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
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

        var modalTerima = new bootstrap.Modal(document.getElementById('modalTerima'));
        var modalTolak = new bootstrap.Modal(document.getElementById('modalTolak'));
        var modalDelete = new bootstrap.Modal(document.getElementById('modalDelete'));

        $(document).on('click', '.btn-terima', function() {
            var cutiId = $(this).data('id');
            var jenisCutiDefault = $(this).data('jenis');
            
            $('#cutiIdTerima').val(cutiId);
            $('#jenisCuti').val(jenisCutiDefault);
            
            modalTerima.show();
        });

        $(document).on('click', '.btn-tolak', function() {
            var cutiId = $(this).data('id');
            $('#cutiIdTolak').val(cutiId);
            $('#alasanPenolakan').val('');
            modalTolak.show();
        });
        
        $(document).on('click', '.btn-delete', function() {
            var cutiId = $(this).data('id');
            var nama = $(this).data('nama');
            $('#cutiIdDelete').val(cutiId);
            $('#namaKaryawanDelete').text(nama); 
            modalDelete.show();
        });
        
        function generateActionButtons(id, jenis, nama, isEditable) {
            var namaKaryawan = nama || 'Karyawan';
            var buttons = '';
            
            if (isEditable) {
                 buttons += `<button class="btn btn-success btn-sm btn-terima" data-id="${id}" data-jenis="${jenis}" data-nama="${namaKaryawan}" title="Setujui"><i class="fa-solid fa-check"></i></button>
                             <button class="btn btn-danger btn-sm btn-tolak" data-id="${id}" data-jenis="${jenis}" data-nama="${namaKaryawan}" title="Tolak"><i class="fa-solid fa-times"></i></button>`;
            } else {
                buttons += `<span class="text-muted" title="Tanggal cuti sudah lewat"><i class="fa-solid fa-lock"></i></span>`;
            }
            
            buttons += ` <button class="btn btn-dark btn-sm btn-delete" data-id="${id}" data-nama="${namaKaryawan}" title="Hapus Data"><i class="fa-solid fa-trash"></i></button>`;
            
            return buttons;
        }
        
        $('#formTerima').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serializeArray();
            var cutiId = $('#cutiIdTerima').val();
            var newJenis = $('#jenisCuti').val(); 
            var row = $('#cuti-row-' + cutiId);
            var nama = row.find('.btn-terima').data('nama') || 'Karyawan';
            var isEditable = row.find('.action-cell').data('editable') === 'true' || row.find('.action-cell').data('editable') === true;
            
            var potongGajiValue = $(this).find('input[name="potong_gaji"]:checked').val();

            $.ajax({
                type: 'POST',
                url: 'proses_verifikasi_cuti.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        modalTerima.hide();
                        
                        row.find('.status-cell').html('<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="fa-solid fa-check-circle me-1"></i>Disetujui</span>');
                        
                        row.find('.action-cell').html(generateActionButtons(cutiId, newJenis, nama, isEditable));
                        
                        const jenisMap = { 'hak': 'Cuti Hak', 'khusus': 'Cuti Khusus', 'dipotong': 'Cuti Lainnya' };
                        const badgeMap = {
                            'hak': 'bg-success',
                            'khusus': 'bg-warning text-dark',
                            'dipotong': 'bg-danger'
                        };
                        
                        var badgeClass = badgeMap[newJenis] || 'bg-secondary';
                        var badgeText = jenisMap[newJenis] || newJenis;
                        
                        row.find('.jenis-cell').html('<span class="badge ' + badgeClass + '">' + badgeText + '</span>');
                        
                        var potongGajiText = (potongGajiValue == 1) ? '<span class="text-danger">Ya</span>' : 'Tidak';
                        row.find('td:nth-child(5)').html(potongGajiText); 

                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() { alert('Terjadi kesalahan koneksi.'); }
            });
        });

        $('#formTolak').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var cutiId = $('#cutiIdTolak').val();
            var row = $('#cuti-row-' + cutiId);
            var nama = row.find('.btn-tolak').data('nama') || 'Karyawan';
            var originalJenis = row.find('.btn-tolak').data('jenis') || 'hak';
            var isEditable = row.find('.action-cell').data('editable') === 'true' || row.find('.action-cell').data('editable') === true;

            $.ajax({
                type: 'POST',
                url: 'proses_verifikasi_cuti.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        modalTolak.hide();
                        
                        row.find('.status-cell').html('<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="fa-solid fa-times-circle me-1"></i>Ditolak</span>');
                        
                        row.find('.action-cell').html(generateActionButtons(cutiId, originalJenis, nama, isEditable));
                        
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() { alert('Terjadi kesalahan koneksi.'); }
            });
        });
        
        $('#formDelete').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var cutiId = $('#cutiIdDelete').val();

            $.ajax({
                type: 'POST',
                url: 'proses_verifikasi_cuti.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        modalDelete.hide();
                        $('#cuti-row-' + cutiId).fadeOut(500, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() { alert('Terjadi kesalahan koneksi.'); }
            });
        });
    });
    </script>
</body>
</html>