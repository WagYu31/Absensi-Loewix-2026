<?php
session_start();
include 'conn.php';

// --- OPTIMALISASI: Ambil semua data yang dibutuhkan di awal ---

// 1. Ambil semua NIP yang sudah terdaftar di tabel 'users'
$stmt_users = $conn->prepare("SELECT nip FROM users");
$stmt_users->execute();
$result_users = $stmt_users->get_result();
$existing_user_nips = array_column($result_users->fetch_all(MYSQLI_ASSOC), 'nip');
$stmt_users->close();

// 2. Ambil semua data karyawan
$stmt_karyawan = $conn->prepare("SELECT nip, nama, status_karyawan FROM karyawan ORDER BY nama ASC");
$stmt_karyawan->execute();
$result_karyawan = $stmt_karyawan->get_result();
$karyawanData = $result_karyawan->fetch_all(MYSQLI_ASSOC);
$stmt_karyawan->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login - Gravitti Tech</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 & Select2 Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        :root {
            --primary: #1877f2;
            --primary-hover: #166fe5;
            --success: #10b981;
            --bg-page: #f0f2f5;
            --text-main: #1c2b36;
            --text-sub: #65676b;
            --radius-card: 16px;
            --radius-input: 10px;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 16px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: #ffffff;
            border-radius: var(--radius-card);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border: 1px solid #e4e6eb;
            padding: 2.25rem 1.75rem;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .brand-logo-img {
            height: 48px;
            width: auto;
            object-fit: contain;
            margin-bottom: 0.75rem;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1c2b36;
            margin: 0 0 0.25rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .brand-subtitle {
            font-size: 0.875rem;
            color: var(--text-sub);
            margin: 0;
        }

        .toggle-nav {
            background: #e4e6eb;
            padding: 3px;
            border-radius: 10px;
            display: flex;
            margin-bottom: 1.5rem;
        }

        .toggle-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-sub);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .toggle-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group-custom {
            margin-bottom: 1rem;
        }

        .form-label-custom {
            font-size: 0.85rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.35rem;
            display: block;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #9ca3af;
            font-size: 1rem;
            pointer-events: none;
        }

        .form-control-custom {
            width: 100%;
            height: 46px;
            padding-left: 42px;
            padding-right: 42px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #1f2937;
            background-color: #ffffff;
            border: 1.5px solid #d1d5db;
            border-radius: var(--radius-input);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control-custom:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.15);
            outline: none;
        }

        .btn-pw-eye {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: #9ca3af;
            padding: 6px;
            cursor: pointer;
        }

        .btn-pw-eye:hover {
            color: #4b5563;
        }

        .btn-submit-primary {
            width: 100%;
            height: 46px;
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-input);
            font-size: 0.95rem;
            font-weight: 700;
            transition: background-color 0.15s ease;
            margin-top: 0.5rem;
        }

        .btn-submit-primary:hover, .btn-submit-primary:focus {
            background-color: var(--primary-hover);
            color: #ffffff;
        }

        .btn-submit-success {
            width: 100%;
            height: 46px;
            background-color: var(--success);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-input);
            font-size: 0.95rem;
            font-weight: 700;
            transition: background-color 0.15s ease;
            margin-top: 0.5rem;
        }

        .btn-submit-success:hover {
            background-color: #059669;
            color: #ffffff;
        }

        .forgot-link {
            font-size: 0.85rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Select2 Customization */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 46px;
            border-radius: var(--radius-input) !important;
            border: 1.5px solid #d1d5db !important;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.15) !important;
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }
            .login-card {
                padding: 1.75rem 1.25rem;
                border-radius: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            
            <!-- Brand Header -->
            <div class="brand-header">
                <img src="img/giti.png" alt="Gravitti Tech" class="brand-logo-img" onerror="this.style.display='none';">
                <h1 class="brand-title">Gravitti Tech</h1>
                <p class="brand-subtitle">Silakan masuk untuk melanjutkan</p>
            </div>

            <!-- Tab Switcher -->
            <div class="toggle-nav">
                <button type="button" class="toggle-btn active" id="tab-signin" onclick="switchForm('signin')">Masuk</button>
                <button type="button" class="toggle-btn" id="tab-signup" onclick="switchForm('signup')">Buat Akun Baru</button>
            </div>

            <!-- Sign In Form -->
            <div id="signin-form-box">
                <form action="login.php" method="post">
                    <div class="form-group-custom">
                        <label for="username" class="form-label-custom">Username</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" class="form-control-custom" name="username" id="username" placeholder="Masukkan username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label-custom mb-0">Password</label>
                            <a href="lupa.php" class="forgot-link">Lupa password?</a>
                        </div>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" class="form-control-custom" name="password" id="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="btn-pw-eye" onclick="togglePassword('password', this)">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-primary">Sign In</button>
                </form>
            </div>

            <!-- Sign Up Form -->
            <div id="signup-form-box" class="d-none">
                <form action="signup.php" method="post">
                    <div class="form-group-custom">
                        <label for="nip" class="form-label-custom">Nama Karyawan</label>
                        <select class="form-select custom-select2" id="nip" name="nip" required>
                            <option value="" disabled selected>-- Pilih Nama Anda --</option>
                            <?php
                            foreach ($karyawanData as $data) {
                                if ($data['status_karyawan'] === 'aktif' && $data['nip'] !== '001' && !in_array($data['nip'], $existing_user_nips)) {
                                    echo "<option value='" . htmlspecialchars($data['nip']) . "'>" . htmlspecialchars($data['nama']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label for="new-username" class="form-label-custom">Buat Username Baru</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-at input-icon"></i>
                            <input type="text" class="form-control-custom" id="new-username" name="new-username" placeholder="Buat username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <label for="new-password" class="form-label-custom">Buat Password Baru</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-key input-icon"></i>
                            <input type="password" class="form-control-custom" id="new-password" name="new-password" placeholder="Buat password baru" required autocomplete="new-password">
                            <button type="button" class="btn-pw-eye" onclick="togglePassword('new-password', this)">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-success" name="submit">Daftar Akun Baru</button>
                </form>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function switchForm(type) {
            if (type === 'signin') {
                $('#tab-signin').addClass('active');
                $('#tab-signup').removeClass('active');
                $('#signup-form-box').addClass('d-none');
                $('#signin-form-box').removeClass('d-none');
            } else {
                $('#tab-signup').addClass('active');
                $('#tab-signin').removeClass('active');
                $('#signin-form-box').addClass('d-none');
                $('#signup-form-box').removeClass('d-none');
            }
        }

        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        }

        $(document).ready(function() {
            $('#nip').select2({
                theme: 'bootstrap-5',
                placeholder: 'Cari & pilih nama Anda',
                dropdownParent: $('#signup-form-box')
            });
        });
    </script>
</body>
</html>