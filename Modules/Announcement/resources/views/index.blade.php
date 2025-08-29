@php
  $configData = Helper::appClasses();
  use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Announcements'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/quill/typography.scss',
    'resources/assets/vendor/libs/quill/katex.scss',
    'resources/assets/vendor/libs/quill/editor.scss'
  ])
@endsection

<!-- Page Styles -->
@section('page-style')
  @vite(['Modules/Announcement/resources/assets/css/announcement.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/quill/katex.js',
    'resources/assets/vendor/libs/quill/quill.js'
  ])
@endsection

@section('page-script')
  @vite([
    'Modules/Announcement/resources/assets/js/announcement-index.js'
  ])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Announcements')"
      :breadcrumbs="[
        ['name' => __('Communication'), 'url' => ''],
        ['name' => __('Announcements'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div class="card-info">
                <p class="card-text">{{ __('Total') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2">{{ $stats['total'] }}</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-primary rounded-pill p-2">
                  <i class="bx bx-message-square-detail bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div class="card-info">
                <p class="card-text">{{ __('Published') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2">{{ $stats['published'] }}</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-success rounded-pill p-2">
                  <i class="bx bx-check-circle bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div class="card-info">
                <p class="card-text">{{ __('Scheduled') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2">{{ $stats['scheduled'] }}</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-warning rounded-pill p-2">
                  <i class="bx bx-time-five bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div class="card-info">
                <p class="card-text">{{ __('Pinned') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2">{{ $stats['pinned'] }}</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-info rounded-pill p-2">
                  <i class="bx bx-pin bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Announcements List -->
    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">{{ __('Announcements List') }}</h5>
        <div class="d-flex justify-content-between align-items-center row pt-3 gap-3 gap-md-0">
          <div class="col-md-4">
            <select id="filter-status" class="form-select">
              <option value="">{{ __('All Status') }}</option>
              <option value="draft">{{ __('Draft') }}</option>
              <option value="published">{{ __('Published') }}</option>
              <option value="scheduled">{{ __('Scheduled') }}</option>
              <option value="expired">{{ __('Expired') }}</option>
              <option value="archived">{{ __('Archived') }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <select id="filter-priority" class="form-select">
              <option value="">{{ __('All Priorities') }}</option>
              <option value="low">{{ __('Low') }}</option>
              <option value="normal">{{ __('Normal') }}</option>
              <option value="high">{{ __('High') }}</option>
              <option value="urgent">{{ __('Urgent') }}</option>
            </select>
          </div>
          <div class="col-md-4">
            @can('announcements.create')
              <button type="button" class="btn btn-primary float-end" onclick="window.location.href='{{ route('announcements.create') }}'">
                <i class="bx bx-plus me-1"></i> {{ __('Add Announcement') }}
              </button>
            @endcan
          </div>
        </div>
      </div>
      
      <div class="card-datatable table-responsive">
        <table class="datatables-announcements table border-top" id="announcements-table">
          <thead>
            <tr>
              <th>{{ __('Title') }}</th>
              <th>{{ __('Type') }}</th>
              <th>{{ __('Priority') }}</th>
              <th>{{ __('Target') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Publish Date') }}</th>
              <th>{{ __('Read %') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($announcements as $announcement)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    @if($announcement->is_pinned)
                      <i class="bx bx-pin text-primary me-2"></i>
                    @endif
                    <div>
                      <span class="fw-medium">{{ $announcement->title }}</span>
                      <small class="text-muted d-block">{{ Str::limit($announcement->description, 50) }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-{{ $announcement->type === 'important' ? 'danger' : ($announcement->type === 'event' ? 'info' : 'secondary') }}">
                    {{ ucfirst($announcement->type) }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-label-{{ $announcement->priority === 'urgent' ? 'danger' : ($announcement->priority === 'high' ? 'warning' : ($announcement->priority === 'normal' ? 'primary' : 'secondary')) }}">
                    {{ ucfirst($announcement->priority) }}
                  </span>
                </td>
                <td>
                  @if($announcement->target_audience === 'all')
                    <span class="badge bg-label-primary">{{ __('All Employees') }}</span>
                  @elseif($announcement->target_audience === 'departments')
                    <span class="badge bg-label-info">{{ $announcement->departments->count() }} {{ __('Departments') }}</span>
                  @elseif($announcement->target_audience === 'teams')
                    <span class="badge bg-label-warning">{{ $announcement->teams->count() }} {{ __('Teams') }}</span>
                  @else
                    <span class="badge bg-label-secondary">{{ $announcement->users->count() }} {{ __('Users') }}</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-label-{{ $announcement->status === 'published' ? 'success' : ($announcement->status === 'scheduled' ? 'warning' : ($announcement->status === 'draft' ? 'secondary' : 'danger')) }}">
                    {{ ucfirst($announcement->status) }}
                  </span>
                </td>
                <td data-order="{{ $announcement->publish_date ? $announcement->publish_date->format('Y-m-d') : '' }}">
                  {{ $announcement->publish_date ? $announcement->publish_date->format('M d, Y') : '-' }}
                </td>
                <td data-order="{{ $announcement->read_percentage }}">
                  <div class="d-flex align-items-center">
                    <div class="progress w-100 me-2" style="height: 6px;">
                      <div class="progress-bar" style="width: {{ $announcement->read_percentage }}%"></div>
                    </div>
                    <small>{{ $announcement->read_percentage }}%</small>
                  </div>
                </td>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                      <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('announcements.show', $announcement->id) }}">
                        <i class="bx bx-show me-1"></i> {{ __('View') }}
                      </a>
                      @can('announcements.edit')
                        <a class="dropdown-item" href="{{ route('announcements.edit', $announcement->id) }}">
                          <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
                        </a>
                      @endcan
                      @can('announcements.pin')
                        <a class="dropdown-item toggle-pin" href="javascript:void(0);" data-id="{{ $announcement->id }}">
                          <i class="bx {{ $announcement->is_pinned ? 'bx-pin' : 'bxs-pin' }} me-1"></i>
                          {{ $announcement->is_pinned ? __('Unpin') : __('Pin') }}
                        </a>
                      @endcan
                      @can('announcements.delete')
                        <form action="{{ route('announcements.destroy', $announcement->id) }}" method="POST" class="d-inline delete-form">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger">
                            <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
                          </button>
                        </form>
                      @endcan
                    </div>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection