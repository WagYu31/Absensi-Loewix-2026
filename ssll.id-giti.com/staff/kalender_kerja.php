<?php
session_start();
// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
}
include '../conn.php';

// Cek siapa yang berulang tahun hari ini (Month-Day match)
$today_md = date('m-d');
$birthday_employees = [];
$res_bday = $conn->query("SELECT nama, pas_photo, tanggal_lahir, jabatan, TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur FROM karyawan WHERE DATE_FORMAT(tanggal_lahir, '%m-%d') = '$today_md' AND status_karyawan = 'aktif' AND deleted_at IS NULL");
if ($res_bday) {
    while ($rb = $res_bday->fetch_assoc()) {
        $birthday_employees[] = $rb;
    }
}

$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Kerja 3D - Gravitti Tech</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            --card-radius-lg: 24px;
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
            --success-3d: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);
            --danger-3d: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #b91c1c 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #f1f5f9 !important;
        }

        .main-content-wrapper {
            background: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%) !important;
            min-height: 100vh;
        }

        /* 3D Header Banner */
        .page-specific-header {
            background: var(--header-gradient) !important;
            color: #ffffff;
            padding: 2.25rem 0 4.5rem 0 !important;
            margin-bottom: -50px !important;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-specific-header h1 {
            font-weight: 800 !important;
            font-size: 1.65rem !important;
            letter-spacing: -0.5px;
            color: #ffffff !important;
        }

        /* Birthday Celebration Banner 3D */
        .birthday-banner-3d {
            position: relative;
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 50%, #ec4899 100%) !important;
            border-radius: var(--card-radius-lg) !important;
            padding: 1.5rem 1.75rem !important;
            box-shadow: 0 20px 40px -10px rgba(124, 58, 237, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.2) inset !important;
            overflow: hidden;
            animation: pulseBdayGlow 3s infinite alternate;
        }

        @keyframes pulseBdayGlow {
            0% { box-shadow: 0 15px 35px -10px rgba(124, 58, 237, 0.4); }
            100% { box-shadow: 0 25px 50px -5px rgba(236, 72, 153, 0.6); }
        }

        .bday-icon-wrapper {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            animation: bdayBounce 2s infinite ease-in-out;
        }

        @keyframes bdayBounce {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-6px) rotate(8deg); }
        }

        .bday-card-item {
            background: rgba(255, 255, 255, 0.18) !important;
            backdrop-filter: blur(15px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 18px !important;
            padding: 8px 16px 8px 10px !important;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .bday-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* 3D Main Card Container */
        .main-calendar-card {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 12px 24px -12px rgba(15, 23, 42, 0.08) !important;
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
        }

        /* FullCalendar Custom 3D Theme */
        #calendar {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }

        .fc .fc-toolbar.fc-header-toolbar {
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 12px;
        }

        .fc .fc-toolbar-title {
            font-size: 1.5rem !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            letter-spacing: -0.5px;
        }

        .fc .fc-button {
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            border-radius: 12px !important;
            padding: 6px 14px !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03), 0 2px 0 #cbd5e1 !important;
            transition: all 0.15s ease-out !important;
            text-transform: capitalize !important;
        }

        .fc .fc-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08), 0 3px 0 #94a3b8 !important;
            color: #1e293b !important;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background: var(--primary-3d) !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
        }

        .fc .fc-today-button {
            background: rgba(37, 99, 235, 0.1) !important;
            color: #2563eb !important;
            border-color: rgba(37, 99, 235, 0.25) !important;
        }

        .fc-theme-standard .fc-scrollgrid {
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            overflow: hidden;
        }

        .fc .fc-col-header-cell {
            background: #f8fafc !important;
            font-weight: 800 !important;
            color: #475569 !important;
            padding: 0.85rem 0 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .fc .fc-daygrid-day-frame {
            min-height: 115px !important;
            padding: 4px;
        }

        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(37, 99, 235, 0.06) !important;
        }

        .fc .fc-daygrid-day-number {
            padding: 0.4rem 0.6rem !important;
            font-weight: 800 !important;
            color: #334155 !important;
            font-size: 0.85rem;
        }

        /* 3D Event Chips */
        .fc-event {
            border-radius: 10px !important;
            padding: 5px 10px !important;
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            border: none !important;
            margin-top: 3px !important;
            transition: all 0.2s ease;
        }

        .fc-event:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25) !important;
        }

        .event-libur-merah {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%) !important;
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3), 0 2px 0 #991b1b !important;
        }

        .event-spesial-kuning {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(217, 119, 6, 0.3), 0 2px 0 #b45309 !important;
        }

        .event-ultah-biru {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%) !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3), 0 2px 0 #1d4ed8 !important;
        }

        /* Modal Styles */
        .modal-content-3d {
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
            overflow: hidden;
        }

        .modal-content-3d .modal-header {
            background: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 1.25rem 1.5rem !important;
        }

        .modal-content-3d .modal-title {
            font-weight: 800 !important;
            color: #1e293b !important;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-calendar-days me-2 text-primary-light"></i>Kalender Kerja Perusahaan</h1>
                <p class="small mb-0 opacity-80">Kelola hari libur, acara khusus perusahaan, dan jadwal ulang tahun karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content px-0">
            <div class="container-fluid px-lg-4">

                <!-- Animated Birthday Celebration Banner -->
                <?php if (!empty($birthday_employees)): ?>
                <div class="birthday-banner-3d mb-4 no-print">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative z-1">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bday-icon-wrapper">
                                <span class="fs-2">🎂</span>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-dark font-black px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem; background: #fbbf24;"><i class="fa-solid fa-crown me-1"></i>HARI INI BERULANG TAHUN!</span>
                                </div>
                                <h4 class="fw-extrabold text-white mb-0 mt-1" style="letter-spacing: -0.5px;">
                                    🎉 Selamat Ulang Tahun Kepada:
                                </h4>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <?php foreach ($birthday_employees as $bemp): ?>
                            <div class="bday-card-item">
                                <img src="../uploads/<?php echo htmlspecialchars($bemp['pas_photo'] ?: 'default.png'); ?>" class="bday-avatar" onerror="this.onerror=null; this.src='https://via.placeholder.com/50/003c9c/ffffff?Text=<?php echo strtoupper(substr($bemp['nama'], 0, 1)); ?>';">
                                <div>
                                    <div class="fw-bold text-white fs-6" style="text-transform: capitalize;"><?php echo htmlspecialchars($bemp['nama']); ?></div>
                                    <div class="text-white-50 small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($bemp['jabatan'] ?: 'Karyawan'); ?> <?php if ($bemp['umur']) echo "• " . $bemp['umur'] . " Thn"; ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Main Calendar Card -->
                <div class="main-calendar-card">
                    <!-- Legend Bar 3D -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 p-3 rounded-4 bg-light border border-slate-200">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold small text-secondary me-2"><i class="fa-solid fa-tags me-1 text-primary"></i>Keterangan Warna:</span>
                            <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-umbrella-beach me-1"></i>Hari Libur Nasional</span>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-star me-1"></i>Acara Spesial</span>
                            <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-cake-candles me-1"></i>Ulang Tahun Karyawan</span>
                        </div>
                        <small class="text-muted fst-italic"><i class="fa-solid fa-circle-info me-1 text-primary"></i>Klik tanggal untuk menambah acara baru.</small>
                    </div>

                    <div id='calendar'></div>
                </div>

                <!-- Event Summary List Card -->
                <div class="main-calendar-card mt-3 p-0" style="overflow: hidden;">
                    <div class="bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-dark mb-0 fs-6" id="event-list-title"><i class="fa-solid fa-list-check me-2 text-primary"></i>Daftar Acara & Ulang Tahun Bulan Ini</h6>
                    </div>
                    <div class="p-0">
                        <ul class="list-group list-group-flush" id="event-list">
                            <li class="list-group-item text-muted p-4 text-center">Memuat daftar acara...</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Event -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-3d">
                <form id="eventForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventModalLabel">Tambah Acara Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" id="eventId" name="id">
                        <div class="mb-3">
                            <label for="eventDate" class="form-label fw-bold small text-secondary">Tanggal</label>
                            <input type="date" class="form-control rounded-3" id="eventDate" name="tanggal_merah" readonly required>
                        </div>
                        <div class="mb-3">
                            <label for="eventDescription" class="form-label fw-bold small text-secondary">Keterangan Acara</label>
                            <input type="text" class="form-control rounded-3" id="eventDescription" name="keterangan" required placeholder="Contoh: Libur Hari Raya Idul Fitri">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Status Hari</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="libur" id="liburYes" value="yes" checked>
                                    <label class="form-check-label fw-semibold text-danger" for="liburYes">Hari Libur (Merah)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="libur" id="liburNo" value="no">
                                    <label class="form-check-label fw-semibold text-warning" for="liburNo">Acara Spesial (Oranye)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-danger btn-sm rounded-3 fw-bold me-auto" id="deleteEventBtn"><i class="fa-solid fa-trash me-1"></i>Hapus</button>
                        <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold" id="saveEventBtn"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/id.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($birthday_employees)): ?>
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
        <?php endif; ?>

        const calendarEl = document.getElementById('calendar');
        const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        const eventForm = document.getElementById('eventForm');

        function updateEventList(events, view) {
            const eventListEl = document.getElementById('event-list');
            const eventListTitle = document.getElementById('event-list-title');
            
            eventListTitle.innerHTML = `<i class="fa-solid fa-list-check me-2 text-primary"></i>Daftar Acara & Ulang Tahun: <strong>${view.title}</strong>`;
            eventListEl.innerHTML = '';

            const eventsInView = events.filter(e => {
                if (!e.start) return false;
                const eventTime = e.start.getTime();
                const viewStartTime = view.activeStart.getTime();
                const viewEndTime = view.activeEnd.getTime();
                return eventTime >= viewStartTime && eventTime < viewEndTime;
            });
            
            if (eventsInView.length === 0) {
                eventListEl.innerHTML = `<li class="list-group-item text-muted p-4 text-center">Tidak ada acara pada bulan ${view.title}.</li>`;
                return;
            }

            eventsInView.sort((a, b) => a.start.getTime() - b.start.getTime());

            eventsInView.forEach(event => {
                let date = new Date(event.startStr + 'T00:00:00');
                let formattedDate = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                let badgeClass = '', badgeText = '', icon = '';

                if(event.classNames.includes('event-libur-merah')) { 
                    [badgeClass, badgeText, icon] = ['bg-danger', 'Hari Libur', 'fa-umbrella-beach']; 
                } else if (event.classNames.includes('event-spesial-kuning')) { 
                    [badgeClass, badgeText, icon] = ['bg-warning text-dark', 'Acara Spesial', 'fa-star']; 
                } else if (event.classNames.includes('event-ultah-biru')) { 
                    [badgeClass, badgeText, icon] = ['bg-primary', 'Ulang Tahun', 'fa-cake-candles']; 
                }

                eventListEl.innerHTML += `<li class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <div><span class="fw-bold text-dark me-2">${formattedDate}:</span> <span class="fw-semibold text-secondary">${event.title}</span></div>
                    <span class="badge ${badgeClass} rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid ${icon} me-1"></i>${badgeText}</span>
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

            datesSet: function(info) {
                try {
                    updateEventList(calendar.getEvents(), info.view);
                } catch(e) {
                    console.error("Error saat datesSet:", e);
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