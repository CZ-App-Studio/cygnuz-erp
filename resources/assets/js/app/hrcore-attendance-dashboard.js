$(function () {
    'use strict';

    // Variables
    let dt_team_attendance;
    let dt_pending_regularizations;
    let attendanceTrendsChart;

    // CSRF Token Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize dashboard
    initializeDashboard();

    function initializeDashboard() {
        loadStatistics();
        initializeDataTables();
        initializeCharts();
        setupDateFilter();
        
        // Auto-refresh every 5 minutes
        setInterval(function() {
            loadStatistics();
            refreshTeamAttendance();
        }, 300000);
    }

    // Load dashboard statistics
    function loadStatistics() {
        $.ajax({
            url: pageData.routes.stats,
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    
                    // Update statistics cards
                    $('#stat-present').text(data.today.present);
                    $('#stat-absent').text(data.today.absent);
                    $('#stat-late').text(data.today.late);
                    $('#stat-pending').text(data.pending_regularizations);
                    
                    // Update weekly summary
                    $('#weekly-avg').text(data.weekly.avg_attendance);
                    $('#weekly-hours').text(data.weekly.total_hours + ' hrs');
                    $('#team-size').text(data.team_size);
                    
                    // Update attendance trends chart
                    updateAttendanceTrendsChart(data.monthly_trends);
                }
            },
            error: function() {
                console.error('Failed to load statistics');
            }
        });
    }

    // Initialize DataTables
    function initializeDataTables() {
        // Team Attendance DataTable
        if ($('.datatables-team-attendance').length) {
            dt_team_attendance = $('.datatables-team-attendance').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: pageData.routes.teamAttendance,
                    data: function(d) {
                        d.date = $('#attendance-date').val();
                    }
                },
                columns: [
                    { data: 'employee_info', name: 'name', searchable: true },
                    { data: 'department', name: 'designation.department.name', searchable: true },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'check_in', name: 'check_in', orderable: false, searchable: false },
                    { data: 'check_out', name: 'check_out', orderable: false, searchable: false },
                    { data: 'total_hours', name: 'total_hours', orderable: false, searchable: false }
                ],
                order: [[0, 'asc']],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                language: {
                    paginate: {
                        previous: '<i class="bx bx-chevron-left"></i>',
                        next: '<i class="bx bx-chevron-right"></i>'
                    }
                }
            });
        }

        // Pending Regularizations DataTable
        if ($('.datatables-pending-regularizations').length) {
            dt_pending_regularizations = $('.datatables-pending-regularizations').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: pageData.routes.pendingRegularizations
                },
                columns: [
                    { data: 'employee_info', name: 'user.name', searchable: true },
                    { data: 'date', name: 'date' },
                    { data: 'type', name: 'type', orderable: false },
                    { data: 'requested_times', name: 'requested_times', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']],
                pageLength: 5,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                language: {
                    paginate: {
                        previous: '<i class="bx bx-chevron-left"></i>',
                        next: '<i class="bx bx-chevron-right"></i>'
                    }
                }
            });
        }
    }

    // Initialize Charts
    function initializeCharts() {
        // Attendance Trends Chart
        const chartElement = document.getElementById('attendanceTrendsChart');
        if (chartElement) {
            const chartOptions = {
                series: [{
                    name: pageData.labels.present,
                    data: []
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                colors: ['#28a745'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        type: 'vertical',
                        colorStops: [
                            {
                                offset: 0,
                                color: '#28a745',
                                opacity: 0.8
                            },
                            {
                                offset: 100,
                                color: '#28a745',
                                opacity: 0.1
                            }
                        ]
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    categories: [],
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    show: true,
                    tickAmount: 4
                },
                grid: {
                    show: true,
                    strokeDashArray: 3,
                    borderColor: '#e0e6ed'
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(val) {
                            return val + ' employees';
                        }
                    }
                }
            };

            attendanceTrendsChart = new ApexCharts(chartElement, chartOptions);
            attendanceTrendsChart.render();
        }
    }

    // Update Attendance Trends Chart
    function updateAttendanceTrendsChart(trendsData) {
        if (attendanceTrendsChart && trendsData) {
            const dates = trendsData.map(item => item.date);
            const presentCounts = trendsData.map(item => item.present);

            attendanceTrendsChart.updateOptions({
                xaxis: {
                    categories: dates
                }
            });

            attendanceTrendsChart.updateSeries([{
                name: pageData.labels.present,
                data: presentCounts
            }]);
        }
    }

    // Setup date filter
    function setupDateFilter() {
        $('#attendance-date').on('change', function() {
            refreshTeamAttendance();
        });
    }

    // Refresh team attendance
    window.refreshTeamAttendance = function() {
        if (dt_team_attendance) {
            dt_team_attendance.ajax.reload();
        }
    };

    // Refresh chart
    window.refreshChart = function() {
        loadStatistics();
    };

    // Export chart
    window.exportChart = function() {
        if (attendanceTrendsChart) {
            attendanceTrendsChart.dataURI().then(function(uri) {
                const link = document.createElement('a');
                link.href = uri.imgURI;
                link.download = 'attendance-trends.png';
                link.click();
            });
        }
    };

    // Global functions for regularization actions
    window.viewRegularization = function(id) {
        $.ajax({
            url: pageData.routes.regularizationView.replace(':id', id),
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    let html = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Employee:</strong><br>
                                ${data.user.name} (${data.user.employee_code || 'N/A'})
                            </div>
                            <div class="col-md-6">
                                <strong>Date:</strong><br>
                                ${new Date(data.regularization.date).toLocaleDateString()}
                            </div>
                            <div class="col-md-6">
                                <strong>Type:</strong><br>
                                <span class="badge bg-label-info">${getTypeLabel(data.regularization.type)}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge ${getStatusBadgeClass(data.regularization.status)}">${getStatusLabel(data.regularization.status)}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Requested Check-in:</strong><br>
                                ${data.regularization.requested_check_in_time || 'N/A'}
                            </div>
                            <div class="col-md-6">
                                <strong>Requested Check-out:</strong><br>
                                ${data.regularization.requested_check_out_time || 'N/A'}
                            </div>
                            <div class="col-12">
                                <strong>Reason:</strong><br>
                                <p class="mb-0">${data.regularization.reason}</p>
                            </div>
                        </div>`;
                    
                    $('#quickActionTitle').text('Regularization Details');
                    $('#quickActionContent').html(html);
                    $('#quickActionSubmit').hide();
                    $('#quickActionModal').modal('show');
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: pageData.labels.error
                });
            }
        });
    };

    window.approveRegularization = function(id) {
        Swal.fire({
            title: 'Approve Request',
            text: pageData.labels.confirmApprove,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: pageData.labels.approve,
            cancelButtonText: 'Cancel',
            input: 'textarea',
            inputPlaceholder: 'Manager comments (optional)...',
            inputAttributes: {
                maxlength: 500
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.routes.regularizationApprove.replace(':id', id),
                    type: 'POST',
                    data: {
                        manager_comments: result.value || ''
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            dt_pending_regularizations.ajax.reload();
                            loadStatistics(); // Refresh stats
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: pageData.labels.approveSuccess,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.data || pageData.labels.error
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: pageData.labels.error
                        });
                    }
                });
            }
        });
    };

    window.rejectRegularization = function(id) {
        Swal.fire({
            title: 'Reject Request',
            text: pageData.labels.confirmReject,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: pageData.labels.reject,
            cancelButtonText: 'Cancel',
            input: 'textarea',
            inputPlaceholder: 'Reason for rejection (required)...',
            inputAttributes: {
                maxlength: 500,
                required: true
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Please provide a reason for rejection';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.routes.regularizationReject.replace(':id', id),
                    type: 'POST',
                    data: {
                        manager_comments: result.value
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            dt_pending_regularizations.ajax.reload();
                            loadStatistics(); // Refresh stats
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: pageData.labels.rejectSuccess,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.data || pageData.labels.error
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: pageData.labels.error
                        });
                    }
                });
            }
        });
    };

    // Helper functions
    function getTypeLabel(type) {
        const types = {
            'missing_checkin': 'Missing Check-in',
            'missing_checkout': 'Missing Check-out',
            'wrong_time': 'Wrong Time',
            'forgot_punch': 'Forgot to Punch',
            'other': 'Other'
        };
        return types[type] || type;
    }

    function getStatusLabel(status) {
        const statuses = {
            'pending': 'Pending',
            'approved': 'Approved',
            'rejected': 'Rejected'
        };
        return statuses[status] || status;
    }

    function getStatusBadgeClass(status) {
        const classes = {
            'pending': 'bg-label-warning',
            'approved': 'bg-label-success',
            'rejected': 'bg-label-danger'
        };
        return classes[status] || 'bg-label-secondary';
    }
});