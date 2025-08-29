/**
 * HRCore Attendance View
 */

'use strict';

$(function () {
    // Initialize map if location data exists
    if (pageData.attendance.logs.length > 0 && typeof L !== 'undefined') {
        initializeMap();
    }
});

/**
 * Initialize leaflet map with attendance locations
 */
function initializeMap() {
    // Create map centered on first location
    const firstLog = pageData.attendance.logs[0];
    const map = L.map('attendanceMap').setView([firstLog.latitude, firstLog.longitude], 15);

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add markers for each attendance log
    pageData.attendance.logs.forEach(log => {
        const icon = L.divIcon({
            html: `<div class="custom-marker ${log.type === 'check_in' ? 'marker-success' : 'marker-danger'}">
                     <i class="bx bx-${log.type === 'check_in' ? 'log-in-circle' : 'log-out-circle'}"></i>
                   </div>`,
            iconSize: [30, 30],
            className: 'attendance-marker'
        });

        const marker = L.marker([log.latitude, log.longitude], { icon: icon }).addTo(map);
        
        // Add popup with details
        const popupContent = `
            <div class="attendance-popup">
                <strong>${log.type === 'check_in' ? pageData.labels.checkIn : pageData.labels.checkOut}</strong><br>
                <small>${log.time}</small><br>
                ${log.address ? `<small>${log.address}</small>` : ''}
            </div>
        `;
        
        marker.bindPopup(popupContent);
    });

    // Fit map to show all markers
    if (pageData.attendance.logs.length > 1) {
        const group = new L.featureGroup(
            pageData.attendance.logs.map(log => L.marker([log.latitude, log.longitude]))
        );
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

// Add custom styles for map markers
const style = document.createElement('style');
style.textContent = `
    .attendance-marker {
        background: transparent;
        border: none;
    }
    .custom-marker {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    .marker-success {
        background-color: #71dd37;
    }
    .marker-danger {
        background-color: #ff3e1d;
    }
    .attendance-popup {
        min-width: 150px;
    }
`;
document.head.appendChild(style);