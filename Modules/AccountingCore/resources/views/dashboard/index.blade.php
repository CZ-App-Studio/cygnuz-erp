@extends('layouts.layoutMaster')

@section('title', __('Basic Accounting Dashboard'))

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
  <x-breadcrumb :title="__('Dashboard')" :breadcrumbs="$breadcrumbs" />

  {{-- Summary Cards --}}
  <div class="row">
    <div class="col-lg-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text">{{ __('Total Income') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0">{{ \App\Helpers\FormattingHelper::formatCurrency($summary['income']) }}</h4>
              </div>
              <small class="text-success">{{ __('This Month') }}</small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-success rounded p-2">
                <i class="bx bx-trending-up bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text">{{ __('Total Expenses') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0">{{ \App\Helpers\FormattingHelper::formatCurrency($summary['expense']) }}</h4>
              </div>
              <small class="text-danger">{{ __('This Month') }}</small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-danger rounded p-2">
                <i class="bx bx-trending-down bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text">{{ __('Net Balance') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0">{{ \App\Helpers\FormattingHelper::formatCurrency($summary['balance']) }}</h4>
              </div>
              <small class="{{ $summary['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $summary['balance'] >= 0 ? __('Profit') : __('Loss') }}
              </small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-{{ $summary['balance'] >= 0 ? 'primary' : 'warning' }} rounded p-2">
                <i class="bx bx-wallet bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="card-info">
              <p class="card-text">{{ __('Transactions') }}</p>
              <div class="d-flex align-items-end mb-2">
                <h4 class="card-title mb-0">{{ $summary['total_count'] }}</h4>
              </div>
              <small>{{ __('This Month') }}</small>
            </div>
            <div class="card-icon">
              <span class="badge bg-label-info rounded p-2">
                <i class="bx bx-receipt bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Income vs Expenses Chart --}}
    <div class="col-12 col-lg-8 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <h5 class="card-title mb-0">{{ __('Income vs Expenses') }}</h5>
          <div class="dropdown">
            <button class="btn btn-sm btn-label-primary dropdown-toggle" type="button" id="chartPeriod" data-bs-toggle="dropdown">
              {{ __('This Month') }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="javascript:void(0);" data-period="this_month">{{ __('This Month') }}</a></li>
              <li><a class="dropdown-item" href="javascript:void(0);" data-period="last_month">{{ __('Last Month') }}</a></li>
              <li><a class="dropdown-item" href="javascript:void(0);" data-period="this_year">{{ __('This Year') }}</a></li>
            </ul>
          </div>
        </div>
        <div class="card-body">
          <div id="incomeExpenseChart"></div>
        </div>
      </div>
    </div>

    {{-- Category Distribution --}}
    <div class="col-12 col-lg-4 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Expense Categories') }}</h5>
        </div>
        <div class="card-body">
          <div id="categoryChart"></div>
          <div class="mt-3">
            @foreach($topCategories as $category)
              <div class="d-flex align-items-center mb-3">
                <div class="flex-grow-1">
                  <small class="text-muted">{{ $category->name }}</small>
                  <div class="fw-medium">{{ \App\Helpers\FormattingHelper::formatCurrency($category->total) }}</div>
                </div>
                <div class="text-end">
                  <small class="text-muted">{{ $category->transaction_count }} {{ __('transactions') }}</small>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Recent Transactions --}}
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Recent Transactions') }}</h5>
          @can('accountingcore.transactions.index')
            <a href="{{ route('accountingcore.transactions.index') }}" class="btn btn-sm btn-label-primary">
              {{ __('View All') }}
            </a>
          @endcan
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('Category') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Amount') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentTransactions as $transaction)
                <tr>
                  <td>{{ \App\Helpers\FormattingHelper::formatDate($transaction->transaction_date) }}</td>
                  <td>{{ $transaction->description }}</td>
                  <td>
                    <span class="badge bg-label-secondary">{{ $transaction->category->name }}</span>
                  </td>
                  <td>
                    <span class="badge bg-label-{{ $transaction->type === 'income' ? 'success' : 'danger' }}">
                      {{ __(ucfirst($transaction->type)) }}
                    </span>
                  </td>
                  <td class="{{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                    {{ $transaction->type === 'income' ? '+' : '-' }}{{ \App\Helpers\FormattingHelper::formatCurrency($transaction->amount) }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center">{{ __('No transactions found') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  @vite(['Modules/AccountingCore/resources/assets/js/dashboard.js'])
  <script>
    // Pass data from PHP to JavaScript
    window.pageData = {
      urls: {
        statistics: "{{ route('accountingcore.statistics') }}"
      },
      labels: {
        income: @json(__('Income')),
        expenses: @json(__('Expenses')),
        total: @json(__('Total')),
        noExpenseData: @json(__('No expense data available'))
      },
      chartData: @json($chartData),
      topCategories: @json($topCategories),
      currencyFormat: '{{ \App\Helpers\FormattingHelper::formatCurrency(0) }}'
    };
  </script>
@endsection