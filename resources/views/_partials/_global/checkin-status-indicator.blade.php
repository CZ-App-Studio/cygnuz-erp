{{-- Global Check-in Status Indicator --}}
<div id="globalCheckinIndicator" class="global-checkin-indicator" style="display: none;">
    <div class="checkin-card">
        <div class="checkin-header">
            <div class="status-dot"></div>
            <span class="status-text">{{ __('Checked In') }}</span>
            <button type="button" class="btn-close" id="closeCheckinIndicator" aria-label="Close">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <div class="checkin-details">
            <div class="time-info">
                <small class="text-muted">{{ __('Since') }}: </small>
                <span id="globalCheckInTime">--:--</span>
            </div>
            <div class="working-hours">
                <small class="text-muted">{{ __('Working') }}: </small>
                <span id="globalWorkingHours">00:00</span>
            </div>
        </div>
        <div class="checkin-actions">
            <a href="{{ route('hrcore.attendance.web-attendance') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-log-out me-1"></i>
                {{ __('Check Out') }}
            </a>
        </div>
    </div>
</div>

{{-- Minimized Check-in Icon --}}
<div id="minimizedCheckinIcon" class="minimized-checkin-icon" style="display: none;" title="{{ __('You are checked in - Click to view details') }}">
    <div class="mini-status-dot"></div>
    <i class="bx bx-time-five"></i>
</div>

<style>
.global-checkin-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 280px;
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.global-checkin-indicator.slide-out {
    animation: slideOutRight 0.3s ease-in;
}

.checkin-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.checkin-header {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.status-dot {
    width: 10px;
    height: 10px;
    background: #4CAF50;
    border-radius: 50%;
    margin-right: 8px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
    }
}

.status-text {
    font-weight: 600;
    flex: 1;
}

.btn-close {
    background: none;
    border: none;
    color: white;
    font-size: 16px;
    cursor: pointer;
    padding: 2px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.btn-close:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.checkin-details {
    margin-bottom: 12px;
    font-size: 14px;
}

.time-info, .working-hours {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
}

.checkin-actions .btn {
    width: 100%;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 13px;
    padding: 6px 12px;
    transition: all 0.2s;
}

.checkin-actions .btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
    transform: translateY(-1px);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .global-checkin-indicator {
        bottom: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .checkin-card {
        padding: 12px;
    }
}

/* Hide on very small screens */
@media (max-width: 480px) {
    .global-checkin-indicator {
        display: none !important;
    }
    .minimized-checkin-icon {
        display: none !important;
    }
}

/* Minimized Check-in Icon */
.minimized-checkin-icon {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    animation: slideInUp 0.3s ease-out;
    position: relative;
}

.minimized-checkin-icon:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
}

.minimized-checkin-icon .mini-status-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    background: #4CAF50;
    border-radius: 50%;
    border: 2px solid white;
    animation: pulse 2s infinite;
}

@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideOutDown {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(100%);
        opacity: 0;
    }
}

.minimized-checkin-icon.slide-out {
    animation: slideOutDown 0.3s ease-in;
}

/* Mobile responsive for minimized icon */
@media (max-width: 768px) {
    .minimized-checkin-icon {
        bottom: 15px;
        right: 15px;
        width: 45px;
        height: 45px;
        font-size: 18px;
    }
    
    .minimized-checkin-icon .mini-status-dot {
        top: 6px;
        right: 6px;
        width: 6px;
        height: 6px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const indicator = document.getElementById('globalCheckinIndicator');
    const minimizedIcon = document.getElementById('minimizedCheckinIcon');
    const closeBtn = document.getElementById('closeCheckinIndicator');
    let updateInterval;
    let isVisible = false;
    let isMinimized = false;
    let shouldShow = false;

    // Check if indicator should be shown
    function checkGlobalStatus() {
        fetch('{{ route("hrcore.attendance.global-status") }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    shouldShow = data.data.showIndicator;
                    
                    if (shouldShow) {
                        if (!isVisible && !isMinimized) {
                            showIndicator(data.data);
                        } else if (isVisible) {
                            updateIndicator(data.data);
                        } else if (isMinimized) {
                            updateMinimizedData(data.data);
                        }
                    } else {
                        hideAll();
                    }
                }
            })
            .catch(error => {
                console.error('Error checking global status:', error);
            });
    }

    // Show the indicator
    function showIndicator(data) {
        hideMinimizedIcon();
        updateIndicatorData(data);
        indicator.style.display = 'block';
        isVisible = true;
        isMinimized = false;
        
        // Update working hours every minute
        if (!updateInterval) {
            updateInterval = setInterval(updateWorkingHours, 60000);
        }
    }

    // Hide the indicator and show minimized icon
    function minimizeIndicator() {
        indicator.classList.add('slide-out');
        setTimeout(() => {
            indicator.style.display = 'none';
            indicator.classList.remove('slide-out');
            isVisible = false;
            
            if (shouldShow) {
                showMinimizedIcon();
            }
        }, 300);
    }

    // Show minimized icon
    function showMinimizedIcon() {
        minimizedIcon.style.display = 'flex';
        isMinimized = true;
    }

    // Hide minimized icon
    function hideMinimizedIcon() {
        if (isMinimized) {
            minimizedIcon.classList.add('slide-out');
            setTimeout(() => {
                minimizedIcon.style.display = 'none';
                minimizedIcon.classList.remove('slide-out');
                isMinimized = false;
            }, 300);
        }
    }

    // Hide all elements
    function hideAll() {
        hideMinimizedIcon();
        if (isVisible) {
            indicator.classList.add('slide-out');
            setTimeout(() => {
                indicator.style.display = 'none';
                indicator.classList.remove('slide-out');
                isVisible = false;
            }, 300);
        }
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }

    // Update indicator data
    function updateIndicator(data) {
        updateIndicatorData(data);
    }

    // Update minimized data (for tooltip updates if needed)
    function updateMinimizedData(data) {
        if (data.checkInTime && data.workingHours) {
            minimizedIcon.title = `{{ __('Checked in at') }} ${data.checkInTime} - {{ __('Working') }}: ${data.workingHours}`;
        }
    }

    // Update indicator with new data
    function updateIndicatorData(data) {
        if (data.checkInTime) {
            document.getElementById('globalCheckInTime').textContent = data.checkInTime;
        }
        if (data.workingHours) {
            document.getElementById('globalWorkingHours').textContent = data.workingHours;
        }
    }

    // Update working hours display
    function updateWorkingHours() {
        // This could be enhanced to calculate real-time working hours
        // For now, we'll just refresh the status
        checkGlobalStatus();
    }

    // Close button event (minimize instead of hide)
    closeBtn.addEventListener('click', function() {
        minimizeIndicator();
    });

    // Minimized icon click event (restore full indicator)
    minimizedIcon.addEventListener('click', function() {
        if (shouldShow) {
            // Refetch latest data and show
            checkGlobalStatus();
            if (shouldShow) {
                const currentData = {
                    checkInTime: document.getElementById('globalCheckInTime').textContent,
                    workingHours: document.getElementById('globalWorkingHours').textContent
                };
                showIndicator(currentData);
            }
        }
    });

    // Expose functions globally
    window.checkGlobalStatus = checkGlobalStatus;
    window.resetCheckinIndicator = function() {
        // Reset and show full indicator when action performed
        if (shouldShow && isMinimized) {
            checkGlobalStatus();
        }
    };

    // Hide when on attendance page
    if (window.location.pathname.includes('/hrcore/attendance/web-attendance')) {
        hideAll();
    } else {
        // Initial check
        checkGlobalStatus();
        
        // Also check status every 5 minutes
        setInterval(checkGlobalStatus, 300000);
    }
});
</script>