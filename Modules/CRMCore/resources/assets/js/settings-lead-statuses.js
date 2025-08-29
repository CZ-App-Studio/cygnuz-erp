'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // DOM Elements
    const offcanvasElement = document.getElementById('offcanvasLeadStatusForm');
    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
    const leadStatusForm = document.getElementById('leadStatusForm');
    const saveStatusBtn = $('#saveStatusBtn');
    const statusListElement = document.getElementById('status-list');

    // Ensure pageData is available from Blade
    if (typeof pageData === 'undefined' || !pageData.urls) {
        console.error('pageData object with URLs is not defined in the Blade view.');
        return;
    }

    // --- Helper Functions ---
    const getUrl = (template, id) => template.replace(':id', id);

    const resetFormValidation = (form) => {
        $(form).find('.is-invalid').removeClass('is-invalid');
        $(form).find('.invalid-feedback').text('');
    };

    const resetOffcanvasForm = () => {
        resetFormValidation(leadStatusForm);
        leadStatusForm.reset();
        $('#status_id').val('');
        $('#formMethod').val('POST'); // Default to POST for create
        $('#offcanvasLeadStatusFormLabel').text(pageData.labels.addLeadStatus);
        $('#status_is_default').prop('checked', false);
        $('#status_is_final').prop('checked', false);
        saveStatusBtn.prop('disabled', false).html(pageData.labels.saveStatus);
    };

    const populateOffcanvasForEdit = (status) => {
        resetOffcanvasForm();
        $('#offcanvasLeadStatusFormLabel').text(pageData.labels.editLeadStatus);
        $('#status_id').val(status.id);
        $('#formMethod').val('PUT'); // Method for update

        $('#status_name').val(status.name);
        $('#status_color').val(status.color || '#6c757d'); // Default color if null
        $('#status_is_default').prop('checked', status.is_default);
        $('#status_is_final').prop('checked', status.is_final);

        offcanvas.show();
    };

    // Re-renders the status list (simple version, for complex lists consider a templating engine or full reload)
    const refreshStatusList = () => {
        // For a truly dynamic update without page reload, you'd fetch all statuses again and rebuild the list.
        // For simplicity here, we'll just advise a reload or assume few enough items that it's not a major issue.
        // A more robust solution would fetch `pageData.statuses` again or use the response from store/update.
        // This is a placeholder for a more complete refresh if needed.
        // For now, the controller actions for store/update would typically trigger a page reload
        // or the JS would manipulate the DOM directly if only one item changes.
        // Given AJAX form, best is to reload the list dynamically:
        window.location.reload(); // Simplest for now, or implement dynamic list update
    };


    // --- Offcanvas Management ---
    $('#add-new-status-btn').on('click', function () {
        resetOffcanvasForm();
        offcanvas.show();
    });

    $(document).on('click', '.edit-lead-status', function () {
        const url = getUrl(pageData.urls.getLeadStatusTemplate, $(this).data('id'));
        $.get(url, function (response) {
            populateOffcanvasForEdit(response);
        }).fail(function() {
            Swal.fire(pageData.labels.error, pageData.labels.couldNotFetch, 'error');
        });
    });

    offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);

    // --- Form Submission (AJAX) ---
    $(leadStatusForm).on('submit', function (e) {
        e.preventDefault();
        resetFormValidation(this);

        const statusId = $('#status_id').val();
        let url = pageData.urls.store;
        let method = 'POST'; // HTML form method is POST

        if (statusId) {
            url = getUrl(pageData.urls.updateTemplate, statusId);
            // For PUT, Laravel expects _method field in FormData or POST with X-HTTP-Method-Override
        }

        const formData = new FormData(this);
        // Ensure boolean values are sent correctly (unchecked checkboxes are not sent)
        formData.set('is_default', $('#status_is_default').is(':checked') ? '1' : '0');
        formData.set('is_final', $('#status_is_final').is(':checked') ? '1' : '0');


        const originalButtonText = saveStatusBtn.html();
        saveStatusBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + pageData.labels.saving);

        $.ajax({
            url: url, type: 'POST', data: formData, processData: false, contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    offcanvas.hide();
                    Swal.fire(pageData.labels.success, response.message, 'success');
                    refreshStatusList(); // Reload the list or page
                } else {
                     Swal.fire(pageData.labels.error, response.message || pageData.labels.operationFailed, 'error');
                }
            },
            error: function (jqXHR) {
                if (jqXHR.status === 422 && jqXHR.responseJSON?.data?.errors) {
                    $.each(jqXHR.responseJSON.data.errors, function (key, value) {
                        const input = $(`#status_${key}`); // Assuming IDs like status_name
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(value[0]);
                        }
                    });
                    Swal.fire(pageData.labels.validationError, jqXHR.responseJSON.message || pageData.labels.correctErrors, 'error');

                } else {
                    Swal.fire(pageData.labels.error, jqXHR.responseJSON?.message || pageData.labels.unexpectedError, 'error');
                }
            },
            complete: function () {
                saveStatusBtn.prop('disabled', false).html(originalButtonText);
            }
        });
    });

    // --- Delete Status (AJAX) ---
    $(document).on('click', '.delete-lead-status', function () {
        const statusId = $(this).data('id');
        const url = getUrl(pageData.urls.destroyTemplate, statusId);

        Swal.fire({
            title: pageData.labels.confirmDelete, text: pageData.labels.confirmDeleteText, icon: 'warning',
            showCancelButton: true, confirmButtonText: pageData.labels.confirmDeleteButton, cancelButtonText: pageData.labels.cancelButton,
            customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                Swal.fire({ title: pageData.labels.deleting, allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                $.ajax({
                    url: url, type: 'DELETE',
                    success: function (response) {
                        Swal.close();
                        if (response.status === 'success') {
                            Swal.fire(pageData.labels.deleted, response.message, 'success');
                            refreshStatusList(); // Reload or remove item from DOM
                        } else {
                            Swal.fire(pageData.labels.error + '!', response.message || pageData.labels.couldNotDelete, 'error');
                        }
                    },
                    error: function () {
                        Swal.close();
                        Swal.fire(pageData.labels.error + '!', pageData.labels.unexpectedError, 'error');
                    }
                });
            }
        });
    });

    // --- SortableJS for Reordering ---
    if (statusListElement) {
        new Sortable(statusListElement, {
            animation: 150,
            handle: '.bx-grid-vertical', // Optional: if you want a specific handle for dragging
            onEnd: function (evt) {
                const order = Array.from(statusListElement.children).map(item => $(item).data('id'));
                $.ajax({
                    url: pageData.urls.updateOrder,
                    type: 'POST',
                    data: { order: order },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({ icon: 'success', title: pageData.labels.orderUpdated, text: response.message, timer: 1000, showConfirmButton: false });
                        } else {
                             Swal.fire(pageData.labels.error, response.message || pageData.labels.couldNotUpdateOrder, 'error');
                             // Consider reverting order visually if backend fails
                        }
                    },
                    error: function() {
                        Swal.fire(pageData.labels.error, pageData.labels.failedToSaveOrder, 'error');
                    }
                });
            }
        });
    }
});
