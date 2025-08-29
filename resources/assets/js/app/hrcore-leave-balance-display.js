/**
 * Leave Balance Display Chart
 */

'use strict';

(function () {
  // Check if ApexCharts is available and we have data
  if (typeof ApexCharts !== 'undefined' && window.leaveBalanceData) {
    const chartEl = document.querySelector('#leaveBalanceChart');
    
    if (chartEl) {
      const chartOptions = {
        series: [
          {
            name: 'Entitled',
            data: window.leaveBalanceData.entitlement
          },
          {
            name: 'Used',
            data: window.leaveBalanceData.used
          },
          {
            name: 'Available',
            data: window.leaveBalanceData.available
          }
        ],
        chart: {
          type: 'bar',
          height: 300,
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
        stroke: {
          show: true,
          width: 2,
          colors: ['transparent']
        },
        xaxis: {
          categories: window.leaveBalanceData.labels
        },
        yaxis: {
          title: {
            text: 'Days'
          }
        },
        fill: {
          opacity: 1
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + " days";
            }
          }
        },
        colors: ['#696cff', '#ff6b6b', '#51d28c'],
        legend: {
          position: 'top',
          horizontalAlign: 'center'
        }
      };
      
      const chart = new ApexCharts(chartEl, chartOptions);
      chart.render();
    }
  }
})();