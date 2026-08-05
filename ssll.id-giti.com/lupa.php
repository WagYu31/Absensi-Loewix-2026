<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lupa Password - Gravitti Tech</title>
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

    <style>
        :root {
            --primary: #3b82f6;
            --indigo: #6366f1;
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

        /* Animated Ambient Light Blobs */
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

        @keyframes floatBlob {
            0% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(30px, 40px) scale(1.08); }
            100% { transform: translate(-20px, 20px) scale(0.95); }
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
        }

        .brand-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .brand-icon-wrapper {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 26px;
            margin-bottom: 1rem;
            box-shadow: 0 12px 25px -6px rgba(15, 23, 42, 0.4);
        }

        .brand-title {
            font-size: 1.55rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
            margin-bottom: 0.25rem;
        }

        .brand-subtitle {
            font-size: 0.875rem;
            color: var(--slate-muted);
            font-weight: 500;
            line-height: 1.45;
        }

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
            padding-right: 16px;
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
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
            transform: translateY(-1px);
        }

        .custom-input:focus ~ .input-icon,
        .input-icon-group:focus-within .input-icon {
            color: #2563eb;
        }

        .btn-shimmer {
            position: relative;
            overflow: hidden;
            border: none;
            height: 54px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: var(--input-radius);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        }

        .btn-shimmer:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px rgba(37, 99, 235, 0.5);
            color: #ffffff;
        }

        .btn-shimmer:active {
            transform: translateY(0) scale(0.98);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--slate-muted);
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            color: #2563eb;
        }

        @media (max-width: 576px) {
            body {
                padding: 14px;
                align-items: flex-start;
                padding-top: 28px;
            }
            .login-card {
                padding: 1.85rem 1.35rem;
                border-radius: 24px;
            }
            .custom-input, .btn-shimmer {
                height: 50px;
            }
        }
    </style>
</head>
<body>

    <div class="ambient-blob blob-1"></div>
    <div class="ambient-blob blob-2"></div>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="brand-header">
                <div class="brand-icon-wrapper">
                    <i class="fa-solid fa-shield-key"></i>
                </div>
                <h1 class="brand-title">Lupa Password?</h1>
                <p class="brand-subtitle">Masukkan username dan email terdaftar untuk verifikasi reset password.</p>
            </div>

            <form action="forgot.php" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label-custom">USERNAME</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" class="custom-input" name="username" id="username" placeholder="Masukkan username Anda" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="email" class="form-label-custom">EMAIL TERDAFTAR</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" class="custom-input" name="email" id="email" placeholder="contoh@domain.com" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-shimmer w-100 mb-3">
                    Kirim Kode OTP <i class="fa-solid fa-paper-plane ms-2"></i>
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="index.php" class="back-link">
                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Halaman Login
                </a>
            </div>

        </div>
    </div>

</body>
</html>
