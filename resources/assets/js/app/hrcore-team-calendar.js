/**
 * Team Leave Calendar
 */

'use strict';

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function() {
  // Get calendar data from window
  const calendarData = window.teamCalendarData || { leaves: [], leaveTypes: [] };
  
  // Initialize calendar
  const calendarEl = document.getElementById('teamCalendar');
  
  if (calendarEl) {
    const calendar = new Calendar(calendarEl, {
      plugins: [dayGridPlugin, interactionPlugin],
      initialView: 'dayGridMonth',
      headerToolbar: false, // We're using custom navigation
      height: 'auto',
      editable: false,
      selectable: false,
      events: calendarData.leaves,
      eventDisplay: 'block',
      displayEventTime: false,
      eventDidMount: function(info) {
        // Add tooltip with leave details
        info.el.setAttribute('data-bs-toggle', 'tooltip');
        info.el.setAttribute('data-bs-placement', 'top');
        info.el.setAttribute('data-bs-html', 'true');
        
        const event = info.event;
        const tooltipContent = `
          <div class="text-start">
            <strong>${event.extendedProps.user}</strong><br>
            <small>${event.extendedProps.leaveType}</small><br>
            <small>${event.extendedProps.totalDays} day(s)</small>
            ${event.extendedProps.isHalfDay ? '<br><small>Half Day (' + event.extendedProps.halfDayType + ')</small>' : ''}
            <br><span class="badge bg-label-${event.extendedProps.status === 'approved' ? 'success' : 'warning'}">${event.extendedProps.status}</span>
          </div>
        `;
        info.el.setAttribute('title', tooltipContent);
        
        // Initialize tooltip
        new bootstrap.Tooltip(info.el);
        
        // Add status class
        info.el.classList.add('leave-status-' + event.extendedProps.status);
      },
      eventClick: function(info) {
        // Show leave details modal
        showLeaveDetails(info.event);
      },
      dayCellDidMount: function(info) {
        // Highlight today
        if (info.isToday) {
          info.el.classList.add('fc-day-today');
        }
        
        // Highlight weekends
        if (info.date.getDay() === 0 || info.date.getDay() === 6) {
          info.el.classList.add('fc-day-weekend');
        }
      }
    });
    
    calendar.render();
    
    // Custom navigation buttons
    document.getElementById('calendarPrev')?.addEventListener('click', () => {
      calendar.prev();
      updateCalendarTitle();
    });
    
    document.getElementById('calendarNext')?.addEventListener('click', () => {
      calendar.next();
      updateCalendarTitle();
    });
    
    document.getElementById('calendarToday')?.addEventListener('click', () => {
      calendar.today();
      updateCalendarTitle();
    });
    
    // Update calendar title
    function updateCalendarTitle() {
      const title = calendar.view.title;
      const titleEl = document.querySelector('.fc-toolbar-title');
      if (titleEl) {
        titleEl.textContent = title;
      }
    }
    
    // Initialize Select2
    if ($('.select2').length) {
      $('.select2').select2({
        placeholder: 'Select an option',
        allowClear: true
      });
    }
    
    // Apply filters
    document.getElementById('applyFilters')?.addEventListener('click', function() {
      applyCalendarFilters(calendar);
    });
    
    // Filter functions
    function applyCalendarFilters(calendar) {
      const departmentFilter = document.getElementById('departmentFilter').value;
      const leaveTypeFilters = Array.from(document.querySelectorAll('.leave-type-filter:checked')).map(cb => cb.value);
      const statusFilters = Array.from(document.querySelectorAll('.status-filter:checked')).map(cb => cb.value);
      
      // Remove all events
      calendar.removeAllEvents();
      
      // Filter events
      const filteredEvents = calendarData.leaves.filter(event => {
        // Leave type filter
        if (leaveTypeFilters.length > 0 && !leaveTypeFilters.includes(event.leaveType)) {
          return false;
        }
        
        // Status filter
        if (statusFilters.length > 0 && !statusFilters.includes(event.status)) {
          return false;
        }
        
        // Department filter would require additional data
        // For now, return true if department filter is not implemented
        
        return true;
      });
      
      // Add filtered events
      calendar.addEventSource(filteredEvents);
      
      // Show success message
      showToast('Filters applied successfully', 'success');
    }
    
    // Show leave details
    function showLeaveDetails(event) {
      const modalContent = `
        <div class="modal fade" id="leaveDetailsModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Leave Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row mb-3">
                  <div class="col-sm-4">
                    <strong>Employee:</strong>
                  </div>
                  <div class="col-sm-8">
                    ${event.extendedProps.user}
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4">
                    <strong>Leave Type:</strong>
                  </div>
                  <div class="col-sm-8">
                    <span class="badge" style="background-color: ${event.backgroundColor}">
                      ${event.extendedProps.leaveType}
                    </span>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4">
                    <strong>Duration:</strong>
                  </div>
                  <div class="col-sm-8">
                    ${event.start.toLocaleDateString()} - ${new Date(event.end.getTime() - 86400000).toLocaleDateString()}
                    <br><small>${event.extendedProps.totalDays} day(s)</small>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4">
                    <strong>Status:</strong>
                  </div>
                  <div class="col-sm-8">
                    <span class="badge bg-label-${event.extendedProps.status === 'approved' ? 'success' : 'warning'}">
                      ${event.extendedProps.status}
                    </span>
                  </div>
                </div>
                ${event.extendedProps.reason ? `
                <div class="row">
                  <div class="col-sm-4">
                    <strong>Reason:</strong>
                  </div>
                  <div class="col-sm-8">
                    ${event.extendedProps.reason}
                  </div>
                </div>
                ` : ''}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Remove existing modal if any
      const existingModal = document.getElementById('leaveDetailsModal');
      if (existingModal) {
        existingModal.remove();
      }
      
      // Add modal to body
      document.body.insertAdjacentHTML('beforeend', modalContent);
      
      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('leaveDetailsModal'));
      modal.show();
      
      // Remove modal from DOM when hidden
      document.getElementById('leaveDetailsModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
      });
    }
  }
  
  // Show toast notification
  function showToast(message, type = 'info') {
    const toastHtml = `
      <div class="bs-toast toast toast-placement-ex m-2 fade bg-${type === 'success' ? 'success' : 'primary'} top-0 end-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
          <i class="bx bx-${type === 'success' ? 'check' : 'info-circle'} me-2"></i>
          <div class="me-auto fw-semibold">${type === 'success' ? 'Success' : 'Info'}</div>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          ${message}
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
      const toast = document.querySelector('.bs-toast');
      if (toast) {
        toast.remove();
      }
    }, 3000);
  }
});