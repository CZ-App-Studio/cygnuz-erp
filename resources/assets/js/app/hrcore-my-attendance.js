/**
 * My Attendance Page
 */

'use strict';

$(document).ready(function() {
  // DataTable initialization
  let dt;
  const attendanceTable = $('#attendanceTable');
  if (attendanceTable.length) {
    dt = attendanceTable.DataTable({
      order: [[0, 'desc']],
      pageLength: 15,
      responsive: true,
      language: {
        paginate: {
          previous: '<i class="bx bx-chevron-left"></i>',
          next: '<i class="bx bx-chevron-right"></i>'
        }
      },
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    });

    // Date range filter
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
      const fp = flatpickr(dateFilter, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        maxDate: 'today',
        onChange: function(selectedDates, dateStr) {
          if (selectedDates.length === 2) {
            filterByDateRange(selectedDates[0], selectedDates[1]);
          }
        }
      });
    }
  }

  // Filter by date range
  function filterByDateRange(startDate, endDate) {
    // Custom filtering logic for DataTable
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      const dateStr = data[0]; // Date column
      const date = new Date(dateStr);
      
      if (date >= startDate && date <= endDate) {
        return true;
      }
      return false;
    });
    
    // Redraw table
    dt.draw();
    
    // Remove custom filter after drawing
    $.fn.dataTable.ext.search.pop();
  }

  // Export attendance
  $('#exportAttendance').on('click', function() {
    if (!dt) return;
    
    // Get current filtered data
    const filteredData = dt.rows({ search: 'applied' }).data().toArray();
    
    // Convert to CSV
    let csv = 'Date,Day,Check In,Check Out,Total Hours,Overtime,Status\n';
    filteredData.forEach(row => {
      // Clean HTML from cells
      const cleanRow = row.map(cell => {
        const div = document.createElement('div');
        div.innerHTML = cell;
        return div.textContent || div.innerText || '';
      });
      csv += cleanRow.slice(0, 7).join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `attendance_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    // Show success message
    Swal.fire({
      icon: 'success',
      title: 'Export Successful',
      text: 'Your attendance data has been exported.',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  });

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Real-time clock for today's status
  function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit', 
      second: '2-digit' 
    });
    
    const clockElement = document.getElementById('currentTime');
    if (clockElement) {
      clockElement.textContent = timeString;
    }
  }

  // Update clock every second
  setInterval(updateClock, 1000);
  updateClock(); // Initial call

  // Check-in/out status checker
  function checkAttendanceStatus() {
    fetch('/hrcore/attendance/today-status', {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        updateTodayStatus(data.data);
      }
    })
    .catch(error => {
      console.error('Error checking attendance status:', error);
    });
  }

  // Update today's status UI
  function updateTodayStatus(statusData) {
    // Update check-in time
    const checkInElement = document.querySelector('.check-in-time');
    if (checkInElement) {
      checkInElement.textContent = statusData.checkInTime || '--:--';
    }
    
    // Update check-out time
    const checkOutElement = document.querySelector('.check-out-time');
    if (checkOutElement) {
      checkOutElement.textContent = statusData.checkOutTime || '--:--';
    }
    
    // Update total hours
    const totalHoursElement = document.querySelector('.total-hours');
    if (totalHoursElement) {
      totalHoursElement.textContent = statusData.totalHours ? `${statusData.totalHours} hrs` : '--';
    }
  }

  // Check status every 5 minutes
  setInterval(checkAttendanceStatus, 5 * 60 * 1000);

  // Monthly statistics chart (if needed)
  const statsChartEl = document.getElementById('monthlyStatsChart');
  if (statsChartEl) {
    const statsChartOptions = {
      series: [{
        name: 'Present',
        data: [/* monthly data */]
      }, {
        name: 'Absent',
        data: [/* monthly data */]
      }, {
        name: 'Late',
        data: [/* monthly data */]
      }],
      chart: {
        type: 'bar',
        height: 200,
        stacked: true,
        toolbar: {
          show: false
        }
      },
      colors: ['#71dd37', '#ff3e1d', '#ffab00'],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '50%'
        }
      },
      xaxis: {
        categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4']
      },
      legend: {
        position: 'top'
      },
      fill: {
        opacity: 1
      }
    };

    const statsChart = new ApexCharts(statsChartEl, statsChartOptions);
    statsChart.render();
  }
});  // End of document ready