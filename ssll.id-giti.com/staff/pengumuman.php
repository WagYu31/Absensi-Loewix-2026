<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include '../get-kar-login-data.php';

$target_dir = "../uploads/pengumuman/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$error_msg = '';
$success_msg = '';

// Handling Form Submission (Insert / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $judul = trim($_POST['judul'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        $jenis = trim($_POST['jenis'] ?? 'Info');
        $nip_creator = $_SESSION['nip'];

        if (empty($judul) || empty($isi)) {
            $error_msg = "Judul dan isi pengumuman tidak boleh kosong.";
        } else {
            $gambar_filename = null;
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['gambar']['tmp_name'];
                $file_name = $_FILES['gambar']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($file_ext, $allowed_exts)) {
                    $gambar_filename = 'announcement_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    if (!move_uploaded_file($file_tmp, $target_dir . $gambar_filename)) {
                        $gambar_filename = null;
                        $error_msg = "Gagal mengunggah gambar ke folder server.";
                    }
                } else {
                    $error_msg = "Format gambar tidak didukung (gunakan JPG, PNG, WEBP, atau GIF).";
                }
            }

            if (empty($error_msg)) {
                if ($action === 'create') {
                    $stmt = $conn->prepare("INSERT INTO pengumuman (nip, jenis, judul, isi, gambar, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssss", $nip_creator, $jenis, $judul, $isi, $gambar_filename);
                    if ($stmt->execute()) {
                        $success_msg = "Pengumuman baru berhasil dipublikasikan!";
                    } else {
                        $error_msg = "Gagal menyimpan pengumuman ke database: " . $conn->error;
                    }
                    $stmt->close();
                } else {
                    // Update
                    if ($gambar_filename) {
                        // Delete old image if exists
                        $stmt_old = $conn->prepare("SELECT gambar FROM pengumuman WHERE id = ?");
                        $stmt_old->bind_param("i", $id);
                        $stmt_old->execute();
                        $res_old = $stmt_old->get_result();
                        if ($row_old = $res_old->fetch_assoc()) {
                            if (!empty($row_old['gambar']) && file_exists($target_dir . $row_old['gambar'])) {
                                unlink($target_dir . $row_old['gambar']);
                            }
                        }
                        $stmt_old->close();

                        $stmt = $conn->prepare("UPDATE pengumuman SET jenis = ?, judul = ?, isi = ?, gambar = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("ssssi", $jenis, $judul, $isi, $gambar_filename, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE pengumuman SET jenis = ?, judul = ?, isi = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("sssi", $jenis, $judul, $isi, $id);
                    }

                    if ($stmt->execute()) {
                        $success_msg = "Pengumuman berhasil diperbarui!";
                    } else {
                        $error_msg = "Gagal memperbarui pengumuman: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE pengumuman SET deleted_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success_msg = "Pengumuman berhasil dihapus.";
            } else {
                $error_msg = "Gagal menghapus pengumuman.";
            }
            $stmt->close();
        }
    }
}

// --- PAGINATION SETTINGS ---
$limit_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit_per_page;

$search = trim($_GET['search'] ?? '');
$where_clause = "deleted_at IS NULL";
$bind_types = "";
$bind_params = [];

if (!empty($search)) {
    $where_clause .= " AND (judul LIKE ? OR isi LIKE ? OR jenis LIKE ?)";
    $like_search = "%$search%";
    $bind_types .= "sss";
    $bind_params[] = $like_search;
    $bind_params[] = $like_search;
    $bind_params[] = $like_search;
}

// Total Announcements count for pagination
$sql_count = "SELECT COUNT(id) as total FROM pengumuman WHERE $where_clause";
$stmt_count = $conn->prepare($sql_count);
if (!empty($bind_types)) {
    $stmt_count->bind_param($bind_types, ...$bind_params);
}
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total_row = $res_count->fetch_assoc();
$total_announcements = $total_row['total'] ?? 0;
$total_pages = ceil($total_announcements / $limit_per_page);
$stmt_count->close();

// Fetch Announcements
$sql_fetch = "SELECT id, judul, isi, jenis, created_at, gambar FROM pengumuman WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt_fetch = $conn->prepare($sql_fetch);
$fetch_types = $bind_types . "ii";
$fetch_params = array_merge($bind_params, [$limit_per_page, $offset]);
$stmt_fetch->bind_param($fetch_types, ...$fetch_params);
$stmt_fetch->execute();
$announcements = $stmt_fetch->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_fetch->close();

$current_page_basename = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengumuman - Gravitti Tech</title>
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
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            background: #f1f5f9 !important;
        }
        .main-content-wrapper {
            background: #f1f5f9;
            min-height: 100vh;
        }
        .page-specific-header {
            background: var(--header-gradient) !important;
            color: #ffffff;
            padding: 2.25rem 0 4.5rem 0 !important;
            margin-bottom: -50px !important;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25) !important;
        }
        .card-main {
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--card-radius-lg);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .badge-type {
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 8px;
        }
        
        /* Scale inline stickers inside headings and links */
        table td img, .modal-title img {
            max-height: 24px !important;
            width: auto !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
        
        /* Premium Emoji & Sticker Board CSS */
        .picker-btn-toggle {
            font-size: 0.8rem;
            font-weight: 700;
            color: #4b5563;
            background: #ffffff;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }
        .picker-btn-toggle:hover {
            background: #f3f4f6;
            color: #111827;
        }
        .board-container {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 16px;
            padding: 12px;
            margin-top: 10px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        .board-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .board-tab-link {
            font-size: 0.78rem;
            font-weight: 800;
            color: #6b7280;
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .board-tab-link.active {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2);
        }
        .board-content-pane {
            max-height: 180px;
            overflow-y: auto;
            display: grid;
            gap: 8px;
        }
        .emoji-grid {
            grid-template-columns: repeat(10, 1fr);
            font-size: 1.35rem;
            user-select: none;
        }
        .emoji-item {
            text-align: center;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            transition: background 0.15s;
        }
        .emoji-item:hover {
            background: #e5e7eb;
        }
        .sticker-grid {
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }
        .sticker-item {
            cursor: pointer;
            border-radius: 10px;
            border: 1px solid transparent;
            background: #ffffff;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .sticker-item:hover {
            border-color: #3b82f6;
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .sticker-item img {
            width: 46px;
            height: 46px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <!-- Header Banner -->
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="fw-bold text-white fs-3"><i class="fa-solid fa-bullhorn me-2 text-primary-light"></i>Kelola Pengumuman</h1>
                    <p class="small opacity-80 mb-0">Publikasikan informasi, berita, dan instruksi penting perusahaan untuk seluruh karyawan.</p>
                </div>
                <div>
                    <button class="btn btn-light btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-sm" onclick="openCreateModal()">
                        <i class="fa-solid fa-plus me-1.5"></i>Buat Pengumuman Baru
                    </button>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <div class="card-main">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <h6 class="fw-extrabold text-dark m-0"><i class="fa-solid fa-list me-2 text-primary"></i>Daftar Pengumuman</h6>
                        
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="max-width: 280px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Cari judul / isi..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm rounded-3 px-3 fw-bold">Cari</button>
                            <?php if(!empty($search)): ?>
                                <a href="<?php echo $current_page_basename; ?>" class="btn btn-outline-secondary btn-sm rounded-3 px-2.5">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center p-5 text-muted">Belum ada pengumuman yang diterbitkan.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                                    <thead class="table-light text-uppercase text-secondary fw-bold" style="font-size: 0.75rem;">
                                        <tr>
                                            <th class="ps-3" width="60">No.</th>
                                            <th>Gambar</th>
                                            <th>Info Pengumuman</th>
                                            <th>Isi Ringkas</th>
                                            <th>Kategori</th>
                                            <th>Tanggal Rilis</th>
                                            <th class="text-center" width="140">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = $offset + 1; foreach ($announcements as $item): ?>
                                            <tr>
                                                <td class="ps-3 text-secondary fw-semibold"><?php echo $no++; ?></td>
                                                <td>
                                                    <?php if(!empty($item['gambar']) && file_exists($target_dir . $item['gambar'])): ?>
                                                        <img src="../uploads/pengumuman/<?php echo htmlspecialchars($item['gambar']); ?>" alt="Img" style="width: 54px; height: 54px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                                                    <?php else: ?>
                                                        <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; border-radius: 8px; font-size: 1.2rem;"><i class="fa-solid fa-image"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo $item['judul']; ?></div>
                                                </td>
                                                <td>
                                                    <span class="text-secondary"><?php echo htmlspecialchars(substr(strip_tags($item['isi']), 0, 80)) . (strlen(strip_tags($item['isi'])) > 80 ? '...' : ''); ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $kat = strtolower($item['jenis']);
                                                    if ($kat === 'penting') {
                                                        echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold rounded-pill px-2.5 py-1">Penting</span>';
                                                    } elseif ($kat === 'acara') {
                                                        echo '<span class="badge bg-warning-subtle text-dark border border-warning fw-bold rounded-pill px-2.5 py-1">Acara</span>';
                                                    } else {
                                                        echo '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold rounded-pill px-2.5 py-1">Informasi</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-secondary"><?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary rounded-3 px-2.5 py-1 me-1" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" title="Edit Pengumuman"><i class="fa-solid fa-pen-to-square"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger rounded-3 px-2.5 py-1" onclick="confirmDelete(<?php echo $item['id']; ?>)" title="Hapus Pengumuman"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="announcementModalLabel"><i class="fa-solid fa-bullhorn me-2"></i>Buat Pengumuman</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="announcementForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="formId" value="">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Kategori Pengumuman</label>
                            <select class="form-select rounded-3" name="jenis" id="formJenis" required>
                                <option value="Info">Informasi Umum</option>
                                <option value="Penting">Penting</option>
                                <option value="Acara">Acara / Event</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Judul Pengumuman</label>
                            <input type="text" class="form-control rounded-3" name="judul" id="formJudul" placeholder="Ketik judul pengumuman..." required>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small text-secondary mb-0">Isi Pengumuman</label>
                                <button type="button" class="btn btn-sm picker-btn-toggle rounded-pill px-2.5 py-1" onclick="toggleEmojiStickerBoard()">
                                    <i class="fa-solid fa-face-smile text-warning me-1.5"></i>Sisipkan Emoji & Stiker
                                </button>
                            </div>
                            <textarea class="form-control rounded-3" name="isi" id="formIsi" rows="6" placeholder="Ketik pesan atau detail pengumuman secara lengkap..." required></textarea>
                            
                            <!-- Hidden tabbed emoji and animated sticker board -->
                            <div id="emojiStickerBoard" class="board-container d-none">
                                <div class="board-tabs">
                                    <button type="button" class="board-tab-link active" onclick="switchBoardTab('emojis')"><i class="fa-regular fa-face-grin-stars me-1 text-warning"></i> Emoji</button>
                                    <button type="button" class="board-tab-link" onclick="switchBoardTab('stickers')"><i class="fa-solid fa-bolt me-1 text-danger"></i> Stiker Bergerak (GIF)</button>
                                </div>
                                
                                <div id="paneEmojis" class="board-content-pane emoji-grid">
                                    <!-- Populated dynamically via JS -->
                                </div>
                                
                                <div id="paneStickers" class="board-content-pane sticker-grid d-none">
                                    <!-- Populated dynamically via JS -->
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Lampiran Gambar (Opsional)</label>
                            <input type="file" class="form-control rounded-3" name="gambar" id="formGambar" accept="image/*">
                            <div class="form-text small">Mendukung format JPG, PNG, WEBP, atau GIF. Ukuran gambar ideal 800x600px.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4" id="submitBtn">Terbitkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form (Invisible) -->
    <form id="deleteForm" method="POST" class="d-none">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId" value="">
    </form>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            <?php if (!empty($success_msg)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo htmlspecialchars($success_msg); ?>',
                    timer: 1800,
                    showConfirmButton: false
                });
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: '<?php echo htmlspecialchars($error_msg); ?>'
                });
            <?php endif; ?>
        });

        const annModal = new bootstrap.Modal(document.getElementById('announcementModal'));

        function openCreateModal() {
            $('#formAction').val('create');
            $('#formId').val('');
            $('#formJenis').val('Info');
            $('#formJudul').val('');
            $('#formIsi').val('');
            $('#formGambar').val('');
            $('#emojiStickerBoard').addClass('d-none');
            $('#announcementModalLabel').html('<i class="fa-solid fa-bullhorn me-2"></i>Buat Pengumuman Baru');
            $('#submitBtn').text('Terbitkan');
            annModal.show();
        }

        function openEditModal(item) {
            $('#formAction').val('edit');
            $('#formId').val(item.id);
            $('#formJenis').val(item.jenis);
            $('#formJudul').val(item.judul);
            $('#formIsi').val(item.isi);
            $('#formGambar').val('');
            $('#emojiStickerBoard').addClass('d-none');
            $('#announcementModalLabel').html('<i class="fa-solid fa-pen-to-square me-2"></i>Edit Pengumuman');
            $('#submitBtn').text('Simpan Perubahan');
            annModal.show();
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Pengumuman?',
                text: "Karyawan tidak akan dapat melihat pengumuman ini lagi.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#deleteId').val(id);
                    $('#deleteForm').submit();
                }
            });
        }

        // Emoji & Sticker board helper functions
        const emojisList = [
            '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','😷','🤒','🤕','🤑','🤠','😈','👿','👹','👺','🤡','💩','👻','💀','☠️','👽','👾','🤖','🎃','😺','😸','😹','😻','😼','😽','🙀','😿','😾','👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','👂','🦻','👃','🧠','🫀','🫁','🦷','🦴','👀','👁','👅','👄','💋','🩸','📢','🔔','🔥','✨','🎉','🎊','🎈','🎁','🎀','🏆','🥇','🥈','🥉','💡','💻','📅','📌','📍','🚀','🎯'
        ];

        const stickersList = [
            { name: 'Megaphone', url: 'https://media.giphy.com/media/VbnUQpnihPSIgIXNVv/giphy.gif' },
            { name: 'Fire / Semangat', url: 'https://media.giphy.com/media/l0IybQ67MfjacTI52/giphy.gif' },
            { name: 'Congrats Popper', url: 'https://media.giphy.com/media/26tP21a9ZCZBY3jG0/giphy.gif' },
            { name: 'Congrats Sparkle', url: 'https://media.giphy.com/media/3o7qE1YN7aBOFPRw8E/giphy.gif' },
            { name: 'Rocket Launch', url: 'https://media.giphy.com/media/tXL4FHPSnVJ0A/giphy.gif' },
            { name: 'Bullseye Target', url: 'https://media.giphy.com/media/3o7TKSjRrfIPjei1fG/giphy.gif' },
            { name: 'Siren Warning', url: 'https://media.giphy.com/media/l3q2zVr6cu95nF6O4/giphy.gif' },
            { name: 'Trophy Victory', url: 'https://media.giphy.com/media/xT0xezQGU5xCDSK316/giphy.gif' },
            { name: 'Birthday Cake', url: 'https://media.giphy.com/media/3o85xGocUH8TCQDDry/giphy.gif' },
            { name: 'Like Thumbs Up', url: 'https://media.giphy.com/media/l41YkxvU8c7J7Bba0/giphy.gif' }
        ];

        // Populate board
        function initEmojiStickerBoard() {
            // Emojis
            let emojiHtml = '';
            emojisList.forEach(emoji => {
                emojiHtml += `<div class="emoji-item" onclick="insertValueToEditor('${emoji}', false)">${emoji}</div>`;
            });
            $('#paneEmojis').html(emojiHtml);

            // Stickers
            let stickerHtml = '';
            stickersList.forEach(stk => {
                stickerHtml += `
                    <div class="sticker-item" onclick="insertValueToEditor('${stk.url}', true)" title="${stk.name}">
                        <img src="${stk.url}" alt="${stk.name}">
                    </div>
                `;
            });
            $('#paneStickers').html(stickerHtml);
        }

        function toggleEmojiStickerBoard() {
            $('#emojiStickerBoard').toggleClass('d-none');
        }

        function switchBoardTab(tab) {
            $('.board-tab-link').removeClass('active');
            if (tab === 'emojis') {
                $('.board-tab-link').first().addClass('active');
                $('#paneEmojis').removeClass('d-none');
                $('#paneStickers').addClass('d-none');
            } else {
                $('.board-tab-link').last().addClass('active');
                $('#paneEmojis').addClass('d-none');
                $('#paneStickers').removeClass('d-none');
            }
        }

        function insertValueToEditor(val, isSticker) {
            const textarea = document.getElementById('formIsi');
            let insertText = val;
            if (isSticker) {
                insertText = `<img src="${val}" style="width: 80px; height: 80px; display: inline-block; vertical-align: middle;" />`;
            }

            // Insert at cursor position
            if (textarea.selectionStart || textarea.selectionStart === 0) {
                const startPos = textarea.selectionStart;
                const endPos = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, startPos) + insertText + textarea.value.substring(endPos, textarea.value.length);
                textarea.focus();
                textarea.selectionStart = startPos + insertText.length;
                textarea.selectionEnd = startPos + insertText.length;
            } else {
                textarea.value += insertText;
            }
        }

        // Initialize board
        initEmojiStickerBoard();
    </script>
</body>
</html>
