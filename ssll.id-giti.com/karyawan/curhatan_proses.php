<?php
session_start();
include '../conn.php';
$nip = $_SESSION['nip'] ?? '';

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action == 'post') {
        $isi = mysqli_real_escape_string($conn, $_POST['isi'] ?? '');
        $fn = null;
        $tp = null;
        if (!empty($_FILES['media']['name'])) {
            $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            $fn = time() . '_' . uniqid() . '.' . $ext;
            $tp = (in_array($ext, ['mp4', 'webm'])) ? 'video' : 'image';
            if (!is_dir("../uploads/story/")) mkdir("../uploads/story/", 0777, true);
            move_uploaded_file($_FILES['media']['tmp_name'], "../uploads/story/" . $fn);
        }
        $conn->query("INSERT INTO curhatan (nip, isi_status, media, tipe_media) VALUES ('$nip','$isi','$fn','$tp')");
        exit;
    }

    if ($action == 'like') {
        $id = mysqli_real_escape_string($conn, $_POST['id'] ?? '');
        $c = $conn->query("SELECT id FROM curhatan_like WHERE curhatan_id='$id' AND nip='$nip'");
        if ($c && $c->num_rows > 0) {
            $conn->query("DELETE FROM curhatan_like WHERE curhatan_id='$id' AND nip='$nip'");
        } else {
            $conn->query("INSERT INTO curhatan_like (curhatan_id, nip) VALUES ('$id','$nip')");
        }
        $res_tot = $conn->query("SELECT COUNT(*) as total FROM curhatan_like WHERE curhatan_id='$id'");
        $row_tot = $res_tot->fetch_assoc();
        echo json_encode(['total' => $row_tot['total']]);
        exit;
    }

    if ($action == 'fetch') {
        $limit = 5;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $off = ($page - 1) * $limit;
        $hmt = isset($_GET['hemat']) ? (int)$_GET['hemat'] : 0;
        $sch = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

        $sql = "SELECT c.*, k.nama, k.pas_photo,
                (SELECT COUNT(*) FROM curhatan_like WHERE curhatan_id=c.id) as total_l,
                (SELECT COUNT(*) FROM curhatan_like WHERE curhatan_id=c.id AND nip='$nip') as is_l
                FROM curhatan c 
                JOIN karyawan k ON c.nip=k.nip 
                WHERE c.isi_status LIKE '%$sch%'
                ORDER BY c.created_at DESC LIMIT $limit OFFSET $off";

        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($r = $res->fetch_assoc()) {
                $potong = strlen($r['isi_status']) > 70;
                $txt = $potong ? substr($r['isi_status'], 0, 70) . "..." : $r['isi_status'];
?>
                <div class="post-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="../uploads/<?php echo $r['pas_photo']; ?>" class="profile-img" onerror="this.src='../uploads/default.png'">
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($r['nama']); ?></div>
                            <div class="text-muted small"><?php echo date('d M, H:i', strtotime($r['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="mb-2" style="font-size: 14px;"><?php echo nl2br(htmlspecialchars($txt)); ?></div>
                    <?php if ($r['media']) : ?>
                        <?php if ($hmt == 1) : ?>
                            <div class="p-2 border rounded small bg-light"><i class="fas fa-link me-1"></i><a href="../uploads/story/<?php echo $r['media']; ?>" target="_blank">Lihat Media</a></div>
                        <?php else : ?>
                            <div class="media-box">
                                <?php if ($r['tipe_media'] == 'image') : ?>
                                    <img src="../uploads/story/<?php echo $r['media']; ?>">
                                <?php else : ?>
                                    <video controls><source src="../uploads/story/<?php echo $r['media']; ?>" type="video/mp4"></video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <hr class="my-2 opacity-25">
                    <button class="btn-like <?php echo ($r['is_l']) ? 'active' : ''; ?>" data-id="<?php echo $r['id']; ?>">
                        <i class="fas fa-heart me-1"></i><span class="l-count"><?php echo $r['total_l']; ?></span> Suka
                    </button>
                </div>
<?php
            }
        }
        exit;
    }
}