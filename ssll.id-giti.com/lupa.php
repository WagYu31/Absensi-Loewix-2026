<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lupa Password - Gravitti Tech</title>
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

    <style>
        :root {
            --primary: #1877f2;
            --primary-hover: #166fe5;
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

        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1c2b36;
            margin: 0 0 0.25rem 0;
        }

        .brand-subtitle {
            font-size: 0.875rem;
            color: var(--text-sub);
            margin: 0;
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
            padding-right: 14px;
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

        .btn-submit-primary:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
        }

        .back-link {
            font-size: 0.875rem;
            color: var(--text-sub);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-link:hover {
            color: var(--primary);
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
            
            <div class="brand-header">
                <h1 class="brand-title">Lupa Password</h1>
                <p class="brand-subtitle">Masukkan username dan email Anda untuk menerima OTP</p>
            </div>

            <form action="forgot.php" method="post">
                <div class="form-group-custom">
                    <label for="username" class="form-label-custom">Username</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" class="form-control-custom" name="username" id="username" placeholder="Masukkan username Anda" required>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="email" class="form-label-custom">Email</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" class="form-control-custom" name="email" id="email" placeholder="Masukkan email terdaftar" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit-primary">Kirim Kode OTP</button>
            </form>

            <div class="text-center mt-3">
                <a href="index.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
                </a>
            </div>

        </div>
    </div>

</body>
</html>
