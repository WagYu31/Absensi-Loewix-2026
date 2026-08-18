<?php
session_start();

// Cek apakah pengguna telah login (Semua peran bisa melihat pengumuman)
if (!isset($_SESSION['nip'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
// get-kar-login-data.php menyediakan $nip (session NIP), $nama, $jabatan, $nik (database NIK)
include 'get-kar-login-data.php';

$loggedInUserNip = $_SESSION['nip']; // Untuk link di sidebar/nav bawah
$nama_karyawan_login = $nama ?? 'Karyawan';

// --- PENGATURAN PAGINATION ---
$limit_per_halaman = 5; // Jumlah pengumuman per halaman
$halaman_aktif = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$halaman_aktif = max($halaman_aktif, 1);
$offset = ($halaman_aktif - 1) * $limit_per_halaman;

// Hitung total pengumuman untuk pagination
$totalResultPengumuman = $conn->query("SELECT COUNT(id) as total FROM pengumuman WHERE deleted_at IS NULL");
$totalRowPengumuman = $totalResultPengumuman->fetch_assoc();
$totalDataPengumuman = $totalRowPengumuman['total'] ?? 0;
$totalPagesPengumuman = ceil($totalDataPengumuman / $limit_per_halaman);

// --- AMBIL DATA SEMUA PENGUMUMAN DARI DATABASE DENGAN PAGINATION ---
$pengumuman_semua_list = [];
$sql_semua_pengumuman = "SELECT id, judul, isi, jenis, created_at, gambar, media 
                         FROM pengumuman 
                         WHERE deleted_at IS NULL 
                         ORDER BY created_at DESC 
                         LIMIT ? OFFSET ?";

$stmt_semua_pengumuman = $conn->prepare($sql_semua_pengumuman);
if ($stmt_semua_pengumuman) {
    $stmt_semua_pengumuman->bind_param("ii", $limit_per_halaman, $offset);
    $stmt_semua_pengumuman->execute();
    $result_semua_pengumuman = $stmt_semua_pengumuman->get_result();
    if ($result_semua_pengumuman->num_rows > 0) {
        while ($row_sp = $result_semua_pengumuman->fetch_assoc()) {
            $pengumuman_semua_list[] = $row_sp;
        }
    }
    $stmt_semua_pengumuman->close();
} else {
    error_log("Gagal prepare query semua pengumuman: " . $conn->error);
}

$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Pengumuman - Grav-Tech</title>
    <meta name="description" content="Daftar semua pengumuman perusahaan Grav-Tech" />
    <meta name="keywords" content="pengumuman, informasi, kantor, gravitti technology" />
    <meta name="author" content="Irviani & AI Assistant" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/pengumuman-styles.css">
    <style>
        /* Scale inline stickers inside headings and links */
        .announcement-item-title a img {
            max-height: 24px !important;
            width: auto !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
        .modal-title img {
            max-height: 28px !important;
            width: auto !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Semua Pengumuman</h1>
                <p>Informasi dan berita terbaru dari perusahaan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">

                <?php if (empty($pengumuman_semua_list)): ?>
                    <div class="text-center p-5">
                        <i class="fa-solid fa-folder-open fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Belum Ada Pengumuman</h4>
                        <p class="text-muted">Saat ini tidak ada pengumuman yang dapat ditampilkan.</p>
                        <a href="dashboard_karyawan.php" class="btn btn-outline-primary mt-2">Kembali ke Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="announcement-list-container">
                        <?php foreach ($pengumuman_semua_list as $item): ?>
                            <div class="card announcement-list-item mb-3 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title announcement-item-title">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#announcementDetailModal_<?php echo htmlspecialchars($item['id']); ?>">
                                            <?php echo $item['judul']; ?>
                                        </a>
                                    </h5>
                                    <div class="announcement-item-meta mb-2">
                                        <span class="me-3" title="Tanggal Publikasi">
                                            <i class="fa-regular fa-calendar-alt me-1"></i>
                                            <?php
                                            try {
                                                echo (new DateTime($item['created_at']))->format('d M Y, H:i');
                                            } catch (Exception $e) {
                                                echo htmlspecialchars($item['created_at']);
                                            }
                                            ?>
                                        </span>
                                        <?php if (!empty($item['jenis'])): ?>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border" title="Kategori">
                                                <i class="fa-solid fa-tag me-1"></i><?php echo htmlspecialchars($item['jenis']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text announcement-item-snippet">
                                        <?php
                                        $isi_snippet = strip_tags($item['isi']);
                                        echo htmlspecialchars(substr($isi_snippet, 0, 180)) . (strlen($isi_snippet) > 180 ? '...' : '');
                                        ?>
                                    </p>
                                    <button type="button" class="btn btn-sm btn-outline-primary read-more-btn" data-bs-toggle="modal" data-bs-target="#announcementDetailModal_<?php echo htmlspecialchars($item['id']); ?>">
                                        Baca Selengkapnya <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPagesPengumuman > 1): ?>
                        <nav aria-label="Page navigation Pengumuman" class="mt-4 pt-2">
                            <ul class="pagination justify-content-center">
                                <?php if ($halaman_aktif > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?php echo $halaman_aktif - 1; ?>">Sebelumnya</a></li>
                                <?php endif; ?>

                                <?php
                                $start_page_nav = max(1, $halaman_aktif - 2);
                                $end_page_nav = min($totalPagesPengumuman, $halaman_aktif + 2);
                                if ($halaman_aktif <= 3) $end_page_nav = min($totalPagesPengumuman, 5);
                                if ($halaman_aktif >= $totalPagesPengumuman - 2) $start_page_nav = max(1, $totalPagesPengumuman - 4);

                                if ($start_page_nav > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                for ($p_nav = $start_page_nav; $p_nav <= $end_page_nav; $p_nav++): ?>
                                    <li class="page-item <?php echo ($p_nav == $halaman_aktif) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $p_nav; ?>"><?php echo $p_nav; ?></a>
                                    </li>
                                <?php endfor;
                                if ($end_page_nav < $totalPagesPengumuman) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                ?>

                                <?php if ($halaman_aktif < $totalPagesPengumuman): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?php echo $halaman_aktif + 1; ?>">Berikutnya</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php endif; ?>

                <div class="footer">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>
            </div>
        </div>
    </div> <?php include 'nav/bottom-nav.php'; ?>

    <?php if (!empty($pengumuman_semua_list)): ?>
        <?php foreach ($pengumuman_semua_list as $item_modal): ?>
            <div class="modal fade" id="announcementDetailModal_<?php echo htmlspecialchars($item_modal['id']); ?>" tabindex="-1" aria-labelledby="announcementDetailModalLabel_<?php echo htmlspecialchars($item_modal['id']); ?>" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="announcementDetailModalLabel_<?php echo htmlspecialchars($item_modal['id']); ?>">
                                <?php echo $item_modal['judul']; ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3 modal-announcement-meta">
                                <small class="text-muted">
                                    <i class="fa-regular fa-calendar-alt me-1"></i> Diposting:
                                    <?php
                                    try {
                                        echo (new DateTime($item_modal['created_at']))->format('d F Y, H:i');
                                    } catch (Exception $e) {
                                        echo htmlspecialchars($item_modal['created_at']);
                                    }
                                    ?>
                                    <?php if (!empty($item_modal['jenis'])): ?>
                                        | <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle ms-1">
                                            <i class="fa-solid fa-tag me-1"></i><?php echo htmlspecialchars($item_modal['jenis']); ?>
                                        </span>
                                    <?php endif; ?>
                                </small>
                            </div>

                            <?php if (!empty($item_modal['gambar'])): ?>
                                <div class="mb-3 text-center">
                                    <img src="../uploads/pengumuman/<?php echo htmlspecialchars($item_modal['gambar']); ?>"
                                        alt="Gambar Pengumuman: <?php echo htmlspecialchars($item_modal['judul']); ?>"
                                        class="img-fluid rounded modal-announcement-image"
                                        style="max-height: 450px; object-fit: contain; border: 1px solid #eee;">
                                </div>
                            <?php endif; ?>

                            <hr class="my-3">
                            <div class="modal-announcement-content">
                                <?php
                                // Jika isi adalah plain text dengan newline
                                // echo nl2br(htmlspecialchars($item_modal['isi'])); 
                                // Jika isi adalah HTML yang sudah aman dari editor WYSIWYG
                                echo $item_modal['isi'];
                                ?>
                            </div>

                            <?php
                            if (!empty($item_modal['media'])):
                                $media_url = $item_modal['media'];
                                $youtube_embed_url = '';
                                if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $media_url, $matches)) {
                                    $youtube_embed_url = 'https://www.youtube.com/embed/' . $matches[1];
                                }
                            ?>
                                <hr class="my-3">
                                <h6 class="modal-subtitle-section">Media Terkait:</h6>
                                <?php if ($youtube_embed_url): ?>
                                    <div class="ratio ratio-16x9">
                                        <iframe src="<?php echo htmlspecialchars($youtube_embed_url); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <p><a href="<?php echo htmlspecialchars($media_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-link me-1"></i>Lihat Media Tambahan</a></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Skrip untuk menu aktif (sesuaikan dengan nama file PHP yang benar)
            var currentPath = "<?php echo $current_page_basename; ?>";

            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
            // Khusus untuk halaman ini
            if (currentPath === "semua-pengumuman.php" && !$('.sidebar-menu a[href="semua-pengumuman.php"]').hasClass('active')) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="semua-pengumuman.php"]').addClass('active');
            }


            // Untuk Navigasi Bawah Mobile
            $('.custom-nav__link.active').removeClass('active');
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) {
                // FAB is visually distinct
            } else if (currentPath === "dashboard_karyawan.php") {
                $('.custom-nav__link[href="dashboard_karyawan.php"]').addClass('active');
            } else if (currentPath === "profile.php") {
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }
            // Halaman semua pengumuman tidak ada di bottom nav utama
        });
    </script>
</body>

</html>