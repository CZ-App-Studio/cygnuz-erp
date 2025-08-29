@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Create Announcement'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/quill/typography.scss',
    'resources/assets/vendor/libs/quill/katex.scss',
    'resources/assets/vendor/libs/quill/editor.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/quill/katex.js',
    'resources/assets/vendor/libs/quill/quill.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js'
  ])
@endsection

@section('page-script')
  @vite([
    'Modules/Announcement/resources/assets/js/announcement-create.js'
  ])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Create Announcement')"
      :breadcrumbs="[
        ['name' => __('Communication'), 'url' => ''],
        ['name' => __('Announcements'), 'url' => route('announcements.index')],
        ['name' => __('Create'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    <form action="{{ route('announcements.store') }}" method="POST" enctype="multipart/form-data" id="announcementForm">
      @csrf
      
      <div class="row">
        <!-- Left Column -->
        <div class="col-12 col-lg-8">
          <!-- Basic Information -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="card-title mb-0">{{ __('Basic Information') }}</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label" for="title">{{ __('Title') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" 
                       placeholder="{{ __('Enter announcement title') }}" value="{{ old('title') }}" required>
                @error('title')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label class="form-label" for="description">{{ __('Short Description') }} <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" 
                          rows="3" placeholder="{{ __('Brief description of the announcement') }}" required>{{ old('description') }}</textarea>
                @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label class="form-label" for="content">{{ __('Content') }}</label>
                <div id="editor-container">
                  <div id="quill-editor" style="height: 300px;">{!! old('content') !!}</div>
                </div>
                <input type="hidden" name="content" id="content" value="{{ old('content') }}">
                @error('content')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="type">{{ __('Type') }} <span class="text-danger">*</span></label>
                  <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                    <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>{{ __('General') }}</option>
                    <option value="important" {{ old('type') == 'important' ? 'selected' : '' }}>{{ __('Important') }}</option>
                    <option value="event" {{ old('type') == 'event' ? 'selected' : '' }}>{{ __('Event') }}</option>
                    <option value="policy" {{ old('type') == 'policy' ? 'selected' : '' }}>{{ __('Policy') }}</option>
                    <option value="update" {{ old('type') == 'update' ? 'selected' : '' }}>{{ __('Update') }}</option>
                  </select>
                  @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label" for="priority">{{ __('Priority') }} <span class="text-danger">*</span></label>
                  <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                    <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>{{ __('Normal') }}</option>
                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                  </select>
                  @error('priority')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="attachment">{{ __('Attachment') }}</label>
                <input type="file" class="form-control @error('attachment') is-invalid @enderror" id="attachment" name="attachment">
                <div class="form-text">{{ __('Max file size: 10MB. Supported formats: PDF, DOC, DOCX, JPG, PNG') }}</div>
                @error('attachment')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <!-- Target Audience -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="card-title mb-0">{{ __('Target Audience') }}</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label" for="target_audience">{{ __('Select Target Audience') }} <span class="text-danger">*</span></label>
                <select class="form-select @error('target_audience') is-invalid @enderror" id="target_audience" name="target_audience" required>
                  <option value="all" {{ old('target_audience') == 'all' ? 'selected' : '' }}>{{ __('All Employees') }}</option>
                  <option value="departments" {{ old('target_audience') == 'departments' ? 'selected' : '' }}>{{ __('Specific Departments') }}</option>
                  <option value="teams" {{ old('target_audience') == 'teams' ? 'selected' : '' }}>{{ __('Specific Teams') }}</option>
                  <option value="specific_users" {{ old('target_audience') == 'specific_users' ? 'selected' : '' }}>{{ __('Specific Users') }}</option>
                </select>
                @error('target_audience')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Departments Selection -->
              <div class="mb-3 audience-selection" id="departments-selection" style="display: none;">
                <label class="form-label" for="departments">{{ __('Select Departments') }}</label>
                <select class="form-select select2 @error('departments') is-invalid @enderror" id="departments" name="departments[]" multiple>
                  @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ in_array($department->id, old('departments', [])) ? 'selected' : '' }}>
                      {{ $department->name }}
                    </option>
                  @endforeach
                </select>
                @error('departments')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Teams Selection -->
              <div class="mb-3 audience-selection" id="teams-selection" style="display: none;">
                <label class="form-label" for="teams">{{ __('Select Teams') }}</label>
                <select class="form-select select2 @error('teams') is-invalid @enderror" id="teams" name="teams[]" multiple>
                  @foreach($teams as $team)
                    <option value="{{ $team->id }}" {{ in_array($team->id, old('teams', [])) ? 'selected' : '' }}>
                      {{ $team->name }}
                    </option>
                  @endforeach
                </select>
                @error('teams')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Users Selection -->
              <div class="mb-3 audience-selection" id="users-selection" style="display: none;">
                <label class="form-label" for="users">{{ __('Select Users') }}</label>
                <select class="form-select select2 @error('users') is-invalid @enderror" id="users" name="users[]" multiple>
                  @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ in_array($user->id, old('users', [])) ? 'selected' : '' }}>
                      {{ $user->name }} ({{ $user->email }})
                    </option>
                  @endforeach
                </select>
                @error('users')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-12 col-lg-4">
          <!-- Publishing Options -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="card-title mb-0">{{ __('Publishing Options') }}</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label" for="status">{{ __('Status') }} <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                  <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                  <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>{{ __('Publish Now') }}</option>
                  <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>{{ __('Schedule') }}</option>
                </select>
                @error('status')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label class="form-label" for="publish_date">{{ __('Publish Date') }}</label>
                <input type="text" class="form-control flatpickr-datetime @error('publish_date') is-invalid @enderror" 
                       id="publish_date" name="publish_date" placeholder="{{ __('Select date and time') }}" 
                       value="{{ old('publish_date') }}">
                @error('publish_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label class="form-label" for="expiry_date">{{ __('Expiry Date') }}</label>
                <input type="text" class="form-control flatpickr-datetime @error('expiry_date') is-invalid @enderror" 
                       id="expiry_date" name="expiry_date" placeholder="{{ __('Select date and time') }}" 
                       value="{{ old('expiry_date') }}">
                @error('expiry_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }}>
                <label class="form-check-label" for="is_pinned">{{ __('Pin this announcement') }}</label>
              </div>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="requires_acknowledgment" name="requires_acknowledgment" value="1" {{ old('requires_acknowledgment') ? 'checked' : '' }}>
                <label class="form-check-label" for="requires_acknowledgment">{{ __('Require acknowledgment') }}</label>
              </div>
            </div>
          </div>

          <!-- Notification Options -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="card-title mb-0">{{ __('Notification Options') }}</h5>
            </div>
            <div class="card-body">
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification" value="1" {{ old('send_notification', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="send_notification">{{ __('Send in-app notification') }}</label>
              </div>

              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="send_email" name="send_email" value="1" {{ old('send_email') ? 'checked' : '' }}>
                <label class="form-check-label" for="send_email">{{ __('Send email notification') }}</label>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="card">
            <div class="card-body">
              <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="bx bx-save me-1"></i> {{ __('Create Announcement') }}
              </button>
              <a href="{{ route('announcements.index') }}" class="btn btn-label-secondary w-100">
                <i class="bx bx-x me-1"></i> {{ __('Cancel') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
@endsection