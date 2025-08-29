{{-- Real-time Utilities Panel --}}
<div id="utilities-panel" class="utilities-panel" style="display: none;">
    <div class="utilities-panel-backdrop"></div>
    <div class="utilities-panel-content">
        <div class="utilities-panel-header">
            <h5 class="utilities-panel-title">
                <i class="bx bx-cog me-2"></i>
                {{ __('Real-time Utilities') }}
            </h5>
            <button type="button" class="btn-close-panel" id="closeUtilitiesPanel">
                <i class="bx bx-x"></i>
            </button>
        </div>

        <div class="utilities-panel-body">
            {{-- Attendance Section --}}
            <div class="utilities-section" id="attendanceSection">
                <div class="section-header">
                    <i class="bx bx-time-five text-primary me-2"></i>
                    <h6 class="section-title mb-0">{{ __('Attendance Status') }}</h6>
                </div>

                <div class="section-content" id="attendanceContent">
                    <div class="status-display">
                        <div class="status-badge" id="statusBadge">
                            <div class="status-dot"></div>
                            <span id="statusText">{{ __('Not checked in') }}</span>
                        </div>

                        <div class="time-display mt-2" id="timeDisplay" style="display: none;">
                            <div class="time-item">
                                <small class="text-muted">{{ __('Check-in') }}:</small>
                                <span id="checkInTime">--:--</span>
                            </div>
                            <div class="time-item">
                                <small class="text-muted">{{ __('Working') }}:</small>
                                <span id="workingHours">00:00</span>
                            </div>
                        </div>

                        <div class="quick-actions mt-3" id="quickActions">
                            <a href="{{ route('hrcore.attendance.web-attendance') }}" class="btn btn-sm btn-primary w-100" id="attendanceAction">
                                <span id="actionText">{{ __('Go to Attendance') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Links Section --}}
            <div class="utilities-section">
                <div class="section-header">
                    <i class="bx bx-link text-info me-2"></i>
                    <h6 class="section-title mb-0">{{ __('Quick Links') }}</h6>
                </div>
                <div class="section-content">
                    <div class="quick-links">
                        <a href="{{ route('hrcore.attendance.index') }}" class="quick-link-item">
                            <i class="bx bx-list-ul"></i>
                            <span>{{ __('Attendance Records') }}</span>
                        </a>
                        <a href="{{ route('hrcore.leaves.index') }}" class="quick-link-item">
                            <i class="bx bx-calendar-x"></i>
                            <span>{{ __('Leave Requests') }}</span>
                        </a>
                        <a href="{{ route('hrcore.employees.my-profile') }}" class="quick-link-item">
                            <i class="bx bx-user"></i>
                            <span>{{ __('My Profile') }}</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- System Info Section --}}
            <div class="utilities-section">
                <div class="section-header">
                    <i class="bx bx-info-circle text-success me-2"></i>
                    <h6 class="section-title mb-0">{{ __('System Info') }}</h6>
                </div>
                <div class="section-content">
                    <div class="system-info">
                        <div class="info-item">
                            <small class="text-muted">{{ __('Current Time') }}:</small>
                            <span id="currentTime">{{ now()->format('h:i A') }}</span>
                        </div>
                        <div class="info-item">
                            <small class="text-muted">{{ __('Date') }}:</small>
                            <span>{{ now()->format('M d, Y') }}</span>
                        </div>
                        <div class="info-item">
                            <small class="text-muted">{{ __('User') }}:</small>
                            <span>{{ auth()->user()->getFullName() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Utilities Panel Toggle Button --}}
<div class="utilities-panel-toggle" id="utilitiesPanelToggle">
    <div class="toggle-inner">
        <i class="bx bx-cog"></i>
        <div class="notification-badge" id="attendanceNotification" style="display: none;"></div>
    </div>
</div>

<style>
/* Utilities Panel Styles */
.utilities-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    font-family: inherit;
}

.utilities-panel-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(67, 89, 113, 0.6);
    backdrop-filter: blur(4px);
}

.utilities-panel-content {
    position: absolute;
    top: 0;
    right: 0;
    width: 400px;
    height: 100%;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    box-shadow: -4px 0 20px rgba(67, 89, 113, 0.15);
    transform: translateX(100%);
    transition: transform 0.3s ease;
    overflow-y: auto;
}

.utilities-panel.show .utilities-panel-content {
    transform: translateX(0);
}

.utilities-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-gray-100);
}

.utilities-panel-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--bs-heading-color);
}

.btn-close-panel {
    background: none;
    border: none;
    color: var(--bs-body-color);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-close-panel:hover {
    background: var(--bs-gray-200);
    color: var(--bs-heading-color);
}

.utilities-panel-body {
    padding: 0;
}

.utilities-section {
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.utilities-section:last-child {
    border-bottom: none;
}

.section-header {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem 0.75rem;
    background: var(--bs-gray-50);
}

.section-title {
    color: var(--bs-heading-color);
    font-weight: 600;
}

.section-content {
    padding: 0.75rem 1.5rem 1.5rem;
}

/* Attendance Status Styles */
.status-display {
    text-align: center;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    background: var(--bs-gray-200);
    color: var(--bs-body-color);
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.checked-in {
    background: linear-gradient(135deg, #71dd37, #5cb85c);
    color: white;
}

.status-badge.checked-out {
    background: linear-gradient(135deg, #ffab00, #ff8c00);
    color: white;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--bs-body-color);
    margin-right: 0.5rem;
}

.status-badge.checked-in .status-dot {
    background: #fff;
    animation: pulse 2s infinite;
}

.status-badge.checked-out .status-dot {
    background: #fff;
}

.time-display {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

.time-item {
    flex: 1;
    text-align: center;
    padding: 0.75rem;
    background: var(--bs-gray-100);
    border-radius: 8px;
}

.time-item span {
    display: block;
    font-weight: 600;
    color: var(--bs-heading-color);
    margin-top: 0.25rem;
}

/* Quick Links Styles */
.quick-links {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quick-link-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    background: var(--bs-gray-100);
    color: var(--bs-body-color);
    text-decoration: none;
    transition: all 0.2s;
}

.quick-link-item:hover {
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
    text-decoration: none;
}

.quick-link-item i {
    font-size: 1.125rem;
    margin-right: 0.75rem;
    width: 20px;
}

/* System Info Styles */
.system-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.info-item span {
    font-weight: 500;
    color: var(--bs-heading-color);
}

/* Toggle Button Styles */
.utilities-panel-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9998;
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #696cff, #5a67d8);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(105, 108, 255, 0.4);
    transition: all 0.3s ease;
}

.utilities-panel-toggle:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 8px 25px rgba(105, 108, 255, 0.6);
}

.toggle-inner {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.notification-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 12px;
    height: 12px;
    background: #ff3e1d;
    border: 2px solid white;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .utilities-panel-content {
        width: 100%;
    }

    .utilities-panel-toggle {
        bottom: 15px;
        right: 15px;
        width: 50px;
        height: 50px;
    }

    .toggle-inner {
        font-size: 1.25rem;
    }

    .time-display {
        flex-direction: column;
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .utilities-panel-toggle {
        display: none;
    }
}

/* Animation for checked in status */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
    }
}

/* Additional mobile adjustments */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const panel = document.getElementById('utilities-panel');
    const toggleBtn = document.getElementById('utilitiesPanelToggle');
    const closeBtn = document.getElementById('closeUtilitiesPanel');
    const backdrop = panel.querySelector('.utilities-panel-backdrop');
    const attendanceSection = document.getElementById('attendanceSection');
    const notification = document.getElementById('attendanceNotification');

    let updateInterval;
    let clockInterval;

    // Panel toggle functionality
    function showPanel() {
        panel.style.display = 'block';
        setTimeout(() => panel.classList.add('show'), 10);
        document.body.style.overflow = 'hidden';
        checkAttendanceStatus();
    }

    function hidePanel() {
        panel.classList.remove('show');
        setTimeout(() => {
            panel.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    // Event listeners
    toggleBtn.addEventListener('click', showPanel);
    closeBtn.addEventListener('click', hidePanel);
    backdrop.addEventListener('click', hidePanel);

    // Escape key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && panel.classList.contains('show')) {
            hidePanel();
        }
    });

    // Check attendance status
    function checkAttendanceStatus() {
        fetch('{{ route("hrcore.attendance.global-status") }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateAttendanceDisplay(data.data);
                    updateNotification(data.data);
                }
            })
            .catch(error => {
                console.error('Error checking attendance status:', error);
            });
    }

    // Update attendance display
    function updateAttendanceDisplay(data) {
        const statusBadge = document.getElementById('statusBadge');
        const statusText = document.getElementById('statusText');
        const timeDisplay = document.getElementById('timeDisplay');
        const checkInTimeEl = document.getElementById('checkInTime');
        const workingHoursEl = document.getElementById('workingHours');
        const actionBtn = document.getElementById('attendanceAction');
        const actionText = document.getElementById('actionText');

        // Reset classes
        statusBadge.className = 'status-badge';

        if (data.isCheckedIn) {
            statusBadge.classList.add('checked-in');
            statusText.textContent = '{{ __("Checked In") }}';
            timeDisplay.style.display = 'flex';
            checkInTimeEl.textContent = data.checkInTime || '--:--';
            workingHoursEl.textContent = data.workingHours || '00:00';
            actionText.innerHTML = '<i class="bx bx-log-out me-1"></i>' + '{{ __("Check Out") }}';
        } else {
            statusText.textContent = '{{ __("Not Checked In") }}';
            timeDisplay.style.display = 'none';
            actionText.innerHTML = '<i class="bx bx-log-in me-1"></i>' + '{{ __("Check In") }}';
        }
    }

    // Update notification badge
    function updateNotification(data) {
        if (data.isCheckedIn) {
            notification.style.display = 'block';
        } else {
            notification.style.display = 'none';
        }
    }

    // Update current time
    function updateCurrentTime() {
        const timeEl = document.getElementById('currentTime');
        const now = new Date();
        timeEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    // Initialize
    checkAttendanceStatus();
    updateCurrentTime();

    // Set up intervals
    updateInterval = setInterval(checkAttendanceStatus, 300000); // Every 5 minutes
    clockInterval = setInterval(updateCurrentTime, 60000); // Every minute

    // Expose global functions
    window.refreshUtilitiesPanel = checkAttendanceStatus;

    // Update when attendance actions are performed
    window.addEventListener('attendance-updated', function() {
        setTimeout(checkAttendanceStatus, 1000);
    });
});
</script>
