<?php
session_start();
// Keamanan: Memperbolehkan semua role yang sudah login (karyawan, admin, superadmin)
if (!isset($_SESSION['nip'])) {
    header('Location: index.php');
    exit();
}
// Tidak perlu koneksi DB di sini, karena semua data diambil oleh API
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Kerja - Grav-Tech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/absen-styles.css">

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <style>
        .fc-event { cursor: pointer; color: white !important; font-weight: 500; padding: 5px; font-size: 0.8rem; }
        .event-libur-merah   { background-color: #d9534f !important; border-color: #b94a48 !important; }
        .event-spesial-kuning { background-color: #ffc107 !important; border-color: #d39e00 !important; color: #333 !important; }
        .event-ultah-biru    { background-color: #0d6efd !important; border-color: #0a58ca !important; }
        .fc-daygrid-day.fc-day-today { background-color: rgba(13, 110, 253, 0.1) !important; }
        .list-group-item strong { color: #333; }
    /* ==========================================================================
       1. Gaya Umum & Kontainer Kalender
       ========================================================================== */
    #calendar {
        /*font-family: 'Poppins', sans-serif;*/
        font-size: 0.9rem;
    }

    /* ==========================================================================
       2. Header Toolbar (Tombol & Judul)
       ========================================================================== */
    .fc .fc-toolbar.fc-header-toolbar {
        margin-bottom: 1.5rem;
        flex-wrap: wrap; /* Agar responsif di layar kecil */
    }

    .fc .fc-toolbar-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #333;
    }

    /* Menyesuaikan tombol FullCalendar agar terlihat seperti tombol Bootstrap */
    .fc .fc-button {
        background: var(--bs-light) !important;
        border: 1px solid var(--bs-border-color) !important;
        color: var(--bs-body-color) !important;
        text-transform: capitalize;
        box-shadow: none !important;
        padding: 0.375rem 0.75rem;
        transition: all 0.2s ease-in-out;
    }
    
    .fc .fc-button:hover {
        background: var(--bs-secondary-bg) !important;
    }

    /* Tombol yang sedang aktif (seperti 'Bulan' atau 'Minggu') */
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: var(--bs-primary) !important;
        border-color: var(--bs-primary) !important;
        color: white !important;
    }

    /* Tombol 'today' */
    .fc .fc-today-button {
        background-color: var(--bs-primary-bg-subtle) !important;
        color: var(--bs-primary-text-emphasis) !important;
        border-color: var(--bs-primary-border-subtle) !important;
        font-weight: 500;
    }
    .fc .fc-today-button:hover {
         background-color: var(--bs-primary) !important;
         color: white !important;
    }
    .fc .fc-today-button:disabled {
        background: var(--bs-light) !important;
    }


    /* ==========================================================================
       3. Tampilan Grid (Bulanan) & List (Mingguan)
       ========================================================================== */

    /* Header Hari (Sen, Sel, Rab, ...) */
    .fc-theme-standard .fc-scrollgrid {
        border: 1px solid var(--bs-border-color);
    }
    .fc .fc-col-header-cell {
        background-color: var(--bs-light);
        font-weight: 600;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--bs-border-color);
    }

    /* Kotak tanggal */
    .fc .fc-daygrid-day-frame {
        min-height: 120px; /* Memberi ruang lebih di setiap kotak tanggal */
    }
    .fc .fc-daygrid-day.fc-day-today {
        background-color: rgba(13, 110, 253, 0.08) !important; /* Warna biru muda untuk hari ini */
    }
    .fc .fc-daygrid-day-number {
        padding: 0.5rem;
        font-weight: 500;
    }
    
    /* Tampilan Daftar (untuk mobile) */
    .fc-theme-standard .fc-list, .fc-theme-standard .fc-list-day {
        border: none;
    }
    .fc-theme-standard .fc-list-day-cushion {
        background-color: var(--bs-light);
    }
    .fc .fc-list-event-title a {
        color: var(--bs-body-color);
        text-decoration: none;
    }
    .fc .fc-list-event:hover td {
        background-color: rgba(0,0,0,0.03);
    }


    /* ==========================================================================
       4. Tampilan Acara (Event)
       ========================================================================== */
    .fc-event {
        border-radius: 5px !important;
        padding: 5px 8px !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        color: white !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .fc-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

/* Definisi warna untuk setiap jenis event dengan gradien */
.event-libur-merah {
    background-image: linear-gradient(45deg, #e74c3c, #c0392b) !important;
    border: none !important;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
.event-spesial-kuning {
    background-image: linear-gradient(45deg, #f1c40f, #f39c12) !important;
    border: none !important;
    color: #333 !important; /* Teks gelap tetap dipertahankan agar mudah dibaca */
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}
    .event-ultah-biru {
        /* Gradien diagonal dari biru ke ungu/indigo */
        background-image: linear-gradient(45deg, #0d6efd, #6f42c1) !important; 
        border: none !important; /* Hapus border agar lebih mulus */
        box-shadow: 0 2px 5px rgba(0,0,0,0.2); /* Tambahkan sedikit bayangan */
        text-shadow: 0 1px 2px rgba(0,0,0,0.2); /* Tambahkan bayangan pada teks agar lebih terbaca */
    }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Kalender Kerja Perusahaan</h1>
                <p>Cek hari libur, acara perusahaan, dan lihat ulang tahun rekan kerja Anda.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-lg-4">
                        <div id='calendar'></div>
                    </div>
                </div>

                </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/id.js'></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'id',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            // API untuk mengambil data tetap sama
            events: 'api_get_events.php',
            
            // --- PERBEDAAN UTAMA ADA DI SINI ---

            // 1. dateClick DITIADAKAN
            // Karyawan tidak bisa klik tanggal kosong untuk menambah acara.
            
            // 2. eventClick DIUBAH menjadi hanya menampilkan alert informasi
            eventClick: function(info) {
                // Mencegah link default (jika ada)
                info.jsEvent.preventDefault(); 
                
                let eventType = '';
                if(info.event.classNames.includes('event-libur-merah')) { eventType = 'Hari Libur'; }
                else if (info.event.classNames.includes('event-spesial-kuning')) { eventType = 'Acara Spesial'; }
                else if (info.event.classNames.includes('event-ultah-biru')) { eventType = 'Ulang Tahun'; }

                // Tampilkan detail acara dalam sebuah alert sederhana
                alert(
                    `Acara: ${info.event.title}\n` +
                    `Tanggal: ${info.event.start.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}\n` +
                    `Status: ${eventType}`
                );
            }
        });

        calendar.render();
    });
    </script>
</body>
</html>