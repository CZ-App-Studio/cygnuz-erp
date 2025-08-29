@extends('layouts.layoutMaster')

@section('title', 'Test Charts')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="mb-4">ApexCharts Test</h4>
  
  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Simple Line Chart</h5>
        </div>
        <div class="card-body">
          <div id="testChart1"></div>
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Simple Donut Chart</h5>
        </div>
        <div class="card-body">
          <div id="testChart2"></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  window.addEventListener('load', function() {
    console.log('Window loaded');
    console.log('ApexCharts available:', typeof ApexCharts !== 'undefined');
    
    if (typeof ApexCharts !== 'undefined') {
      // Simple line chart
      const lineOptions = {
        chart: {
          type: 'line',
          height: 300
        },
        series: [{
          name: 'Sales',
          data: [30, 40, 35, 50, 49, 60]
        }],
        xaxis: {
          categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
        }
      };
      
      const lineChart = new ApexCharts(document.querySelector("#testChart1"), lineOptions);
      lineChart.render();
      
      // Simple donut chart
      const donutOptions = {
        chart: {
          type: 'donut',
          height: 300
        },
        series: [44, 55, 13, 43],
        labels: ['Team A', 'Team B', 'Team C', 'Team D']
      };
      
      const donutChart = new ApexCharts(document.querySelector("#testChart2"), donutOptions);
      donutChart.render();
    } else {
      console.error('ApexCharts not loaded!');
    }
  });
</script>
@endsection