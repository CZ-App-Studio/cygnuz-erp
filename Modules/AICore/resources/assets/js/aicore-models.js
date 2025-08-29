/**
 * AI Core Models JavaScript
 * Handles model management functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Core Models page loaded');
    
    initializeModels();
    setupEventListeners();
    initializeDataTable();
});

/**
 * Initialize models functionality
 */
function initializeModels() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Select2 for filters if available
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#provider-filter, #type-filter, #status-filter').each(function() {
            const $this = $(this);
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: $this.data('placeholder') || 'Select value',
                dropdownParent: $this.parent(),
                allowClear: true,
                width: '100%'
            });
        });
    }
}

/**
 * Initialize DataTable
 */
function initializeDataTable() {
    const table = document.querySelector('#models-table');
    if (!table) return;

    // Initialize DataTable if available
    if (typeof DataTable !== 'undefined') {
        window.modelsDataTable = new DataTable(table, {
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']], // Sort by model name
            columnDefs: [
                { orderable: false, targets: [8] }, // Actions column not sortable
                { type: 'num', targets: [3, 4, 5] } // Numeric columns
            ]
        });
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Filter change events
    document.querySelectorAll('#provider-filter, #type-filter, #status-filter').forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    // Clear filters button
    const clearBtn = document.querySelector('[onclick="clearFilters()"]');
    if (clearBtn) {
        clearBtn.addEventListener('click', clearFilters);
    }


    // Test model buttons
    document.querySelectorAll('.test-model').forEach(button => {
        button.addEventListener('click', function() {
            const modelId = this.getAttribute('data-model-id');
            openModelTestModal(modelId);
        });
    });

    // Model test form
    const testForm = document.querySelector('#model-test-form');
    const runTestBtn = document.querySelector('#run-test-btn');
    
    if (testForm && runTestBtn) {
        runTestBtn.addEventListener('click', runModelTest);
    }

    // Delete form confirmations with SweetAlert2
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formElement = this;
            
            // Use SweetAlert2 for confirmation
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: window.pageData?.translations?.deleteTitle || 'Are you sure?',
                    text: window.pageData?.translations?.deleteConfirm || 'This will permanently delete the AI model. This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: window.pageData?.translations?.confirmButton || 'Yes, delete it!',
                    cancelButtonText: window.pageData?.translations?.cancelButton || 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        formElement.submit();
                    }
                });
            } else {
                // Fallback to default confirm if SweetAlert2 is not available
                const confirmMessage = window.pageData?.translations?.deleteConfirm || 'Are you sure you want to delete this model?';
                if (confirm(confirmMessage)) {
                    formElement.submit();
                }
            }
        });
    });
}

/**
 * Apply table filters
 */
function applyFilters() {
    if (!window.modelsDataTable) return;

    const providerFilter = document.querySelector('#provider-filter').value;
    const typeFilter = document.querySelector('#type-filter').value;
    const statusFilter = document.querySelector('#status-filter').value;

    // Apply filters to DataTable
    window.modelsDataTable
        .column(1).search(providerFilter) // Provider column
        .column(2).search(typeFilter)     // Type column
        .column(7).search(statusFilter)   // Status column
        .draw();
}

/**
 * Clear all filters
 */
function clearFilters() {
    // Reset filter dropdowns and trigger change for Select2
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#provider-filter, #type-filter, #status-filter').val('').trigger('change');
    } else {
        // Fallback for non-Select2
        document.querySelector('#provider-filter').value = '';
        document.querySelector('#type-filter').value = '';
        document.querySelector('#status-filter').value = '';
    }

    // Clear DataTable filters
    if (window.modelsDataTable) {
        window.modelsDataTable
            .search('')
            .columns().search('')
            .draw();
    }
}

/**
 * Open model test modal
 */
function openModelTestModal(modelId) {
    const modal = document.querySelector('#modelTestModal');
    if (!modal) return;

    // Store model ID for testing
    window.currentTestModelId = modelId;

    // Reset form
    const form = document.querySelector('#model-test-form');
    if (form) {
        form.reset();
        // Set default values
        document.querySelector('#test-max-tokens').value = 100;
        document.querySelector('#test-temperature').value = 0.7;
    }

    // Hide previous results
    const resultsDiv = document.querySelector('#test-results');
    if (resultsDiv) {
        resultsDiv.classList.add('d-none');
    }

    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Run model test
 */
function runModelTest() {
    if (!window.currentTestModelId) {
        console.error('No model ID set for testing');
        return;
    }

    const prompt = document.querySelector('#test-prompt').value.trim();
    if (!prompt) {
        alert('Please enter a test prompt');
        return;
    }

    const maxTokens = parseInt(document.querySelector('#test-max-tokens').value) || 100;
    const temperature = parseFloat(document.querySelector('#test-temperature').value) || 0.7;

    const runBtn = document.querySelector('#run-test-btn');
    const originalContent = runBtn.innerHTML;
    
    // Update button state
    runBtn.disabled = true;
    runBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Testing...';

    // Hide previous results
    const resultsDiv = document.querySelector('#test-results');
    if (resultsDiv) {
        resultsDiv.classList.add('d-none');
    }

    // Prepare request data
    const requestData = {
        prompt: prompt,
        max_tokens: maxTokens,
        temperature: temperature,
        module_name: 'AICore'
    };

    // Make API call
    const startTime = Date.now();
    
    // Build the correct URL for the model test endpoint
    const testUrl = `/aicore/models/${window.currentTestModelId}/test`;
    
    fetch(testUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        const endTime = Date.now();
        const responseTime = endTime - startTime;
        
        displayTestResults(data, responseTime);
    })
    .catch(error => {
        console.error('Model test failed:', error);
        displayTestResults({
            success: false,
            message: 'Test failed: ' + error.message
        }, 0);
    })
    .finally(() => {
        // Restore button state
        runBtn.disabled = false;
        runBtn.innerHTML = originalContent;
    });
}

/**
 * Display test results
 */
function displayTestResults(data, responseTime) {
    const resultsDiv = document.querySelector('#test-results');
    const responseElement = document.querySelector('#test-response');
    const tokensElement = document.querySelector('#tokens-used');
    const costElement = document.querySelector('#cost-used');
    const timeElement = document.querySelector('#response-time');

    if (!resultsDiv) return;

    if (data.success && data.data) {
        // Display successful response
        if (responseElement) {
            responseElement.textContent = data.data.response || data.data.content || 'No response content';
            responseElement.className = 'mb-0'; // Reset class
        }
        
        if (tokensElement) {
            // Display detailed token usage if available
            if (data.data.usage) {
                const usage = data.data.usage;
                tokensElement.innerHTML = `Total: ${usage.total_tokens || 0} (Prompt: ${usage.prompt_tokens || 0}, Completion: ${usage.completion_tokens || 0})`;
            } else {
                tokensElement.textContent = data.data.total_tokens || data.data.tokens_used || '-';
            }
        }
        
        if (costElement) {
            const cost = data.data.cost || 0;
            costElement.textContent = '$' + cost.toFixed(6);
        }
        
        if (timeElement) {
            timeElement.textContent = responseTime + ' ms';
        }
        
        // Show usage logged indicator if available
        if (data.data.usage_logged) {
            console.log('Usage analytics recorded for this test');
        }
        
        resultsDiv.classList.remove('d-none');
    } else {
        // Display error
        if (responseElement) {
            responseElement.textContent = `Error: ${data.message || 'Unknown error occurred'}`;
            responseElement.className = 'mb-0 text-danger';
        }
        
        resultsDiv.classList.remove('d-none');
    }
}

// Global functions
window.clearFilters = clearFilters;