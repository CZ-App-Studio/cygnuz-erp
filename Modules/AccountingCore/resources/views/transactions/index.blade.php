@extends('layouts.layoutMaster')

@section('title', __('Transactions'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/tagify/tagify.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/tagify/tagify.js'
  ])
@endsection

@section('content')
  <x-breadcrumb :title="__('Transactions')" :breadcrumbs="$breadcrumbs" />

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Transactions') }}</h5>
      @can('accountingcore.transactions.create')
        <button type="button" class="btn btn-primary create-transaction">
          <i class="bx bx-plus me-1"></i> {{ __('Add Transaction') }}
        </button>
      @endcan
    </div>
    <div class="card-datatable table-responsive">
      <table class="dt-responsive table" id="transactionsTable">
        <thead>
          <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Description') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Amount') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  {{-- Transaction Form Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="transactionFormOffcanvas" aria-labelledby="offcanvasLabel">
    <div class="offcanvas-header">
      <h5 id="offcanvasLabel">{{ __('Add Transaction') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form id="transactionForm">
        @csrf
        <input type="hidden" id="transactionId" name="id">
        
        <div class="mb-3">
          <label class="form-label" for="transaction_date">{{ __('Date') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control date-picker" id="transaction_date" name="transaction_date" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="type">{{ __('Type') }} <span class="text-danger">*</span></label>
          <select class="form-select" id="type" name="type" required>
            <option value="">{{ __('Select Type') }}</option>
            <option value="income">{{ __('Income') }}</option>
            <option value="expense">{{ __('Expense') }}</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label" for="category_id">{{ __('Category') }} <span class="text-danger">*</span></label>
          <select class="form-select category-select" id="category_id" name="category_id" required>
            <option value="">{{ __('Select Category') }}</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}" data-type="{{ $category->type }}">{{ $category->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label" for="amount">{{ __('Amount') }} <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="description">{{ __('Description') }} <span class="text-danger">*</span></label>
          <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label" for="reference_number">{{ __('Reference Number') }}</label>
          <input type="text" class="form-control" id="reference_number" name="reference_number">
        </div>

        <div class="mb-3">
          <label class="form-label" for="payment_method">{{ __('Payment Method') }}</label>
          <select class="form-select" id="payment_method" name="payment_method">
            <option value="">{{ __('Select Payment Method') }}</option>
            <option value="cash">{{ __('Cash') }}</option>
            <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
            <option value="credit_card">{{ __('Credit Card') }}</option>
            <option value="check">{{ __('Check') }}</option>
            <option value="other">{{ __('Other') }}</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label" for="tags">{{ __('Tags') }}</label>
          <input type="text" class="form-control" id="tags" name="tags" placeholder="{{ __('Comma separated tags') }}">
        </div>

        <div class="mb-3">
          <label class="form-label" for="attachment">{{ __('Attachment') }}</label>
          <input type="file" class="form-control" id="attachment" name="attachment" accept="image/*,.pdf">
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">{{ __('Save') }}</button>
          <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Transaction View Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="transactionViewOffcanvas" aria-labelledby="viewOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 id="viewOffcanvasLabel">{{ __('Transaction Details') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="transactionViewContent">
      <div class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">{{ __('Loading...') }}</span>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  @vite(['Modules/AccountingCore/resources/assets/js/transactions.js'])
  <script>
    // Pass data from PHP to JavaScript
    window.pageData = {
      urls: {
        datatable: "{{ route('accountingcore.transactions.datatable') }}",
        store: "{{ route('accountingcore.transactions.store') }}",
        show: "{{ route('accountingcore.transactions.show', ':id') }}",
        update: "{{ route('accountingcore.transactions.update', ':id') }}",
        destroy: "{{ route('accountingcore.transactions.destroy', ':id') }}"
      },
      labels: {
        addTransaction: @json(__('Add Transaction')),
        editTransaction: @json(__('Edit Transaction')),
        selectCategory: @json(__('Select Category')),
        savedSuccessfully: @json(__('Transaction saved successfully')),
        deletedSuccessfully: @json(__('Transaction deleted successfully')),
        areYouSure: @json(__('Are you sure?')),
        cannotRevert: @json(__('You won\'t be able to revert this!')),
        yesDelete: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        success: @json(__('Success')),
        error: @json(__('Error')),
        errorOccurred: @json(__('Something went wrong')),
        chooseFile: @json(__('Choose file')),
        transactionDetails: @json(__('Transaction Details')),
        transactionNumber: @json(__('Transaction Number')),
        date: @json(__('Date')),
        type: @json(__('Type')),
        category: @json(__('Category')),
        amount: @json(__('Amount')),
        paymentMethod: @json(__('Payment Method')),
        reference: @json(__('Reference')),
        description: @json(__('Description')),
        tags: @json(__('Tags')),
        close: @json(__('Close')),
        loading: @json(__('Loading...')),
        edit: @json(__('Edit')),
        attachment: @json(__('Attachment')),
        viewAttachment: @json(__('View Attachment')),
        createdBy: @json(__('Created by')),
        updatedBy: @json(__('Updated by'))
      },
      categories: @json($categories),
      settings: {
        allowFutureDates: @json($allowFutureDates),
        requireAttachments: @json($requireAttachments)
      }
    };
    
    // Export global functions for inline onclick handlers
    window.viewTransaction = function(id) {
      if (window.AccountingCoreTransactions) {
        window.AccountingCoreTransactions.viewTransaction(id);
      }
    };
    
    window.editTransaction = function(id) {
      if (window.AccountingCoreTransactions) {
        window.AccountingCoreTransactions.editTransaction(id);
      }
    };
    
    window.deleteTransaction = function(id) {
      if (window.AccountingCoreTransactions) {
        window.AccountingCoreTransactions.deleteTransaction(id);
      }
    };
  </script>
@endsection