/**
 * AI Chat Enhanced JavaScript
 * Modern chat interface with real-time features
 */

// Global functions that need to be available immediately
window.createNewChat = function() {
    $.ajax({
        url: window.chatData.routes.createChat,
        type: 'POST',
        data: {
            title: null,
            chat_type: 'general'
        },
        success: function(response) {
            if (response.success) {
                window.location.href = '/aichat?chat_id=' + response.chat.id;
            }
        },
        error: function() {
            toastr.error(window.chatData.translations.error);
        }
    });
};

window.loadChat = function(chatId) {
    window.location.href = '/aichat?chat_id=' + chatId;
};

window.sendMessage = function(event) {
    event.preventDefault();
    
    const message = $('#messageInput').val().trim();
    if (!message) return;
    
    // Model selection removed - using auto-select
    const $sendButton = $('#sendButton');
    const $input = $('#messageInput');
    
    // Disable input
    $sendButton.prop('disabled', true);
    $input.prop('disabled', true);
    
    // Add user message to UI
    addMessageToUI('user', message);
    
    // Clear input and reset height
    $input.val('').css('height', 'auto');
    
    // Show typing indicator
    showTypingIndicator();
    
    // Send to server
    $.ajax({
        url: window.chatData.routes.sendMessage,
        type: 'POST',
        data: {
            chat_id: window.currentChatId,
            message: message,
            // model_id removed - using auto-select
        },
        success: function(response) {
            if (response.success) {
                hideTypingIndicator();
                
                // Add assistant message
                addMessageToUI('assistant', response.assistant_message.content, {
                    id: response.assistant_message.id,
                    model: response.assistant_message.model?.name,
                    time: new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
                });
                
                // Update chat list
                updateChatListItem(window.currentChatId, message);
            }
        },
        error: function(xhr) {
            hideTypingIndicator();
            
            // Check if we have an assistant_message in the response (from server)
            if (xhr.responseJSON && xhr.responseJSON.assistant_message) {
                // Use the error message from server
                addMessageToUI('assistant', xhr.responseJSON.assistant_message.content, {
                    id: xhr.responseJSON.assistant_message.id,
                    isError: true,
                    time: new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
                });
            } else {
                // Fallback to client-side error handling
                let userFriendlyMessage = 'Sorry, I encountered an error processing your request. Please try again.';
                
                if (xhr.status === 503 || (xhr.responseJSON?.message && xhr.responseJSON.message.includes('overloaded'))) {
                    userFriendlyMessage = 'The AI service is currently busy. Please try again in a moment.';
                } else if (xhr.status === 429) {
                    userFriendlyMessage = 'Too many requests. Please wait a moment before trying again.';
                } else if (xhr.status === 401 || xhr.status === 403) {
                    userFriendlyMessage = 'Authentication error. Please refresh the page and try again.';
                } else if (xhr.status === 500) {
                    userFriendlyMessage = 'Server error occurred. Please try again later.';
                } else if (xhr.status === 0) {
                    userFriendlyMessage = 'Connection lost. Please check your internet connection and try again.';
                }
                
                addMessageToUI('assistant', userFriendlyMessage, {
                    isError: true
                });
            }
            
            // Update chat list even for errors
            updateChatListItem(window.currentChatId, message);
            
            // Log the actual error for debugging (only in console)
            console.error('AI Chat Error:', xhr.responseJSON?.message || xhr.statusText);
        },
        complete: function() {
            $sendButton.prop('disabled', false);
            $input.prop('disabled', false).focus();
        }
    });
};

// Removed clearChat and deleteChat functions as they are no longer needed

window.archiveChat = function(chatId) {
    Swal.fire({
        title: window.chatData.translations.confirmArchive,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Archive'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: window.chatData.routes.archiveChat.replace(':id', chatId),
                type: 'POST',
                success: function() {
                    window.location.href = '/aichat';
                }
            });
        }
    });
};

// Removed exportChat function as it is no longer needed

window.copyMessage = function(messageId) {
    // Get the plain text content without HTML tags
    const messageElement = $(`.message[data-message-id="${messageId}"] .message-text`)[0];
    const messageText = messageElement ? messageElement.innerText || messageElement.textContent : '';
    navigator.clipboard.writeText(messageText).then(() => {
        toastr.success(window.chatData.translations.messageCopied);
    });
};

window.togglePin = function(messageId) {
    $.ajax({
        url: window.chatData.routes.togglePin.replace(':id', messageId),
        type: 'POST',
        success: function(response) {
            const $icon = $(`.message[data-message-id="${messageId}"] .bx-pin, .message[data-message-id="${messageId}"] .bxs-pin`);
            if (response.is_pinned) {
                $icon.removeClass('bx-pin').addClass('bxs-pin');
            } else {
                $icon.removeClass('bxs-pin').addClass('bx-pin');
            }
        }
    });
};

window.startWithPrompt = function(prompt) {
    // Create new chat and send the prompt
    $.ajax({
        url: window.chatData.routes.createChat,
        type: 'POST',
        data: {
            title: prompt.substring(0, 50)
        },
        success: function(response) {
            if (response.success) {
                window.currentChatId = response.chat.id;
                window.location.href = '/aichat?chat_id=' + window.currentChatId + '&prompt=' + encodeURIComponent(prompt);
            }
        }
    });
};

window.toggleSidebar = function() {
    $('#chatSidebar').toggleClass('mobile-open');
};

window.showArchivedChats = function() {
    // Redirect to archived view
    window.location.href = '/aichat?archived=true';
};

window.showActiveChats = function() {
    // Redirect to active chats view
    window.location.href = '/aichat';
};

window.regenerateMessage = function(messageId) {
    // TODO: Implement message regeneration
    toastr.info('Regenerate feature coming soon');
};

// Helper functions that will be available after DOM ready
let addMessageToUI, hideTypingIndicator, showTypingIndicator, updateChatListItem;

// Store current chat ID globally
window.currentChatId = null;

$(function() {
    'use strict';

    // Initialize
    window.currentChatId = window.chatData.currentChatId;
    let isTyping = false;
    let messageTimeout = null;
    
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize components
    initializeChat();
    initializeEventListeners();
    initializeTextarea();
    
    // Initialize highlight.js for existing code blocks
    if (typeof hljs !== 'undefined') {
        document.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightElement(block);
        });
    }
    
    /**
     * Initialize chat components
     */
    function initializeChat() {
        // Initialize Select2 for model selection
        $('#modelSelect').select2({
            theme: 'bootstrap5',
            placeholder: 'Auto-select Model',
            allowClear: true,
            width: 'resolve'
        });
        
        // Initialize perfect scrollbar if available
        if (typeof PerfectScrollbar !== 'undefined') {
            const chatMessagesEl = document.querySelector('#chatMessages');
            const chatListEl = document.querySelector('#chatList');
            
            if (chatMessagesEl) {
                new PerfectScrollbar(chatMessagesEl, {
                    wheelSpeed: 0.5,
                    swipeEasing: true,
                    wheelPropagation: false
                });
            }
            
            if (chatListEl) {
                new PerfectScrollbar(chatListEl, {
                    wheelSpeed: 0.5,
                    swipeEasing: true,
                    wheelPropagation: false
                });
            }
        }
        
        // Scroll to bottom of messages
        scrollToBottom();
    }
    
    /**
     * Initialize event listeners
     */
    function initializeEventListeners() {
        // Model selection change
        $('#modelSelect').on('change', function() {
            const modelName = $(this).find('option:selected').text().trim();
            $('#currentModel').text(modelName || 'Auto-select');
        });
        
        // Search chats
        $('#searchChats').on('input', debounce(function() {
            const query = $(this).val().toLowerCase().trim();
            filterChatList(query);
            
            // Show/hide clear button
            if (query.length > 0) {
                $('#clearSearch').show();
            } else {
                $('#clearSearch').hide();
            }
        }, 300));
        
        // Clear search button
        $('#clearSearch').on('click', function() {
            $('#searchChats').val('').trigger('input');
            $(this).hide();
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + Enter to send
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && $('#messageInput').is(':focus')) {
                e.preventDefault();
                $('#chatForm').submit();
            }
            
            // Escape to clear input
            if (e.key === 'Escape' && $('#messageInput').is(':focus')) {
                $('#messageInput').val('').focus();
            }
        });
    }
    
    /**
     * Initialize auto-resizing textarea
     */
    function initializeTextarea() {
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }
    }
    
    /**
     * Add message to UI
     */
    addMessageToUI = function(role, content, metadata = {}) {
        const messageHtml = createMessageHTML(role, content, metadata);
        $('#typingIndicator').before(messageHtml);
        scrollToBottom();
    }
    
    /**
     * Create message HTML
     */
    function createMessageHTML(role, content, metadata = {}) {
        const isUser = role === 'user';
        const avatar = isUser ? 'U' : 'AI';
        const messageClass = isUser ? 'user' : 'assistant';
        
        let timeInfo = metadata.time || new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        if (metadata.model) {
            timeInfo += ' • ' + metadata.model;
        }
        
        // Render markdown for assistant messages, escape HTML for user messages
        let messageContent;
        if (isUser) {
            messageContent = escapeHtml(content);
        } else {
            // Use marked.js if available, otherwise fallback to escaped HTML
            if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
                // Configure marked for better code highlighting
                marked.setOptions({
                    breaks: true,
                    gfm: true,
                    highlight: function(code, lang) {
                        // Use highlight.js if available
                        if (typeof hljs !== 'undefined' && lang) {
                            try {
                                return hljs.highlight(code, { language: lang }).value;
                            } catch (e) {
                                // If language is not supported, try auto-detection
                                return hljs.highlightAuto(code).value;
                            }
                        } else if (typeof hljs !== 'undefined') {
                            // Auto-detect language
                            return hljs.highlightAuto(code).value;
                        }
                        return code;
                    }
                });
                // Parse markdown and sanitize the output
                const rawHtml = marked.parse(content);
                messageContent = DOMPurify.sanitize(rawHtml, {
                    ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                                  'blockquote', 'code', 'pre', 'ul', 'ol', 'li', 'a', 'table', 'thead',
                                  'tbody', 'tr', 'td', 'th', 'hr', 'del', 'ins', 'sup', 'sub'],
                    ALLOWED_ATTR: ['href', 'target', 'rel', 'class']
                });
            } else {
                // Fallback: basic markdown-like formatting
                messageContent = escapeHtml(content)
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                    .replace(/`(.+?)`/g, '<code>$1</code>')
                    .replace(/\n/g, '<br>');
            }
        }
        
        return `
            <div class="message ${messageClass}" data-message-id="${metadata.id || ''}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <div class="message-text">${messageContent}</div>
                    <div class="message-time">${timeInfo}</div>
                    ${!isUser ? `
                    <div class="message-actions">
                        <button class="message-action-btn" onclick="copyMessage('${metadata.id}')" title="Copy">
                            <i class="bx bx-copy"></i>
                        </button>
                        <button class="message-action-btn" onclick="regenerateMessage('${metadata.id}')" title="Regenerate">
                            <i class="bx bx-refresh"></i>
                        </button>
                        <button class="message-action-btn" onclick="togglePin('${metadata.id}')" title="Pin">
                            <i class="bx bx-pin"></i>
                        </button>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    /**
     * Show typing indicator
     */
    showTypingIndicator = function() {
        $('#typingIndicator').show();
        scrollToBottom();
    }
    
    /**
     * Hide typing indicator
     */
    hideTypingIndicator = function() {
        $('#typingIndicator').hide();
    }
    
    /**
     * Scroll to bottom of messages
     */
    function scrollToBottom() {
        const container = document.getElementById('chatMessages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    
    
    /**
     * Filter chat list locally
     */
    function filterChatList(query) {
        const $chatItems = $('.chat-item');
        
        if (!query) {
            // Show all chats if query is empty
            $chatItems.show();
            return;
        }
        
        $chatItems.each(function() {
            const $item = $(this);
            const title = $item.find('.chat-item-title').text().toLowerCase();
            const preview = $item.find('.chat-item-preview').text().toLowerCase();
            
            // Check if query matches title or preview
            if (title.includes(query) || preview.includes(query)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        // Show no results message if all items are hidden
        const visibleItems = $chatItems.filter(':visible').length;
        if (visibleItems === 0) {
            if ($('#noSearchResults').length === 0) {
                $('#chatList').append(`
                    <div id="noSearchResults" class="p-4 text-center">
                        <i class="bx bx-search-alt bx-lg text-muted mb-2" style="font-size: 48px;"></i>
                        <p class="text-muted mb-0">${window.chatData.translations.noResults || 'No conversations found'}</p>
                        <p class="text-muted small">Try a different search term</p>
                    </div>
                `);
            }
        } else {
            $('#noSearchResults').remove();
        }
    }
    
    /**
     * Search chats from server (optional, for future use)
     */
    function searchChatsFromServer(query) {
        $.ajax({
            url: window.chatData.routes.searchChats,
            type: 'GET',
            data: { query: query },
            success: function(response) {
                if (response.success) {
                    updateChatList(response.chats);
                }
            },
            error: function() {
                toastr.error('Search failed');
            }
        });
    }
    
    /**
     * Update chat list item
     */
    updateChatListItem = function(chatId, lastMessage) {
        const $item = $(`.chat-item[data-chat-id="${chatId}"]`);
        if ($item.length) {
            // Strip markdown from preview
            let preview = lastMessage;
            preview = preview.replace(/\*\*(.*?)\*\*/g, '$1'); // Bold
            preview = preview.replace(/\*(.*?)\*/g, '$1'); // Italic
            preview = preview.replace(/`{3}[\s\S]*?`{3}/g, '[code block]'); // Code blocks
            preview = preview.replace(/`(.*?)`/g, '$1'); // Inline code
            preview = preview.replace(/^#{1,6}\s+(.*)/gm, '$1'); // Headers
            preview = preview.replace(/\[([^\]]+)\]\([^\)]+\)/g, '$1'); // Links
            preview = preview.replace(/^[\*\-\+]\s+/gm, ''); // List items
            preview = preview.replace(/^\d+\.\s+/gm, ''); // Numbered lists
            preview = preview.replace(/\n+/g, ' '); // Newlines to spaces
            preview = preview.trim();
            
            // Update preview text
            let $preview = $item.find('.chat-item-preview');
            if ($preview.length === 0) {
                // Create preview div if it doesn't exist
                $item.find('.chat-item-title').after('<div class="chat-item-preview"></div>');
                $preview = $item.find('.chat-item-preview');
            }
            $preview.text(preview.substring(0, 80) + (preview.length > 80 ? '...' : ''));
            
            // Update time
            $item.find('.chat-item-time').html('<i class="bx bx-time-five" style="font-size: 10px;"></i> Just now');
        }
    }
    
    
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    // Check if there's a prompt in URL params
    const urlParams = new URLSearchParams(window.location.search);
    const prompt = urlParams.get('prompt');
    if (prompt && currentChatId) {
        $('#messageInput').val(prompt);
        setTimeout(() => {
            $('#chatForm').submit();
        }, 500);
    }
});