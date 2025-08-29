<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>{{ __('Changed At') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Setting Key') }}</th>
                <th>{{ __('Old Value') }}</th>
                <th>{{ __('New Value') }}</th>
                <th>{{ __('Changed By') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $item)
            <tr>
                <td>
                    <span class="text-nowrap">{{ $item->changed_at->format('Y-m-d H:i:s') }}</span>
                    <br>
                    <small class="text-muted">{{ $item->changed_at->diffForHumans() }}</small>
                </td>
                <td>
                    @if($item->setting_type === 'system')
                        <span class="badge bg-label-primary">{{ __('System') }}</span>
                    @else
                        <span class="badge bg-label-info">{{ __('Module') }}</span>
                        @if($item->module)
                            <br>
                            <small>{{ $item->module }}</small>
                        @endif
                    @endif
                </td>
                <td>
                    <code>{{ $item->setting_key }}</code>
                </td>
                <td>
                    @if(is_array($item->old_value) || is_object($item->old_value))
                        <pre class="mb-0">{{ json_encode($item->old_value, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        <span class="text-muted">{{ $item->old_value ?: '-' }}</span>
                    @endif
                </td>
                <td>
                    @if(is_array($item->new_value) || is_object($item->new_value))
                        <pre class="mb-0">{{ json_encode($item->new_value, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        <span>{{ $item->new_value ?: '-' }}</span>
                    @endif
                </td>
                <td>
                    @if($item->user)
                        <x-datatable-user :user="$item->user" :linkRoute="'account.viewUser'" />
                    @else
                        <span class="text-muted">{{ __('System') }}</span>
                    @endif
                    @if($item->ip_address)
                        <br>
                        <small class="text-muted">{{ $item->ip_address }}</small>
                    @endif
                </td>
                <td>
                    <button class="btn btn-sm btn-icon btn-label-warning" 
                            onclick="rollbackSetting({{ $item->id }})"
                            title="{{ __('Rollback to previous value') }}">
                        <i class="bx bx-undo"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bx bx-history bx-lg mb-2"></i>
                        <p>{{ __('No changes recorded yet') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($history->hasPages())
<div class="mt-3">
    <nav aria-label="Settings history pagination">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            {{-- Previous Page Link --}}
            @if ($history->onFirstPage())
                <li class="page-item disabled"><span class="page-link">{{ __('Previous') }}</span></li>
            @else
                <li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadHistoryPage({{ $history->currentPage() - 1 }})">{{ __('Previous') }}</a></li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($history->getUrlRange(1, $history->lastPage()) as $page => $url)
                @if ($page == $history->currentPage())
                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadHistoryPage({{ $page }})">{{ $page }}</a></li>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($history->hasMorePages())
                <li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadHistoryPage({{ $history->currentPage() + 1 }})">{{ __('Next') }}</a></li>
            @else
                <li class="page-item disabled"><span class="page-link">{{ __('Next') }}</span></li>
            @endif
        </ul>
    </nav>
    
    {{-- Pagination Info --}}
    <div class="text-center mt-2">
        <small class="text-muted">
            {{ __('Showing') }} {{ $history->firstItem() }} {{ __('to') }} {{ $history->lastItem() }} {{ __('of') }} {{ $history->total() }} {{ __('results') }}
        </small>
    </div>
</div>
@endif

<script>
function rollbackSetting(historyId) {
    Swal.fire({
        title: @json(__('Rollback Setting?')),
        text: @json(__('This will restore the setting to its previous value.')),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: @json(__('Yes, rollback')),
        cancelButtonText: @json(__('Cancel'))
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('settings/history') }}/${historyId}/rollback`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: @json(__('Success')),
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            loadHistory();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('Error')),
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('Error')),
                        text: @json(__('Failed to rollback setting'))
                    });
                }
            });
        }
    });
}

function loadHistoryPage(page) {
    // Show loading state
    $('#settings-content').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    // Load the history page via AJAX
    $.ajax({
        url: '{{ route("settings.history.index") }}',
        method: 'GET',
        data: { page: page },
        success: function(response) {
            $('#settings-content').html(response);
        },
        error: function() {
            $('#settings-content').html(`
                <div class="alert alert-danger">
                    <i class="bx bx-error-circle me-2"></i>
                    Failed to load history page. Please try again.
                </div>
            `);
        }
    });
}

// Function to refresh history (used by rollback)
function loadHistory() {
    loadHistoryPage(1);
}
</script>