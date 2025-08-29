$(function () {
    // Setup AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Toggle blacklist
    window.toggleBlacklist = function(id, blacklist) {
        $('#blacklist-action').val(blacklist);
        
        if (blacklist) {
            $('#blacklistModalTitle').text(pageData.labels.addToBlacklist);
            $('#blacklist-reason-group').show();
            $('#blacklist-warning').show();
            $('#blacklistForm button[type="submit"]').removeClass('btn-success').addClass('btn-danger').text(pageData.labels.addToBlacklist);
        } else {
            $('#blacklistModalTitle').text(pageData.labels.removeFromBlacklist);
            $('#blacklist-reason-group').hide();
            $('#blacklist-warning').hide();
            $('#blacklistForm button[type="submit"]').removeClass('btn-danger').addClass('btn-success').text(pageData.labels.removeFromBlacklist);
        }
        
        $('#blacklistModal').modal('show');
    }
    
    // Blacklist form submission
    $('#blacklistForm').on('submit', function(e) {
        e.preventDefault();
        
        const blacklist = $('#blacklist-action').val() === 'true';
        const reason = $('#blacklist-reason').val();
        
        $.post(pageData.urls.blacklist, {
            blacklist: blacklist,
            reason: reason
        }).done(function(response) {
            if (response.status === 'success') {
                toastr.success(response.data.message);
                $('#blacklistModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        }).fail(function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response.data || pageData.labels.errorOccurred);
        });
    });
});