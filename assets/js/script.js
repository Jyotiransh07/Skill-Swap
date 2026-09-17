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

  // Custom Vanilla JS Modal Engine (Bypasses Bootstrap CDN dependency completely)
  document.addEventListener('click', function(e) {
    // 1. Open Modal
    const toggleBtn = e.target.closest('[data-bs-toggle="modal"]');
    if (toggleBtn) {
      e.preventDefault();
      const targetSelector = toggleBtn.getAttribute('data-bs-target');
      if (targetSelector) {
        // Escape selector if it starts with #
        const targetId = targetSelector.startsWith('#') ? targetSelector.substring(1) : targetSelector;
        const modal = document.getElementById(targetId);
        
        if (modal) {
          modal.style.display = 'block';
          // Trigger browser reflow to enable transition
          void modal.offsetWidth;
          modal.classList.add('show');
          modal.removeAttribute('aria-hidden');
          modal.setAttribute('aria-modal', 'true');
          modal.setAttribute('role', 'dialog');
          
          let backdrop = document.querySelector('.modal-backdrop');
          if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
          }
          document.body.classList.add('modal-open');
          document.body.style.overflow = 'hidden';
          document.body.style.paddingRight = '17px'; // Prevent scrollbar shift
        }
      }
    }
    
    // 2. Close Modal via Dismiss Button
    const dismissBtn = e.target.closest('[data-bs-dismiss="modal"]');
    if (dismissBtn) {
      e.preventDefault();
      const modal = dismissBtn.closest('.modal');
      closeCustomModal(modal);
    }
    
    // 3. Close Modal on Backdrop Click
    if (e.target.classList.contains('modal')) {
      closeCustomModal(e.target);
    }
    
    // 4. Custom Vanilla JS Dropdown Engine
    const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"]');
    if (dropdownToggle) {
      e.preventDefault();
      e.stopPropagation();
      const parent = dropdownToggle.closest('.dropdown');
      if (parent) {
        const menu = parent.querySelector('.dropdown-menu');
        if (menu) {
          // Close all other dropdowns
          document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
            if (openMenu !== menu) {
              openMenu.classList.remove('show');
              const otherToggle = openMenu.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
              if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
            }
          });
          
          // Toggle current
          if (menu.classList.contains('show')) {
            menu.classList.remove('show');
            dropdownToggle.setAttribute('aria-expanded', 'false');
          } else {
            menu.classList.add('show');
            dropdownToggle.setAttribute('aria-expanded', 'true');
          }
        }
      }
    } else {
      // Clicked outside: close all dropdowns
      if (!e.target.closest('.dropdown-menu')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
          openMenu.classList.remove('show');
          const toggle = openMenu.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
          if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
      }
    }
  });

  function closeCustomModal(modal) {
    if (modal) {
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        modal.removeAttribute('role');
        
        // Only remove backdrop if there are no other open modals
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length === 0) {
          const backdrop = document.querySelector('.modal-backdrop');
          if (backdrop) backdrop.remove();
          document.body.classList.remove('modal-open');
          document.body.style.overflow = '';
          document.body.style.paddingRight = '';
        }
      }, 150); // wait for fade transition
    }
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

  // Fallback: Ensure Toast/Alert Close Buttons Work
  const alertCloseBtns = document.querySelectorAll('.alert .btn-close');
  alertCloseBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const alert = this.closest('.alert');
      if (alert) {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
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
