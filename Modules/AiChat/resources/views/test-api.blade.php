@extends('layouts.layoutMaster')

@section('title', __('AI Chat API Test'))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">{{ __('AI Chat') }} /</span> {{ __('API Test') }}
    </h4>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Test AI Chat API with Gemini') }}</h5>
                </div>
                <div class="card-body">
                    <!-- Test Connection -->
                    <div class="mb-4">
                        <h6>{{ __('1. Test API Connection') }}</h6>
                        <button type="button" class="btn btn-primary" id="testConnection">
                            <i class="bx bx-check-circle"></i> {{ __('Test Connection') }}
                        </button>
                        <div id="connectionResult" class="mt-2"></div>
                    </div>

                    <!-- Chat Test -->
                    <div class="mb-4">
                        <h6>{{ __('2. Test Chat Endpoint') }}</h6>
                        <div class="mb-3">
                            <label for="chatMessage" class="form-label">{{ __('Message') }}</label>
                            <textarea class="form-control" id="chatMessage" rows="3" placeholder="{{ __('Enter your message...') }}">Hello! Can you tell me what AI provider you are using?</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="provider" class="form-label">{{ __('Provider') }}</label>
                            <select class="form-control" id="provider">
                                <option value="">{{ __('Auto Select') }}</option>
                                <option value="gemini">Gemini</option>
                                <option value="openai">OpenAI</option>
                                <option value="claude">Claude</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-success" id="testChat">
                            <i class="bx bx-send"></i> {{ __('Send Chat') }}
                        </button>
                        <div id="chatResult" class="mt-3"></div>
                    </div>

                    <!-- Completion Test -->
                    <div class="mb-4">
                        <h6>{{ __('3. Test Completion Endpoint') }}</h6>
                        <div class="mb-3">
                            <label for="completionPrompt" class="form-label">{{ __('Prompt') }}</label>
                            <textarea class="form-control" id="completionPrompt" rows="2" placeholder="{{ __('Enter your prompt...') }}">Complete this sentence: The future of AI is</textarea>
                        </div>
                        <button type="button" class="btn btn-info" id="testCompletion">
                            <i class="bx bx-edit"></i> {{ __('Test Completion') }}
                        </button>
                        <div id="completionResult" class="mt-3"></div>
                    </div>

                    <!-- Summarization Test -->
                    <div class="mb-4">
                        <h6>{{ __('4. Test Summarization Endpoint') }}</h6>
                        <div class="mb-3">
                            <label for="textToSummarize" class="form-label">{{ __('Text to Summarize') }}</label>
                            <textarea class="form-control" id="textToSummarize" rows="4" placeholder="{{ __('Enter text to summarize...') }}">Artificial Intelligence (AI) has evolved significantly over the past decade. Machine learning algorithms have become more sophisticated, enabling computers to perform tasks that once required human intelligence. From natural language processing to computer vision, AI is transforming industries and changing the way we interact with technology. The development of large language models has particularly accelerated progress in understanding and generating human-like text.</textarea>
                        </div>
                        <button type="button" class="btn btn-warning" id="testSummarize">
                            <i class="bx bx-file"></i> {{ __('Test Summarization') }}
                        </button>
                        <div id="summarizeResult" class="mt-3"></div>
                    </div>

                    <!-- Provider List -->
                    <div class="mb-4">
                        <h6>{{ __('5. Get Available Providers') }}</h6>
                        <button type="button" class="btn btn-secondary" id="getProviders">
                            <i class="bx bx-list-ul"></i> {{ __('Get Providers') }}
                        </button>
                        <div id="providersResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(function() {
    // Setup AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    });

    // Test Connection
    $('#testConnection').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);
        $('#connectionResult').html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Testing...');

        $.ajax({
            url: '/api/v1/aichat/test',
            method: 'GET',
            success: function(response) {
                $('#connectionResult').html(`
                    <div class="alert alert-success">
                        <strong>✅ Connection Successful!</strong><br>
                        AICore Available: ${response.aicore_available ? 'Yes' : 'No'}<br>
                        Gemini Available: ${response.gemini_available ? 'Yes' : 'No'}
                    </div>
                `);
            },
            error: function(xhr) {
                $('#connectionResult').html(`
                    <div class="alert alert-danger">
                        <strong>❌ Connection Failed!</strong><br>
                        ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Test Chat
    $('#testChat').on('click', function() {
        const btn = $(this);
        const message = $('#chatMessage').val();
        const provider = $('#provider').val();
        
        if (!message) {
            alert('Please enter a message');
            return;
        }

        btn.prop('disabled', true);
        $('#chatResult').html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Sending...');

        const data = {
            message: message,
            max_tokens: 500
        };
        
        if (provider) {
            data.provider = provider;
        }

        $.ajax({
            url: '/api/v1/aichat/chat',
            method: 'POST',
            data: JSON.stringify(data),
            success: function(response) {
                $('#chatResult').html(`
                    <div class="alert alert-success">
                        <strong>Response:</strong><br>
                        <pre>${response.data.response}</pre>
                        <small class="text-muted">
                            Model: ${response.data.model}<br>
                            Tokens: ${response.data.usage?.total_tokens || 'N/A'}<br>
                            Processing Time: ${response.data.processing_time}ms
                        </small>
                    </div>
                `);
            },
            error: function(xhr) {
                $('#chatResult').html(`
                    <div class="alert alert-danger">
                        <strong>Error:</strong><br>
                        ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Test Completion
    $('#testCompletion').on('click', function() {
        const btn = $(this);
        const prompt = $('#completionPrompt').val();
        const provider = $('#provider').val();
        
        if (!prompt) {
            alert('Please enter a prompt');
            return;
        }

        btn.prop('disabled', true);
        $('#completionResult').html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Processing...');

        const data = {
            prompt: prompt,
            max_tokens: 200
        };
        
        if (provider) {
            data.provider = provider;
        }

        $.ajax({
            url: '/api/v1/aichat/complete',
            method: 'POST',
            data: JSON.stringify(data),
            success: function(response) {
                $('#completionResult').html(`
                    <div class="alert alert-info">
                        <strong>Completion:</strong><br>
                        <pre>${response.data.response}</pre>
                        <small class="text-muted">
                            Model: ${response.data.model}<br>
                            Tokens: ${response.data.usage?.total_tokens || 'N/A'}
                        </small>
                    </div>
                `);
            },
            error: function(xhr) {
                $('#completionResult').html(`
                    <div class="alert alert-danger">
                        <strong>Error:</strong><br>
                        ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Test Summarization
    $('#testSummarize').on('click', function() {
        const btn = $(this);
        const text = $('#textToSummarize').val();
        const provider = $('#provider').val();
        
        if (!text) {
            alert('Please enter text to summarize');
            return;
        }

        btn.prop('disabled', true);
        $('#summarizeResult').html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Summarizing...');

        const data = {
            text: text,
            max_length: 100,
            style: 'concise'
        };
        
        if (provider) {
            data.provider = provider;
        }

        $.ajax({
            url: '/api/v1/aichat/summarize',
            method: 'POST',
            data: JSON.stringify(data),
            success: function(response) {
                $('#summarizeResult').html(`
                    <div class="alert alert-warning">
                        <strong>Summary:</strong><br>
                        <pre>${response.data.response}</pre>
                        <small class="text-muted">
                            Model: ${response.data.model}<br>
                            Tokens: ${response.data.usage?.total_tokens || 'N/A'}
                        </small>
                    </div>
                `);
            },
            error: function(xhr) {
                $('#summarizeResult').html(`
                    <div class="alert alert-danger">
                        <strong>Error:</strong><br>
                        ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Get Providers
    $('#getProviders').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);
        $('#providersResult').html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading...');

        $.ajax({
            url: '/api/v1/aichat/providers',
            method: 'GET',
            success: function(response) {
                let html = '<div class="alert alert-secondary"><strong>Available Providers:</strong><ul>';
                response.data.forEach(provider => {
                    html += `<li><strong>${provider.name}</strong> (${provider.type})`;
                    if (provider.models && provider.models.length > 0) {
                        html += '<ul>';
                        provider.models.forEach(model => {
                            html += `<li>${model.name} - ${model.identifier} (Max Tokens: ${model.max_tokens})</li>`;
                        });
                        html += '</ul>';
                    }
                    html += '</li>';
                });
                html += '</ul></div>';
                $('#providersResult').html(html);
            },
            error: function(xhr) {
                $('#providersResult').html(`
                    <div class="alert alert-danger">
                        <strong>Error:</strong><br>
                        ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection