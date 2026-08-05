<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lupa Password 3D - Gravitti Tech</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
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
            padding: 20px;
            perspective: 1000px;
            overflow-x: hidden;
            touch-action: manipulation;
        }

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

        .card-3d {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: var(--card-radius);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow: 
                0 30px 60px -12px rgba(15, 23, 42, 0.15),
                0 18px 36px -18px rgba(15, 23, 42, 0.12),
                inset 0 1px 1px rgba(255, 255, 255, 0.9);
            padding: 2.25rem 1.85rem;
            transform-style: preserve-3d;
            will-change: transform;
            transition: transform 0.15s ease-out, box-shadow 0.3s ease;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 1.65rem;
            transform-style: preserve-3d;
        }

        .logo-3d-frame {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            color: #ffffff;
            font-size: 24px;
            box-shadow: 0 12px 24px -6px rgba(15, 23, 42, 0.25);
            margin-bottom: 0.85rem;
            transform: translateZ(35px);
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 0.25rem 0;
            transform: translateZ(25px);
        }

        .brand-subtitle {
            font-size: 0.875rem;
            color: var(--text-sub);
            font-weight: 500;
            margin: 0;
            transform: translateZ(20px);
        }

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
        }

        .form-control-3d {
            width: 100%;
            height: 50px;
            padding-left: 46px;
            padding-right: 16px;
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
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
            transform: translateY(-2px);
        }

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

        .back-link {
            font-size: 0.875rem;
            color: var(--text-sub);
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transform: translateZ(20px);
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #2563eb;
        }

        @media (max-width: 480px) {
            body { padding: 14px; }
            .card-3d { padding: 1.85rem 1.35rem; border-radius: 22px; }
        }
    </style>
</head>
<body>

    <div class="orb-3d orb-1"></div>
    <div class="orb-3d orb-2"></div>

    <div class="scene-container">
        <div class="card-3d" id="card3d">
            
            <div class="brand-header">
                <div class="logo-3d-frame">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h1 class="brand-title">Lupa Password</h1>
                <p class="brand-subtitle">Masukkan username dan email Anda untuk menerima OTP</p>
            </div>

            <form action="forgot.php" method="post">
                <div class="form-group-custom">
                    <label for="username" class="form-label-custom">USERNAME</label>
                    <div class="input-wrapper-3d">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" class="form-control-3d" name="username" id="username" placeholder="Masukkan username" required>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="email" class="form-label-custom">EMAIL TERDAFTAR</label>
                    <div class="input-wrapper-3d">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" class="form-control-3d" name="email" id="email" placeholder="contoh@domain.com" required>
                    </div>
                </div>

                <button type="submit" class="btn-3d-primary">
                    Kirim Kode OTP <i class="fa-solid fa-paper-plane ms-2"></i>
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="index.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
                </a>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        $(document).ready(function() {
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

            document.addEventListener('mousemove', (e) => {
                apply3DTilt(e.clientX, e.clientY);
            });

            document.addEventListener('mouseleave', reset3DTilt);

            card.addEventListener('touchmove', (e) => {
                if (e.touches.length > 0) {
                    const touch = e.touches[0];
                    apply3DTilt(touch.clientX, touch.clientY);
                }
            }, { passive: true });

            card.addEventListener('touchend', reset3DTilt);

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
</body>
</html>
