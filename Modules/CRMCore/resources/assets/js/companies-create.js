$(function () {
    // Initialize Select2
    $('.select2').select2({
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option';
        }
    });

    // Initialize user select with AJAX
    const userSelect = $('.select2-users');
    if (userSelect.length) {
        userSelect.select2({
            placeholder: userSelect.data('placeholder') || 'Select an option',
            allowClear: true,
            ajax: {
                url: userSelect.data('ajax--url'),
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });
    }
});