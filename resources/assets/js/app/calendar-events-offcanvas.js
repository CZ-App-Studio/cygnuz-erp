/**
 * Calendar Events with Offcanvas - ERP Standard
 */
'use strict';

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
  // CSRF Token Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Use global pageData if available, otherwise fallback
  const pageData = window.pageData || {
    urls: {
      eventsAjax: '/calendar/events',
      eventStore: '/calendar/events',
      eventDetails: '/calendar/events/__ID__/details',
      eventUpdate: '/calendar/events/__ID__',
      eventDelete: '/calendar/events/__ID__',
      searchClients: '/calendar/events/searchClientsAjax',
      searchRelatedEntities: '/calendar/events/searchRelatedEntities'
    },
    eventTypeColors: {
      'Meeting': '#007bff',
      'Training': '#ffc107',
      'Leave': '#6c757d',
      'Holiday': '#28a745',
      'Deadline': '#dc3545',
      'Company Event': '#17a2b8',
      'Interview': '#6f42c1',
      'Onboarding Session': '#fd7e14',
      'Performance Review': '#20c997',
      'Client Appointment': '#6610f2',
      'Project Meeting': '#e83e8c',
      'Other': '#6c757d'
    },
    defaultEventColor: '#6c757d'
  };

  // Offcanvas instances
  const viewEventOffcanvas = new bootstrap.Offcanvas(document.getElementById('viewEventOffcanvas'));
  const eventOffcanvas = new bootstrap.Offcanvas(document.getElementById('eventOffcanvas'));

  // FullCalendar initialization using proper import
  const calendarEl = document.getElementById('calendar');
  const calendar = new Calendar(calendarEl, {
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    weekends: true,
    editable: true,
    droppable: false,

    // Event Sources
    events: function(fetchInfo, successCallback, failureCallback) {
      $.ajax({
        url: pageData.urls.eventsAjax,
        method: 'GET',
        data: {
          start: fetchInfo.startStr,
          end: fetchInfo.endStr
        },
        success: function(data) {
          successCallback(data);
        },
        error: function(xhr) {
          console.error('Error fetching events:', xhr);
          failureCallback(xhr);
        }
      });
    },

    // Event click handler
    eventClick: function(info) {
      info.jsEvent.preventDefault();
      showEventDetails(info.event.id);
    },

    // Date select handler (for creating new events)
    select: function(info) {
      openEventForm(null, info.startStr, info.endStr);
      calendar.unselect();
    },

    // Event drag/resize handlers
    eventDrop: function(info) {
      updateEventDates(info.event);
    },

    eventResize: function(info) {
      updateEventDates(info.event);
    }
  });

  calendar.render();

  // Initialize Select2 for attendees
  initializeSelect2();

  // Initialize date pickers
  initializeDatePickers();

  // Event handlers
  setupEventHandlers();

  /**
   * Show event details in offcanvas
   */
  function showEventDetails(eventId) {
    const url = pageData.urls.eventDetails.replace('__ID__', eventId);
    $.ajax({
      url: url,
      method: 'GET',
      success: function(data) {
        console.log('Event data received:', data); // Debug log
        populateEventDetails(data);
        viewEventOffcanvas.show();
      },
      error: function(xhr) {
        console.error('Error fetching event details:', xhr);
        Swal.fire('Error', 'Failed to load event details', 'error');
      }
    });
  }

  /**
   * Populate event details offcanvas
   */
  function populateEventDetails(data) {
    console.log('Event data received:', data); // Debug log

    // Handle both camelCase and snake_case field names
    const getValue = (snakeCase, camelCase) => data[snakeCase] || data[camelCase] || '';

    $('#viewEventTitle').text(getValue('event_title', 'eventTitle') || 'Untitled Event');

    // Event type badge
    const eventTypeBadge = $('#viewEventTypeBadge');
    const eventType = getValue('event_type', 'eventType');
    if (eventType) {
      eventTypeBadge.text(eventType).removeClass().addClass('badge bg-label-primary').show();
    } else {
      eventTypeBadge.hide();
    }

    // Date and time
    const eventStart = getValue('event_start', 'eventStart');
    const eventEnd = getValue('event_end', 'eventEnd');

    $('#viewEventStart').text(eventStart ? formatDateTime(eventStart) : 'Not specified');
    $('#viewEventEnd').text(eventEnd ? formatDateTime(eventEnd) : 'Not specified');

    // All day indicator
    const allDay = data.all_day || data.allDay || false;
    $('#viewEventAllDay').toggle(!!allDay);

    // Related entity section
    const relatedSection = $('#viewRelatedSection');
    const relatedType = getValue('related_type', 'relatedType');
    const relatedId = getValue('related_id', 'relatedId');
    const relatedName = getValue('related_name', 'relatedName');

    if (relatedType && relatedId && relatedName) {
      $('#viewRelatedName').text(relatedName);
      $('#viewRelatedType').text(getRelatedTypeLabel(relatedType));

      // Set appropriate icon based on type
      const relatedIcon = $('#viewRelatedIcon i');
      relatedIcon.removeClass().addClass(getRelatedTypeIcon(relatedType));

      relatedSection.show();
    } else {
      const clientId = getValue('client_id', 'clientId');
      const clientName = getValue('client_name', 'clientName');

      if (clientId && clientName) {
        // Legacy client support
        $('#viewRelatedName').text(clientName);
        $('#viewRelatedType').text('Client');

        const relatedIcon = $('#viewRelatedIcon i');
        relatedIcon.removeClass().addClass('bx bx-user');

        relatedSection.show();
      } else {
        relatedSection.hide();
      }
    }

    // Location and links
    const locationArea = $('#viewLocationArea');
    const meetingLinkArea = $('#viewMeetingLinkArea');
    const noLocationMessage = $('#viewNoLocationMessage');

    let hasLocationInfo = false;

    const eventLocation = getValue('event_location', 'eventLocation');
    const meetingLink = getValue('meeting_link', 'meetingLink');

    if (eventLocation && eventLocation.trim()) {
      $('#viewEventLocation').text(eventLocation);
      locationArea.show();
      hasLocationInfo = true;
    } else {
      locationArea.hide();
    }

    if (meetingLink && meetingLink.trim()) {
      $('#viewEventMeetingLink').attr('href', meetingLink).text(meetingLink);
      meetingLinkArea.show();
      hasLocationInfo = true;
    } else {
      meetingLinkArea.hide();
    }

    noLocationMessage.toggle(!hasLocationInfo);

    // Attendees
    const attendeePreviews = data.attendeePreviews || data.attendee_previews || [];
    const attendeesCount = attendeePreviews.length;
    $('#viewEventAttendeesCount').text(attendeesCount);

    const attendeesList = $('#viewEventAttendeesList');
    if (attendeesCount > 0) {
      let attendeesHtml = '';
      attendeePreviews.forEach(function(attendee) {
        let avatarHtml = '';
        if (attendee.avatar) {
          avatarHtml = `<img src="${attendee.avatar}" alt="${attendee.name}" class="attendee-avatar">`;
        } else {
          // Use initials with background color
          const bgColor = attendee.background_color || '#6c757d';
          avatarHtml = `<div class="attendee-avatar d-flex align-items-center justify-content-center" style="background-color: ${bgColor}; color: white; font-weight: bold; font-size: 12px;">${attendee.initials || '?'}</div>`;
        }

        attendeesHtml += `
          <div class="attendee-item">
            ${avatarHtml}
            <div class="attendee-info">
              <div class="fw-medium">${attendee.name}</div>
            </div>
          </div>
        `;
      });
      attendeesList.html(attendeesHtml);
    } else {
      attendeesList.html('<div class="text-muted fst-italic">No attendees</div>');
    }

    // Description
    const eventDescription = $('#viewEventDescription');
    const noDescriptionMessage = $('#viewNoDescriptionMessage');
    const description = getValue('event_description', 'eventDescription');

    if (description && description.trim()) {
      eventDescription.text(description).show();
      noDescriptionMessage.hide();
    } else {
      eventDescription.hide();
      noDescriptionMessage.show();
    }

    // Store event ID for edit/delete actions
    $('#viewEventOffcanvas').data('event-id', data.id);
  }

  /**
   * Open event form (add/edit)
   */
  function openEventForm(eventId = null, startDate = null, endDate = null) {
    // Reset form
    $('#eventForm')[0].reset();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    $('#event_id').val(eventId || '');

    // Set form title
    const title = eventId ? 'Edit Event' : 'Add Event';
    $('#eventOffcanvasLabel').html(`<i class="bx bx-calendar-plus me-2"></i>${title}`);

    // Show/hide delete button
    $('#deleteEventBtn').toggleClass('d-none', !eventId);

    if (eventId) {
      // Load event data for editing
      loadEventForEdit(eventId);
    } else {
      // Set default dates if provided
      if (startDate) {
        $('#event_start').val(formatDateTimeForInput(startDate));
      }
      if (endDate) {
        $('#event_end').val(formatDateTimeForInput(endDate));
      }
    }

    eventOffcanvas.show();
  }

  /**
   * Load event data for editing
   */
  function loadEventForEdit(eventId) {
    const url = pageData.urls.eventDetails.replace('__ID__', eventId);
    $.ajax({
      url: url,
      method: 'GET',
      success: function(data) {
        populateEventForm(data);
      },
      error: function(xhr) {
        console.error('Error loading event for edit:', xhr);
        Swal.fire('Error', 'Failed to load event data', 'error');
      }
    });
  }

  /**
   * Populate event form with data
   */
  function populateEventForm(data) {
    console.log('Populating form with data:', data); // Debug log

    // Handle both camelCase and snake_case field names
    const getValue = (snakeCase, camelCase) => data[snakeCase] || data[camelCase] || '';

    $('#event_title').val(getValue('event_title', 'eventTitle'));
    $('#event_type').val(getValue('event_type', 'eventType')).trigger('change');

    // Format dates for datetime-local input
    const eventStart = getValue('event_start', 'eventStart');
    const eventEnd = getValue('event_end', 'eventEnd');

    if (eventStart) {
      $('#event_start').val(formatDateTimeForInput(eventStart));
    }
    if (eventEnd) {
      $('#event_end').val(formatDateTimeForInput(eventEnd));
    }

    const allDay = data.all_day || data.allDay || false;
    $('#all_day').prop('checked', !!allDay);

    $('#event_location').val(getValue('event_location', 'eventLocation'));
    $('#meeting_link').val(getValue('meeting_link', 'meetingLink'));
    $('#event_description').val(getValue('event_description', 'eventDescription'));

    // Set color
    const color = data.color || '';
    if (color) {
      $(`input[name="color"][value="${color}"]`).prop('checked', true);
    } else {
      $('#color_default').prop('checked', true);
    }

    // Set related entity
    const relatedType = getValue('related_type', 'relatedType');
    const relatedId = getValue('related_id', 'relatedId');
    const relatedName = getValue('related_name', 'relatedName');

    if (relatedType && relatedId) {
      $('#related_type').val(relatedType).trigger('change');
      // The related_id dropdown will be populated when related_type changes
      setTimeout(function() {
        // Add the option first, then select it
        if (relatedName) {
          const option = new Option(relatedName, relatedId, true, true);
          $('#related_id').append(option);
        }
        $('#related_id').val(relatedId).trigger('change');
      }, 500);
    }

    // Set attendees
    const attendeeIds = data.attendee_ids || data.attendeeIds || [];
    if (attendeeIds.length > 0) {
      $('#attendee_ids').val(attendeeIds).trigger('change');
    }

    // Legacy client support
    const clientId = getValue('client_id', 'clientId');
    const clientName = getValue('client_name', 'clientName');

    if (clientId && clientName) {
      const option = new Option(clientName, clientId, true, true);
      $('#client_id').append(option).trigger('change');
    }
  }

  /**
   * Save event (create or update)
   */
  function saveEvent() {
    const formData = new FormData($('#eventForm')[0]);
    const eventId = $('#event_id').val();
    const isEdit = eventId && eventId !== '';

    const url = isEdit ? pageData.urls.eventUpdate.replace('__ID__', eventId) : pageData.urls.eventStore;
    const method = isEdit ? 'PUT' : 'POST';

    // Convert FormData to regular object for AJAX
    const data = {};
    formData.forEach((value, key) => {
      if (data[key]) {
        // Handle multiple values (like attendee_ids[])
        if (Array.isArray(data[key])) {
          data[key].push(value);
        } else {
          data[key] = [data[key], value];
        }
      } else {
        data[key] = value;
      }
    });

    $.ajax({
      url: url,
      method: method,
      data: data,
      success: function(response) {
        eventOffcanvas.hide();
        calendar.refetchEvents();
        Swal.fire('Success', response.message || 'Event saved successfully', 'success');
      },
      error: function(xhr) {
        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON.errors;
          displayValidationErrors(errors);
        } else {
          console.error('Error saving event:', xhr);
          Swal.fire('Error', 'Failed to save event', 'error');
        }
      }
    });
  }

  /**
   * Delete event
   */
  function deleteEvent(eventId) {
    Swal.fire({
      title: 'Delete Event?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Delete'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.eventDelete.replace('__ID__', eventId),
          method: 'DELETE',
          success: function(response) {
            viewEventOffcanvas.hide();
            eventOffcanvas.hide();
            calendar.refetchEvents();
            Swal.fire('Deleted', response.message || 'Event deleted successfully', 'success');
          },
          error: function(xhr) {
            console.error('Error deleting event:', xhr);
            Swal.fire('Error', 'Failed to delete event', 'error');
          }
        });
      }
    });
  }

  /**
   * Update event dates (drag/resize)
   */
  function updateEventDates(event) {
    // Show loading indicator
    Swal.fire({
      title: 'Updating...',
      text: 'Please wait while we update the event.',
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      }
    });

    // First fetch the full event details to ensure we have all required fields
    $.ajax({
      url: pageData.urls.eventDetails.replace('__ID__', event.id),
      method: 'GET',
      success: function(response) {
        // The response contains the event data with camelCase properties
        const eventData = response;
        
        // Update only the date/time fields
        const updateData = {
          event_title: eventData.eventTitle || eventData.event_title || event.title,
          event_type: eventData.eventType || eventData.event_type || event.extendedProps.eventType,
          event_start: event.start.toISOString(),
          event_end: event.end ? event.end.toISOString() : null,
          all_day: event.allDay ? 1 : 0,
          // Preserve other fields - handle both camelCase and snake_case
          event_location: eventData.eventLocation || eventData.event_location || '',
          event_description: eventData.eventDescription || eventData.event_description || '',
          color: eventData.color || null,
          related_type: eventData.relatedType || eventData.related_type || null,
          related_id: eventData.relatedId || eventData.related_id || null,
          meeting_link: eventData.meetingLink || eventData.meeting_link || '',
          attendee_ids: eventData.attendeeIds || eventData.attendee_ids || []
        };

        // Include client_id if it exists (for backward compatibility)
        // Check both camelCase and snake_case versions
        if (eventData.clientId || eventData.client_id) {
          updateData.client_id = eventData.clientId || eventData.client_id;
        }
        
        // For Client Appointment type, ensure client_id is set from related fields if not already present
        if (updateData.event_type === 'Client Appointment' && !updateData.client_id) {
          if (eventData.relatedType === 'Modules\\FieldManager\\app\\Models\\Client' && eventData.relatedId) {
            updateData.client_id = eventData.relatedId;
          } else if (eventData.related_type === 'Modules\\FieldManager\\app\\Models\\Client' && eventData.related_id) {
            updateData.client_id = eventData.related_id;
          }
        }

        // List of allowed related types from the validation
        const allowedRelatedTypes = [
          'App\\Models\\Client',
          'App\\Models\\Company',
          'App\\Models\\Contact',
          'Modules\\PMCore\\app\\Models\\Project'
        ];
        
        // Fix double backslashes in related_type
        if (updateData.related_type) {
          updateData.related_type = updateData.related_type.replace(/\\\\/g, '\\');
        }
        
        // Clean up the data - convert empty strings to null for optional fields
        if (!updateData.related_type || updateData.related_type === '') {
          updateData.related_type = null;
        }
        
        // Check if related_type is in the allowed list
        if (updateData.related_type && !allowedRelatedTypes.includes(updateData.related_type)) {
          // Not in allowed list, remove it to avoid validation error
          console.warn(`Related type '${updateData.related_type}' is not in allowed list, removing it.`);
          delete updateData.related_type;
          delete updateData.related_id;
        }
        
        if (!updateData.related_id || updateData.related_id === '') {
          updateData.related_id = null;
        }
        if (!updateData.color || updateData.color === '') {
          updateData.color = null;
        }
        
        // Remove related fields if both are null (to avoid validation issues)
        if (!updateData.related_type && !updateData.related_id) {
          delete updateData.related_type;
          delete updateData.related_id;
        }

        console.log('Event data received:', eventData);
        console.log('Related type value:', updateData.related_type);
        console.log('Related id value:', updateData.related_id);
        console.log('Update data to send:', JSON.stringify(updateData, null, 2));

        // Now update with complete data
        $.ajax({
          url: pageData.urls.eventUpdate.replace('__ID__', event.id),
          method: 'PUT',
          data: updateData,
          success: function(response) {
            Swal.close();
            calendar.refetchEvents();
            // Show success message
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Event updated successfully',
              timer: 1500,
              showConfirmButton: false
            });
          },
          error: function(xhr) {
            Swal.close();
            console.error('Error updating event:', xhr);
            calendar.refetchEvents(); // Revert changes
            
            let errorMessage = 'Failed to update event.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
              icon: 'error',
              title: 'Update Failed',
              text: errorMessage
            });
          }
        });
      },
      error: function(xhr) {
        Swal.close();
        console.error('Error fetching event details:', xhr);
        calendar.refetchEvents(); // Revert changes
        Swal.fire('Error', 'Failed to fetch event details', 'error');
      }
    });
  }

  /**
   * Setup event handlers
   */
  function setupEventHandlers() {
    // Save event button
    $('#saveEventBtn').on('click', function() {
      saveEvent();
    });

    // Edit event from details view
    $('#editEventBtnView').on('click', function() {
      const eventId = $('#viewEventOffcanvas').data('event-id');
      viewEventOffcanvas.hide();
      setTimeout(() => openEventForm(eventId), 300);
    });

    // Delete event from details view
    $('#deleteEventBtnView').on('click', function() {
      const eventId = $('#viewEventOffcanvas').data('event-id');
      deleteEvent(eventId);
    });

    // Delete event from form
    $('#deleteEventBtn').on('click', function() {
      const eventId = $('#event_id').val();
      deleteEvent(eventId);
    });

    // Event type change handler
    $('#event_type').on('change', function() {
      const eventType = $(this).val();
      handleEventTypeChange(eventType);
    });

    // Related type change handler
    $('#related_type').on('change', function() {
      const relatedType = $(this).val();
      loadRelatedEntities(relatedType);
    });

    // All day checkbox handler
    $('#all_day').on('change', function() {
      const isAllDay = $(this).is(':checked');
      toggleAllDayFields(isAllDay);
    });
  }

  /**
   * Handle event type changes
   */
  function handleEventTypeChange(eventType) {
    // Show/hide related entity section based on event type
    const showRelatedSection = ['Client Appointment', 'Project Meeting', 'Company Event'].includes(eventType);
    $('#related_entity_section').toggle(showRelatedSection);

    // Show/hide legacy client section for backward compatibility
    const showClientSection = eventType === 'Client Appointment';
    $('#client_selection_area').toggle(showClientSection);
    $('#client_required_indicator').toggle(showClientSection);
  }

  /**
   * Load related entities based on type
   */
  function loadRelatedEntities(relatedType) {
    const relatedIdSelect = $('#related_id');
    relatedIdSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>');

    if (!relatedType) {
      relatedIdSelect.prop('disabled', true).empty().append('<option value="">Select...</option>');
      return;
    }

    // This would need to be implemented in the controller
    $.ajax({
      url: pageData.urls.searchRelatedEntities,
      method: 'GET',
      data: { type: relatedType, limit: 50 },
      success: function(data) {
        relatedIdSelect.empty().append('<option value="">Select...</option>');
        data.forEach(function(item) {
          relatedIdSelect.append(`<option value="${item.id}">${item.name}</option>`);
        });
        relatedIdSelect.prop('disabled', false);
      },
      error: function(xhr) {
        console.error('Error loading related entities:', xhr);
        relatedIdSelect.empty().append('<option value="">Error loading</option>');
      }
    });
  }

  /**
   * Initialize Select2
   */
  function initializeSelect2() {
    // Attendees select2
    $('.select2-attendees').select2({
      placeholder: 'Select attendees...',
      allowClear: true,
      dropdownParent: $('#eventOffcanvas')
    });

    // Client select2 (legacy)
    $('.select2-client-ajax').select2({
      placeholder: 'Search for a client...',
      allowClear: true,
      dropdownParent: $('#eventOffcanvas'),
      ajax: {
        url: pageData.urls.searchClients,
        dataType: 'json',
        delay: 250,
        data: function(params) {
          return { q: params.term, page: params.page };
        },
        processResults: function(data) {
          return {
            results: data.results,
            pagination: { more: data.pagination.more }
          };
        }
      }
    });

    // Event type select2
    $('#event_type').select2({
      placeholder: 'Select type...',
      dropdownParent: $('#eventOffcanvas')
    });

    // Related entity selects
    $('#related_type, #related_id').select2({
      dropdownParent: $('#eventOffcanvas')
    });
  }

  /**
   * Initialize date pickers
   */
  function initializeDatePickers() {
    const dateTimeConfig = {
      enableTime: true,
      dateFormat: 'Y-m-d H:i',
      time_24hr: true
    };

    $('#event_start').flatpickr(dateTimeConfig);
    $('#event_end').flatpickr(dateTimeConfig);
  }

  /**
   * Toggle all day fields
   */
  function toggleAllDayFields(isAllDay) {
    if (isAllDay) {
      // Convert to date-only format
      const startDate = $('#event_start').val().split(' ')[0];
      $('#event_start').val(startDate);
      $('#event_end').val('');
    }
  }

  /**
   * Display validation errors
   */
  function displayValidationErrors(errors) {
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    // Display new errors
    Object.keys(errors).forEach(function(field) {
      const input = $(`[name="${field}"]`);
      const feedback = input.siblings('.invalid-feedback');

      input.addClass('is-invalid');
      feedback.text(errors[field][0]);
    });
  }

  /**
   * Utility functions
   */
  function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    return date.toLocaleString();
  }

  function formatDateTimeForInput(dateTimeString) {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    // Format for flatpickr: YYYY-MM-DD HH:mm
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
  }

  function getRelatedTypeLabel(relatedType) {
    const typeMap = {
      'App\\Models\\Client': 'Client',
      'Modules\\CRMCore\\app\\Models\\Company': 'Company',
      'Modules\\CRMCore\\app\\Models\\Contact': 'Contact',
      'Modules\\PMCore\\app\\Models\\Project': 'Project'
    };
    return typeMap[relatedType] || 'Unknown';
  }

  function getRelatedTypeIcon(relatedType) {
    const iconMap = {
      'App\\Models\\Client': 'bx bx-user',
      'Modules\\CRMCore\\app\\Models\\Company': 'bx bx-buildings',
      'Modules\\CRMCore\\app\\Models\\Contact': 'bx bx-user-circle',
      'Modules\\PMCore\\app\\Models\\Project': 'bx bx-folder'
    };
    return iconMap[relatedType] || 'bx bx-link';
  }

  // Global function to create new event (can be called from toolbar)
  window.createNewEvent = function() {
    openEventForm();
  };
});
