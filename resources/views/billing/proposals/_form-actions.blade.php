{{-- Proposal Form Actions Partial --}}
<div class="d-flex justify-content-between align-items-center">
    <div class="form-actions-left">
        @can('billing.proposals.view')
            <a href="{{ route('billing.proposals.index') }}" class="btn btn-label-secondary">
                <i class="bx bx-arrow-back me-1"></i>{{ __('Back to List') }}
            </a>
        @endcan
    </div>
    
    <div class="form-actions-right d-flex gap-2">
        @if(isset($proposal) && $proposal->exists)
            {{-- Edit Mode Actions --}}
            @can('billing.proposals.view')
                <a href="{{ route('billing.proposals.show', $proposal->id) }}" class="btn btn-label-info">
                    <i class="bx bx-show-alt me-1"></i>{{ __('View') }}
                </a>
            @endcan
            
            @can('billing.proposals.send')
                <button type="button" class="btn btn-label-primary" onclick="sendProposal({{ $proposal->id }})">
                    <i class="bx bx-send me-1"></i>{{ __('Send') }}
                </button>
            @endcan
            
            @can('billing.proposals.print')
                <a href="{{ route('billing.proposals.print', $proposal->id) }}" target="_blank" class="btn btn-label-secondary">
                    <i class="bx bx-printer me-1"></i>{{ __('Print') }}
                </a>
            @endcan
            
            @can('billing.proposals.duplicate')
                <button type="button" class="btn btn-label-warning" onclick="duplicateProposal({{ $proposal->id }})">
                    <i class="bx bx-copy me-1"></i>{{ __('Duplicate') }}
                </button>
            @endcan
            
            @can('billing.proposals.convert')
                <button type="button" class="btn btn-label-success" onclick="convertToInvoice({{ $proposal->id }})">
                    <i class="bx bx-transfer me-1"></i>{{ __('Convert to Invoice') }}
                </button>
            @endcan
            
            @can('billing.proposals.edit')
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
            
            @can('billing.proposals.delete')
                <button type="button" class="btn btn-danger" onclick="deleteProposal({{ $proposal->id }})">
                    <i class="bx bx-trash me-1"></i>{{ __('Delete') }}
                </button>
            @endcan
        @else
            {{-- Create Mode Actions --}}
            @can('billing.proposals.create')
                <button type="button" class="btn btn-label-secondary" onclick="saveDraft()">
                    <i class="bx bx-save me-1"></i>{{ __('Save as Draft') }}
                </button>
                <button type="submit" name="action" value="save_continue" class="btn btn-label-success">
                    <i class="bx bx-save me-1"></i>{{ __('Save & Continue') }}
                </button>
                <button type="submit" name="action" value="create" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>{{ __('Create Proposal') }}
                </button>
            @else
                {{-- User doesn't have create permission --}}
                <div class="alert alert-warning d-inline-block mb-0 py-2 px-3">
                    <i class="bx bx-info-circle me-1"></i>{{ __('You do not have permission to create proposals') }}
                </div>
            @endcan
        @endif
    </div>
</div>

{{-- Status Change Actions (only if proposal exists) --}}
@if(isset($proposal) && $proposal->exists)
    <div class="mt-3 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ __('Status Actions:') }}</strong>
            </div>
            <div class="d-flex gap-2">
                @canany(['billing.proposals.accept', 'billing.proposals.reject', 'billing.proposals.revise'])
                    @if($proposal->status === 'draft')
                        @can('billing.proposals.send')
                            <button type="button" class="btn btn-sm btn-label-info" onclick="changeProposalStatus({{ $proposal->id }}, 'sent')">
                                <i class="bx bx-send me-1"></i>{{ __('Mark as Sent') }}
                            </button>
                        @endcan
                    @endif
                    
                    @if(in_array($proposal->status, ['sent', 'under_review']))
                        @can('billing.proposals.accept')
                            <button type="button" class="btn btn-sm btn-label-success" onclick="changeProposalStatus({{ $proposal->id }}, 'accepted')">
                                <i class="bx bx-check me-1"></i>{{ __('Accept') }}
                            </button>
                        @endcan
                        
                        @can('billing.proposals.reject')
                            <button type="button" class="btn btn-sm btn-label-danger" onclick="changeProposalStatus({{ $proposal->id }}, 'rejected')">
                                <i class="bx bx-x me-1"></i>{{ __('Reject') }}
                            </button>
                        @endcan
                        
                        @can('billing.proposals.revise')
                            <button type="button" class="btn btn-sm btn-label-warning" onclick="changeProposalStatus({{ $proposal->id }}, 'revision_requested')">
                                <i class="bx bx-edit me-1"></i>{{ __('Request Revision') }}
                            </button>
                        @endcan
                    @endif
                    
                    @if($proposal->status === 'accepted')
                        @can('billing.proposals.convert')
                            <button type="button" class="btn btn-sm btn-success" onclick="convertToInvoice({{ $proposal->id }})">
                                <i class="bx bx-transfer me-1"></i>{{ __('Convert to Invoice') }}
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
function sendProposal(proposalId) {
    @can('billing.proposals.send')
        Swal.fire({
            title: '{{ __('Send Proposal?') }}',
            text: '{{ __('This will send the proposal to the client via email.') }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, send it!') }}',
            cancelButtonText: '{{ __('Cancel') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/billing/proposals/${proposalId}/send`;
            }
        });
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to send proposals.') }}', 'error');
    @endcan
}

function duplicateProposal(proposalId) {
    @can('billing.proposals.duplicate')
        window.location.href = `/billing/proposals/${proposalId}/duplicate`;
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to duplicate proposals.') }}', 'error');
    @endcan
}

function convertToInvoice(proposalId) {
    @can('billing.proposals.convert')
        Swal.fire({
            title: '{{ __('Convert to Invoice?') }}',
            text: '{{ __('This will create a new invoice based on this proposal.') }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, convert it!') }}',
            cancelButtonText: '{{ __('Cancel') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/billing/proposals/${proposalId}/convert-to-invoice`;
            }
        });
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to convert proposals.') }}', 'error');
    @endcan
}

function deleteProposal(proposalId) {
    @can('billing.proposals.delete')
        Swal.fire({
            title: '{{ __('Delete Proposal?') }}',
            text: '{{ __('This action cannot be undone!') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, delete it!') }}',
            cancelButtonText: '{{ __('Cancel') }}',
            customClass: {
                confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/billing/proposals/${proposalId}`;
                form.innerHTML = `
                    @csrf
                    @method('DELETE')
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to delete proposals.') }}', 'error');
    @endcan
}

function changeProposalStatus(proposalId, status) {
    const statusPermissions = {
        'sent': '{{ auth()->user()->can('billing.proposals.send') ? 'true' : 'false' }}',
        'accepted': '{{ auth()->user()->can('billing.proposals.accept') ? 'true' : 'false' }}',
        'rejected': '{{ auth()->user()->can('billing.proposals.reject') ? 'true' : 'false' }}',
        'revision_requested': '{{ auth()->user()->can('billing.proposals.revise') ? 'true' : 'false' }}'
    };
    
    if (statusPermissions[status] === 'false') {
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to change this status.') }}', 'error');
        return;
    }
    
    let confirmText = '';
    switch(status) {
        case 'accepted':
            confirmText = '{{ __('Are you sure you want to accept this proposal?') }}';
            break;
        case 'rejected':
            confirmText = '{{ __('Are you sure you want to reject this proposal?') }}';
            break;
        case 'revision_requested':
            confirmText = '{{ __('Are you sure you want to request revisions for this proposal?') }}';
            break;
        default:
            confirmText = '{{ __('Are you sure you want to change the status?') }}';
    }
    
    Swal.fire({
        title: '{{ __('Change Status?') }}',
        text: confirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '{{ __('Yes, change it!') }}',
        cancelButtonText: '{{ __('Cancel') }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/billing/proposals/${proposalId}/status`, {
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
    });
}

function saveDraft() {
    @can('billing.proposals.create')
        const form = document.querySelector('form');
        const draftInput = document.createElement('input');
        draftInput.type = 'hidden';
        draftInput.name = 'status';
        draftInput.value = 'draft';
        form.appendChild(draftInput);
        form.submit();
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to create proposals.') }}', 'error');
    @endcan
}
</script>
@endpush