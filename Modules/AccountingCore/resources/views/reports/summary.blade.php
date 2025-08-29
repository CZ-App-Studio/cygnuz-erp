@extends('layouts.layoutMaster')

@section('title', __('Income & Expense Summary'))

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
  <x-breadcrumb :title="__('Income & Expense Summary')" :breadcrumbs="$breadcrumbs" />

  {{-- Date Range Filter --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('accountingcore.reports.summary') }}" class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
          <input type="text" class="form-control date-picker" id="start_date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="end_date">{{ __('End Date') }}</label>
          <input type="text" class="form-control date-picker" id="end_date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label d-block">&nbsp;</label>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-filter me-1"></i> {{ __('Apply Filter') }}
          </button>
          @can('accountingcore.reports.summary.export-pdf')
            <a href="{{ route('accountingcore.reports.summary.export-pdf', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-label-success">
              <i class="bx bx-download me-1"></i> {{ __('Export PDF') }}
            </a>
          @endcan
        </div>
      </form>
    </div>
  </div>

  {{-- Summary Cards --}}
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h6 class="card-title text-muted mb-1">{{ __('Total Income') }}</h6>
              <h3 class="mb-0 text-success">{{ \App\Helpers\FormattingHelper::formatCurrency($summary['income']) }}</h3>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-trending-up bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h6 class="card-title text-muted mb-1">{{ __('Total Expenses') }}</h6>
              <h3 class="mb-0 text-danger">{{ \App\Helpers\FormattingHelper::formatCurrency($summary['expense']) }}</h3>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger">
                <i class="bx bx-trending-down bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h6 class="card-title text-muted mb-1">{{ __('Net Profit/Loss') }}</h6>
              <h3 class="mb-0 {{ $summary['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                {{ \App\Helpers\FormattingHelper::formatCurrency($summary['profit']) }}
              </h3>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-{{ $summary['profit'] >= 0 ? 'success' : 'danger' }}">
                <i class="bx bx-{{ $summary['profit'] >= 0 ? 'wallet' : 'error' }} bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row --}}
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Income by Category') }}</h5>
        </div>
        <div class="card-body">
          <div id="incomeByCategoryChart"></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Expenses by Category') }}</h5>
        </div>
        <div class="card-body">
          <div id="expenseByCategoryChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Monthly Trend Chart --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">{{ __('Monthly Trend') }}</h5>
    </div>
    <div class="card-body">
      <div id="monthlyTrendChart"></div>
    </div>
  </div>

  {{-- Detailed Tables --}}
  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Top Income Categories') }}</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>{{ __('Category') }}</th>
                  <th class="text-end">{{ __('Count') }}</th>
                  <th class="text-end">{{ __('Amount') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($incomeByCategory as $item)
                  <tr>
                    <td>{{ $item->category ? $item->category->name : __('Uncategorized') }}</td>
                    <td class="text-end">{{ $item->count }}</td>
                    <td class="text-end text-success">{{ \App\Helpers\FormattingHelper::formatCurrency($item->total) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">{{ __('No income data found') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Top Expense Categories') }}</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>{{ __('Category') }}</th>
                  <th class="text-end">{{ __('Count') }}</th>
                  <th class="text-end">{{ __('Amount') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($expenseByCategory as $item)
                  <tr>
                    <td>{{ $item->category ? $item->category->name : __('Uncategorized') }}</td>
                    <td class="text-end">{{ $item->count }}</td>
                    <td class="text-end text-danger">{{ \App\Helpers\FormattingHelper::formatCurrency($item->total) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">{{ __('No expense data found') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
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
    const incomeCategories = @json($incomeByCategory->pluck('category.name')->map(fn($name) => $name ?: 'Uncategorized'));
    const incomeValues = @json($incomeByCategory->pluck('total')).map(v => parseFloat(v) || 0);
    
    const expenseCategories = @json($expenseByCategory->pluck('category.name')->map(fn($name) => $name ?: 'Uncategorized'));
    const expenseValues = @json($expenseByCategory->pluck('total')).map(v => parseFloat(v) || 0);
    
    const monthlyData = @json($monthlyBreakdown);
    const monthLabels = monthlyData.map(item => item.month);
    const monthlyIncome = monthlyData.map(item => parseFloat(item.income) || 0);
    const monthlyExpense = monthlyData.map(item => parseFloat(item.expense) || 0);
    
    // Filter out invalid data
    const validIncomeData = incomeValues.filter((value, index) => {
        return value && !isNaN(value) && value > 0;
    });
    const validIncomeCategories = incomeCategories.filter((category, index) => {
        return incomeValues[index] && !isNaN(incomeValues[index]) && incomeValues[index] > 0;
    });
    
    const validExpenseData = expenseValues.filter((value, index) => {
        return value && !isNaN(value) && value > 0;
    });
    const validExpenseCategories = expenseCategories.filter((category, index) => {
        return expenseValues[index] && !isNaN(expenseValues[index]) && expenseValues[index] > 0;
    });
    
    // Income by Category Chart
    if (validIncomeData.length > 0) {
        const incomeChartOptions = {
            series: validIncomeData,
            labels: validIncomeCategories,
            chart: {
                type: 'donut',
                height: 350
            },
            colors: ['#28a745', '#20c997', '#17a2b8', '#6f42c1', '#e83e8c'],
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
                position: 'bottom'
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
        
        const incomeChart = new ApexCharts(document.querySelector("#incomeByCategoryChart"), incomeChartOptions);
        incomeChart.render();
    } else {
        document.querySelector('#incomeByCategoryChart').innerHTML = '<div class="text-center text-muted py-5">{{ __("No income data to display") }}</div>';
    }
    
    // Expense by Category Chart
    if (validExpenseData.length > 0) {
        const expenseChartOptions = {
            series: validExpenseData,
            labels: validExpenseCategories,
            chart: {
                type: 'donut',
                height: 350
            },
            colors: ['#dc3545', '#fd7e14', '#ffc107', '#6c757d', '#343a40'],
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
                position: 'bottom'
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
        
        const expenseChart = new ApexCharts(document.querySelector("#expenseByCategoryChart"), expenseChartOptions);
        expenseChart.render();
    } else {
        document.querySelector('#expenseByCategoryChart').innerHTML = '<div class="text-center text-muted py-5">{{ __("No expense data to display") }}</div>';
    }
    
    // Monthly Trend Chart
    if (monthlyData.length > 0 && monthLabels.length > 0) {
        // Debug data
        console.log('Monthly Labels:', monthLabels);
        console.log('Monthly Income:', monthlyIncome);
        console.log('Monthly Expense:', monthlyExpense);
        
        const monthlyTrendOptions = {
            series: [{
                name: '{{ __("Income") }}',
                data: monthlyIncome
            }, {
                name: '{{ __("Expenses") }}',
                data: monthlyExpense
            }],
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: true
                },
                zoom: {
                    enabled: false
                }
            },
            colors: ['#28a745', '#dc3545'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                }
            },
            markers: {
                size: 5,
                colors: ['#28a745', '#dc3545'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: {
                    size: 7
                }
            },
            xaxis: {
                categories: monthLabels,
                title: {
                    text: '{{ __("Month") }}'
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("Amount") }}'
                },
                min: 0,
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
            tooltip: {
                shared: true,
                intersect: false,
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
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        };
    
        const monthlyTrendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), monthlyTrendOptions);
        monthlyTrendChart.render();
    } else {
        document.querySelector('#monthlyTrendChart').innerHTML = '<div class="text-center text-muted py-5">{{ __("No monthly data to display") }}</div>';
    }
});
</script>
@endsection