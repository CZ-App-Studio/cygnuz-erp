@props([
    'user',
    'showCode' => true,
    'linkRoute' => 'hrcore.employees.show',
    'avatarSize' => 'sm'
])

<div class="d-flex justify-content-start align-items-center user-name">
    <div class="avatar-wrapper">
        <div class="avatar avatar-{{ $avatarSize }} me-3">
            @if($user->hasProfilePicture() || $user->profile_picture)
                <img src="{{ $user->getProfilePicture() }}" alt="Avatar" class="rounded-circle" />
            @else
                <span class="avatar-initial rounded-circle bg-label-primary">{{ $user->getInitials() }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex flex-column">
        @if(\Illuminate\Support\Facades\Route::has($linkRoute))
            <a href="{{ route($linkRoute, $user->id) }}"
               class="text-heading text-truncate">
                <span class="fw-medium">{{ $user->getFullName() }}</span>
            </a>
        @else
            <span class="text-heading text-truncate fw-medium">{{ $user->getFullName() }}</span>
        @endif
        @if($showCode && isset($user->code))
            <small class="text-muted">{{ $user->code }}</small>
        @elseif(isset($user->email))
            <small class="text-muted">{{ $user->email }}</small>
        @elseif(isset($user->email_primary))
            <small class="text-muted">{{ $user->email_primary }}</small>
        @else
            <small class="text-muted">-</small>
        @endif
    </div>
</div>
