/**
 * AI Core Provider Form JavaScript
 * Handles provider create/edit form functionality
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('AI Core Provider Form loaded');
        
        initializeForm();
        setupEventListeners();
    });
    
    /**
     * Initialize form functionality
     */
    function initializeForm() {
        // Initialize Select2 dropdowns
        if ($.fn.select2) {
            // Type dropdown
            $('#type').each(function() {
                var $this = $(this);
                if (!$this.hasClass('select2-hidden-accessible')) {
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownParent: $this.parent(),
                        placeholder: 'Select provider type',
                        allowClear: false
                    });
                }
            });
            
            // Priority dropdown
            $('#priority').each(function() {
                var $this = $(this);
                if (!$this.hasClass('select2-hidden-accessible')) {
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownParent: $this.parent(),
                        minimumResultsForSearch: Infinity
                    });
                }
            });
        }
        
        // Show initial help content
        var currentType = $('#type').val();
        if (currentType) {
            showProviderHelp(currentType);
            updateFormFieldsBasedOnType(currentType);
        }
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Provider type change
        $(document).on('change', '#type', function() {
            var value = $(this).val();
            showProviderHelp(value);
            updateFormFieldsBasedOnType(value);
        });
        
        // API key visibility toggle
        $(document).on('click', '#toggle-api-key', function() {
            var $apiKeyInput = $('#api_key');
            var type = $apiKeyInput.attr('type');
            
            if (type === 'password') {
                $apiKeyInput.attr('type', 'text');
                $(this).html('<i class="bx bx-hide"></i>');
            } else {
                $apiKeyInput.attr('type', 'password');
                $(this).html('<i class="bx bx-show"></i>');
            }
        });
        
        // Test connection button
        $(document).on('click', '#test-connection-btn', function() {
            var providerId = $(this).data('provider-id');
            if (providerId) {
                testExistingProvider(providerId);
            } else {
                testFormData();
            }
        });
        
        // Form submission
        $(document).on('submit', '#provider-form', function(e) {
            var $form = $(this);
            var name = $('#name').val();
            var type = $('#type').val();
            var apiKey = $('#api_key').val();
            
            // Basic validation
            if (!name || name.trim() === '') {
                e.preventDefault();
                alert('Provider name is required');
                $('#name').focus();
                return false;
            }
            
            if (!type) {
                e.preventDefault();
                alert('Provider type is required');
                $('#type').select2('open');
                return false;
            }
            
            if (type !== 'local' && (!apiKey || apiKey.trim() === '')) {
                e.preventDefault();
                alert('API key is required for non-local providers');
                $('#api_key').focus();
                return false;
            }
            
            // Fix checkbox value
            var isActive = $('#is_active').is(':checked');
            if (!isActive && $('#is_active_hidden').length === 0) {
                $form.append('<input type="hidden" name="is_active" id="is_active_hidden" value="0">');
            }
            
            console.log('Form validation passed, submitting...');
            return true;
        });
    }
    
    /**
     * Show provider-specific help content
     */
    function showProviderHelp(providerType) {
        var $helpContainer = $('#provider-help-content');
        if ($helpContainer.length === 0 || !window.pageData || !window.pageData.providerHelp) {
            return;
        }
        
        var helpData = window.pageData.providerHelp[providerType];
        if (!helpData) {
            $helpContainer.html(
                '<div class="text-center text-muted">' +
                '<i class="bx bx-info-circle bx-lg"></i>' +
                '<p class="mt-2">Select a provider type to see setup instructions</p>' +
                '</div>'
            );
            return;
        }
        
        $helpContainer.html(
            '<div class="alert alert-info">' +
            '<h6 class="alert-heading mb-2">' + helpData.title + '</h6>' +
            '<div class="small">' + helpData.content + '</div>' +
            '</div>'
        );
    }
    
    /**
     * Update form fields based on provider type
     */
    function updateFormFieldsBasedOnType(providerType) {
        var $endpointInput = $('#endpoint_url');
        var $apiKeyInput = $('#api_key');
        var $rateLimitInput = $('#max_requests_per_minute');
        var $tokenLimitInput = $('#max_tokens_per_request');
        var $costInput = $('#cost_per_token');
        
        // Set default values based on provider type
        var defaults = {
            openai: {
                endpoint: 'https://api.openai.com/v1',
                rateLimit: 60,
                tokenLimit: 8192,
                cost: 0.000015
            },
            claude: {
                endpoint: 'https://api.anthropic.com/v1',
                rateLimit: 50,
                tokenLimit: 4096,
                cost: 0.000009
            },
            // Gemini configuration - use module defaults if available
            gemini: window.GeminiProviderDefaults || {
                endpoint: '',
                rateLimit: 60,
                tokenLimit: 4096,
                cost: 0.000001
            },
            local: {
                endpoint: 'http://localhost:8080/v1',
                rateLimit: 100,
                tokenLimit: 4000,
                cost: 0
            },
            custom: {
                endpoint: '',
                rateLimit: 60,
                tokenLimit: 4000,
                cost: 0.00001
            }
        };
        
        var config = defaults[providerType];
        if (config) {
            if ($endpointInput.length && !$endpointInput.val()) {
                $endpointInput.val(config.endpoint);
            }
            if ($rateLimitInput.length && !$rateLimitInput.val()) {
                $rateLimitInput.val(config.rateLimit);
            }
            if ($tokenLimitInput.length && !$tokenLimitInput.val()) {
                $tokenLimitInput.val(config.tokenLimit);
            }
            if ($costInput.length && !$costInput.val()) {
                $costInput.val(config.cost);
            }
        }
        
        // Show/hide API key field for local providers
        var $apiKeyGroup = $apiKeyInput.closest('.col-md-6');
        if ($apiKeyGroup.length) {
            if (providerType === 'local') {
                $apiKeyGroup.hide();
                $apiKeyInput.prop('required', false);
            } else {
                $apiKeyGroup.show();
                $apiKeyInput.prop('required', true);
            }
        }
    }
    
    /**
     * Test existing provider connection
     */
    function testExistingProvider(providerId) {
        if (!window.pageData || !window.pageData.routes || !window.pageData.routes.testConnection) {
            console.error('Test connection route not configured');
            return;
        }
        
        var $button = $('#test-connection-btn');
        var originalContent = $button.html();
        
        // Update button state
        $button.prop('disabled', true);
        $button.html('<i class="bx bx-loader-alt bx-spin"></i> Testing...');
        
        var url = window.pageData.routes.testConnection.replace(':id', providerId);
        
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                showTestResult(data);
            },
            error: function(xhr) {
                console.error('Connection test failed:', xhr);
                showTestResult({
                    success: false,
                    message: 'Connection test failed: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error')
                });
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false);
                $button.html(originalContent);
            }
        });
    }
    
    /**
     * Test form data (for create form)
     */
    function testFormData() {
        var $form = $('#provider-form');
        if (!$form.length) return;
        
        var type = $('#type').val();
        var apiKey = $('#api_key').val();
        
        // Basic validation
        if (!type) {
            alert('Please select a provider type first');
            return;
        }
        
        if (type !== 'local' && !apiKey) {
            alert('Please enter an API key to test the connection');
            return;
        }
        
        // Simulate connection test for form data
        showTestResult({
            success: true,
            message: 'Form validation passed. Save the provider to test the actual connection.',
            response_time: 'N/A'
        });
    }
    
    /**
     * Show test result
     */
    function showTestResult(data) {
        var alertClass = data.success ? 'alert-success' : 'alert-danger';
        var icon = data.success ? 'bx-check-circle' : 'bx-x-circle';
        
        var alertHtml = 
            '<div class="alert ' + alertClass + ' alert-dismissible fade show">' +
            '<i class="bx ' + icon + ' me-2"></i>' +
            '<strong>' + (data.success ? 'Success!' : 'Error!') + '</strong> ' + data.message;
        
        if (data.response_time) {
            alertHtml += '<br><small>Response time: ' + data.response_time + 'ms</small>';
        }
        
        alertHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        
        // Insert alert at the top of the form
        var $form = $('#provider-form');
        if ($form.length) {
            var $alert = $(alertHtml);
            $form.prepend($alert);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $alert.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }
    
})(jQuery);