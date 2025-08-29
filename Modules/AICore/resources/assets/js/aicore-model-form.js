/**
 * AI Core Model Form JavaScript
 * Handles model create/edit form functionality
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('AI Core Model Form loaded');
        
        initializeModelForm();
        setupEventListeners();
        setupValidation();
    });
    
    /**
     * Initialize model form functionality
     */
    function initializeModelForm() {
        // Initialize Select2 dropdowns with proper configuration
        if ($.fn.select2) {
            // Provider dropdown
            $('#provider_id').each(function() {
                var $this = $(this);
                if (!$this.hasClass('select2-hidden-accessible')) {
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownParent: $this.parent(),
                        placeholder: 'Select Provider',
                        allowClear: false
                    });
                }
            });
            
            // Type dropdown
            $('#type').each(function() {
                var $this = $(this);
                if (!$this.hasClass('select2-hidden-accessible')) {
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownParent: $this.parent(),
                        placeholder: 'Select Type',
                        allowClear: false
                    });
                }
            });
        }
        
        // Initialize tooltips if available
        if ($.fn.tooltip) {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
        
        console.log('Model form initialized');
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Provider selection change
        $(document).on('change', '#provider_id', function() {
            var providerId = $(this).val();
            if (providerId) {
                updateModelSuggestions(providerId);
            }
        });
        
        // Model type selection change
        $(document).on('change', '#type', function() {
            var type = $(this).val();
            updateModelTypeHelpers(type);
        });
        
        // Configuration validation
        $(document).on('blur', '#configuration', function() {
            validateJsonConfiguration();
        });
        
        // Auto-format model identifier
        $(document).on('blur', '#model_identifier', function() {
            formatModelIdentifier();
        });
        
        // Form submission
        $(document).on('submit', 'form', function(e) {
            var $form = $(this);
            
            // Fix checkbox values
            var supportsStreaming = $('#supports_streaming').is(':checked');
            if (!supportsStreaming && $('#supports_streaming_hidden').length === 0) {
                $form.append('<input type="hidden" name="supports_streaming" id="supports_streaming_hidden" value="0">');
            }
            
            var isActive = $('#is_active').is(':checked');
            if (!isActive && $('#is_active_hidden').length === 0) {
                $form.append('<input type="hidden" name="is_active" id="is_active_hidden" value="0">');
            }
            
            // Validate required fields
            var provider = $('#provider_id').val();
            var type = $('#type').val();
            var name = $('#name').val();
            var identifier = $('#model_identifier').val();
            
            if (!provider) {
                e.preventDefault();
                alert('Please select a provider');
                $('#provider_id').select2('open');
                return false;
            }
            
            if (!type) {
                e.preventDefault();
                alert('Please select a model type');
                $('#type').select2('open');
                return false;
            }
            
            if (!name || name.trim() === '') {
                e.preventDefault();
                alert('Model name is required');
                $('#name').focus();
                return false;
            }
            
            if (!identifier || identifier.trim() === '') {
                e.preventDefault();
                alert('Model identifier is required');
                $('#model_identifier').focus();
                return false;
            }
            
            // Validate JSON configuration if provided
            var configValue = $('#configuration').val().trim();
            if (configValue) {
                try {
                    JSON.parse(configValue);
                } catch (e) {
                    alert('Invalid JSON in configuration field');
                    $('#configuration').focus();
                    return false;
                }
            }
            
            console.log('Form validation passed, submitting...');
            return true;
        });
        
        console.log('Event listeners set up for model form');
    }
    
    /**
     * Setup form validation
     */
    function setupValidation() {
        // Real-time validation for token costs
        $(document).on('input', 'input[type="number"]', function() {
            var value = parseFloat($(this).val());
            var min = parseFloat($(this).attr('min'));
            var max = parseFloat($(this).attr('max'));
            
            if (!isNaN(min) && value < min) {
                $(this).addClass('is-invalid');
            } else if (!isNaN(max) && value > max) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
    }
    
    /**
     * Update model suggestions based on provider
     */
    function updateModelSuggestions(providerId) {
        // Get provider type from option text
        var providerText = $('#provider_id option:selected').text();
        var matches = providerText.match(/\((\w+)\)/);
        var providerType = matches ? matches[1].toLowerCase() : null;
        
        if (!providerType) return;
        
        // Suggest common model identifiers based on provider type
        var suggestions = getModelSuggestions(providerType);
        
        if (suggestions.length > 0) {
            showModelSuggestions(suggestions);
        }
    }
    
    /**
     * Get model suggestions for provider type
     */
    function getModelSuggestions(providerType) {
        var suggestions = {
            'openai': [
                'gpt-4o',
                'gpt-4o-mini',
                'gpt-4-turbo',
                'gpt-4-turbo-preview',
                'gpt-4',
                'gpt-3.5-turbo',
                'dall-e-3',
                'dall-e-2',
                'text-embedding-3-large',
                'text-embedding-3-small',
                'text-embedding-ada-002'
            ],
            'claude': [
                'claude-3-5-sonnet-20241022',
                'claude-3-5-haiku-20241022',
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307',
                'claude-2.1',
                'claude-instant-1.2'
            ],
            'local': [
                'llama-2-7b',
                'llama-2-13b',
                'mistral-7b',
                'codellama-7b'
            ],
            'custom': []
        };
        
        // Gemini suggestions are handled by the GeminiAIProvider module if installed
        // Don't include them here as it's a separate paid addon
        
        return suggestions[providerType] || [];
    }
    
    /**
     * Show model suggestions near the input
     */
    function showModelSuggestions(suggestions) {
        var $input = $('#model_identifier');
        var $helpText = $input.siblings('.form-text');
        
        if (suggestions.length === 0) {
            $helpText.text('The exact identifier used by the provider\'s API');
            return;
        }
        
        var suggestionHtml = 'Suggestions: ';
        suggestions.slice(0, 3).forEach(function(suggestion, index) {
            if (index > 0) suggestionHtml += ', ';
            suggestionHtml += '<code class="suggestion-link" style="cursor: pointer;">' + suggestion + '</code>';
        });
        
        $helpText.html(suggestionHtml);
        
        // Add click handlers for suggestions
        $('.suggestion-link').off('click').on('click', function() {
            $input.val($(this).text());
            $input.focus();
        });
    }
    
    /**
     * Update helpers based on model type
     */
    function updateModelTypeHelpers(type) {
        var typeHelpers = {
            'text': 'Text generation models for conversations, completions, and analysis',
            'image': 'Image generation models for creating visual content',
            'embedding': 'Models that convert text into numerical vectors for similarity search',
            'multimodal': 'Models that can process both text and images together',
            'code': 'Models optimized for code generation and completion',
            'audio': 'Models for speech-to-text or text-to-speech',
            'video': 'Models for video generation or analysis'
        };
        
        var $typeSelect = $('#type');
        var $helpText = $('<div class="form-text"></div>');
        
        // Remove existing help text if any
        $typeSelect.parent().find('.form-text').remove();
        
        if (typeHelpers[type]) {
            $helpText.text(typeHelpers[type]);
            $typeSelect.parent().append($helpText);
        }
    }
    
    /**
     * Validate JSON configuration
     */
    function validateJsonConfiguration() {
        var $textarea = $('#configuration');
        var value = $textarea.val().trim();
        
        if (!value) {
            $textarea.removeClass('is-invalid is-valid');
            return;
        }
        
        try {
            JSON.parse(value);
            $textarea.removeClass('is-invalid').addClass('is-valid');
            
            // Remove error message if exists
            $textarea.siblings('.invalid-feedback').remove();
        } catch (e) {
            $textarea.removeClass('is-valid').addClass('is-invalid');
            
            // Show error message
            var $feedback = $textarea.siblings('.invalid-feedback');
            if (!$feedback.length) {
                $feedback = $('<div class="invalid-feedback"></div>');
                $textarea.after($feedback);
            }
            $feedback.text('Invalid JSON format: ' + e.message);
        }
    }
    
    /**
     * Auto-format model identifier
     */
    function formatModelIdentifier() {
        var $input = $('#model_identifier');
        var value = $input.val().trim();
        
        if (!value) return;
        
        // Convert to lowercase and replace spaces with hyphens (common pattern)
        value = value.toLowerCase().replace(/\s+/g, '-');
        $input.val(value);
    }
    
})(jQuery);