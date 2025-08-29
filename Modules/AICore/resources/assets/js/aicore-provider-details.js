/**
 * AI Core Provider Details JavaScript
 * Handles provider detail view functionality and charts
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Core Provider Details loaded');
    
    initializeProviderDetails();
    initializeCharts();
    setupEventListeners();
});

/**
 * Initialize provider details functionality
 */
function initializeProviderDetails() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize DataTable for models if available
    const modelsTable = document.querySelector('#models-table');
    if (modelsTable && typeof DataTable !== 'undefined') {
        new DataTable(modelsTable, {
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']], // Sort by model name
            columnDefs: [
                { orderable: false, targets: [8] } // Actions column not sortable
            ]
        });
    }
}

/**
 * Initialize charts
 */
function initializeCharts() {
    if (typeof window.pageData === 'undefined' || !window.pageData.usageStats) {
        console.warn('Usage stats data not available');
        return;
    }

    initializeUsageChart();
}

/**
 * Initialize usage chart
 */
function initializeUsageChart() {
    const chartElement = document.querySelector('#usage-chart');
    if (!chartElement) return;

    const usageStats = window.pageData.usageStats;
    
    // Check if we have data
    if (!usageStats || Object.keys(usageStats).length === 0) {
        chartElement.innerHTML = `
            <div class="text-center py-4">
                <i class="bx bx-data bx-lg text-muted"></i>
                <p class="text-muted mt-2">No usage data available</p>
            </div>
        `;
        return;
    }

    // Prepare chart data (this would typically come from the backend)
    const options = {
        series: [{
            name: 'Requests',
            data: usageStats.daily_requests || []
        }, {
            name: 'Tokens',
            data: usageStats.daily_tokens || []
        }],
        chart: {
            height: 300,
            type: 'line',
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

    const chart = new ApexCharts(chartElement, options);
    chart.render();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Test connection button
    const testBtn = document.querySelector('#test-connection-btn');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            const providerId = this.getAttribute('data-provider-id');
            testProviderConnection(providerId);
        });
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
 * Test provider connection
 */
function testProviderConnection(providerId) {
    if (!window.pageData?.routes?.testConnection) {
        console.error('Test connection route not configured');
        return;
    }

    const button = document.querySelector('#test-connection-btn');
    const originalContent = button.innerHTML;
    
    // Update button state
    button.disabled = true;
    button.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Testing...';

    fetch(window.pageData.routes.testConnection, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        showConnectionTestResult(data);
    })
    .catch(error => {
        console.error('Connection test failed:', error);
        showConnectionTestResult({
            success: false,
            message: 'Connection test failed: ' + error.message
        });
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalContent;
    });
}

/**
 * Show connection test result
 */
function showConnectionTestResult(data) {
    const alertClass = data.success ? 'alert-success' : 'alert-danger';
    const icon = data.success ? 'bx-check-circle' : 'bx-x-circle';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="bx ${icon} me-2"></i>
        <strong>${data.success ? 'Connection successful!' : 'Connection failed!'}</strong> ${data.message}
        ${data.response_time ? `<br><small>Response time: ${data.response_time}ms</small>` : ''}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert alert at the top of the page
    const container = document.querySelector('.container-xxl');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

/**
 * Update chart period
 */
function updateChartPeriod(period) {
    console.log('Updating chart period to:', period);
    
    // Update dropdown text
    const periodButton = document.querySelector('.dropdown-toggle');
    const periodText = {
        '7': 'Last 7 Days',
        '30': 'Last 30 Days', 
        '90': 'Last 90 Days'
    };
    
    if (periodButton && periodText[period]) {
        periodButton.textContent = periodText[period];
    }

    // This would typically fetch new data from the server
    // For now, we'll just update the chart with mock data
    // fetchUsageData(period).then(data => updateChart(data));
}