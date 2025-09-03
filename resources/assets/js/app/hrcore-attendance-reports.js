/**
 * Attendance Reports
 */

'use strict';

$(document).ready(function() {
  // Get report data from window
  const reportData = window.attendanceReportData || {
    monthlyStats: [],
    totalPresent: 0,
    totalAbsent: 0,
    totalLate: 0
  };

  // Chart Colors
  const chartColors = {
    primary: '#696cff',
    success: '#71dd37',
    warning: '#ffab00',
    danger: '#ff3e1d',
    info: '#03c3ec',
    secondary: '#8592a3'
  };

  // Initialize date range picker
  const dateRangeEl = document.getElementById('dateRange');
  if (dateRangeEl) {
    flatpickr(dateRangeEl, {
      mode: 'range',
      dateFormat: 'Y-m-d',
      maxDate: 'today'
    });
  }

  // Report period change handler
  $('#reportPeriod').on('change', function() {
    const value = $(this).val();
    if (value === 'custom') {
      $('#customDateRange').show();
    } else {
      $('#customDateRange').hide();
    }
  });

  // Initialize Attendance Trend Chart
  const attendanceTrendChartEl = document.querySelector('#attendanceTrendChart');
  if (attendanceTrendChartEl) {
    const categories = reportData.monthlyStats.map(stat => stat.month);
    const presentData = reportData.monthlyStats.map(stat => stat.present);
    const absentData = reportData.monthlyStats.map(stat => stat.absent);
    const lateData = reportData.monthlyStats.map(stat => stat.late);

    const attendanceTrendChartOptions = {
      series: [{
        name: 'Present',
        data: presentData
      }, {
        name: 'Absent',
        data: absentData
      }, {
        name: 'Late',
        data: lateData
      }],
      chart: {
        type: 'line',
        height: 350,
        toolbar: {
          show: true,
          tools: {
            download: true,
            selection: false,
            zoom: false,
            zoomin: false,
            zoomout: false,
            pan: false,
            reset: false
          }
        }
      },
      colors: [chartColors.success, chartColors.danger, chartColors.warning],
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      xaxis: {
        categories: categories,
        labels: {
          style: {
            fontSize: '12px'
          }
        }
      },
      yaxis: {
        title: {
          text: 'Days'
        },
        min: 0
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (val) {
            return val + ' days';
          }
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'left'
      },
      grid: {
        borderColor: '#f1f1f1',
        xaxis: {
          lines: {
            show: false
          }
        },
        yaxis: {
          lines: {
            show: true
          }
        }
      },
      markers: {
        size: 5,
        colors: ['#fff'],
        strokeColors: [chartColors.success, chartColors.danger, chartColors.warning],
        strokeWidth: 2,
        hover: {
          size: 7
        }
      }
    };

    const attendanceTrendChart = new ApexCharts(attendanceTrendChartEl, attendanceTrendChartOptions);
    attendanceTrendChart.render();
  }

  // Initialize Attendance Distribution Chart
  const attendanceDistributionChartEl = document.querySelector('#attendanceDistributionChart');
  if (attendanceDistributionChartEl) {
    const attendanceDistributionChartOptions = {
      series: [
        reportData.totalPresent,
        reportData.totalAbsent,
        reportData.totalLate
      ],
      chart: {
        type: 'donut',
        height: 280
      },
      labels: ['Present', 'Absent', 'Late'],
      colors: [chartColors.success, chartColors.danger, chartColors.warning],
      dataLabels: {
        enabled: true,
        formatter: function (val, opts) {
          return opts.w.config.series[opts.seriesIndex] + ' days';
        }
      },
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              name: {
                show: true,
                fontSize: '14px',
                fontWeight: 600,
                offsetY: -5
              },
              value: {
                show: true,
                fontSize: '16px',
                fontWeight: 400,
                color: undefined,
                offsetY: 16,
                formatter: function (val) {
                  return val + ' days';
                }
              },
              total: {
                show: true,
                showAlways: false,
                label: 'Total',
                fontSize: '12px',
                fontWeight: 400,
                color: '#8592a3',
                formatter: function (w) {
                  const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                  return total + ' days';
                }
              }
            }
          }
        }
      },
      legend: {
        show: false
      },
      stroke: {
        width: 3,
        colors: ['#fff']
      },
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            height: 250
          }
        }
      }]
    };

    const attendanceDistributionChart = new ApexCharts(attendanceDistributionChartEl, attendanceDistributionChartOptions);
    attendanceDistributionChart.render();
  }

  // Generate Report button
  $('#generateReport').on('click', function() {
    const period = $('#reportPeriod').val();
    let startDate, endDate;

    // Calculate date range based on period
    const today = new Date();
    switch(period) {
      case 'current_month':
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        break;
      case 'last_month':
        startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        endDate = new Date(today.getFullYear(), today.getMonth(), 0);
        break;
      case 'last_3_months':
        startDate = new Date(today.getFullYear(), today.getMonth() - 2, 1);
        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        break;
      case 'last_6_months':
        startDate = new Date(today.getFullYear(), today.getMonth() - 5, 1);
        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        break;
      case 'current_year':
        startDate = new Date(today.getFullYear(), 0, 1);
        endDate = new Date(today.getFullYear(), 11, 31);
        break;
      case 'custom':
        const dateRange = $('#dateRange').val();
        if (!dateRange) {
          Swal.fire({
            icon: 'warning',
            title: 'Date Range Required',
            text: 'Please select a date range for custom period.',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          return;
        }
        const dates = dateRange.split(' to ');
        startDate = new Date(dates[0]);
        endDate = new Date(dates[1]);
        break;
    }

    // Show loading
    Swal.fire({
      title: 'Generating Report',
      text: 'Please wait while we generate your report...',
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      }
    });

    // Simulate report generation (replace with actual AJAX call)
    setTimeout(() => {
      Swal.fire({
        icon: 'success',
        title: 'Report Generated',
        text: 'Your attendance report has been generated successfully.',
        customClass: {
          confirmButton: 'btn btn-success'
        },
        buttonsStyling: false
      });
      
      // Reload or update charts/data here
    }, 2000);
  });

  // Export functionality removed for self-service reports
  // Export button handler and related functions have been removed

  // Auto-refresh charts on window resize
  let resizeTimer;
  $(window).on('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      // Trigger chart resize
      window.dispatchEvent(new Event('resize'));
    }, 250);
  });
});