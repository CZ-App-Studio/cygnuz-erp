/**
 * AccountingCore Dashboard JavaScript
 */

// Global variables
let incomeExpenseChart = null;
let categoryChart = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('AccountingCore Dashboard: Initializing...');
    
    // Initialize charts
    initializeCharts();
    
    // Setup event listeners
    setupEventListeners();
});

/**
 * Initialize ApexCharts
 */
function initializeCharts() {
    // Check if ApexCharts is available
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts not loaded, retrying in 500ms...');
        setTimeout(initializeCharts, 500);
        return;
    }
    
    // Get page data
    const pageData = window.pageData || {};
    const chartData = pageData.chartData || {};
    const topCategories = pageData.topCategories || [];
    
    console.log('Chart data:', chartData);
    console.log('Top categories:', topCategories);
    
    // Initialize Income vs Expense Chart
    initIncomeExpenseChart(chartData);
    
    // Initialize Category Chart
    initCategoryChart(topCategories);
}

/**
 * Initialize Income vs Expense Chart
 */
function initIncomeExpenseChart(chartData) {
    const chartEl = document.querySelector('#incomeExpenseChart');
    if (!chartEl) {
        console.error('Income/Expense chart element not found');
        return;
    }
    
    const options = {
        chart: {
            height: 300,
            type: 'area',
            toolbar: { show: false }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        series: [
            {
                name: pageData.labels.income,
                data: chartData.income || []
            },
            {
                name: pageData.labels.expenses,
                data: chartData.expenses || []
            }
        ],
        xaxis: {
            categories: chartData.labels || []
        },
        colors: ['#71dd37', '#ff3e1d'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return formatCurrency(value);
                }
            }
        }
    };
    
    try {
        incomeExpenseChart = new ApexCharts(chartEl, options);
        incomeExpenseChart.render();
        console.log('Income/Expense chart rendered successfully');
    } catch (error) {
        console.error('Error rendering Income/Expense chart:', error);
    }
}

/**
 * Initialize Category Chart
 */
function initCategoryChart(topCategories) {
    const chartEl = document.querySelector('#categoryChart');
    if (!chartEl) {
        console.error('Category chart element not found');
        return;
    }
    
    // Extract data from categories
    const categoryData = topCategories.map(cat => parseFloat(cat.total || 0));
    const categoryLabels = topCategories.map(cat => cat.name || 'Unknown');
    
    if (categoryData.length === 0) {
        chartEl.innerHTML = '<div class="text-center text-muted p-5">' + (pageData.labels.noExpenseData || 'No expense data available') + '</div>';
        return;
    }
    
    const options = {
        chart: {
            height: 300,
            type: 'donut'
        },
        labels: categoryLabels,
        series: categoryData,
        colors: ['#696cff', '#ff3e1d', '#ffab00', '#03c3ec', '#71dd37'],
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val.toFixed(1) + '%';
            }
        },
        legend: { show: false },
        stroke: { width: 5, colors: ['#fff'] },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: pageData.labels.total || 'Total',
                            formatter: function() {
                                const total = categoryData.reduce((a, b) => a + b, 0);
                                return formatCurrency(total);
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return formatCurrency(value);
                }
            }
        }
    };
    
    try {
        categoryChart = new ApexCharts(chartEl, options);
        categoryChart.render();
        console.log('Category chart rendered successfully');
    } catch (error) {
        console.error('Error rendering Category chart:', error);
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Period selector
    document.querySelectorAll('[data-period]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.dataset.period;
            const buttonText = this.textContent;
            
            // Update button text
            const chartPeriodBtn = document.getElementById('chartPeriod');
            if (chartPeriodBtn) {
                chartPeriodBtn.textContent = buttonText;
            }
            
            // Load new statistics
            loadStatistics(period);
        });
    });
}

/**
 * Load statistics via AJAX
 */
function loadStatistics(period) {
    const urls = window.pageData?.urls || {};
    const statisticsUrl = urls.statistics;
    
    if (!statisticsUrl) {
        console.error('Statistics URL not defined');
        return;
    }
    
    fetch(statisticsUrl + '?period=' + period, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.chartData) {
            updateCharts(data.chartData);
        }
    })
    .catch(error => {
        console.error('Error loading statistics:', error);
    });
}

/**
 * Update charts with new data
 */
function updateCharts(chartData) {
    if (incomeExpenseChart && chartData) {
        incomeExpenseChart.updateOptions({
            xaxis: { categories: chartData.labels || [] },
            series: [
                { name: pageData.labels.income, data: chartData.income || [] },
                { name: pageData.labels.expenses, data: chartData.expenses || [] }
            ]
        });
        console.log('Charts updated with new data');
    }
}

/**
 * Format currency helper
 */
function formatCurrency(value) {
    // Use the format from pageData if available
    if (window.pageData?.currencyFormat) {
        return window.pageData.currencyFormat.replace('{value}', value.toLocaleString());
    }
    return '$' + value.toLocaleString();
}

// Export functions for external use
window.AccountingCoreDashboard = {
    initializeCharts,
    loadStatistics,
    updateCharts
};