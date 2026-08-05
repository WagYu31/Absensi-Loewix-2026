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

    // 2. Detect OS / Browser
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    // Don't show install banner if already running as standalone app
    if (isStandalone) return;

    // 3. Inject PWA Install Banner HTML & Styles
    document.addEventListener('DOMContentLoaded', () => {
        injectPWABanner();
    });

    function injectPWABanner() {
        if (document.getElementById('pwa-install-banner')) return;

        const css = `
            #pwa-install-banner {
                position: fixed;
                top: 12px;
                left: 12px;
                right: 12px;
                z-index: 99999;
                background: rgba(15, 23, 42, 0.95);
                backdrop-filter: blur(20px) saturate(180%);
                -webkit-backdrop-filter: blur(20px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 20px;
                padding: 12px 16px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
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
                width: 44px;
                height: 44px;
                border-radius: 12px;
                object-fit: cover;
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
                flex-shrink: 0;
            }

            .pwa-text-info {
                flex-grow: 1;
                min-width: 0;
            }

            .pwa-title {
                font-weight: 800;
                font-size: 0.9rem;
                color: #ffffff;
                line-height: 1.2;
                margin-bottom: 2px;
            }

            .pwa-desc {
                font-size: 0.75rem;
                color: #94a3b8;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .btn-pwa-install {
                background: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%) !important;
                color: #ffffff !important;
                font-weight: 800 !important;
                font-size: 0.8rem !important;
                padding: 8px 16px !important;
                border-radius: 14px !important;
                border: none !important;
                box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4), 0 2px 0 #1d4ed8 !important;
                transition: all 0.2s ease !important;
                flex-shrink: 0;
                cursor: pointer;
            }

            .btn-pwa-install:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(37, 99, 235, 0.6) !important;
            }

            .btn-pwa-close {
                background: transparent;
                border: none;
                color: #64748b;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 4px;
                line-height: 1;
            }

            .btn-pwa-close:hover {
                color: #ffffff;
            }

            /* iOS Modal */
            #pwa-ios-modal {
                position: fixed;
                inset: 0;
                z-index: 100000;
                background: rgba(0, 0, 0, 0.75);
                backdrop-filter: blur(8px);
                display: flex;
                align-items: flex-end;
                justify-content: center;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
            }

            #pwa-ios-modal.active {
                opacity: 1;
                pointer-events: auto;
            }

            .pwa-ios-card {
                background: #ffffff;
                border-radius: 28px 28px 0 0;
                padding: 24px;
                width: 100%;
                max-width: 500px;
                text-align: center;
                transform: translateY(100%);
                transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            #pwa-ios-modal.active .pwa-ios-card {
                transform: translateY(0);
            }
        `;

        const styleEl = document.createElement('style');
        styleEl.innerHTML = css;
        document.head.appendChild(styleEl);

        // Build HTML
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.style.display = 'none'; // Hidden until prompt ready or iOS
        banner.innerHTML = `
            <img src="/img/logo.png" class="pwa-app-icon" onerror="this.src='/img/giti.png';">
            <div class="pwa-text-info">
                <div class="pwa-title">Install Absensi Loewix</div>
                <div class="pwa-desc">Pasang aplikasi di HP Anda</div>
            </div>
            <button id="pwa-btn-trigger" class="btn-pwa-install">Install <i class="fa-solid fa-download ms-1"></i></button>
            <button id="pwa-btn-dismiss" class="btn-pwa-close">&times;</button>
        `;
        document.body.appendChild(banner);

        // Build iOS Modal
        const iosModal = document.createElement('div');
        iosModal.id = 'pwa-ios-modal';
        iosModal.innerHTML = `
            <div class="pwa-ios-card">
                <div class="fs-1 text-primary mb-2">📱</div>
                <h5 class="fw-bold text-dark mb-2">Install Absensi di iPhone / iPad</h5>
                <p class="text-secondary small mb-4">Untuk memasang aplikasi ini di layar utama HP Anda, ikuti 2 langkah mudah berikut:</p>
                <div class="bg-light p-3 rounded-4 mb-3 text-start small">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-primary rounded-circle p-2 fs-6">1</span>
                        <div>Ketuk tombol <strong>Bagikan (Share)</strong> <span class="fs-5">📤</span> di bagian bawah Safari.</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-primary rounded-circle p-2 fs-6">2</span>
                        <div>Gulir ke bawah & pilih <strong>'Tambahkan ke Layar Utama'</strong> (Add to Home Screen) <span class="fs-5">➕</span>.</div>
                    </div>
                </div>
                <button id="pwa-ios-close" class="btn btn-primary rounded-pill w-100 fw-bold py-2">Mengerti</button>
            </div>
        `;
        document.body.appendChild(iosModal);

        // Bind Dismiss
        document.getElementById('pwa-btn-dismiss').addEventListener('click', () => {
            banner.style.display = 'none';
            sessionStorage.setItem('pwa_dismissed', 'true');
        });

        document.getElementById('pwa-ios-close').addEventListener('click', () => {
            iosModal.classList.remove('active');
        });

        // Trigger Install Action
        document.getElementById('pwa-btn-trigger').addEventListener('click', () => {
            if (isIOS) {
                iosModal.classList.add('active');
            } else if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted PWA install prompt');
                        banner.style.display = 'none';
                    }
                    deferredPrompt = null;
                });
            } else {
                alert("Gunakan browser Chrome, Edge, atau Safari untuk menginstall aplikasi ini ke layar HP Anda.");
            }
        });

        // Show for iOS if not dismissed
        if (isIOS && !sessionStorage.getItem('pwa_dismissed')) {
            banner.style.display = 'flex';
        }
    }

    // 4. Listen for beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        if (!sessionStorage.getItem('pwa_dismissed')) {
            const banner = document.getElementById('pwa-install-banner');
            if (banner) banner.style.display = 'flex';
        }
    });

    // 5. Global helper function to trigger install anytime (e.g. from Profile or Menu)
    window.triggerPWAInstall = function () {
        if (isIOS) {
            const modal = document.getElementById('pwa-ios-modal');
            if (modal) modal.classList.add('active');
        } else if (deferredPrompt) {
            deferredPrompt.prompt();
        } else {
            alert("Aplikasi siap diinstall! Buka menu browser Anda lalu pilih 'Tambahkan ke Layar Utama' / 'Install App'.");
        }
    };
})();
