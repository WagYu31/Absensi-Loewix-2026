<?php
session_start();

// Jika pengguna sudah login, langsung arahkan ke Dashboard
if (isset($_SESSION['nip']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'karyawan') {
        header("Location: karyawan/home.php");
        exit();
    } else {
        header("Location: staff/grafik-kinerja.php");
        exit();
    }
}

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
    <title>Gravitti Tech - Login 3D Mobile</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- PWA Web App Manifest & Icons -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Absensi Loewix">
    <link rel="apple-touch-icon" href="/img/logo.png">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 & Select2 Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        :root {
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
            --success-3d: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);
            --bg-page: #f1f5f9;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --card-radius: 28px;
            --input-radius: 14px;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #e2e8f0;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(241, 245, 249, 0.8) 0px, transparent 100%);
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 16px;
            perspective: 1000px;
            overflow-x: hidden;
            touch-action: manipulation;
        }

        /* Ambient 3D Floating Orbs */
        .orb-3d {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(60px);
            opacity: 0.6;
            animation: float3d 16s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, #3b82f6 0%, rgba(37, 99, 235, 0) 70%);
            top: -100px;
            left: -100px;
        }

        .orb-2 {
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
            bottom: -120px;
            right: -100px;
            animation-delay: -8s;
        }

        @keyframes float3d {
            0% { transform: translateY(0px) rotate(0deg) scale(1); }
            50% { transform: translateY(-25px) rotate(180deg) scale(1.05); }
            100% { transform: translateY(20px) rotate(360deg) scale(0.95); }
        }

        .scene-container {
            width: 100%;
            max-width: 410px;
            perspective: 1000px;
            position: relative;
            z-index: 2;
        }

        /* 3D Glassmorphic Card */
        .card-3d {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: var(--card-radius);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow: 
                0 30px 60px -12px rgba(15, 23, 42, 0.15),
                0 18px 36px -18px rgba(15, 23, 42, 0.12),
                inset 0 1px 1px rgba(255, 255, 255, 0.9),
                inset 0 -2px 4px rgba(0, 0, 0, 0.03);
            padding: 2.25rem 1.85rem;
            transform-style: preserve-3d;
            will-change: transform;
            transition: transform 0.15s ease-out, box-shadow 0.3s ease;
        }

        /* 3D Layer Elevation */
        .layer-depth-1 { transform: translateZ(15px); }
        .layer-depth-2 { transform: translateZ(28px); }
        .layer-depth-3 { transform: translateZ(40px); }

        /* 3D Logo Frame */
        .brand-header {
            text-align: center;
            margin-bottom: 1.65rem;
            transform-style: preserve-3d;
        }

        .logo-3d-frame {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 22px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 
                0 12px 24px -6px rgba(15, 23, 42, 0.1),
                0 4px 8px -2px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 1);
            margin-bottom: 0.85rem;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: translateZ(35px);
        }

        .brand-logo-img {
            height: 48px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.06));
        }

        .brand-subtitle {
            font-size: 0.875rem;
            color: var(--text-sub);
            font-weight: 500;
            margin: 0;
            transform: translateZ(20px);
        }

        /* 3D Nav Tabs */
        .toggle-nav-3d {
            background: rgba(226, 232, 240, 0.7);
            padding: 4px;
            border-radius: 16px;
            display: flex;
            margin-bottom: 1.5rem;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
            transform: translateZ(25px);
        }

        .toggle-btn-3d {
            flex: 1;
            padding: 10px 14px;
            border: none;
            background: transparent;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-sub);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
        }

        .toggle-btn-3d.active {
            background: #ffffff;
            color: #2563eb;
            box-shadow: 
                0 6px 16px rgba(37, 99, 235, 0.15),
                0 2px 4px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        /* 3D Form Inputs */
        .form-group-custom {
            margin-bottom: 1.1rem;
            transform: translateZ(20px);
        }

        .form-label-custom {
            font-size: 0.825rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.35rem;
            display: block;
            letter-spacing: 0.2px;
        }

        .input-wrapper-3d {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: #94a3b8;
            font-size: 1.05rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-control-3d {
            width: 100%;
            height: 50px;
            padding-left: 46px;
            padding-right: 44px;
            font-size: 0.95rem;
            font-family: inherit;
            font-weight: 500;
            color: #0f172a;
            background-color: rgba(255, 255, 255, 0.9);
            border: 1.5px solid #cbd5e1;
            border-radius: var(--input-radius);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.03);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-control-3d:focus {
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 
                0 0 0 4px rgba(59, 130, 246, 0.15),
                0 8px 16px -4px rgba(59, 130, 246, 0.12);
            outline: none;
            transform: translateY(-2px);
        }

        .form-control-3d:focus ~ .input-icon,
        .input-wrapper-3d:focus-within .input-icon {
            color: #2563eb;
        }

        .btn-pw-eye {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: #94a3b8;
            padding: 8px;
            cursor: pointer;
            border-radius: 8px;
            transition: color 0.2s ease;
        }

        .btn-pw-eye:hover {
            color: #0f172a;
        }

        /* 3D Tactile Buttons */
        .btn-3d-primary {
            width: 100%;
            height: 52px;
            background: var(--primary-3d);
            color: #ffffff;
            border: none;
            border-radius: var(--input-radius);
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            box-shadow: 
                0 8px 20px rgba(37, 99, 235, 0.35),
                0 4px 0 #1d4ed8;
            transition: all 0.15s ease-out;
            margin-top: 0.5rem;
            transform: translateZ(30px);
            cursor: pointer;
        }

        .btn-3d-primary:hover, .btn-3d-primary:focus {
            transform: translateZ(35px) translateY(-2px);
            box-shadow: 
                0 12px 25px rgba(37, 99, 235, 0.45),
                0 6px 0 #1e40af;
            color: #ffffff;
        }

        .btn-3d-primary:active {
            transform: translateZ(20px) translateY(2px);
            box-shadow: 
                0 4px 10px rgba(37, 99, 235, 0.3),
                0 1px 0 #1e40af;
        }

        .btn-3d-success {
            width: 100%;
            height: 52px;
            background: var(--success-3d);
            color: #ffffff;
            border: none;
            border-radius: var(--input-radius);
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            box-shadow: 
                0 8px 20px rgba(16, 185, 129, 0.35),
                0 4px 0 #047857;
            transition: all 0.15s ease-out;
            margin-top: 0.5rem;
            transform: translateZ(30px);
            cursor: pointer;
        }

        .btn-3d-success:hover, .btn-3d-success:focus {
            transform: translateZ(35px) translateY(-2px);
            box-shadow: 
                0 12px 25px rgba(16, 185, 129, 0.45),
                0 6px 0 #065f46;
            color: #ffffff;
        }

        .btn-3d-success:active {
            transform: translateZ(20px) translateY(2px);
            box-shadow: 
                0 4px 10px rgba(16, 185, 129, 0.3),
                0 1px 0 #065f46;
        }

        .forgot-link {
            font-size: 0.825rem;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* Select2 Customization */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 50px;
            border-radius: var(--input-radius) !important;
            border: 1.5px solid #cbd5e1 !important;
            background-color: rgba(255, 255, 255, 0.9) !important;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
        }

        .select2-dropdown {
            border-radius: 16px !important;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15) !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            overflow: hidden;
        }

        @media (max-width: 480px) {
            body {
                padding: 14px;
            }
            .card-3d {
                padding: 1.85rem 1.35rem;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>

    <!-- Ambient 3D Orbs -->
    <div class="orb-3d orb-1"></div>
    <div class="orb-3d orb-2"></div>

    <div class="scene-container">
        <div class="card-3d" id="card3d">
            
            <!-- Brand Header with 3D Frame -->
            <div class="brand-header">
                <div class="logo-3d-frame">
                    <img src="img/giti.png" alt="Gravitti Tech" class="brand-logo-img" onerror="this.style.display='none'; document.getElementById('text-fallback').style.display='block';">
                    <h1 class="brand-title m-0" id="text-fallback" style="display: none; font-size: 1.5rem; font-weight: 800;">Gravitti Tech</h1>
                </div>
                <p class="brand-subtitle">Silakan masuk untuk melanjutkan</p>
            </div>

            <!-- 3D Tab Switcher -->
            <div class="toggle-nav-3d">
                <button type="button" class="toggle-btn-3d active" id="tab-signin" onclick="switchForm('signin')">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
                </button>
                <button type="button" class="toggle-btn-3d" id="tab-signup" onclick="switchForm('signup')">
                    <i class="fa-solid fa-user-plus me-2"></i>Buat Akun
                </button>
            </div>

            <!-- Sign In Form -->
            <div id="signin-form-box">
                <form action="login.php" method="post">
                    <div class="form-group-custom">
                        <label for="username" class="form-label-custom">USERNAME</label>
                        <div class="input-wrapper-3d">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" class="form-control-3d" name="username" id="username" placeholder="Masukkan username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label-custom mb-0">PASSWORD</label>
                            <a href="lupa.php" class="forgot-link">Lupa password?</a>
                        </div>
                        <div class="input-wrapper-3d">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" class="form-control-3d" name="password" id="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="btn-pw-eye" onclick="togglePassword('password', this)" title="Lihat Password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-3d-primary">
                        Sign In <i class="fa-solid fa-arrow-right-long ms-2"></i>
                    </button>
                </form>
            </div>

            <!-- Sign Up Form -->
            <div id="signup-form-box" class="d-none">
                <form action="signup.php" method="post">
                    <div class="form-group-custom">
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

                    <div class="form-group-custom">
                        <label for="new-username" class="form-label-custom">BUAT USERNAME BARU</label>
                        <div class="input-wrapper-3d">
                            <i class="fa-solid fa-at input-icon"></i>
                            <input type="text" class="form-control-3d" id="new-username" name="new-username" placeholder="Buat username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <label for="new-password" class="form-label-custom">BUAT PASSWORD BARU</label>
                        <div class="input-wrapper-3d">
                            <i class="fa-solid fa-key input-icon"></i>
                            <input type="password" class="form-control-3d" id="new-password" name="new-password" placeholder="Buat password baru" required autocomplete="new-password">
                            <button type="button" class="btn-pw-eye" onclick="togglePassword('new-password', this)" title="Lihat Password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-3d-success" name="submit">
                        Daftar Akun Baru <i class="fa-solid fa-user-check ms-2"></i>
                    </button>
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

            // Universal 3D Tilt for both Mobile Touch and Desktop Mouse
            const card = document.getElementById('card3d');

            function apply3DTilt(clientX, clientY) {
                const rect = card.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                const xAxis = (centerX - clientX) / 16;
                const yAxis = (clientY - centerY) / 16;
                card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
            }

            function reset3DTilt() {
                card.style.transition = 'transform 0.4s ease';
                card.style.transform = `rotateY(0deg) rotateX(0deg)`;
                setTimeout(() => { card.style.transition = 'transform 0.15s ease-out'; }, 400);
            }

            // Mouse Desktop Event
            document.addEventListener('mousemove', (e) => {
                apply3DTilt(e.clientX, e.clientY);
            });

            document.addEventListener('mouseleave', reset3DTilt);

            // Mobile Touch Events (Swipe / Drag on card tilts card in 3D)
            card.addEventListener('touchmove', (e) => {
                if (e.touches.length > 0) {
                    const touch = e.touches[0];
                    apply3DTilt(touch.clientX, touch.clientY);
                }
            }, { passive: true });

            card.addEventListener('touchend', reset3DTilt);

            // Device Gyroscope Orientation Tilt for Mobile HP
            if (window.DeviceOrientationEvent) {
                window.addEventListener('deviceorientation', (e) => {
                    if (e.gamma !== null && e.beta !== null) {
                        const tiltX = Math.min(Math.max(e.gamma, -25), 25) / 1.5;
                        const tiltY = Math.min(Math.max(e.beta - 45, -25), 25) / 1.5;
                        card.style.transform = `rotateY(${tiltX}deg) rotateX(${tiltY}deg)`;
                    }
                }, true);
            }
        });
    </script>
    <script src="assets/js/pwa-install.js?v=<?php echo time(); ?>"></script>
</body>
</html>