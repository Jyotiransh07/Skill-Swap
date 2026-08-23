/**
 * SkillSwap Campus - Master JavaScript Interactive Utilities
 */

document.addEventListener('DOMContentLoaded', function () {
  // Initialize Bootstrap Tooltips if available
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  // Password Visibility Toggle
  const togglePassBtns = document.querySelectorAll('.toggle-password');
  togglePassBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
      }
    });
  });

  // Mark Notification as Read via AJAX
  const markReadBtns = document.querySelectorAll('.btn-mark-notification-read');
  markReadBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const notificationId = this.getAttribute('data-id');
      const itemEl = this.closest('.notification-item');

      fetch('../../api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_read&id=' + encodeURIComponent(notificationId)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          if (itemEl) {
            itemEl.classList.remove('bg-light');
            itemEl.classList.add('opacity-75');
          }
          this.remove();
          updateNotificationBadge();
        }
      })
      .catch(err => console.error('Error marking notification:', err));
    });
  });

  // Helper: Update Notification Counter Badge
  function updateNotificationBadge() {
    const badgeEl = document.getElementById('notificationBadgeCount');
    if (badgeEl) {
      let count = parseInt(badgeEl.innerText) || 0;
      count = Math.max(0, count - 1);
      if (count === 0) {
        badgeEl.style.display = 'none';
      } else {
        badgeEl.innerText = count;
      }
    }
  }
});
