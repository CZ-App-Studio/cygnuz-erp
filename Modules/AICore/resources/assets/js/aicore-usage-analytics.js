/**
 * AI Core Usage Analytics JavaScript
 * Handles analytics dashboard functionality and charts
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Core Usage Analytics loaded');
    
    initializeAnalytics();
    initializeCharts();
    setupEventListeners();
    initializeDataTable();
});

/**
 * Initialize analytics functionality
 */
function initializeAnalytics() {
    // Initialize date range picker if available
    if (typeof $ !== 'undefined' && $.fn.datepicker) {
        $('#date-range').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }

    // Initialize Select2 for filters
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#period-filter, #provider-filter, #module-filter, #logs-status-filter').select2({
            theme: 'bootstrap-5'
        });
    }

    // Show/hide date range based on period selection
    const periodFilter = document.querySelector('#period-filter');
    if (periodFilter) {
        toggleDateRangeVisibility(periodFilter.value);
    }
}

/**
 * Initialize charts
 */
function initializeCharts() {
    if (typeof window.pageData === 'undefined' || !window.pageData.chartData) {
        console.warn('Chart data not available');
        return;
    }

    initializeUsageTrendsChart();
    initializeModuleUsageChart();
    initializeProviderCostChart();
}

/**
 * Initialize usage trends chart
 */
function initializeUsageTrendsChart() {
    const chartElement = document.querySelector('#usage-trends-chart');
    if (!chartElement) return;

    const usageTrends = window.pageData.chartData.usageTrends || [];

    const options = {
        series: [{
            name: 'Requests',
            data: usageTrends.map(item => ({
                x: item.date,
                y: item.requests
            }))
        }, {
            name: 'Tokens',
            data: usageTrends.map(item => ({
                x: item.date,
                y: item.tokens
            }))
        }],
        chart: {
            height: 350,
            type: 'line',
            toolbar: {
                show: true
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#7367f0', '#28c76f'],
        xaxis: {
            type: 'datetime',
            title: {
                text: 'Date'
            }
        },
        yaxis: [{
            title: {
                text: 'Requests'
            },
            seriesName: 'Requests'
        }, {
            opposite: true,
            title: {
                text: 'Tokens'
            },
            seriesName: 'Tokens'
        }],
        tooltip: {
            shared: true,
            intersect: false
        },
        legend: {
            position: 'top'
        }
    };

    window.usageTrendsChart = new ApexCharts(chartElement, options);
    window.usageTrendsChart.render();
}

/**
 * Initialize module usage chart
 */
function initializeModuleUsageChart() {
    const chartElement = document.querySelector('#module-usage-chart');
    if (!chartElement) return;

    const moduleUsage = window.pageData.chartData.moduleUsage || [];

    const options = {
        series: moduleUsage.map(item => item.requests),
        chart: {
            type: 'donut',
            height: 300
        },
        labels: moduleUsage.map(item => item.module),
        colors: ['#7367f0', '#28c76f', '#ff9f43', '#ea5455', '#00cfe8'],
        legend: {
            position: 'bottom'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%'
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' requests';
                }
            }
        }
    };

    window.moduleUsageChart = new ApexCharts(chartElement, options);
    window.moduleUsageChart.render();
}

/**
 * Initialize provider cost chart
 */
function initializeProviderCostChart() {
    const chartElement = document.querySelector('#provider-cost-chart');
    if (!chartElement) return;

    const providerCost = window.pageData.chartData.providerCost || [];

    const options = {
        series: [{
            data: providerCost.map(item => ({
                x: item.provider,
                y: parseFloat(item.cost)
            }))
        }],
        chart: {
            type: 'bar',
            height: 300
        },
        colors: ['#7367f0'],
        plotOptions: {
            bar: {
                horizontal: true,
                distributed: true
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return '$' + val.toFixed(2);
            }
        },
        xaxis: {
            title: {
                text: 'Cost ($)'
            }
        },
        yaxis: {
            title: {
                text: 'Provider'
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return '$' + val.toFixed(2);
                }
            }
        }
    };

    window.providerCostChart = new ApexCharts(chartElement, options);
    window.providerCostChart.render();
}

/**
 * Initialize DataTable
 */
function initializeDataTable() {
    const table = document.querySelector('#usage-logs-table');
    if (!table) return;

    if (typeof DataTable !== 'undefined') {
        window.usageLogsDataTable = new DataTable(table, {
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']], // Sort by timestamp descending
            columnDefs: [
                { orderable: false, targets: [8] } // Actions column not sortable
            ]
        });
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Period filter change
    const periodFilter = document.querySelector('#period-filter');
    if (periodFilter) {
        periodFilter.addEventListener('change', function() {
            toggleDateRangeVisibility(this.value);
        });
    }

    // Apply filters button
    const applyBtn = document.querySelector('[onclick="applyFilters()"]');
    if (applyBtn) {
        applyBtn.addEventListener('click', applyFilters);
    }

    // Refresh data button
    const refreshBtn = document.querySelector('[onclick="refreshData()"]');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshData);
    }

    // Chart metric selector
    document.querySelectorAll('.chart-metric').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const metric = this.getAttribute('data-metric');
            updateChartMetric(metric);
        });
    });

    // Status filter for logs
    const statusFilter = document.querySelector('#logs-status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            applyLogsFilter(this.value);
        });
    }

    // View log details buttons
    document.querySelectorAll('.view-log-details').forEach(button => {
        button.addEventListener('click', function() {
            const logId = this.getAttribute('data-log-id');
            viewLogDetails(logId);
        });
    });
}

/**
 * Toggle date range visibility
 */
function toggleDateRangeVisibility(period) {
    const dateRangeContainer = document.querySelector('#date-range-container');
    if (!dateRangeContainer) return;

    if (period === 'custom') {
        dateRangeContainer.style.display = 'block';
    } else {
        dateRangeContainer.style.display = 'none';
    }
}

/**
 * Apply filters
 */
function applyFilters() {
    const period = document.querySelector('#period-filter').value;
    const provider = document.querySelector('#provider-filter').value;
    const module = document.querySelector('#module-filter').value;
    const dateRange = document.querySelector('#date-range').value;

    // Update page data
    if (window.pageData) {
        window.pageData.currentPeriod = period;
        window.pageData.currentProvider = provider;
        window.pageData.currentModule = module;
    }

    // Build query parameters
    const params = new URLSearchParams();
    params.append('period', period);
    if (provider) params.append('provider', provider);
    if (module) params.append('module', module);
    if (period === 'custom' && dateRange) {
        params.append('date_range', dateRange);
    }

    // Reload page with new filters - use current page URL instead of a route
    const currentUrl = window.location.pathname;
    window.location.href = currentUrl + '?' + params.toString();
}

/**
 * Refresh data
 */
function refreshData() {
    if (window.pageData?.routes?.getData) {
        window.location.reload();
    } else {
        location.reload();
    }
}

/**
 * Update chart metric
 */
function updateChartMetric(metric) {
    console.log('Updating chart metric to:', metric);
    
    // Update dropdown text
    const button = document.querySelector('.dropdown-toggle');
    const metricNames = {
        'requests': 'Requests',
        'tokens': 'Tokens',
        'cost': 'Cost',
        'response_time': 'Response Time'
    };
    
    if (button && metricNames[metric]) {
        button.textContent = metricNames[metric];
    }

    // This would typically update the chart with new data
    // For now, we'll just log the change
}

/**
 * Apply logs filter
 */
function applyLogsFilter(status) {
    if (!window.usageLogsDataTable) return;

    window.usageLogsDataTable
        .column(7) // Status column
        .search(status)
        .draw();
}

/**
 * View log details
 */
function viewLogDetails(logId) {
    console.log('Viewing details for log ID:', logId);
    
    // Check if we have the route available
    if (!window.pageData || !window.pageData.routes) {
        console.error('Page data not available');
        window.location.href = '/aicore/usage/' + logId;
        return;
    }
    
    // Create the URL for fetching details
    const detailsUrl = '/aicore/usage/' + logId;
    
    // Fetch the details via AJAX
    $.ajax({
        url: detailsUrl,
        method: 'GET',
        dataType: 'json',
        beforeSend: function() {
            // Show loading indicator if needed
            if (window.Swal) {
                Swal.fire({
                    title: 'Loading...',
                    text: 'Fetching log details',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        },
        success: function(response) {
            if (window.Swal) {
                Swal.close();
            }
            
            // Create and show the offcanvas with details
            showLogDetailsOffcanvas(response);
        },
        error: function(xhr, status, error) {
            console.error('Error fetching log details:', error);
            
            if (window.Swal) {
                Swal.close();
            }
            
            // Fallback to direct navigation
            window.location.href = detailsUrl;
        }
    });
}

/**
 * Show log details in an offcanvas
 */
function showLogDetailsOffcanvas(data) {
    const log = data.log;
    const metrics = data.metrics;
    const comparisonStats = data.comparisonStats;
    
    // Remove any existing offcanvas
    const existingOffcanvas = document.getElementById('logDetailsOffcanvas');
    if (existingOffcanvas) {
        existingOffcanvas.remove();
    }
    
    // Create offcanvas HTML
    const offcanvasHtml = `
        <div class="offcanvas offcanvas-end" tabindex="-1" id="logDetailsOffcanvas" style="width: 600px;">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">AI Usage Log Details #${log.id}</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <!-- Status Badge -->
                <div class="mb-4">
                    ${getStatusBadge(log.status)}
                </div>
                
                <!-- Basic Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Timestamp</small>
                                <strong>${new Date(log.created_at).toLocaleString()}</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Module</small>
                                <span class="badge bg-label-primary">${log.module_name}</span>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Operation</small>
                                <strong>${log.operation_type}</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">AI Model</small>
                                <strong>${log.model ? log.model.name : 'N/A'}</strong>
                            </div>
                            ${log.user ? `
                            <div class="col-12 mb-3">
                                <small class="text-muted d-block">User</small>
                                <strong>${log.user.name} (${log.user.email})</strong>
                            </div>
                            ` : ''}
                        </div>
                        ${log.error_message ? `
                        <div class="alert alert-danger mb-0">
                            <strong>Error:</strong> ${log.error_message}
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <!-- Token Usage -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Token Usage</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Prompt Tokens</span>
                                <strong>${log.prompt_tokens.toLocaleString()}</strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: ${metrics.prompt_percentage}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Completion Tokens</span>
                                <strong>${log.completion_tokens.toLocaleString()}</strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: ${metrics.completion_percentage}%"></div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-0">Total Tokens</h6>
                            <h5 class="mb-0 text-primary">${log.total_tokens.toLocaleString()}</h5>
                        </div>
                    </div>
                </div>
                
                <!-- Performance & Cost -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Performance & Cost</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Processing Time</small>
                                <strong>${log.processing_time_ms ? log.processing_time_ms.toLocaleString() + ' ms' : 'N/A'}</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Tokens/Second</small>
                                <strong>${metrics.tokens_per_second.toFixed(2)}</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Request Cost</small>
                                <strong class="text-warning">$${parseFloat(log.cost).toFixed(6)}</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Cost per Token</small>
                                <strong>$${metrics.cost_per_token.toFixed(8)}</strong>
                            </div>
                        </div>
                        ${comparisonStats ? `
                        <hr>
                        <h6 class="mb-2">7-Day Comparison</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Avg Processing Time</small>
                                <strong>${Math.round(comparisonStats.avg_processing_time).toLocaleString()} ms</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Avg Cost</small>
                                <strong>$${parseFloat(comparisonStats.avg_cost).toFixed(6)}</strong>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <a href="/aicore/usage/${log.id}" class="btn btn-primary flex-fill">
                        <i class="bx bx-fullscreen"></i> View Full Details
                    </a>
                    <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Append to body
    document.body.insertAdjacentHTML('beforeend', offcanvasHtml);
    
    // Show the offcanvas
    const offcanvasElement = document.getElementById('logDetailsOffcanvas');
    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
    offcanvas.show();
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const badges = {
        'success': '<span class="badge bg-label-success">Success</span>',
        'error': '<span class="badge bg-label-danger">Error</span>',
        'timeout': '<span class="badge bg-label-warning">Timeout</span>'
    };
    return badges[status] || `<span class="badge bg-label-secondary">${status}</span>`;
}

// Global functions
window.applyFilters = applyFilters;
window.refreshData = refreshData;