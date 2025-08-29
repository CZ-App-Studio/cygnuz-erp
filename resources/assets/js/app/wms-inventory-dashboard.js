/**
 * Page: WMS & Inventory Dashboard
 * -----------------------------------------------------------------------------
 */

$(function () {
  'use strict';

  // Add CSRF token to all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize variables
  const warehouseValueChartEl = document.querySelector('#inventoryValueChart');
  const transactionChartEl = document.querySelector('#transactionChart');
  const warehouseChartConfig = {
    chart: {
      height: 300,
      type: 'donut'
    },
    labels: pageData.warehouseValues.labels,
    series: pageData.warehouseValues.series,
    colors: [
      '#696cff',
      '#8592a3',
      '#71dd37',
      '#03c3ec',
      '#ff3e1d',
      '#986ce6',
      '#4ebaeb'
    ],
    stroke: {
      width: 5,
      colors: ['#fff']
    },
    dataLabels: {
      enabled: false
    },
    legend: {
      show: true,
      position: 'bottom'
    },
    tooltip: {
      y: {
        formatter: function(value) {
          return '$' + value.toFixed(2);
        }
      }
    },
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            name: {
              show: true
            },
            value: {
              show: true,
              formatter: function(value) {
                return '$' + value.toFixed(2);
              }
            },
            total: {
              show: true,
              label: 'Total',
              formatter: function(w) {
                const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                return '$' + sum.toFixed(2);
              }
            }
          }
        }
      }
    }
  };

  const transactionChartConfig = {
    chart: {
      height: 300,
      type: 'bar',
      stacked: false,
      toolbar: {
        show: false
      }
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '55%',
        endingShape: 'rounded'
      }
    },
    dataLabels: {
      enabled: false
    },
    colors: ['#696cff', '#03c3ec'],
    series: [
      {
        name: 'Adjustments',
        data: pageData.monthlyTransactions.adjustment_data
      },
      {
        name: 'Transfers',
        data: pageData.monthlyTransactions.transfer_data
      }
    ],
    xaxis: {
      categories: pageData.monthlyTransactions.months
    },
    yaxis: {
      title: {
        text: 'Number of Transactions'
      }
    },
    tooltip: {
      y: {
        formatter: function(val) {
          return val + " transactions";
        }
      }
    },
    legend: {
      position: 'top'
    }
  };

  // Initialize charts if elements exist
  if (warehouseValueChartEl) {
    const warehouseChart = new ApexCharts(warehouseValueChartEl, warehouseChartConfig);
    warehouseChart.render();
  }

  if (transactionChartEl) {
    const transactionChart = new ApexCharts(transactionChartEl, transactionChartConfig);
    transactionChart.render();
  }
});
