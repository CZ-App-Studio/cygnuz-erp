@extends('layouts.layoutMaster')

@section('title', __('Category Performance Report'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('content')
  <x-breadcrumb :title="__('Category Performance')" :breadcrumbs="$breadcrumbs" />

  {{-- Filter Form --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('accountingcore.reports.category-performance') }}" class="row g-3">
        <div class="col-md-3">
          <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
          <input type="text" class="form-control date-picker" id="start_date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="end_date">{{ __('End Date') }}</label>
          <input type="text" class="form-control date-picker" id="end_date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="type">{{ __('Type') }}</label>
          <select class="form-select" id="type" name="type">
            <option value="all" {{ $type === 'all' ? 'selected' : '' }}>{{ __('All Types') }}</option>
            <option value="income" {{ $type === 'income' ? 'selected' : '' }}>{{ __('Income Only') }}</option>
            <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>{{ __('Expense Only') }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label d-block">&nbsp;</label>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-filter me-1"></i> {{ __('Apply Filter') }}
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Performance Charts --}}
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Category Distribution') }}</h5>
        </div>
        <div class="card-body">
          <div id="categoryDistributionChart"></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Top 10 Categories') }}</h5>
        </div>
        <div class="card-body">
          <div id="topCategoriesChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Detailed Performance Table --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('Category Performance Details') }}</h5>
      <div>
        <span class="badge bg-label-info">
          {{ __(':count Categories', ['count' => count($performanceData)]) }}
        </span>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>{{ __('Category') }}</th>
              <th>{{ __('Type') }}</th>
              <th class="text-center">{{ __('Transaction Count') }}</th>
              <th class="text-end">{{ __('Total Amount') }}</th>
              <th class="text-end">{{ __('Average') }}</th>
              <th class="text-end">{{ __('% of Total') }}</th>
            </tr>
          </thead>
          <tbody>
            @php
              $grandTotal = collect($performanceData)->sum('total');
            @endphp
            @forelse($performanceData as $data)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    @if($data['category']->icon)
                      <i class="{{ $data['category']->icon }} me-2"></i>
                    @endif
                    <span>{{ $data['category']->name }}</span>
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-{{ $data['category']->type === 'income' ? 'success' : 'danger' }}">
                    {{ ucfirst($data['category']->type) }}
                  </span>
                </td>
                <td class="text-center">{{ $data['count'] }}</td>
                <td class="text-end fw-semibold {{ $data['category']->type === 'income' ? 'text-success' : 'text-danger' }}">
                  {{ \App\Helpers\FormattingHelper::formatCurrency($data['total']) }}
                </td>
                <td class="text-end">{{ \App\Helpers\FormattingHelper::formatCurrency($data['average']) }}</td>
                <td class="text-end">
                  @php
                    $percentage = $grandTotal > 0 ? ($data['total'] / $grandTotal * 100) : 0;
                  @endphp
                  <div class="d-flex align-items-center justify-content-end">
                    <span class="me-2">{{ number_format($percentage, 1) }}%</span>
                    <div class="progress" style="width: 60px; height: 6px;">
                      <div class="progress-bar bg-{{ $data['category']->type === 'income' ? 'success' : 'danger' }}" 
                           role="progressbar" 
                           style="width: {{ $percentage }}%">
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  {{ __('No category data found for the selected criteria') }}
                </td>
              </tr>
            @endforelse
          </tbody>
          @if(count($performanceData) > 0)
            <tfoot class="table-light">
              <tr class="fw-bold">
                <td colspan="2">{{ __('Total') }}</td>
                <td class="text-center">{{ collect($performanceData)->sum('count') }}</td>
                <td class="text-end">{{ \App\Helpers\FormattingHelper::formatCurrency($grandTotal) }}</td>
                <td class="text-end">-</td>
                <td class="text-end">100%</td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers
    flatpickr('.date-picker', {
        dateFormat: 'Y-m-d',
        maxDate: 'today'
    });
    
    // Prepare chart data
    const performanceData = @json($performanceData);
    
    if (performanceData.length > 0) {
        // Filter out invalid data
        const validData = performanceData.filter(item => 
            item.total && !isNaN(item.total) && item.total > 0
        );
        
        if (validData.length > 0) {
            // Category Distribution Chart (Pie)
            const categories = validData.map(item => item.category.name);
            const amounts = validData.map(item => parseFloat(item.total) || 0);
            
            const distributionOptions = {
                series: amounts,
                labels: categories,
            chart: {
                type: 'pie',
                height: 350
            },
            colors: validData.map(item => 
                item.category.type === 'income' ? '#28a745' : '#dc3545'
            ),
            dataLabels: {
                formatter: function(val, opts) {
                    return val.toFixed(1) + '%';
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: 'USD'
                        }).format(value);
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 300
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };
        
        const distributionChart = new ApexCharts(
            document.querySelector("#categoryDistributionChart"), 
            distributionOptions
        );
        distributionChart.render();
        
        // Top 10 Categories Chart (Horizontal Bar)
        const top10Data = validData.slice(0, 10);
        const top10Categories = top10Data.map(item => item.category.name);
        const top10Amounts = top10Data.map(item => parseFloat(item.total) || 0);
        
        const topCategoriesOptions = {
            series: [{
                name: '{{ __("Amount") }}',
                data: top10Amounts
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: top10Data.map(item => 
                item.category.type === 'income' ? '#28a745' : '#dc3545'
            ),
            dataLabels: {
                enabled: true,
                offsetX: -6,
                style: {
                    fontSize: '12px',
                    colors: ['#fff']
                },
                formatter: function(value) {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(value);
                }
            },
            xaxis: {
                categories: top10Categories,
                labels: {
                    formatter: function(value) {
                        return new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: 'USD',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(value);
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: 'USD'
                        }).format(value);
                    }
                }
            }
        };
        
            const topCategoriesChart = new ApexCharts(
                document.querySelector("#topCategoriesChart"), 
                topCategoriesOptions
            );
            topCategoriesChart.render();
        } else {
            document.querySelector('#categoryDistributionChart').innerHTML = '<div class="text-center text-muted py-5">{{ __("No data to display") }}</div>';
            document.querySelector('#topCategoriesChart').innerHTML = '<div class="text-center text-muted py-5">{{ __("No data to display") }}</div>';
        }
    } else {
        document.querySelector('#categoryDistributionChart').innerHTML = '<div class="text-center text-muted py-5">{{ __("No data to display") }}</div>';
        document.querySelector('#topCategoriesChart').innerHTML = '<div class="text-center text-muted py-5">{{ __("No data to display") }}</div>';
    }
});
</script>
@endsection