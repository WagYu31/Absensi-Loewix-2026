<?php
session_start();
// Keamanan: Hanya admin dan superadmin yang boleh mengakses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: index.php');
    exit();
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
            margin-bottom: 2rem !important;
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
                <div class="main-calendar-card">
                    <div id='calendar'></div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        const eventForm = document.getElementById('eventForm');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'id',
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
            events: 'api_get_events.php',
            
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