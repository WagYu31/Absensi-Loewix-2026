/* assets/js/pwa-install.js - Absensi Loewix PWA Installer Engine */

(function () {
    let deferredPrompt = null;

    // 1. Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then((reg) => console.log('PWA Service Worker registered:', reg.scope))
                .catch((err) => console.log('PWA Service Worker registration failed:', err));
        });
    }

    // 2. Detect OS / Browser & Standalone Mode
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    // 3. Listen for beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        if (!isStandalone && !sessionStorage.getItem('pwa_dismissed')) {
            const banner = document.getElementById('pwa-install-banner');
            if (banner) banner.style.display = 'flex';
        }
    });

    // 4. Inject PWA UI Modals & Styles
    document.addEventListener('DOMContentLoaded', () => {
        injectPWAStylesAndModals();
    });

    function injectPWAStylesAndModals() {
        if (document.getElementById('pwa-styles')) return;

        const css = `
            /* PWA Top Banner - Matched to Senja Wisata Style */
            #pwa-install-banner {
                position: fixed;
                top: 10px;
                left: 10px;
                right: 10px;
                z-index: 999999;
                background: #1e293b;
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 16px;
                padding: 10px 14px;
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                animation: slideDownPWA 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            @keyframes slideDownPWA {
                from { transform: translateY(-100px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }

            .pwa-app-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                object-fit: cover;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
                flex-shrink: 0;
            }

            .pwa-text-info {
                flex-grow: 1;
                min-width: 0;
            }

            .pwa-title {
                font-weight: 800;
                font-size: 0.88rem;
                color: #ffffff;
                line-height: 1.2;
                margin-bottom: 1px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .pwa-desc {
                font-size: 0.75rem;
                color: #94a3b8;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .btn-pwa-install {
                background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
                color: #ffffff !important;
                font-weight: 800 !important;
                font-size: 0.82rem !important;
                padding: 6px 16px !important;
                border-radius: 12px !important;
                border: none !important;
                box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4) !important;
                transition: all 0.2s ease !important;
                flex-shrink: 0;
                cursor: pointer;
            }

            .btn-pwa-install:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(249, 115, 22, 0.6) !important;
            }

            .btn-pwa-close {
                background: transparent;
                border: none;
                color: #94a3b8;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 2px 6px;
                line-height: 1;
            }

            .btn-pwa-close:hover {
                color: #ffffff;
            }

            /* Custom PWA Guide Backdrop & Cards */
            .pwa-modal-backdrop {
                position: fixed;
                inset: 0;
                z-index: 1000000;
                background: rgba(15, 23, 42, 0.75);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                display: flex;
                align-items: flex-end;
                justify-content: center;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
                padding: 16px;
            }

            @media (min-width: 576px) {
                .pwa-modal-backdrop {
                    align-items: center;
                }
            }

            .pwa-modal-backdrop.active {
                opacity: 1;
                pointer-events: auto;
            }

            .pwa-guide-card {
                background: #ffffff;
                border-radius: 28px 28px 24px 24px;
                padding: 24px;
                width: 100%;
                max-width: 440px;
                text-align: center;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
                transform: translateY(100%);
                transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            .pwa-modal-backdrop.active .pwa-guide-card {
                transform: translateY(0);
            }

            .pwa-guide-icon-wrapper {
                width: 64px;
                height: 64px;
                border-radius: 20px;
                background: #eff6ff;
                border: 1px solid #dbeafe;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 16px auto;
            }

            .pwa-step-box {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 14px 16px;
                text-align: left;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .pwa-step-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                font-size: 0.85rem;
                color: #334155;
            }

            .pwa-step-num {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: #2563eb;
                color: #ffffff;
                font-size: 0.75rem;
                font-weight: 800;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                margin-top: 1px;
            }
        `;

        const styleEl = document.createElement('style');
        styleEl.id = 'pwa-styles';
        styleEl.innerHTML = css;
        document.head.appendChild(styleEl);

        // Build Top Banner HTML
        if (!isStandalone) {
            const banner = document.createElement('div');
            banner.id = 'pwa-install-banner';
            // Show by default on mobile unless dismissed
            banner.style.display = sessionStorage.getItem('pwa_dismissed') ? 'none' : 'flex';
            banner.innerHTML = `
                <div class="d-flex align-items-center gap-2.5" style="min-width: 0;">
                    <img src="/img/logo.png" class="pwa-app-icon" onerror="this.src='/img/giti.png';">
                    <div class="pwa-text-info">
                        <div class="pwa-title">Instal Absensi Loewix</div>
                        <div class="pwa-desc">ssll.id-giti.com</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button id="pwa-btn-trigger" class="btn-pwa-install">Instal</button>
                    <button id="pwa-btn-dismiss" class="btn-pwa-close">&times;</button>
                </div>
            `;
            document.body.appendChild(banner);

            document.getElementById('pwa-btn-dismiss').addEventListener('click', () => {
                banner.style.display = 'none';
                sessionStorage.setItem('pwa_dismissed', 'true');
            });

            document.getElementById('pwa-btn-trigger').addEventListener('click', () => {
                window.triggerPWAInstall();
            });
        }

        // Build iOS Modal Guide
        const iosModal = document.createElement('div');
        iosModal.id = 'pwa-ios-modal';
        iosModal.className = 'pwa-modal-backdrop';
        iosModal.innerHTML = `
            <div class="pwa-guide-card">
                <div class="pwa-guide-icon-wrapper">
                    <i class="fa-brands fa-apple text-primary fs-1"></i>
                </div>
                <h5 class="fw-extrabold text-dark mb-1">Install di iPhone / iPad</h5>
                <p class="text-secondary small mb-3">Untuk memasang aplikasi di layar utama iOS, ikuti 2 langkah mudah berikut:</p>
                
                <div class="pwa-step-box">
                    <div class="pwa-step-item">
                        <span class="pwa-step-num">1</span>
                        <div>Ketuk tombol <strong>Bagikan (Share)</strong> <i class="fa-solid fa-arrow-up-from-bracket text-primary ms-1"></i> di bagian bawah Safari.</div>
                    </div>
                    <div class="pwa-step-item">
                        <span class="pwa-step-num">2</span>
                        <div>Gulir ke bawah & pilih <strong>'Tambahkan ke Layar Utama'</strong> (Add to Home Screen) <i class="fa-solid fa-plus text-primary ms-1"></i>.</div>
                    </div>
                </div>
                
                <button id="pwa-ios-close" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5 mt-3 shadow-sm">
                    <i class="fa-solid fa-check me-1"></i> Mengerti
                </button>
            </div>
        `;
        document.body.appendChild(iosModal);

        document.getElementById('pwa-ios-close').addEventListener('click', () => {
            iosModal.classList.remove('active');
        });

        // Build Android Modal Guide
        const androidModal = document.createElement('div');
        androidModal.id = 'pwa-android-modal';
        androidModal.className = 'pwa-modal-backdrop';
        androidModal.innerHTML = `
            <div class="pwa-guide-card">
                <div class="pwa-guide-icon-wrapper">
                    <i class="fa-solid fa-mobile-screen-button text-primary fs-1"></i>
                </div>
                <h5 class="fw-extrabold text-dark mb-1">Install Aplikasi Absensi</h5>
                <p class="text-secondary small mb-3">Pasang aplikasi di layar utama HP Anda dengan 3 langkah praktis:</p>
                
                <div class="pwa-step-box">
                    <div class="pwa-step-item">
                        <span class="pwa-step-num">1</span>
                        <div>Ketuk menu <strong>Titik Tiga (⋮)</strong> di sudut kanan atas browser Chrome / Edge.</div>
                    </div>
                    <div class="pwa-step-item">
                        <span class="pwa-step-num">2</span>
                        <div>Pilih <strong>'Tambahkan ke Layar Utama'</strong> atau <strong>'Install Aplikasi'</strong>.</div>
                    </div>
                    <div class="pwa-step-item">
                        <span class="pwa-step-num">3</span>
                        <div>Konfirmasi dengan mengetuk <strong>'Tambah / Install'</strong>.</div>
                    </div>
                </div>
                
                <button id="pwa-android-close" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5 mt-3 shadow-sm">
                    <i class="fa-solid fa-check me-1"></i> Mengerti
                </button>
            </div>
        `;
        document.body.appendChild(androidModal);

        document.getElementById('pwa-android-close').addEventListener('click', () => {
            androidModal.classList.remove('active');
        });
    }

    // 5. Global helper function to trigger install anytime
    window.triggerPWAInstall = function () {
        if (isStandalone) {
            alert("Aplikasi Absensi Loewix sudah terpasang dan berjalan di HP Anda!");
            return;
        }

        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted PWA install prompt');
                    const banner = document.getElementById('pwa-install-banner');
                    if (banner) banner.style.display = 'none';
                }
                deferredPrompt = null;
            });
        } else if (isIOS) {
            const iosModal = document.getElementById('pwa-ios-modal');
            if (iosModal) iosModal.classList.add('active');
        } else {
            const androidModal = document.getElementById('pwa-android-modal');
            if (androidModal) androidModal.classList.add('active');
        }
    };
})();
