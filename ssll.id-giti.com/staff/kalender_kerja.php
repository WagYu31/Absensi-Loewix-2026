<?php
session_start();
// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}
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

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <style>
        .fc-event { 
            cursor: pointer; 
            color: white !important; /* Agar teks event selalu putih */
            font-weight: 500;
            padding: 5px;
            font-size: 0.8rem;
        }
        /* Definisi warna untuk setiap jenis event */
        .event-libur-merah   { background-color: #dc3545 !important; border-color: #b02a37 !important; }
        .event-spesial-kuning { background-color: #ffc107 !important; border-color: #d39e00 !important; color: #333 !important; } /* Teks hitam agar terbaca */
        .event-ultah-biru    { background-color: #0d6efd !important; border-color: #0a58ca !important; }
        .fc-daygrid-day.fc-day-today { background-color: rgba(13, 110, 253, 0.1) !important; }
        .list-group-item strong { color: #333; }
    /* ==========================================================================
       1. Gaya Umum & Kontainer Kalender
       ========================================================================== */
    #calendar {
        font-family: 'Poppins', sans-serif;
        font-size: 0.8rem;
    }

    /* ==========================================================================
       2. Header Toolbar (Tombol & Judul)
       ========================================================================== */
    .fc .fc-toolbar.fc-header-toolbar {
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
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
                <p>Kelola hari libur, acara perusahaan, dan lihat ulang tahun karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-lg-4">
                        <div id='calendar'></div>
                    </div>
                </div>

                <!--<div class="card shadow-sm mt-4">-->
                <!--    <div class="card-header bg-light">-->
                <!--        <h5 class="mb-0" id="event-list-title">Keterangan Acara & Ulang Tahun</h5>-->
                <!--    </div>-->
                <!--    <div class="card-body">-->
                <!--        <ul class="list-group list-group-flush" id="event-list">-->
                <!--           <li class="list-group-item text-muted">Memuat daftar acara...</li>-->
                <!--        </ul>-->
                <!--    </div>-->
                <!--</div>-->
            </div>
        </div>
    </div>

    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="eventForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="eventId" name="id">
                        <div class="mb-3">
                            <label for="eventDate" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="eventDate" name="tanggal_merah" readonly required>
                        </div>
                        <div class="mb-3">
                            <label for="eventDescription" class="form-label">Keterangan Acara</label>
                            <input type="text" class="form-control" id="eventDescription" name="keterangan" required placeholder="Contoh: Libur Hari Raya Idul Fitri">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Hari</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="libur" id="liburYes" value="yes" checked>
                                    <label class="form-check-label" for="liburYes">Hari Libur (Merah)</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="libur" id="liburNo" value="no">
                                    <label class="form-check-label" for="liburNo">Acara Spesial (Kuning)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger me-auto" id="deleteEventBtn">Hapus</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="saveEventBtn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/id.js'></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        const eventForm = document.getElementById('eventForm');

        function updateEventList(events, view) {
            const eventListEl = document.getElementById('event-list');
            const eventListTitle = document.getElementById('event-list-title');
            
            eventListTitle.innerText = 'Keterangan Acara: ' + view.title;
            eventListEl.innerHTML = ''; // Selalu kosongkan list sebelum diisi ulang

            // Cara filter yang lebih andal menggunakan perbandingan timestamp
            const eventsInView = events.filter(e => {
                if (!e.start) return false; // Keamanan jika ada event tanpa tanggal
                const eventTime = e.start.getTime(); // Waktu event dalam milidetik
                const viewStartTime = view.activeStart.getTime(); // Waktu awal bulan yang terlihat
                const viewEndTime = view.activeEnd.getTime(); // Waktu akhir bulan yang terlihat
                
                // Cek apakah waktu event berada di dalam rentang waktu yang terlihat
                return eventTime >= viewStartTime && eventTime < viewEndTime;
            });
            
            if (eventsInView.length === 0) {
                eventListEl.innerHTML = `<li class="list-group-item text-muted">Tidak ada acara pada bulan ${view.title}.</li>`;
                return;
            }

            // Urutkan acara berdasarkan tanggal
            eventsInView.sort((a, b) => a.start.getTime() - b.start.getTime());

            // Tampilkan setiap acara ke dalam list
            eventsInView.forEach(event => {
                let date = new Date(event.startStr + 'T00:00:00');
                let formattedDate = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long' });
                let badgeClass = '', badgeText = '';

                if(event.classNames.includes('event-libur-merah')) { [badgeClass, badgeText] = ['bg-danger', 'Hari Libur']; }
                else if (event.classNames.includes('event-spesial-kuning')) { [badgeClass, badgeText] = ['bg-warning text-dark', 'Acara Spesial']; }
                else if (event.classNames.includes('event-ultah-biru')) { [badgeClass, badgeText] = ['bg-primary', 'Ulang Tahun']; }

                eventListEl.innerHTML += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>${formattedDate}:</strong> ${event.title}</div>
                    <span class="badge ${badgeClass} rounded-pill">${badgeText}</span>
                </li>`;
            });
        }

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'id',
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
            events: 'api_get_events.php',
            
            eventsSet: function(info) {
                try {
                    updateEventList(info.events, calendar.view);
                } catch(e) {
                    console.error("Error saat memperbarui daftar acara:", e);
                }
            },
            
            dateClick: function(info) {
                eventForm.reset();
                $('#eventId').val('');
                $('#eventDate').val(info.dateStr);
                $('#eventModalLabel').text('Tambah Acara Baru');
                $('#liburYes').prop('checked', true);
                $('#deleteEventBtn').hide();
                eventModal.show();
            },

            eventClick: function(info) {
                if (info.event.extendedProps.type === 'ulang_tahun') return;
                
                $('#eventId').val(info.event.id);
                $('#eventDate').val(info.event.startStr);
                $('#eventDescription').val(info.event.title);
                (info.event.extendedProps.is_libur === 'yes') ? $('#liburYes').prop('checked', true) : $('#liburNo').prop('checked', true);
                $('#eventModalLabel').text('Edit Acara');
                $('#deleteEventBtn').show();
                eventModal.show();
            }
        });

        calendar.render();

        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(eventForm);
            formData.append('action', 'save');
            
            $.post({
                url: 'api_manage_event.php', type: 'POST', data: formData, processData: false,
                contentType: false, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') { calendar.refetchEvents(); eventModal.hide(); }
                    else { alert('Error: ' + response.message); }
                },
                error: function() { alert('Gagal terhubung ke server.'); }
            });
        });

        $('#deleteEventBtn').on('click', function() {
            if (!confirm('Apakah Anda yakin ingin menghapus acara ini?')) return;
            const eventId = $('#eventId').val();
            $.post('api_manage_event.php', { action: 'delete', id: eventId }, function(response) {
                if (response.status === 'success') { calendar.refetchEvents(); eventModal.hide(); }
                else { alert('Error: ' + response.message); }
            }, 'json');
        });
    });
    </script>
   
</body>
</html>