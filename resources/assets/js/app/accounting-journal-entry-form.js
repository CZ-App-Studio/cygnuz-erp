$(function () {
    'use strict';

    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize components
    initializeDatePicker();
    initializeRepeater();
    initializeValidation();
    initializeCalculations();

    /**
     * Initialize Flatpickr for date fields
     */
    function initializeDatePicker() {
        if ($('#entry_date').length) {
            $('#entry_date').flatpickr({
                dateFormat: 'Y-m-d',
                defaultDate: new Date()
            });
        }
    }

    /**
     * Initialize jQuery Repeater for journal entry lines
     */
    function initializeRepeater() {
        $('#journal-lines-repeater').repeater({
            show: function () {
                $(this).slideDown();
                // Initialize Select2 for new rows
                $(this).find('.select2-account').select2({
                    placeholder: (pageData.text && pageData.text.selectAccount) || 'Select Account',
                    allowClear: true
                });
                // Recalculate totals
                calculateTotals();
            },
            hide: function (deleteElement) {
                if (confirm('Are you sure you want to delete this line?')) {
                    $(this).slideUp(deleteElement);
                    // Recalculate totals after deletion
                    setTimeout(calculateTotals, 300);
                }
            },
            ready: function () {
                // Initialize Select2 for existing rows
                $('.select2-account').select2({
                    placeholder: (pageData.text && pageData.text.selectAccount) || 'Select Account',
                    allowClear: true
                });
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initializeValidation() {
        const form = $('#journalEntryForm');

        // Prevent both debit and credit in same line
        $(document).on('input', '.debit-amount', function() {
            const row = $(this).closest('[data-repeater-item]');
            const creditField = row.find('.credit-amount');

            if ($(this).val() && parseFloat($(this).val()) > 0) {
                creditField.prop('readonly', true).val('');
            } else {
                creditField.prop('readonly', false);
            }
            calculateTotals();
        });

        $(document).on('input', '.credit-amount', function() {
            const row = $(this).closest('[data-repeater-item]');
            const debitField = row.find('.debit-amount');

            if ($(this).val() && parseFloat($(this).val()) > 0) {
                debitField.prop('readonly', true).val('');
            } else {
                debitField.prop('readonly', false);
            }
            calculateTotals();
        });

        // Form submission
        form.on('submit', function(e) {
            e.preventDefault();

            if (!validateForm()) {
                return false;
            }

            submitForm();
        });
    }

    /**
     * Initialize calculations
     */
    function initializeCalculations() {
        // Calculate totals on any amount field change
        $(document).on('input', '.debit-amount, .credit-amount', function() {
            calculateTotals();
        });

        // Initial calculation
        calculateTotals();
    }

    /**
     * Calculate totals and check balance
     */
    function calculateTotals() {
        let totalDebits = 0;
        let totalCredits = 0;

        // Calculate debits
        $('.debit-amount').each(function() {
            const value = parseFloat($(this).val()) || 0;
            totalDebits += value;
        });

        // Calculate credits
        $('.credit-amount').each(function() {
            const value = parseFloat($(this).val()) || 0;
            totalCredits += value;
        });

        const difference = Math.abs(totalDebits - totalCredits);
        const isBalanced = difference < 0.01; // Allow for small rounding differences

        // Update display
        $('#total-debits').text(totalDebits.toFixed(2));
        $('#total-credits').text(totalCredits.toFixed(2));
        $('#difference').text(difference.toFixed(2));

        // Update balance status
        const statusBadge = $('#balance-status');
        const balanceAlert = $('#balance-alert');

        if (isBalanced && totalDebits > 0) {
            statusBadge.removeClass('bg-label-warning bg-label-danger')
                      .addClass('bg-label-success')
                      .text((pageData.text && pageData.text.balanced) || 'Balanced');
            balanceAlert.hide();
        } else {
            statusBadge.removeClass('bg-label-success bg-label-danger')
                      .addClass('bg-label-warning')
                      .text((pageData.text && pageData.text.notBalanced) || 'Not Balanced');
            balanceAlert.show();
        }
    }

    /**
     * Validate form before submission
     */
    function validateForm() {
        let isValid = true;
        const errorMessages = [];

        // Clear previous validation messages
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').hide();

        // Check required fields
        if (!$('#entry_date').val()) {
            showFieldError('#entry_date', 'Entry date is required');
            isValid = false;
        }

        if (!$('textarea[name="description"]').val().trim()) {
            showFieldError('textarea[name="description"]', 'Description is required');
            isValid = false;
        }

        // Check journal lines
        const lines = $('[data-repeater-item]');
        if (lines.length < 2) {
            errorMessages.push('At least two journal entry lines are required');
            isValid = false;
        }

        let hasValidLines = false;
        lines.each(function() {
            const accountId = $(this).find('select[name*="chart_of_account_id"]').val();
            const debitAmount = parseFloat($(this).find('input[name*="debit_amount"]').val()) || 0;
            const creditAmount = parseFloat($(this).find('input[name*="credit_amount"]').val()) || 0;

            if (!accountId) {
                showFieldError($(this).find('select[name*="chart_of_account_id"]'), 'Account is required');
                isValid = false;
            }

            if (debitAmount === 0 && creditAmount === 0) {
                showFieldError($(this).find('input[name*="debit_amount"]'), 'Either debit or credit amount is required');
                isValid = false;
            }

            if (debitAmount > 0 || creditAmount > 0) {
                hasValidLines = true;
            }
        });

        if (!hasValidLines) {
            errorMessages.push('At least one line must have a debit or credit amount');
            isValid = false;
        }

        // Check if balanced
        const totalDebits = parseFloat($('#total-debits').text()) || 0;
        const totalCredits = parseFloat($('#total-credits').text()) || 0;
        const difference = Math.abs(totalDebits - totalCredits);

        if (difference >= 0.01) {
            errorMessages.push('Journal entry must be balanced (debits must equal credits)');
            isValid = false;
        }

        // Show general error messages
        if (errorMessages.length > 0) {
            Swal.fire({
                title: 'Validation Error',
                html: errorMessages.join('<br>'),
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }

        return isValid;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        const $field = $(field);
        $field.addClass('is-invalid');
        $field.siblings('.invalid-feedback').text(message).show();
    }

    /**
     * Submit form via AJAX
     */
    function submitForm() {
        const form = $('#journalEntryForm');
        const submitButton = form.find('button[type="submit"]');
        const originalText = submitButton.html();

        // Show loading state
        submitButton.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + ((pageData.text && pageData.text.processing) || 'Processing...'));

        $.ajax({
            url: pageData.urls.store || pageData.urls.update,
            method: pageData.mode === 'edit' ? 'PUT' : 'POST',
            data: form.serialize(),
            success: function(response) {
                Swal.fire({
                    title: 'Success!',
                    text: (pageData.text && pageData.text.saved) || 'Saved successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = pageData.urls.index;
                });
            },
            error: function(xhr) {
                submitButton.prop('disabled', false).html(originalText);

                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = '';

                    for (const field in errors) {
                        if (errors.hasOwnProperty(field)) {
                            errorMessage += errors[field].join('<br>') + '<br>';

                            // Show field-specific errors
                            const fieldName = field.replace('lines.', '').replace(/\.\d+\./, '.');
                            const $field = $('[name*="' + fieldName + '"]').first();
                            if ($field.length) {
                                showFieldError($field, errors[field][0]);
                            }
                        }
                    }

                    Swal.fire({
                        title: 'Validation Error',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: (pageData.text && pageData.text.error) || 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    }
});
