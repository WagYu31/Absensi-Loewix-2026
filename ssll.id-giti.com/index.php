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
    <title>Gravitti Tech - Portal Absensi Karyawan</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Pro icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 & Select2 Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --indigo: #6366f1;
            --emerald: #10b981;
            --slate-dark: #0f172a;
            --slate-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.88);
            --glass-border: rgba(255, 255, 255, 0.7);
            --card-radius: 28px;
            --input-radius: 16px;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #0b0f19;
            color: #0f172a;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Ambient Light Blobs (Taste Skill Glow) */
        .ambient-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.55;
            z-index: 0;
            pointer-events: none;
            animation: floatBlob 18s ease-in-out infinite alternate;
        }

        .blob-1 {
            top: -120px;
            left: -120px;
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, #3b82f6 0%, #6366f1 100%);
        }

        .blob-2 {
            bottom: -150px;
            right: -120px;
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, #8b5cf6 0%, #3b82f6 100%);
            animation-delay: -6s;
        }

        .blob-3 {
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.4) 0%, rgba(59, 130, 246, 0.2) 100%);
            animation-delay: -12s;
        }

        @keyframes floatBlob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }
            50% {
                transform: translate(30px, 40px) scale(1.08);
            }
            100% {
                transform: translate(-20px, 20px) scale(0.95);
            }
        }

        .login-wrapper {
            width: 100%;
            max-width: 430px;
            position: relative;
            z-index: 2;
        }

        /* Glassmorphic Luxury Card */
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(28px) saturate(190%);
            -webkit-backdrop-filter: blur(28px) saturate(190%);
            border-radius: var(--card-radius);
            border: 1px solid var(--glass-border);
            box-shadow: 
                0 30px 60px -15px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            padding: 2.25rem 2rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #059669;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 0.85rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Brand Logo Container */
        .brand-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .brand-icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            margin-bottom: 0.75rem;
            box-shadow: 0 12px 25px -6px rgba(15, 23, 42, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            padding: 10px;
        }

        .brand-icon-wrapper::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 22px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(255,255,255,0.4), rgba(255,255,255,0));
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .brand-title {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.6px;
            margin-bottom: 0.2rem;
        }

        .brand-subtitle {
            font-size: 0.875rem;
            color: var(--slate-muted);
            font-weight: 500;
        }

        /* Segmented Control iOS Style */
        .auth-tabs {
            background: rgba(226, 232, 240, 0.6);
            padding: 4px;
            border-radius: 18px;
            display: flex;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .tab-btn {
            flex: 1;
            padding: 11px 16px;
            border: none;
            background: transparent;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--slate-muted);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            z-index: 2;
        }

        .tab-btn.active {
            color: #2563eb;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        /* Form Inputs Modern Touch */
        .form-label-custom {
            font-size: 0.825rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            display: block;
            letter-spacing: 0.2px;
        }

        .input-icon-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            color: #94a3b8;
            font-size: 1.05rem;
            pointer-events: none;
            transition: all 0.2s ease;
        }

        .custom-input {
            width: 100%;
            height: 54px;
            padding-left: 50px;
            padding-right: 48px;
            font-size: 0.95rem;
            font-family: inherit;
            font-weight: 500;
            color: #0f172a;
            background-color: rgba(248, 250, 252, 0.9);
            border: 1.5px solid #e2e8f0;
            border-radius: var(--input-radius);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .custom-input:focus {
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), 0 4px 12px rgba(59, 130, 246, 0.08);
            outline: none;
            transform: translateY(-1px);
        }

        .custom-input:focus ~ .input-icon,
        .input-icon-group:focus-within .input-icon {
            color: #2563eb;
        }

        .btn-toggle-pw {
            position: absolute;
            right: 12px;
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 8px 10px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .btn-toggle-pw:hover {
            color: var(--slate-dark);
            background: rgba(241, 245, 249, 0.8);
        }

        /* Buttons Taste Shimmer Effect */
        .btn-shimmer {
            position: relative;
            overflow: hidden;
            border: none;
            height: 54px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: var(--input-radius);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            letter-spacing: 0.2px;
        }

        .btn-shimmer::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: left 0.75s ease;
        }

        .btn-shimmer:hover::before {
            left: 100%;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        }

        .btn-primary-gradient:hover, .btn-primary-gradient:focus {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px rgba(37, 99, 235, 0.5);
            color: #ffffff;
        }

        .btn-primary-gradient:active {
            transform: translateY(0) scale(0.98);
        }

        .btn-success-gradient {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
        }

        .btn-success-gradient:hover, .btn-success-gradient:focus {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px rgba(16, 185, 129, 0.5);
            color: #ffffff;
        }

        .btn-success-gradient:active {
            transform: translateY(0) scale(0.98);
        }

        /* Links */
        .small-link {
            font-size: 0.825rem;
            font-weight: 700;
            color: #2563eb;
            transition: all 0.2s ease;
        }

        .small-link:hover {
            color: #1d4ed8;
            text-decoration: underline !important;
        }

        /* Select2 Customization */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 54px;
            border-radius: var(--input-radius) !important;
            background-color: rgba(248, 250, 252, 0.9) !important;
            border: 1.5px solid #e2e8f0 !important;
            padding-left: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
            background-color: #ffffff !important;
        }

        .select2-dropdown {
            border-radius: 18px !important;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18) !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            overflow: hidden;
            backdrop-filter: blur(16px);
        }

        .select2-search__field {
            border-radius: 12px !important;
            padding: 10px 14px !important;
        }

        /* Footer Security */
        .auth-footer {
            margin-top: 1.85rem;
            text-align: center;
            font-size: 0.78rem;
            color: var(--slate-muted);
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        /* Mobile Optimization */
        @media (max-width: 576px) {
            body {
                padding: 14px;
                align-items: flex-start;
                padding-top: 28px;
                padding-bottom: 28px;
            }

            .login-card {
                padding: 1.85rem 1.35rem;
                border-radius: 24px;
            }

            .brand-title {
                font-size: 1.55rem;
            }

            .custom-input, .btn-shimmer {
                height: 50px;
            }
        }

        /* Fade Animation for Forms */
        .form-fade-in {
            animation: fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <!-- Ambient Glowing Orbs -->
    <div class="ambient-blob blob-1"></div>
    <div class="ambient-blob blob-2"></div>
    <div class="ambient-blob blob-3"></div>

    <div class="login-wrapper">
        <div class="login-card">
            
            <!-- Brand Header -->
            <div class="brand-header">
                <div class="status-badge">
                    <span class="status-dot"></span> Gravitti Cloud
                </div>
                <br>
                <div class="brand-icon-wrapper">
                    <img src="img/giti.png" alt="Gravitti Tech" style="max-height: 48px; width: auto; object-fit: contain;" onerror="this.remove(); document.getElementById('fallback-icon').style.display='inline-block';">
                    <i class="fa-solid fa-layer-group" id="fallback-icon" style="display: none; font-size: 30px;"></i>
                </div>
                <h1 class="brand-title">Gravitti Tech</h1>
                <p class="brand-subtitle">Portal Absensi & Manajemen Karyawan</p>
            </div>

            <!-- Segmented Control Tabs -->
            <div class="auth-tabs mb-4">
                <button type="button" class="tab-btn active" id="btn-tab-signin" onclick="switchTab('signin')">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
                </button>
                <button type="button" class="tab-btn" id="btn-tab-signup" onclick="switchTab('signup')">
                    <i class="fa-solid fa-user-plus me-2"></i>Buat Akun
                </button>
            </div>

            <!-- Sign In Form -->
            <div id="sign-in-form" class="form-fade-in">
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label-custom">USERNAME</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" class="custom-input" name="username" id="username" placeholder="Masukkan username Anda" autocomplete="username" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label-custom mb-0">PASSWORD</label>
                            <a href="lupa.php" class="small-link text-decoration-none">Lupa password?</a>
                        </div>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" class="custom-input" name="password" id="password" placeholder="Masukkan password Anda" autocomplete="current-password" required>
                            <button type="button" class="btn-toggle-pw" onclick="togglePasswordVisibility('password', this)" title="Lihat password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-shimmer btn-primary-gradient w-100">
                        Sign In <i class="fa-solid fa-arrow-right-long ms-2"></i>
                    </button>
                </form>
            </div>

            <!-- Sign Up Form -->
            <div id="sign-up-form" class="d-none">
                <form action="signup.php" method="post">
                    <div class="mb-3">
                        <label for="nip" class="form-label-custom">NAMA KARYAWAN</label>
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

                    <div class="mb-3">
                        <label for="new-username" class="form-label-custom">BUAT USERNAME BARU</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-at input-icon"></i>
                            <input type="text" class="custom-input" id="new-username" name="new-username" placeholder="Username baru" autocomplete="username" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="new-password" class="form-label-custom">BUAT PASSWORD BARU</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-key input-icon"></i>
                            <input type="password" class="custom-input" id="new-password" name="new-password" placeholder="Password baru" autocomplete="new-password" required>
                            <button type="button" class="btn-toggle-pw" onclick="togglePasswordVisibility('new-password', this)" title="Lihat password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-shimmer btn-success-gradient w-100" name="submit">
                        Daftar Akun Baru <i class="fa-solid fa-user-check ms-2"></i>
                    </button>
                </form>
            </div>

            <!-- Footer SSL -->
            <div class="auth-footer">
                <i class="fa-solid fa-shield-halved text-primary me-1"></i> End-to-End SSL Encrypted Security
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function switchTab(type) {
            if (type === 'signin') {
                $('#btn-tab-signin').addClass('active');
                $('#btn-tab-signup').removeClass('active');
                $('#sign-up-form').addClass('d-none').removeClass('form-fade-in');
                $('#sign-in-form').removeClass('d-none').addClass('form-fade-in');
            } else {
                $('#btn-tab-signup').addClass('active');
                $('#btn-tab-signin').removeClass('active');
                $('#sign-in-form').addClass('d-none').removeClass('form-fade-in');
                $('#sign-up-form').removeClass('d-none').addClass('form-fade-in');
            }
        }

        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        $(document).ready(function() {
            $('#nip').select2({
                theme: 'bootstrap-5',
                placeholder: 'Cari & pilih nama Anda',
                dropdownParent: $('#sign-up-form')
            });
        });
    </script>
</body>
</html>