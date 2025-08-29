/**
 * AI Request Logs Management
 */
$(function() {
    'use strict';
    
    // Initialize select2
    $('.select2').select2();
    
    // Initialize date pickers
    $('.date-picker').flatpickr({
        dateFormat: 'Y-m-d'
    });
    
    // Get page data from blade
    const pageData = window.aiLogsPageData || {};
    
    // DataTable initialization
    var dt = $('#logsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.datatableUrl,
            type: 'GET',
            data: function(d) {
                d.module = $('#filterModule').val();
                d.user_id = $('#filterUser').val();
                d.model_id = $('#filterModel').val();
                d.status = $('#filterStatus').val();
                d.is_flagged = $('#filterFlagged').val();
                d.date_from = $('#dateFrom').val();
                d.date_to = $('#dateTo').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTable Ajax Error:', error, thrown);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            {data: 'id', name: 'id'},
            {data: 'user_display', name: 'user_id', orderable: false},
            {data: 'module_name', name: 'module_name'},
            {data: 'model_display', name: 'model_id', orderable: false},
            {data: 'prompt_preview', name: 'request_prompt', orderable: false},
            {data: 'response_preview', name: 'response_content', orderable: false},
            {data: 'status_badge', name: 'status'},
            {data: 'tokens_display', name: 'total_tokens', orderable: false},
            {data: 'cost_display', name: 'cost'},
            {data: 'flag_status', name: 'is_flagged', orderable: false},
            {data: 'review_status', name: 'reviewed_at', orderable: false},
            {data: 'created_at', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[11, 'desc']],
        dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                text: '<i class="bx bx-refresh me-1"></i> Refresh',
                className: 'btn btn-secondary',
                action: function(e, dt, node, config) {
                    dt.ajax.reload();
                    loadStatistics();
                }
            },
            {
                text: '<i class="bx bx-download me-1"></i> Export',
                className: 'btn btn-primary',
                action: function(e, dt, node, config) {
                    var params = {
                        module: $('#filterModule').val(),
                        user_id: $('#filterUser').val(),
                        model_id: $('#filterModel').val(),
                        status: $('#filterStatus').val(),
                        is_flagged: $('#filterFlagged').val(),
                        date_from: $('#dateFrom').val(),
                        date_to: $('#dateTo').val()
                    };
                    window.location.href = pageData.exportUrl + '?' + $.param(params);
                }
            }
        ]
    });
    
    $('div.head-label').html('<h5 class="card-title mb-0">' + pageData.tableTitle + '</h5>');
    
    // Filter handlers
    $('.filter-input').on('change', function() {
        dt.ajax.reload();
    });
    
    // Load statistics
    function loadStatistics() {
        $.ajax({
            url: pageData.statisticsUrl,
            data: {
                date_from: $('#dateFrom').val(),
                date_to: $('#dateTo').val()
            },
            success: function(data) {
                $('#totalRequests').text(data.total_requests);
                $('#successfulRequests').text(data.successful_requests);
                $('#errorRequests').text(data.error_requests);
                $('#totalCost').text('$' + (parseFloat(data.total_cost) || 0).toFixed(2));
                $('#totalTokens').text((parseInt(data.total_tokens) || 0).toLocaleString());
                $('#flaggedCount').text(data.flagged_count);
                $('#unreviewedCount').text(data.unreviewed_count);
            }
        });
    }
    
    loadStatistics();
    
    // Flag toggle
    $(document).on('change', '.flag-toggle', function() {
        var id = $(this).data('id');
        var checkbox = $(this);
        
        $.ajax({
            url: pageData.baseUrl + '/' + id + '/flag',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Flag status updated');
                    }
                    dt.ajax.reload(null, false);
                }
            },
            error: function() {
                checkbox.prop('checked', !checkbox.prop('checked'));
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to update flag status');
                } else {
                    alert('Failed to update flag status');
                }
            }
        });
    });
    
    // View details
    $(document).on('click', '.view-details', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: pageData.baseUrl + '/' + id,
            success: function(response) {
                $('#detailsModalBody').html(response.html);
                var detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
                detailsModal.show();
            },
            error: function() {
                alert('Failed to load details');
            }
        });
    });
    
    // Review log
    $(document).on('click', '.review-log', function() {
        var id = $(this).data('id');
        $('#reviewLogId').val(id);
        var reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
        reviewModal.show();
    });
    
    // Submit review
    $('#reviewForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#reviewLogId').val();
        
        $.ajax({
            url: pageData.baseUrl + '/' + id + '/review',
            type: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    }
                    var reviewModal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                    reviewModal.hide();
                    $('#reviewForm')[0].reset();
                    dt.ajax.reload(null, false);
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to mark as reviewed');
                } else {
                    alert('Failed to mark as reviewed');
                }
            }
        });
    });
    
    // Copy prompt
    $(document).on('click', '.copy-prompt', function() {
        var prompt = $(this).data('prompt');
        navigator.clipboard.writeText(prompt).then(function() {
            if (typeof toastr !== 'undefined') {
                toastr.success('Prompt copied to clipboard');
            }
        }).catch(function() {
            // Fallback for older browsers
            var textArea = document.createElement("textarea");
            textArea.value = prompt;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                if (typeof toastr !== 'undefined') {
                    toastr.success('Prompt copied to clipboard');
                }
            } catch (err) {
                console.error('Failed to copy prompt');
            }
            document.body.removeChild(textArea);
        });
    });
});