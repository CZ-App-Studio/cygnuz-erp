$(function () {
  'use strict';

  // Initialize charts
  initDealsChart();
  initPipelineChart();
  initLeadSourcesChart();
  initTaskStatusChart();

  // Deals Overview Chart
  function initDealsChart() {
    $.ajax({
      url: pageData.urls.dealsChart,
      method: 'GET',
      success: function(response) {
        if (response.status === 'success' && response.data.chart_data) {
          const chartData = response.data.chart_data;
          
          const options = {
            chart: {
              type: 'line',
              height: 350,
              toolbar: {
                show: false
              }
            },
            series: [
              {
                name: pageData.labels.deals,
                type: 'column',
                data: chartData.map(item => item.total)
              },
              {
                name: pageData.labels.won,
                type: 'column',
                data: chartData.map(item => item.won)
              },
              {
                name: pageData.labels.revenue,
                type: 'line',
                data: chartData.map(item => item.revenue)
              }
            ],
            xaxis: {
              categories: chartData.map(item => item.month)
            },
            yaxis: [
              {
                title: {
                  text: pageData.labels.deals
                }
              },
              {
                opposite: true,
                title: {
                  text: pageData.labels.revenue + ' ($)'
                }
              }
            ],
            colors: ['#71dd37', '#03c3ec', '#ff3e1d'],
            dataLabels: {
              enabled: false
            },
            stroke: {
              width: [0, 0, 3],
              curve: 'smooth'
            },
            plotOptions: {
              bar: {
                columnWidth: '50%'
              }
            },
            fill: {
              opacity: [0.85, 0.85, 1]
            }
          };

          const chart = new ApexCharts(document.querySelector("#dealsChart"), options);
          chart.render();
        }
      },
      error: function() {
        console.error('Failed to load deals chart data');
      }
    });
  }

  // Pipeline Distribution Chart
  function initPipelineChart() {
    const pipelineData = pageData.pipelines || [];
    
    if (pipelineData.length > 0) {
      const options = {
        chart: {
          type: 'donut',
          height: 280
        },
        series: pipelineData.map(p => p.deals_count),
        labels: pipelineData.map(p => p.name),
        colors: ['#71dd37', '#03c3ec', '#ffab00', '#ff3e1d', '#836AF9'],
        dataLabels: {
          enabled: false
        },
        legend: {
          show: false
        },
        plotOptions: {
          pie: {
            donut: {
              size: '70%',
              labels: {
                show: true,
                total: {
                  show: true,
                  label: pageData.labels.deals,
                  formatter: function(w) {
                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                  }
                }
              }
            }
          }
        }
      };

      const chart = new ApexCharts(document.querySelector("#pipelineChart"), options);
      chart.render();
    }
  }

  // Lead Sources Chart
  function initLeadSourcesChart() {
    $.ajax({
      url: pageData.urls.leadsChart,
      method: 'GET',
      success: function(response) {
        if (response.status === 'success' && response.data.chart_data) {
          const chartData = response.data.chart_data;
          
          const options = {
            chart: {
              type: 'bar',
              height: 300,
              toolbar: {
                show: false
              }
            },
            series: [{
              name: pageData.labels.leads,
              data: chartData.map(item => item.count)
            }],
            xaxis: {
              categories: chartData.map(item => item.source),
              labels: {
                rotate: -45,
                trim: true
              }
            },
            colors: ['#03c3ec'],
            dataLabels: {
              enabled: false
            },
            plotOptions: {
              bar: {
                horizontal: true,
                barHeight: '60%'
              }
            }
          };

          const chart = new ApexCharts(document.querySelector("#leadSourcesChart"), options);
          chart.render();
        }
      },
      error: function() {
        console.error('Failed to load lead sources chart data');
      }
    });
  }

  // Task Status Chart
  function initTaskStatusChart() {
    $.ajax({
      url: pageData.urls.tasksChart,
      method: 'GET',
      success: function(response) {
        if (response.status === 'success' && response.data.chart_data) {
          const chartData = response.data.chart_data;
          
          const options = {
            chart: {
              type: 'radialBar',
              height: 300
            },
            series: chartData.map(item => Math.round((item.count / chartData.reduce((sum, i) => sum + i.count, 0)) * 100)),
            labels: chartData.map(item => item.status),
            colors: ['#71dd37', '#ffab00', '#ff3e1d', '#03c3ec'],
            plotOptions: {
              radialBar: {
                dataLabels: {
                  name: {
                    fontSize: '14px'
                  },
                  value: {
                    fontSize: '16px',
                    formatter: function(val) {
                      return val + '%';
                    }
                  },
                  total: {
                    show: true,
                    label: pageData.labels.tasks,
                    formatter: function(w) {
                      return chartData.reduce((sum, item) => sum + item.count, 0);
                    }
                  }
                }
              }
            }
          };

          const chart = new ApexCharts(document.querySelector("#taskStatusChart"), options);
          chart.render();
        }
      },
      error: function() {
        console.error('Failed to load task status chart data');
      }
    });
  }
});