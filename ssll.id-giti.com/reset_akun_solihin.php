<?php
// Standalone Emergency Reset Tool for Solihin (NIP: 13047 / NIK: 578)
include 'conn.php';

$target_nip = '13047';
$target_nik = '578';

// Query employee Solihin
$res_kar = $conn->query("SELECT * FROM karyawan WHERE nip = '$target_nip' OR nik = '$target_nik' OR LOWER(nama) LIKE '%solihin%' LIMIT 1");
$solihin = ($res_kar && $res_kar->num_rows > 0) ? $res_kar->fetch_assoc() : null;

$nip = $solihin ? $solihin['nip'] : $target_nip;
$nik = $solihin ? $solihin['nik'] : $target_nik;
$nama = $solihin ? $solihin['nama'] : 'Solihin';

// Query user account
$res_user = $conn->query("SELECT * FROM users WHERE nip = '$nip' LIMIT 1");
$user_account = ($res_user && $res_user->num_rows > 0) ? $res_user->fetch_assoc() : null;

$success_msg = '';
$error_msg = '';

// Handle CLI execution
if (php_sapi_name() === 'cli') {
    $new_user = 'solihin';
    $new_pass = 'solihin123';
    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

    if ($user_account) {
        $conn->query("UPDATE users SET username = '$new_user', password = '$hashed', role = 'karyawan' WHERE nip = '$nip'");
        echo "\n=== RESET AKUN SOLIHIN BERHASIL ===\n";
        echo "Nama     : $nama\n";
        echo "NIP      : $nip\n";
        echo "NIK      : $nik\n";
        echo "Username : $new_user\n";
        echo "Password : $new_pass\n";
        echo "Role     : karyawan\n";
        echo "Status   : Akun berhasil diupdate!\n\n";
    } else {
        $conn->query("INSERT INTO users (nip, username, password, role) VALUES ('$nip', '$new_user', '$hashed', 'karyawan')");
        echo "\n=== PEMBUATAN AKUN SOLIHIN BERHASIL ===\n";
        echo "Nama     : $nama\n";
        echo "NIP      : $nip\n";
        echo "NIK      : $nik\n";
        echo "Username : $new_user\n";
        echo "Password : $new_pass\n";
        echo "Role     : karyawan\n";
        echo "Status   : Akun baru berhasil dibuat!\n\n";
    }
    exit();
}

// Handle Web POST execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_user = trim($_POST['username'] ?? '');
    $posted_pass = trim($_POST['password'] ?? '');

    if (empty($posted_user) || empty($posted_pass)) {
        $error_msg = "Username dan Password baru tidak boleh kosong!";
    } else {
        // Check if username is used by someone else
        $chk = $conn->query("SELECT nip FROM users WHERE username = '$posted_user' AND nip != '$nip'");
        if ($chk && $chk->num_rows > 0) {
            $error_msg = "Username '$posted_user' sudah digunakan oleh orang lain. Gunakan username lain!";
        } else {
            $hashed = password_hash($posted_pass, PASSWORD_DEFAULT);
            if ($user_account) {
                $conn->query("UPDATE users SET username = '$posted_user', password = '$hashed', role = 'karyawan' WHERE nip = '$nip'");
                $success_msg = "Sukses! Akun <b>$nama</b> telah diperbarui.<br><b>Username:</b> <span class='badge bg-primary fs-6'>$posted_user</span><br><b>Password Baru:</b> <span class='badge bg-success fs-6'>$posted_pass</span>";
            } else {
                $conn->query("INSERT INTO users (nip, username, password, role) VALUES ('$nip', '$posted_user', '$hashed', 'karyawan')");
                $success_msg = "Sukses! Akun login baru untuk <b>$nama</b> berhasil dibuat.<br><b>Username:</b> <span class='badge bg-primary fs-6'>$posted_user</span><br><b>Password Baru:</b> <span class='badge bg-success fs-6'>$posted_pass</span>";
            }
            // Refresh user account info
            $res_user = $conn->query("SELECT * FROM users WHERE nip = '$nip' LIMIT 1");
            $user_account = ($res_user && $res_user->num_rows > 0) ? $res_user->fetch_assoc() : null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Akun & Password Solihin - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #f8fafc;
        }
        .card-reset {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
        }
        .card-header-gradient {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            padding: 24px;
            text-align: center;
        }
        .info-pill {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .form-control-custom {
            background: #0f172a;
            border: 1.5px solid #334155;
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
        }
        .form-control-custom:focus {
            background: #0f172a;
            border-color: #3b82f6;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }
        .btn-preset {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #93c5fd;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-preset:hover {
            background: #3b82f6;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="card-reset">
    <div class="card-header-gradient">
        <div class="mb-2"><i class="fa-solid fa-key text-warning fa-2x"></i></div>
        <h4 class="fw-bold mb-1">Reset Akun Login Karyawan</h4>
        <p class="text-white-50 small mb-0">Atur ulang Username & Password untuk <strong><?php echo htmlspecialchars($nama); ?></strong></p>
    </div>

    <div class="p-4">
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
                <i class="fa-solid fa-circle-check me-2 fs-5"></i>
                <div><?php echo $success_msg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                <div><?php echo $error_msg; ?></div>
            </div>
        <?php endif; ?>

        <!-- Info Karyawan -->
        <div class="info-pill d-flex justify-content-between align-items-center">
            <span class="text-muted small">Nama Karyawan</span>
            <span class="fw-bold text-white"><?php echo htmlspecialchars($nama); ?></span>
        </div>
        <div class="info-pill d-flex justify-content-between align-items-center">
            <span class="text-muted small">NIK / NIP</span>
            <span class="fw-bold text-white"><?php echo htmlspecialchars($nik); ?> / <?php echo htmlspecialchars($nip); ?></span>
        </div>
        <div class="info-pill d-flex justify-content-between align-items-center">
            <span class="text-muted small">Status Akun Login</span>
            <?php if ($user_account): ?>
                <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1"><i class="fa-solid fa-check me-1"></i>Terdaftar (Username: <?php echo htmlspecialchars($user_account['username']); ?>)</span>
            <?php else: ?>
                <span class="badge bg-warning-subtle text-warning border border-warning fw-bold px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Belum Ada Akun</span>
            <?php endif; ?>
        </div>

        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label class="form-label small fw-bold text-slate-300">USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="usernameInput" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user_account['username'] ?? 'solihin'); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-bold text-slate-300 mb-0">PASSWORD BARU</label>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn-preset" onclick="setPass('solihin123')">solihin123</button>
                        <button type="button" class="btn-preset" onclick="setPass('12345678')">12345678</button>
                    </div>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-lock"></i></span>
                    <input type="text" name="password" id="passwordInput" class="form-control form-control-custom" placeholder="Masukkan password baru" value="solihin123" required>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Password akan otomatis dienkripsi secara aman di database.</small>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow mt-2">
                <i class="fa-solid fa-floppy-disk me-2"></i>SIMPAN & RESET SEKARANG
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none text-muted small fw-bold"><i class="fa-solid fa-arrow-left me-1"></i>Kembali ke Halaman Login</a>
        </div>
    </div>
</div>

<script>
function setPass(val) {
    document.getElementById('passwordInput').value = val;
}
</script>
</body>
</html>
