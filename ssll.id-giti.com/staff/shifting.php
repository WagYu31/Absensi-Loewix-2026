<?php
session_start();

// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}

include '../conn.php';
// include 'get-kar-login-data.php';

// Ambil semua data karyawan yang relevan dalam satu array
$karyawan_list = [];
$sql = "SELECT nik, pin_absen, nama, shifting, nip 
        FROM karyawan 
        WHERE pin_absen IS NOT NULL 
          AND pin_absen <> 0 
          AND status_karyawan = 'aktif' 
          AND deleted_at IS NULL
        ORDER BY nama ASC";
$result = $conn->query($sql);
if ($result) {
    $karyawan_list = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Shifting - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Pengaturan Shifting Karyawan</h1>
                <p>Ubah jadwal shifting default untuk setiap karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-body d-flex justify-content-start">
                        <a href="shift-req.php" class="btn btn-warning"><i class="fa-solid fa-user-check"></i> Request Shifting</a>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left title-icon"></i>Daftar Shifting Karyawan</h5>
                    </div>
                    <form method="post" action="update_shift.php">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" style="font-size: 0.9rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>NIK</th>
                                            <th>PIN</th>
                                            <th>Nama Karyawan</th>
                                            <th>Pilihan Shifting</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($karyawan_list)): ?>
                                            <tr><td colspan="4" class="text-center p-5 text-muted">Tidak ada data karyawan yang memenuhi kriteria.</td></tr>
                                        <?php endif; ?>

                                        <?php foreach ($karyawan_list as $karyawan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($karyawan['nik']); ?></td>
                                            <td><?php echo htmlspecialchars($karyawan['pin_absen']); ?></td>
                                            <td style="text-transform:capitalize;"><?php echo htmlspecialchars($karyawan['nama']); ?></td>
                                            <td>
                                                <input type="hidden" name="nik[]" value="<?php echo htmlspecialchars($karyawan['nik']); ?>">
                                                <input type="hidden" name="nip[]" value="<?php echo htmlspecialchars($karyawan['nip']); ?>">
                                                
                                                <div class="d-flex flex-wrap" style="gap: 1rem;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" value="P" 
                                                               name="shift_<?php echo htmlspecialchars($karyawan['nik']); ?>" 
                                                               id="shiftP_<?php echo htmlspecialchars($karyawan['nik']); ?>"
                                                               <?php if ($karyawan['shifting'] === 'P') echo 'checked'; ?>>
                                                        <label class="form-check-label" for="shiftP_<?php echo htmlspecialchars($karyawan['nik']); ?>">Pagi</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" value="M"
                                                               name="shift_<?php echo htmlspecialchars($karyawan['nik']); ?>" 
                                                               id="shiftM_<?php echo htmlspecialchars($karyawan['nik']); ?>"
                                                               <?php if ($karyawan['shifting'] === 'M') echo 'checked'; ?>>
                                                        <label class="form-check-label" for="shiftM_<?php echo htmlspecialchars($karyawan['nik']); ?>">Tengah</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" value="N"
                                                               name="shift_<?php echo htmlspecialchars($karyawan['nik']); ?>" 
                                                               id="shiftS_<?php echo htmlspecialchars($karyawan['nik']); ?>"
                                                               <?php if ($karyawan['shifting'] === 'N') echo 'checked'; ?>>
                                                        <label class="form-check-label" for="shiftS_<?php echo htmlspecialchars($karyawan['nik']); ?>">Siang (09.00)</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" value="S"
                                                               name="shift_<?php echo htmlspecialchars($karyawan['nik']); ?>" 
                                                               id="shiftS_<?php echo htmlspecialchars($karyawan['nik']); ?>"
                                                               <?php if ($karyawan['shifting'] === 'S') echo 'checked'; ?>>
                                                        <label class="form-check-label" for="shiftS_<?php echo htmlspecialchars($karyawan['nik']); ?>">Siang</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" value="T"
                                                               name="shift_<?php echo htmlspecialchars($karyawan['nik']); ?>" 
                                                               id="shiftT_<?php echo htmlspecialchars($karyawan['nik']); ?>"
                                                               <?php if ($karyawan['shifting'] === 'T') echo 'checked'; ?>>
                                                        <label class="form-check-label" for="shiftT_<?php echo htmlspecialchars($karyawan['nik']); ?>">Toko</label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i>Update Shifting</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>