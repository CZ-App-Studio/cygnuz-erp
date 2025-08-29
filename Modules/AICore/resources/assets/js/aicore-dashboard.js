/**
 * AI Core Dashboard JavaScript
 * Handles dashboard functionality, charts, and real-time updates
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Core Dashboard loaded');
    
    // Initialize dashboard
    initializeDashboard();
    initializeCharts();
    setupEventListeners();
});

/**
 * Initialize dashboard functionality
 */
function initializeDashboard() {
    // Auto-refresh dashboard data every 30 seconds
    setInterval(refreshDashboardData, 30000);
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize ApexCharts
 */
function initializeCharts() {
    if (typeof window.pageData === 'undefined' || !window.pageData.chartData) {
        console.warn('Chart data not available');
        return;
    }

    // Usage Trends Chart
    initializeUsageTrendsChart();
}

/**
 * Initialize usage trends chart
 */
function initializeUsageTrendsChart() {
    const chartElement = document.querySelector('#usage-trends-chart');
    if (!chartElement) return;

    // Prepare chart data from cost trends
    let chartData = [];
    if (window.pageData.chartData.costTrends && window.pageData.chartData.costTrends.daily_costs) {
        // Convert daily_costs object to array of {x: date, y: value} for ApexCharts
        const dailyCosts = window.pageData.chartData.costTrends.daily_costs;
        chartData = Object.entries(dailyCosts).map(([date, cost]) => ({
            x: new Date(date).getTime(),
            y: parseFloat(cost)
        }));
    }

    const options = {
        series: [{
            name: 'Daily Cost ($)',
            data: chartData
        }],
        chart: {
            height: 300,
            type: 'line',
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#7367f0'],
        xaxis: {
            type: 'datetime',
            labels: {
                format: 'MMM dd'
            }
        },
        yaxis: {
            title: {
                text: 'Cost ($)'
            },
            labels: {
                formatter: function(value) {
                    return '$' + value.toFixed(2);
                }
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(value) {
                    return '$' + value.toFixed(2);
                }
            }
        },
        noData: {
            text: 'No data available',
            align: 'center',
            verticalAlign: 'middle'
        }
    };

    const chart = new ApexCharts(chartElement, options);
    chart.render();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Provider status refresh
    const refreshBtn = document.querySelector('[onclick="refreshProviderStatus()"]');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshProviderStatus);
    }

    // Period selector for charts
    document.querySelectorAll('[data-period]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.getAttribute('data-period');
            updateChartPeriod(period);
        });
    });
}

/**
 * Refresh dashboard data
 */
function refreshDashboardData() {
    if (!window.pageData?.routes?.getData) return;

    fetch(window.pageData.routes.getData + '?type=overview')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing dashboard data:', error);
        });
}

/**
 * Refresh provider status
 */
function refreshProviderStatus() {
    if (!window.pageData?.routes?.providerStatus) return;

    const statusList = document.querySelector('#provider-status-list');
    if (!statusList) return;

    // Show loading state
    statusList.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';

    fetch(window.pageData.routes.providerStatus + '?type=providers')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProviderStatus(data.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing provider status:', error);
            statusList.innerHTML = '<div class="text-center text-danger">Error loading provider status</div>';
        });
}

/**
 * Update dashboard statistics
 */
function updateDashboardStats(stats) {
    // Update stat cards if they exist
    const elements = {
        'total_providers': stats.total_providers,
        'total_models': stats.total_models,
        'requests_today': stats.today?.total_requests || 0,
        'cost_month': stats.month?.total_cost || 0
    };

    Object.keys(elements).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            if (key === 'cost_month') {
                element.textContent = '$' + Number(elements[key]).toFixed(2);
            } else {
                element.textContent = Number(elements[key]).toLocaleString();
            }
        }
    });
}

/**
 * Update provider status display
 */
function updateProviderStatus(providers) {
    const statusList = document.querySelector('#provider-status-list');
    if (!statusList) return;

    let html = '';
    providers.forEach(provider => {
        const statusClass = provider.is_connected ? 'success' : 'danger';
        const statusText = provider.is_connected ? `${provider.response_time}ms` : 'Offline';
        const statusTextClass = provider.is_connected ? 'success' : 'danger';

        html += `
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <span class="badge badge-dot bg-${statusClass} me-2"></span>
                    <div>
                        <h6 class="mb-0">${provider.name}</h6>
                        <small class="text-muted">${provider.type.charAt(0).toUpperCase() + provider.type.slice(1)}</small>
                    </div>
                </div>
                <div class="text-end">
                    <small class="text-${statusTextClass}">${statusText}</small>
                </div>
            </div>
        `;
    });

    statusList.innerHTML = html;
}

/**
 * Update chart period
 */
function updateChartPeriod(period) {
    // Update chart data based on selected period
    console.log('Updating chart period to:', period);
    // This would typically fetch new data and update the chart
}

// Global functions (for backward compatibility with inline onclick handlers)
window.refreshProviderStatus = refreshProviderStatus;