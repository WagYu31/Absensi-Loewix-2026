<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['nip'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

$nip_user = $_SESSION['nip'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($action == 'fetch') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $hemat = isset($_GET['hemat']) ? (int)$_GET['hemat'] : 0;
    
    $where = "WHERE 1=1";
    if ($search != '') { 
        $where .= " AND curhatan.isi LIKE '%$search%'"; 
    }
    
    $sql = "SELECT curhatan.*, karyawan.nama as nama_karyawan, karyawan.pas_photo, 
            (SELECT COUNT(*) FROM curhatan_like WHERE id_curhat = curhatan.id_curhat) as likes,
            (SELECT COUNT(*) FROM curhatan_like WHERE id_curhat = curhatan.id_curhat AND nip = '$nip_user') as liked
            FROM curhatan 
            LEFT JOIN karyawan ON curhatan.nip = karyawan.nip 
            $where 
            ORDER BY curhatan.created_at DESC 
            LIMIT $limit OFFSET $offset";
    
    $res = $conn->query($sql);

    if (!$res) {
        header('HTTP/1.1 500 Internal Server Error');
        exit("SQL Error: " . $conn->error);
    }

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $is_liked = ($row['liked'] > 0) ? 'active text-primary fw-bold' : 'text-muted';
            $nama_tampil = !empty($row['nama_karyawan']) ? htmlspecialchars($row['nama_karyawan']) : "Karyawan (NIP: ".$row['nip'].")";
            $foto_tampil = !empty($row['pas_photo']) ? $row['pas_photo'] : 'default.png';
            $isi_clean = htmlspecialchars($row['isi']);
            
            $potongan = (strlen($isi_clean) > 70) ? substr($isi_clean, 0, 70) . "..." : $isi_clean;
            $more = (strlen($isi_clean) > 70) ? "<a href='curhatan_detail.php?id=".$row['id_curhat']."' class='text-decoration-none fw-bold'>Read more</a>" : "";
            
            echo '
            <div class="card card-story mb-4 shadow-sm border-0">
                <div class="p-3 d-flex align-items-center">
                    <img src="../uploads/'.$foto_tampil.'" style="width:45px; height:45px; border-radius:50%; object-fit:cover;" class="me-2" onerror="this.src=\'../uploads/default.png\'">
                    <div>
                        <div class="fw-bold" style="font-size:14px;">'.$nama_tampil.'</div>
                        <div class="text-muted" style="font-size:11px;">'.date('d M Y, H:i', strtotime($row['created_at'])).'</div>
                    </div>
                </div>
                <div class="px-3 pb-2" style="font-size:14px; white-space:pre-wrap;">'.$potongan.' '.$more.'</div>';

            if (!empty($row['media'])) {
                $path = "../uploads/story/" . $row['media'];
                if ($hemat == 1) {
                    echo '<div class="px-3 py-2 bg-light border-top border-bottom small"><i class="fa fa-link me-1"></i> <a href="'.$path.'" target="_blank">Lihat Media</a></div>';
                } else {
                    echo '<div class="media-frame" style="background:#000;">';
                    if ($row['tipe_media'] == 'image') {
                        echo '<img src="'.$path.'" loading="lazy" style="width:100%; max-height:500px; object-fit:contain;">';
                    } else {
                        echo '<video controls style="width:100%; max-height:500px;"><source src="'.$path.'" type="video/mp4"></video>';
                    }
                    echo '</div>';
                }
            }

            echo '
                <div class="p-2 d-flex border-top">
                    <button class="btn-action flex-grow-1 '.$is_liked.'" onclick="likeStory('.$row['id_curhat'].')" id="lk-'.$row['id_curhat'].'">
                        <i class="fa-regular fa-thumbs-up me-1"></i> <span id="ct-'.$row['id_curhat'].'">'.$row['likes'].'</span> Suka
                    </button>
                    <a href="curhatan_detail.php?id='.$row['id_curhat'].'" class="btn-action flex-grow-1 text-center text-muted" style="text-decoration:none; padding-top:6px;">
                        <i class="fa-regular fa-comment me-1"></i> Komentar
                    </a>
                </div>
            </div>';
        }
    } else {
        if ($page == 1) echo '<div class="text-center p-5 text-muted card-story">Belum ada curhatan yang tersedia.</div>';
    }
    exit();
}

// Handler Like & Post tetap sama namun pastikan koneksi aman
if ($action == 'like') {
    $id = (int)$_POST['id'];
    $conn->query("INSERT INTO curhatan_like (id_curhat, nip) SELECT $id, '$nip_user' WHERE NOT EXISTS (SELECT 1 FROM curhatan_like WHERE id_curhat = $id AND nip = '$nip_user')");
    if ($conn->affected_rows == 0) {
        $conn->query("DELETE FROM curhatan_like WHERE id_curhat = $id AND nip = '$nip_user'");
    }
    $res = $conn->query("SELECT COUNT(*) as cnt FROM curhatan_like WHERE id_curhat = $id")->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['count' => $res['cnt'] ?? 0]);
    exit();
}
?>