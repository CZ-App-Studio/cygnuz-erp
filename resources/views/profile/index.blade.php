@php
use Illuminate\Support\Facades\Storage;
@endphp
@extends('layouts.layoutMaster')

@section('title', __('My Profile'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('page-style')
<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    border-radius: 0.5rem;
    color: white;
    margin-bottom: 2rem;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #999;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile-stats {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
}

.profile-stat {
    text-align: center;
}

.profile-stat-value {
    font-size: 1.5rem;
    font-weight: bold;
}

.profile-stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.nav-tabs .nav-link {
    color: #697a8d;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.75rem 1.5rem;
}

.nav-tabs .nav-link.active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: transparent;
}

.session-card {
    border-left: 3px solid #667eea;
    transition: all 0.3s ease;
}

.session-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.session-card.current {
    border-left-color: #48bb78;
    background: #f0fdf4;
}
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="position-relative">
                    @if($user->profile_picture)
                        <img src="{{ Storage::url($user->profile_picture) }}" alt="{{ $user->name }}" class="profile-avatar">
                    @else
                        <div class="profile-avatar-placeholder">
                            <i class="bx bx-user"></i>
                        </div>
                    @endif
                    <button type="button" class="btn btn-sm btn-light rounded-circle position-absolute bottom-0 end-0"
                            data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                        <i class="bx bx-camera"></i>
                    </button>
                </div>
            </div>
            <div class="col">
                <h2 class="mb-1">{{ $user->name }}</h2>
                <p class="mb-2">{{ $user->email }}</p>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($user->roles as $role)
                        <span class="badge bg-white text-primary">{{ ucfirst($role->name) }}</span>
                    @endforeach
                </div>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-value">{{ $user->created_at->diffInDays(now()) }}</div>
                        <div class="profile-stat-label">{{ __('Days Active') }}</div>
                    </div>
                    @if(isset($user->team))
                    <div class="profile-stat">
                        <div class="profile-stat-value">{{ $user->team->name ?? '-' }}</div>
                        <div class="profile-stat-label">{{ __('Team') }}</div>
                    </div>
                    @endif
                    @if(isset($user->designation))
                    <div class="profile-stat">
                        <div class="profile-stat-value">{{ $user->designation->name ?? '-' }}</div>
                        <div class="profile-stat-label">{{ __('Designation') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic-info" role="tab">
                        <i class="bx bx-user me-1"></i> {{ __('Basic Information') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#security" role="tab">
                        <i class="bx bx-lock me-1"></i> {{ __('Security') }}
                    </button>
                </li>
                @if($has2FA || app(\App\Services\AddonService\AddonService::class)->isAddonEnabled('TwoFactorAuth'))
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#two-factor" role="tab">
                        <i class="bx bx-shield me-1"></i> {{ __('Two-Factor Auth') }}
                    </button>
                </li>
                @endif
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#sessions" role="tab">
                        <i class="bx bx-devices me-1"></i> {{ __('Sessions') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#notifications" role="tab">
                        <i class="bx bx-bell me-1"></i> {{ __('Notifications') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4">
                <!-- Basic Information Tab -->
                <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                    @include('profile.partials.basic-info')
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    @include('profile.partials.security')
                </div>

                <!-- Two-Factor Authentication Tab -->
                @if($has2FA || app(\App\Services\AddonService\AddonService::class)->isAddonEnabled('TwoFactorAuth'))
                <div class="tab-pane fade" id="two-factor" role="tabpanel">
                    @include('profile.partials.two-factor')
                </div>
                @endif

                <!-- Sessions Tab -->
                <div class="tab-pane fade" id="sessions" role="tabpanel">
                    @include('profile.partials.sessions')
                </div>

                <!-- Notifications Tab -->
                <div class="tab-pane fade" id="notifications" role="tabpanel">
                    @include('profile.partials.notifications')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePictureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Change Profile Picture') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profilePictureForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">{{ __('Choose Image') }}</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" required>
                        <div class="form-text">{{ __('Maximum file size: 2MB. Supported formats: JPEG, PNG, GIF') }}</div>
                    </div>
                    <div id="imagePreview" class="text-center mb-3" style="display: none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 300px; border-radius: 0.5rem;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                @if($user->profile_picture)
                <button type="button" class="btn btn-outline-danger me-auto" id="removeProfilePicture">
                    <i class="bx bx-trash me-1"></i> {{ __('Remove Picture') }}
                </button>
                @endif
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="uploadProfilePicture">
                    <i class="bx bx-upload me-1"></i> {{ __('Upload') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages/profile.js'])
@endsection
