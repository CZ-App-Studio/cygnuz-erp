/**
 * WMS Inventory Dashboard JavaScript
 */
$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize charts if data is available
  if (typeof pageData !== 'undefined') {
    initializeCharts();
  }

  /**
   * Initialize dashboard charts
   */
  function initializeCharts() {
    if (pageData.warehouseValues && pageData.warehouseValues.length > 0) {
      initInventoryValueChart();
    }

    if (pageData.monthlyTransactions && pageData.monthlyTransactions.length > 0) {
      initTransactionChart();
    }
  }

  /**
   * Initialize Inventory Value by Warehouse Chart
   */
  function initInventoryValueChart() {
    const chartElement = document.querySelector('#inventoryValueChart');
    if (!chartElement) return;

    const chartData = pageData.warehouseValues;
    const labels = chartData.map(item => item.warehouse_name);
    const values = chartData.map(item => parseFloat(item.total_value));

    const options = {
      series: values,
      chart: {
        type: 'donut',
        height: 300
      },
      labels: labels,
      colors: ['#826af9', '#2b9bf4', '#26a69a', '#ffb400', '#ff6b6b'],
      legend: {
        position: 'bottom'
      },
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            width: 200
          },
          legend: {
            position: 'bottom'
          }
        }
      }]
    };

    const chart = new ApexCharts(chartElement, options);
    chart.render();
  }

  /**
   * Initialize Monthly Transactions Chart
   */
  function initTransactionChart() {
    const chartElement = document.querySelector('#transactionChart');
    if (!chartElement) return;

    const chartData = pageData.monthlyTransactions;
    const categories = chartData.map(item => item.month);
    const purchases = chartData.map(item => parseInt(item.purchases));
    const sales = chartData.map(item => parseInt(item.sales));
    const adjustments = chartData.map(item => parseInt(item.adjustments));

    const options = {
      series: [
        {
          name: pageData.labels.purchases,
          data: purchases
        },
        {
          name: pageData.labels.sales,
          data: sales
        },
        {
          name: pageData.labels.adjustments,
          data: adjustments
        }
      ],
      chart: {
        type: 'bar',
        height: 300
      },
      colors: ['#26a69a', '#2b9bf4', '#ff6b6b'],
      xaxis: {
        categories: categories
      },
      legend: {
        position: 'top'
      }
    };

    const chart = new ApexCharts(chartElement, options);
    chart.render();
  }
});