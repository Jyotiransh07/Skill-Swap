<?php
/**
 * SkillSwap Campus - Interactive Calendar
 */
require_once __DIR__ . '/../includes/header.php';
?>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<div class="d-flex">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content flex-grow-1 bg-light">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-dark fw-bold">My Schedule</h2>
        <div>
          <span class="badge" style="background-color: #4f46e5;">Scheduled</span>
          <span class="badge bg-success">Completed</span>
          <span class="badge bg-danger">Cancelled</span>
        </div>
      </div>

      <div class="bg-white p-4 rounded shadow-sm border">
        <div id="calendar"></div>
      </div>
    </div>
  </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="eventTitle">Session Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeEventModal()"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <span class="badge bg-primary px-3 py-2 rounded-pill" id="eventStatus"></span>
        </div>
        <ul class="list-group list-group-flush mb-3">
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted"><i class="bi bi-book me-2"></i>Skill</span>
            <span class="fw-semibold" id="eventSkill"></span>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted"><i class="bi bi-person me-2"></i>Partner</span>
            <span class="fw-semibold" id="eventPartner"></span>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted"><i class="bi bi-clock me-2"></i>Time</span>
            <span class="fw-semibold" id="eventTime"></span>
          </li>
          <li class="list-group-item px-0" id="eventLocationWrap">
            <span class="text-muted d-block mb-1"><i class="bi bi-geo-alt me-2"></i>Location / Link</span>
            <div id="eventLocation" class="fw-semibold"></div>
          </li>
        </ul>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" onclick="closeEventModal()">Close</button>
        <a href="sessions.php" class="btn btn-primary-custom">Manage Session</a>
      </div>
    </div>
  </div>
</div>

<style>
/* Calendar Styling Adjustments */
#calendar {
    min-height: 600px;
    font-family: inherit;
}
.fc-theme-standard .fc-scrollgrid { border-color: #e2e8f0; }
.fc-theme-standard td, .fc-theme-standard th { border-color: #e2e8f0; }
.fc .fc-button-primary {
    background-color: #4f46e5;
    border-color: #4f46e5;
}
.fc .fc-button-primary:hover {
    background-color: #4338ca;
    border-color: #4338ca;
}
.fc .fc-button-primary:not(:disabled).fc-button-active, 
.fc .fc-button-primary:not(:disabled):active {
    background-color: #3730a3;
    border-color: #3730a3;
}
.fc-event {
    cursor: pointer;
    border-radius: 4px;
    padding: 2px 4px;
    font-size: 0.85em;
    border: none;
}
.fc-day-today {
    background-color: #f8fafc !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        themeSystem: 'bootstrap5',
        events: '../api/calendar.php',
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // don't let the browser navigate
            
            const props = info.event.extendedProps;
            
            document.getElementById('eventTitle').textContent = props.role + ' ' + props.skill;
            document.getElementById('eventStatus').textContent = props.status;
            document.getElementById('eventStatus').className = 'badge px-3 py-2 rounded-pill ' + 
                (props.status === 'COMPLETED' ? 'bg-success' : (props.status === 'CANCELLED' ? 'bg-danger' : 'bg-primary'));
                
            document.getElementById('eventSkill').textContent = props.skill;
            document.getElementById('eventPartner').textContent = props.partner;
            
            // Format time nicely
            const start = info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const end = info.event.end ? info.event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
            const dateStr = info.event.start.toLocaleDateString();
            document.getElementById('eventTime').textContent = dateStr + ' (' + start + (end ? ' - ' + end : '') + ')';
            
            const locEl = document.getElementById('eventLocation');
            if (props.mode === 'ONLINE' && props.meeting_link) {
                locEl.innerHTML = `<a href="${props.meeting_link}" target="_blank" class="text-primary text-decoration-none"><i class="bi bi-box-arrow-up-right me-1"></i>${props.meeting_link}</a>`;
            } else if (props.location) {
                locEl.textContent = props.location;
            } else {
                locEl.textContent = 'Not specified yet';
            }
            
            // Show modal using the Vanilla JS engine manually if bootstrap fails
            const modalEl = document.getElementById('eventModal');
            modalEl.classList.add('show', 'd-block');
            document.body.classList.add('modal-open');
            
            if(!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }
    });
    
    calendar.render();
});

function closeEventModal() {
    const modalEl = document.getElementById('eventModal');
    modalEl.classList.remove('show', 'd-block');
    document.body.classList.remove('modal-open');
    const backdrop = document.querySelector('.modal-backdrop');
    if(backdrop) backdrop.remove();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
