

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  
  const sidebarToggle = document.getElementById('sidebarToggle');
  const appSidebar = document.querySelector('.app-sidebar');
  if (sidebarToggle && appSidebar) {
    sidebarToggle.addEventListener('click', () => {
      appSidebar.classList.toggle('show');
    });
  }

  
  const liveClockEl = document.getElementById('liveClock');
  if (liveClockEl) {
    const updateClock = () => {
      const now = new Date();
      liveClockEl.textContent = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      });
    };
    updateClock();
    setInterval(updateClock, 1000);
  }

  
  const tableSearchInput = document.getElementById('tableSearchInput');
  if (tableSearchInput) {
    tableSearchInput.addEventListener('keyup', function () {
      const term = this.value.toLowerCase().trim();
      const rows = document.querySelectorAll('.data-table tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    });
  }

  
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

});
