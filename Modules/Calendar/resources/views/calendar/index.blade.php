@php
  use App\Enums\EventType;
@endphp
@extends('layouts.layoutMaster')

@section('title', 'Calendar')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/fullcalendar/fullcalendar.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('page-style')
  <style>
    /* General Styles */
    .select2-container--open {
      z-index: 1056;
    }

    .fc-event {
      cursor: pointer;
    }

    .modal-body .select2-container {
      width: 100% !important;
    }

    .flatpickr-calendar {
      z-index: 1056 !important;
    }

    .swal2-container {
      z-index: 2000 !important;
    }

    /* Offcanvas Styles */
    .offcanvas-end {
      width: 450px !important;
    }

    .offcanvas-body .select2-container {
      width: 100% !important;
    }

    /* Validation */
    .is-invalid {
      border-color: #dc3545 !important;
    }

    .invalid-feedback {
      display: none;
      width: 100%;
      margin-top: 0.25rem;
      font-size: 0.875em;
      color: #dc3545;
    }

    .is-invalid ~ .invalid-feedback, .is-invalid ~ .select2-container + .invalid-feedback {
      display: block;
    }

    .is-invalid .select2-selection {
      border: 1px solid #dc3545 !important;
    }

    /* Event Details Offcanvas Styles */
    .offcanvas .detail-label {
      font-weight: 600;
      color: #566a7f;
      margin-right: 8px;
    }

    .offcanvas .detail-value {
      color: #6f8193;
    }

    .offcanvas .avatar-initial {
      font-size: 0.875rem;
    }

    .offcanvas .card {
      box-shadow: 0 0.125rem 0.25rem rgba(161, 172, 184, 0.15);
      border: 1px solid rgba(161, 172, 184, 0.15);
    }

    .offcanvas .badge {
      font-size: 0.75rem;
    }

    /* Fixed Color Picker */
    .color-radio-group .form-check-input {
      display: none;
    }

    .color-radio-group .form-check-label {
      display: inline-block;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      cursor: pointer;
      border: 2px solid transparent;
      margin-right: 5px;
      transition: border-color 0.2s ease-in-out;
    }

    .color-radio-group .form-check-input:checked + .form-check-label {
      border-color: #435971;
      box-shadow: 0 0 0 2px #fff inset;
    }

    .color-radio-group .form-check-inline {
      margin-right: 0.5rem;
    }

    /* Attendee List Styles */
    .attendee-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }

    .attendee-item {
      display: flex;
      align-items: center;
      padding: 0.5rem 0;
      border-bottom: 1px solid rgba(161, 172, 184, 0.1);
    }

    .attendee-item:last-child {
      border-bottom: none;
    }

    .attendee-info {
      margin-left: 0.75rem;
    }

    /* Ensure event text is visible (e.g., white) */
    .fc-event .fc-event-title,
    .fc-event .fc-event-time,
    .fc-daygrid-event .fc-event-title,
    .fc-timegrid-event .fc-event-title {
      color: white !important;
    }
  </style>
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/fullcalendar/fullcalendar.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  {{-- Use the new offcanvas-specific JavaScript file --}}
  @vite(['resources/assets/js/app/calendar-events-offcanvas.js'])
@endsection

@section('content')
  @php
    $breadcrumbs = [
      ['name' => __('Calendar'), 'url' => '#']
    ];
  @endphp

  <x-breadcrumb
    :title="__('Calendar')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  >
    <x-slot name="actions">
      <button type="button" class="btn btn-primary" onclick="createNewEvent()">
        <i class="bx bx-plus me-1"></i>{{ __('Add Event') }}
      </button>
    </x-slot>
  </x-breadcrumb>

  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
      <div class="card-body">
        <div id='calendar'></div>
      </div>
    </div>
  </div>

  {{-- Pass data to JavaScript --}}
  <script>
    window.pageData = {
      urls: {
        eventsAjax: @json(route('calendar.events.ajax')),
        eventStore: @json(route('calendar.events.store')),
        eventDetails: @json(route('calendar.events.details.ajax', ['id' => '__ID__'])),
        eventUpdate: @json(route('calendar.events.update', ['id' => '__ID__'])),
        eventDelete: @json(route('calendar.events.destroy', ['id' => '__ID__'])),
        searchClients: @json(route('calendar.events.searchClientsAjax')),
        searchRelatedEntities: @json(route('calendar.events.searchRelatedEntities'))
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
      defaultEventColor: '#6c757d',
      relatedEntityTypes: @json($relatedEntityTypes ?? [])
    };
  </script>

  {{-- VIEW Event Offcanvas (Read Only) --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="viewEventOffcanvas" aria-labelledby="viewEventOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="viewEventOffcanvasLabel">
        <i class="bx bx-calendar me-2"></i>Event Details
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      {{-- Event Title & Type Badge --}}
      <div class="mb-4">
        <h4 id="viewEventTitle" class="mb-2"></h4>
        <span id="viewEventTypeBadge" class="badge bg-label-primary"></span>
        <span id="viewEventStatusBadge" class="badge bg-label-success ms-1"></span>
      </div>

      {{-- Date & Time Section --}}
      <div class="mb-4">
        <h6 class="text-muted mb-2">
          <i class="bx bx-time me-1"></i>{{ __('Schedule') }}
        </h6>
        <div class="card">
          <div class="card-body p-3">
            <div class="row">
              <div class="col-6">
                <small class="text-muted">{{ __('Start') }}</small>
                <div id="viewEventStart" class="fw-medium"></div>
              </div>
              <div class="col-6">
                <small class="text-muted">{{ __('End') }}</small>
                <div id="viewEventEnd" class="fw-medium"></div>
              </div>
            </div>
            <div class="mt-2">
              <span id="viewEventAllDay" class="badge bg-label-info" style="display: none;">
                <i class="bx bx-sun me-1"></i>{{ __('All Day') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      {{-- Related Entity Section --}}
      <div class="mb-4" id="viewRelatedSection" style="display: none;">
        <h6 class="text-muted mb-2">
          <i class="bx bx-link me-1"></i>{{ __('Related To') }}
        </h6>
        <div class="card">
          <div class="card-body p-3">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-sm me-2">
                <span class="avatar-initial bg-label-primary rounded" id="viewRelatedIcon">
                  <i class="bx bx-user"></i>
                </span>
              </div>
              <div>
                <div id="viewRelatedName" class="fw-medium"></div>
                <small id="viewRelatedType" class="text-muted"></small>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Location & Meeting Link Section --}}
      <div class="mb-4" id="viewLocationSection">
        <h6 class="text-muted mb-2">
          <i class="bx bx-map me-1"></i>{{ __('Location & Links') }}
        </h6>
        <div class="card">
          <div class="card-body p-3">
            {{-- Physical Location --}}
            <div id="viewLocationArea" style="display: none;">
              <div class="d-flex align-items-center mb-2">
                <i class="bx bx-map-pin text-muted me-2"></i>
                <span id="viewEventLocation"></span>
              </div>
            </div>
            {{-- Meeting Link --}}
            <div id="viewMeetingLinkArea" style="display: none;">
              <div class="d-flex align-items-center">
                <i class="bx bx-link-external text-muted me-2"></i>
                <a href="#" id="viewEventMeetingLink" target="_blank" class="text-primary">
                  {{ __('Join Meeting') }}
                </a>
              </div>
            </div>
            {{-- No location message --}}
            <div id="viewNoLocationMessage" class="text-muted fst-italic">
              {{ __('No location or meeting link specified') }}
            </div>
          </div>
        </div>
      </div>

      {{-- Attendees Section --}}
      <div class="mb-4" id="viewAttendeesSection">
        <h6 class="text-muted mb-2">
          <i class="bx bx-group me-1"></i>{{ __('Attendees') }} <span id="viewEventAttendeesCount" class="badge bg-label-secondary">0</span>
        </h6>
        <div class="card">
          <div class="card-body p-3">
            <div id="viewEventAttendeesList">
              <div class="text-muted fst-italic">{{ __('No attendees') }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Description Section --}}
      <div class="mb-4" id="viewDescriptionSection">
        <h6 class="text-muted mb-2">
          <i class="bx bx-note me-1"></i>{{ __('Description') }}
        </h6>
        <div class="card">
          <div class="card-body p-3">
            <div id="viewEventDescription" style="white-space: pre-wrap;"></div>
            <div id="viewNoDescriptionMessage" class="text-muted fst-italic" style="display: none;">
              {{ __('No description provided') }}
            </div>
          </div>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="d-grid gap-2 mt-4">
        <button type="button" class="btn btn-primary" id="editEventBtnView">
          <i class="bx bx-edit me-1"></i>{{ __('Edit Event') }}
        </button>
        <button type="button" class="btn btn-outline-danger" id="deleteEventBtnView">
          <i class="bx bx-trash me-1"></i>{{ __('Delete Event') }}
        </button>
      </div>
    </div>
  </div>

  {{-- ADD/EDIT Event Offcanvas (Editable Form) --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="eventOffcanvas" aria-labelledby="eventOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="eventOffcanvasLabel">
        <i class="bx bx-calendar-plus me-2"></i>{{ __('Add Event') }}
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form id="eventForm" onsubmit="return false;">
        <input type="hidden" name="event_id" id="event_id">

        {{-- Event Title --}}
        <div class="mb-3">
          <label for="event_title" class="form-label">{{ __('Event Title') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="event_title" name="event_title">
          <div class="invalid-feedback"></div>
        </div>

        {{-- Event Type --}}
        <div class="mb-3">
          <label for="event_type" class="form-label">{{ __('Event Type') }} <span class="text-danger">*</span></label>
          <select class="form-select select2" id="event_type" name="event_type">
            <option value="" disabled selected>{{ __('Select Type...') }}</option>
            @isset($eventTypes)
              @foreach($eventTypes as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
              @endforeach
            @endisset
          </select>
          <div class="invalid-feedback"></div>
        </div>

        {{-- Related Entity Selection --}}
        <div class="mb-3" id="related_entity_section" style="display: none;">
          <label for="related_type" class="form-label">{{ __('Related To') }}</label>
          <div class="row">
            <div class="col-6">
              <select class="form-select" id="related_type" name="related_type">
                <option value="">{{ __('Select Type...') }}</option>
                @isset($relatedEntityTypes)
                  @foreach($relatedEntityTypes as $typeClass => $typeName)
                    <option value="{{ $typeClass }}">{{ $typeName }}</option>
                  @endforeach
                @endisset
              </select>
            </div>
            <div class="col-6">
              <select class="form-select select2" id="related_id" name="related_id" disabled>
                <option value="">{{ __('Select...') }}</option>
              </select>
            </div>
          </div>
          <div class="invalid-feedback"></div>
        </div>

        {{-- Legacy Client Selection (for backward compatibility) --}}
        <div class="mb-3" id="client_selection_area" style="display: none;">
          <label for="client_id" class="form-label">{{ __('Client') }} <span id="client_required_indicator" class="text-danger" style="display: none;">*</span></label>
          <select class="select2-client-ajax form-select" id="client_id" name="client_id">
            <option value="" selected>{{ __('Search for a client...') }}</option>
          </select>
          <div class="invalid-feedback"></div>
        </div>

        {{-- Date & Time --}}
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="event_start" class="form-label">{{ __('Start Date & Time') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="event_start" name="event_start" placeholder="YYYY-MM-DD HH:MM">
            <div class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="event_end" class="form-label">{{ __('End Date & Time') }}</label>
            <input type="text" class="form-control" id="event_end" name="event_end" placeholder="YYYY-MM-DD HH:MM">
            <div class="invalid-feedback"></div>
          </div>
        </div>

        {{-- All Day Toggle --}}
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="all_day" name="all_day" value="1">
          <label class="form-check-label" for="all_day">{{ __('All Day Event') }}</label>
        </div>

        {{-- Attendees --}}
        <div class="mb-3">
          <label for="attendee_ids" class="form-label">{{ __('Attendees') }}</label>
          <div id="select2-attendee-wrapper">
            <select class="select2-attendees form-select" id="attendee_ids" name="attendee_ids[]" multiple="multiple">
              @isset($users)
                @foreach($users as $user)
                  <option data-avatar="{{ $user->profile_picture ?: asset('assets/img/avatars/default.png') }}" value="{{ $user->id }}">
                    {{ $user->first_name }} {{ $user->last_name }}
                  </option>
                @endforeach
              @endisset
            </select>
          </div>
          <div class="invalid-feedback"></div>
          <small class="text-muted d-block">{{ __('Select employees attending. You will be added automatically.') }}</small>
        </div>

        {{-- Location --}}
        <div class="mb-3">
          <label for="event_location" class="form-label">{{ __('Location / Room') }}</label>
          <input type="text" class="form-control" id="event_location" name="event_location" placeholder="e.g., Conference Room A">
          <div class="invalid-feedback"></div>
        </div>

        {{-- Meeting Link --}}
        <div class="mb-3">
          <label for="meeting_link" class="form-label">{{ __('Meeting Link') }}</label>
          <input type="url" class="form-control" id="meeting_link" name="meeting_link" placeholder="https://zoom.us/j/..." pattern="https://.*">
          <div class="invalid-feedback"></div>
        </div>

        {{-- Description --}}
        <div class="mb-3">
          <label for="event_description" class="form-label">{{ __('Description / Notes') }}</label>
          <textarea class="form-control" id="event_description" name="event_description" rows="3"></textarea>
          <div class="invalid-feedback"></div>
        </div>

        {{-- Event Color --}}
        <div class="mb-3">
          <label class="form-label d-block">{{ __('Event Color') }} <small>({{ __('Optional') }})</small></label>
          <div class="d-flex flex-wrap color-radio-group">
            @php $colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1']; @endphp
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="color" id="color_default" value="" checked>
              <label class="form-check-label" for="color_default" title="{{ __('Use Event Type Color') }}" style="border: 1px dashed #ccc; background: linear-gradient(to top right, #eee 48%, transparent 50%, transparent 52%, #eee 54%);">
                <span class="visually-hidden">{{ __('Default') }}</span>
              </label>
            </div>
            @foreach($colors as $index => $colorValue)
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="color" id="color_{{ $index }}" value="{{ $colorValue }}">
                <label class="form-check-label" for="color_{{ $index }}" style="background-color: {{ $colorValue }};" title="{{ $colorValue }}"></label>
              </div>
            @endforeach
          </div>
          <div class="invalid-feedback"></div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-grid gap-2 mt-4">
          <button type="button" class="btn btn-primary" id="saveEventBtn">
            <i class="bx bx-save me-1"></i>{{ __('Save Event') }}
          </button>
          <button type="button" class="btn btn-danger d-none" id="deleteEventBtn">
            <i class="bx bx-trash me-1"></i>{{ __('Delete Event') }}
          </button>
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">
            <i class="bx bx-x me-1"></i>{{ __('Cancel') }}
          </button>
        </div>
      </form>
    </div>
  </div>
@endsection
