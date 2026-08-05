<?php
session_start();
if (!isset($_SESSION['nip'])) { header('Location: ../index.php'); exit(); }
include '../conn.php';
include 'get-kar-login-data.php';
$nip_login = $_SESSION['nip'];
$u = $conn->query("SELECT nama, pas_photo FROM karyawan WHERE nip = '$nip_login'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curhatan Karyawan</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        body { background-color: #f0f2f5; }
        .header-blue { background: #0056b3; color: white; padding: 40px 0 80px 0; }
        .content-container { margin-top: -60px; max-width: 700px; margin-left: auto; margin-right: auto; padding-bottom: 50px; }
        .main-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .profile-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; }
        .input-trigger { background: #f0f2f5; border-radius: 25px; padding: 10px 20px; border: none; width: 100%; text-align: left; color: #65676b; }
        .post-card { background: white; border-radius: 12px; padding: 15px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .media-box { width: 100%; border-radius: 10px; margin-top: 10px; overflow: hidden; background: #000; }
        .media-box img, .media-box video { width: 100%; display: block; max-height: 500px; object-fit: contain; }
        .btn-like { border: none; background: none; color: #65676b; font-weight: 600; padding: 0; }
        .btn-like.active { color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div class="main-content-wrapper p-0">
        <div class="header-blue">
            <div class="container-fluid px-lg-5">
                <h1 class="fw-bold">Curhatan Karyawan</h1>
                <p>Bagikan momen, pengalaman, atau sekadar menyapa rekan kerja.</p>
            </div>
        </div>
        <div class="content-container container-fluid">
            <div class="main-card">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="../uploads/<?php echo $u['pas_photo']; ?>" class="profile-img" onerror="this.src='../uploads/default.png'">
                    <button class="input-trigger" data-bs-toggle="modal" data-bs-target="#postModal">Apa yang Anda pikirkan, <?php echo explode(' ', $u['nama'])[0]; ?>?</button>
                </div>
                <hr>
                <div class="row align-items-center">
                    <div class="col-5">
                        <div class="form-switch d-flex align-items-center">
                            <input class="form-check-input me-2" type="checkbox" id="modeHemat">
                            <label class="small mb-0" for="modeHemat">Mode Hemat Data</label>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="keyword" class="form-control border-start-0" placeholder="Cari curhatan...">
                        </div>
                    </div>
                </div>
            </div>
            <div id="feed"></div>
            <div id="loading" class="text-center py-4"><div class="spinner-border text-primary"></div></div>
        </div>
    </div>

    <div class="modal fade" id="postModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <form id="formPost" enctype="multipart/form-data">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Buat Postingan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <textarea name="isi" class="form-control border-0 bg-light mb-3" rows="4" placeholder="Tuliskan sesuatu..." required maxlength="700"></textarea>
                        <input type="file" name="media" class="form-control" accept="image/*,video/*">
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-primary w-100">Posting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let p = 1, fetchLog = false, hemat = 0, search = "";
        function load(reset = false) {
            if(fetchLog) return;
            fetchLog = true;
            if(reset) { p = 1; $("#feed").empty(); $("#loading").show(); }
            $.ajax({
                url: "curhatan_proses.php",
                type: "GET",
                data: { action: "fetch", page: p, hemat: hemat, search: search },
                success: function(html) {
                    $("#loading").hide();
                    if(html.trim() == "") { 
                        if(p == 1) $("#feed").html('<div class="text-center p-5 text-muted">Belum ada kiriman.</div>');
                        fetchLog = true; // Stop loading if empty
                    } else { 
                        $("#feed").append(html); 
                        p++; 
                        fetchLog = false;
                    }
                },
                error: function(xhr) { 
                    $("#loading").hide(); 
                    console.error("Error detail: ", xhr.responseText);
                }
            });
        }
        $("#modeHemat").change(function() { hemat = $(this).is(":checked") ? 1 : 0; load(true); });
        $("#keyword").keyup(function() { search = $(this).val(); load(true); });
        $("#formPost").submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: "curhatan_proses.php?action=post",
                type: "POST",
                data: new FormData(this),
                processData: false, contentType: false,
                success: function() { location.reload(); }
            });
        });
        $(document).on("click", ".btn-like", function() {
            let id = $(this).data("id"), t = $(this);
            $.post("curhatan_proses.php?action=like", {id: id}, function(res) {
                let r = JSON.parse(res);
                t.find(".l-count").text(r.total);
                t.toggleClass("active");
            });
        });
        $(window).scroll(function() { if($(window).scrollTop() + $(window).height() >= $(document).height() - 200) load(); });
        $(document).ready(function() { load(); });
    </script>
</body>
</html>