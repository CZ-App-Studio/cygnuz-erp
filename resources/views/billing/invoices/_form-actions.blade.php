{{-- Invoice Form Actions Partial --}}
<div class="d-flex justify-content-between align-items-center">
    <div class="form-actions-left">
        @can('billing.invoices.view')
            <a href="{{ route('billing.invoices.index') }}" class="btn btn-label-secondary">
                <i class="bx bx-arrow-back me-1"></i>{{ __('Back to List') }}
            </a>
        @endcan
    </div>
    
    <div class="form-actions-right d-flex gap-2">
        @if(isset($invoice) && $invoice->exists)
            {{-- Edit Mode Actions --}}
            @can('billing.invoices.view')
                <a href="{{ route('billing.invoices.show', $invoice->id) }}" class="btn btn-label-info">
                    <i class="bx bx-show-alt me-1"></i>{{ __('View') }}
                </a>
            @endcan
            
            @can('billing.invoices.send')
                <button type="button" class="btn btn-label-primary" onclick="sendInvoice({{ $invoice->id }})">
                    <i class="bx bx-send me-1"></i>{{ __('Send') }}
                </button>
            @endcan
            
            @can('billing.invoices.print')
                <a href="{{ route('billing.invoices.print', $invoice->id) }}" target="_blank" class="btn btn-label-secondary">
                    <i class="bx bx-printer me-1"></i>{{ __('Print') }}
                </a>
            @endcan
            
            @can('billing.invoices.duplicate')
                <button type="button" class="btn btn-label-warning" onclick="duplicateInvoice({{ $invoice->id }})">
                    <i class="bx bx-copy me-1"></i>{{ __('Duplicate') }}
                </button>
            @endcan
            
            @can('billing.invoices.edit')
                <button type="submit" name="action" value="save_continue" class="btn btn-label-success">
                    <i class="bx bx-save me-1"></i>{{ __('Save & Continue') }}
                </button>
                <button type="submit" name="action" value="save" class="btn btn-primary">
                    <i class="bx bx-check me-1"></i>{{ __('Save Changes') }}
                </button>
            @else
                {{-- Read-only mode when user can't edit --}}
                <span class="text-muted">{{ __('Read-only access') }}</span>
            @endcan
            
            @can('billing.invoices.delete')
                <button type="button" class="btn btn-danger" onclick="deleteInvoice({{ $invoice->id }})">
                    <i class="bx bx-trash me-1"></i>{{ __('Delete') }}
                </button>
            @endcan
        @else
            {{-- Create Mode Actions --}}
            @can('billing.invoices.create')
                <button type="button" class="btn btn-label-secondary" onclick="saveDraft()">
                    <i class="bx bx-save me-1"></i>{{ __('Save as Draft') }}
                </button>
                <button type="submit" name="action" value="save_continue" class="btn btn-label-success">
                    <i class="bx bx-save me-1"></i>{{ __('Save & Continue') }}
                </button>
                <button type="submit" name="action" value="create" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>{{ __('Create Invoice') }}
                </button>
            @else
                {{-- User doesn't have create permission --}}
                <div class="alert alert-warning d-inline-block mb-0 py-2 px-3">
                    <i class="bx bx-info-circle me-1"></i>{{ __('You do not have permission to create invoices') }}
                </div>
            @endcan
        @endif
    </div>
</div>

{{-- Status Change Actions (only if invoice exists) --}}
@if(isset($invoice) && $invoice->exists)
    <div class="mt-3 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ __('Status Actions:') }}</strong>
            </div>
            <div class="d-flex gap-2">
                @canany(['billing.invoices.mark-sent', 'billing.invoices.mark-paid', 'billing.invoices.mark-overdue'])
                    @if($invoice->status === 'draft')
                        @can('billing.invoices.mark-sent')
                            <button type="button" class="btn btn-sm btn-label-info" onclick="changeInvoiceStatus({{ $invoice->id }}, 'sent')">
                                <i class="bx bx-send me-1"></i>{{ __('Mark as Sent') }}
                            </button>
                        @endcan
                    @endif
                    
                    @if(in_array($invoice->status, ['sent', 'overdue']))
                        @can('billing.invoices.mark-paid')
                            <button type="button" class="btn btn-sm btn-label-success" onclick="changeInvoiceStatus({{ $invoice->id }}, 'paid')">
                                <i class="bx bx-check me-1"></i>{{ __('Mark as Paid') }}
                            </button>
                        @endcan
                    @endif
                    
                    @if($invoice->status === 'sent' && $invoice->isOverdue())
                        @can('billing.invoices.mark-overdue')
                            <button type="button" class="btn btn-sm btn-label-danger" onclick="changeInvoiceStatus({{ $invoice->id }}, 'overdue')">
                                <i class="bx bx-time me-1"></i>{{ __('Mark as Overdue') }}
                            </button>
                        @endcan
                    @endif
                @else
                    <span class="text-muted small">{{ __('No status change permissions') }}</span>
                @endcanany
            </div>
        </div>
    </div>
@endif

{{-- JavaScript for actions --}}
@push('scripts')
<script>
function sendInvoice(invoiceId) {
    @can('billing.invoices.send')
        // Implementation for sending invoice
        Swal.fire({
            title: '{{ __('Send Invoice?') }}',
            text: '{{ __('This will send the invoice to the client via email.') }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, send it!') }}',
            cancelButtonText: '{{ __('Cancel') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                // Make AJAX call to send invoice
                window.location.href = `/billing/invoices/${invoiceId}/send`;
            }
        });
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to send invoices.') }}', 'error');
    @endcan
}

function duplicateInvoice(invoiceId) {
    @can('billing.invoices.duplicate')
        window.location.href = `/billing/invoices/${invoiceId}/duplicate`;
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to duplicate invoices.') }}', 'error');
    @endcan
}

function deleteInvoice(invoiceId) {
    @can('billing.invoices.delete')
        Swal.fire({
            title: '{{ __('Delete Invoice?') }}',
            text: '{{ __('This action cannot be undone!') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, delete it!') }}',
            cancelButtonText: '{{ __('Cancel') }}',
            confirmButtonClass: 'btn btn-danger'
        }).then((result) => {
            if (result.isConfirmed) {
                // Make delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/billing/invoices/${invoiceId}`;
                form.innerHTML = `
                    @csrf
                    @method('DELETE')
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to delete invoices.') }}', 'error');
    @endcan
}

function changeInvoiceStatus(invoiceId, status) {
    const statusPermissions = {
        'sent': '{{ auth()->user()->can('billing.invoices.mark-sent') ? 'true' : 'false' }}',
        'paid': '{{ auth()->user()->can('billing.invoices.mark-paid') ? 'true' : 'false' }}',
        'overdue': '{{ auth()->user()->can('billing.invoices.mark-overdue') ? 'true' : 'false' }}'
    };
    
    if (statusPermissions[status] === 'false') {
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to change this status.') }}', 'error');
        return;
    }
    
    // Make AJAX call to update status
    fetch(`/billing/invoices/${invoiceId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: status })
    }).then(response => {
        if (response.ok) {
            location.reload();
        } else {
            Swal.fire('{{ __('Error') }}', '{{ __('Failed to update status.') }}', 'error');
        }
    });
}

function saveDraft() {
    @can('billing.invoices.create')
        // Add 'draft' status to form
        const form = document.querySelector('form');
        const draftInput = document.createElement('input');
        draftInput.type = 'hidden';
        draftInput.name = 'status';
        draftInput.value = 'draft';
        form.appendChild(draftInput);
        form.submit();
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to create invoices.') }}', 'error');
    @endcan
}
</script>
@endpush