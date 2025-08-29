@extends('layouts.layoutMaster')

@section('title', __('AI Chat Assistant'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/toastr/toastr.scss',
    'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss'
  ])
@endsection

@section('page-style')
<style>
  .chat-container {
    height: calc(100vh - 200px);
    display: flex;
  }
  
  .chat-sidebar {
    width: 320px;
    border-right: 1px solid var(--bs-border-color);
    display: flex;
    flex-direction: column;
    background: var(--bs-card-bg);
  }
  
  .chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  
  .chat-list {
    flex: 1;
    overflow-y: auto;
    background: var(--bs-body-bg);
  }
  
  .chat-item {
    padding: 16px;
    border-bottom: 1px solid var(--bs-border-color);
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    background: var(--bs-card-bg);
  }
  
  .chat-item:hover {
    background-color: var(--bs-gray-100);
    transform: translateX(2px);
  }
  
  .dark-layout .chat-item:hover {
    background-color: rgba(255, 255, 255, 0.05);
  }
  
  .chat-item.active {
    background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.15) 0%, rgba(var(--bs-primary-rgb), 0.1) 100%);
    border-left: 3px solid var(--bs-primary);
  }
  
  .chat-item.active::after {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 40%;
    background: var(--bs-primary);
    border-radius: 2px 0 0 2px;
  }
  
  .chat-item-title {
    font-weight: 600;
    color: var(--bs-heading-color);
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  .chat-item-preview {
    font-size: 13px;
    color: var(--bs-secondary-color);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
    margin-bottom: 4px;
  }
  
  .chat-item-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: var(--bs-text-muted);
  }
  
  .chat-item-time {
    display: flex;
    align-items: center;
    gap: 4px;
  }
  
  .chat-item-badges {
    display: flex;
    gap: 6px;
    align-items: center;
  }
  
  .chat-item-badge {
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
  }
  
  .chat-item-badge:not(.unread) {
    background: var(--bs-gray-200);
    color: var(--bs-body-color);
  }
  
  .dark-layout .chat-item-badge:not(.unread) {
    background: rgba(255, 255, 255, 0.1);
  }
  
  .chat-item-badge.unread {
    background: var(--bs-primary);
    color: white;
  }
  
  .sidebar-header {
    padding: 16px;
    background: var(--bs-card-bg);
    border-bottom: 1px solid var(--bs-border-color);
  }
  
  .sidebar-search {
    position: relative;
  }
  
  .sidebar-search input {
    padding-left: 45px !important;
    padding-right: 12px;
    border-radius: 20px;
    border: 1px solid var(--bs-border-color);
    font-size: 14px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
  }
  
  .sidebar-search input:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
  }
  
  .sidebar-search input:not(:placeholder-shown) {
    padding-right: 36px;
  }
  
  .sidebar-search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--bs-text-muted);
    pointer-events: none;
    z-index: 10;
    font-size: 18px;
  }
  
  .sidebar-search input:focus ~ .sidebar-search-icon {
    color: var(--bs-primary);
  }
  
  #clearSearch {
    background: transparent;
    border: none;
    color: var(--bs-secondary-color);
    z-index: 2;
  }
  
  #clearSearch:hover {
    color: var(--bs-heading-color);
  }
  
  .sidebar-footer {
    padding: 12px 16px;
    background: var(--bs-card-bg);
    border-top: 1px solid var(--bs-border-color);
  }
  
  .sidebar-footer a {
    color: var(--bs-body-color);
    font-weight: 500;
    transition: color 0.2s;
  }
  
  .sidebar-footer a:hover {
    color: var(--bs-primary);
  }
  
  .alert-info {
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.15) 0%, rgba(var(--bs-primary-rgb), 0.1) 100%);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.3);
    color: var(--bs-body-color);
    font-weight: 500;
    text-align: center;
  }
  
  .chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: var(--bs-gray-100);
  }
  
  .dark-layout .chat-messages {
    background: var(--bs-dark);
  }
  
  .message {
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
  }
  
  .message.user {
    justify-content: flex-end;
  }
  
  .message-content {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 12px;
    position: relative;
  }
  
  .message-text {
    line-height: 1.6;
  }
  
  .message-text p {
    margin-bottom: 0.5rem;
  }
  
  .message-text p:last-child {
    margin-bottom: 0;
  }
  
  .message-text pre {
    background: var(--bs-dark);
    color: var(--bs-light);
    padding: 12px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 8px 0;
  }
  
  .dark-layout .message-text pre {
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid var(--bs-border-color);
  }
  
  .message-text code {
    background: var(--bs-gray-200);
    color: var(--bs-danger);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Fira Code', 'Courier New', monospace;
    font-size: 0.9em;
  }
  
  .dark-layout .message-text code {
    background: rgba(255, 255, 255, 0.1);
  }
  
  .message-text pre code {
    background: transparent;
    color: inherit;
    padding: 0;
  }
  
  .message-text ul, .message-text ol {
    margin: 8px 0;
    padding-left: 24px;
  }
  
  .message-text li {
    margin: 4px 0;
  }
  
  .message-text blockquote {
    border-left: 4px solid var(--bs-border-color);
    padding-left: 16px;
    margin: 8px 0;
    color: var(--bs-secondary-color);
  }
  
  .message-text h1, .message-text h2, .message-text h3, 
  .message-text h4, .message-text h5, .message-text h6 {
    margin-top: 16px;
    margin-bottom: 8px;
    font-weight: 600;
  }
  
  .message-text h1 { font-size: 1.5em; }
  .message-text h2 { font-size: 1.3em; }
  .message-text h3 { font-size: 1.1em; }
  
  .message-text table {
    border-collapse: collapse;
    width: 100%;
    margin: 8px 0;
  }
  
  .message-text th, .message-text td {
    border: 1px solid var(--bs-border-color);
    padding: 8px;
    text-align: left;
  }
  
  .message-text th {
    background: var(--bs-gray-100);
    font-weight: 600;
  }
  
  .dark-layout .message-text th {
    background: rgba(255, 255, 255, 0.05);
  }
  
  .message-text a {
    color: var(--bs-primary);
    text-decoration: none;
  }
  
  .message-text a:hover {
    text-decoration: underline;
  }
  
  .message-text hr {
    border: none;
    border-top: 1px solid var(--bs-border-color);
    margin: 16px 0;
  }
  
  .message-text strong {
    font-weight: 600;
  }
  
  .message-text em {
    font-style: italic;
  }
  
  .message.user .message-content {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary) 100%);
    color: white;
    border-bottom-right-radius: 4px;
  }
  
  .message.assistant .message-content {
    background: var(--bs-card-bg);
    border: 1px solid var(--bs-border-color);
    border-bottom-left-radius: 4px;
  }
  
  .message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
  }
  
  .message.user .message-avatar {
    background: var(--bs-primary);
    order: 1;
  }
  
  .message.assistant .message-avatar {
    background: #48bb78;
  }
  
  .message-time {
    font-size: 11px;
    color: var(--bs-text-muted);
    margin-top: 4px;
  }
  
  .message.user .message-time {
    text-align: right;
    color: rgba(255,255,255,0.8);
  }
  
  .chat-input-container {
    padding: 20px;
    background: var(--bs-card-bg);
    border-top: 1px solid var(--bs-border-color);
  }
  
  .chat-input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 10px;
  }
  
  .chat-input {
    flex: 1;
    min-height: 44px;
    max-height: 120px;
    padding: 10px 16px;
    border: 1px solid var(--bs-border-color);
    border-radius: 24px;
    resize: none;
    outline: none;
    transition: border-color 0.2s;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
  }
  
  .chat-input:focus {
    border-color: var(--bs-primary);
  }
  
  .typing-indicator {
    display: flex;
    align-items: center;
    padding: 10px;
  }
  
  .typing-indicator span {
    height: 8px;
    width: 8px;
    background: var(--bs-secondary-color);
    border-radius: 50%;
    display: inline-block;
    margin: 0 2px;
    animation: typing 1.4s infinite;
  }
  
  .typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
  }
  
  .typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
  }
  
  @keyframes typing {
    0%, 60%, 100% {
      transform: translateY(0);
      opacity: 0.7;
    }
    30% {
      transform: translateY(-10px);
      opacity: 1;
    }
  }
  
  .chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--bs-secondary-color);
  }
  
  .chat-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 20px;
  }
  
  .suggestion-chip {
    padding: 8px 16px;
    background: var(--bs-card-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    color: var(--bs-body-color);
  }
  
  .suggestion-chip:hover {
    background: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
  }
  
  .message-actions {
    display: none;
    position: absolute;
    top: -30px;
    right: 0;
    background: var(--bs-card-bg);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 4px;
  }
  
  .message:hover .message-actions {
    display: flex;
  }
  
  .message-action-btn {
    padding: 4px 8px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--bs-secondary-color);
    transition: color 0.2s;
  }
  
  .message-action-btn:hover {
    color: var(--bs-heading-color);
  }
  
  .chat-header {
    padding: 16px 20px;
    background: var(--bs-card-bg);
    border-bottom: 1px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  @media (max-width: 768px) {
    .chat-sidebar {
      display: none;
    }
    
    .chat-sidebar.mobile-open {
      display: flex;
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      z-index: 1000;
      background: var(--bs-card-bg);
    }
  }
</style>
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/toastr/toastr.js',
    'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js'
  ])
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github-dark.min.css">
  <script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/highlight.min.js"></script>
@endsection

@section('page-script')
  @vite(['Modules/AiChat/resources/assets/js/ai-chat-enhanced.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y p-0">
  <div class="card h-100">
    <div class="chat-container">
      {{-- Chat Sidebar --}}
      <div class="chat-sidebar" id="chatSidebar">
        <div class="sidebar-header">
          @if(!($showArchived ?? false))
            <button class="btn btn-primary w-100 mb-3" onclick="createNewChat()" style="border-radius: 12px; padding: 10px;">
              <i class="bx bx-plus-circle me-1"></i> {{ __('New Chat') }}
            </button>
          @else
            <div class="alert alert-info mb-3" style="padding: 10px; border-radius: 12px;">
              <i class="bx bx-archive me-1"></i> {{ __('Viewing Archived Chats') }}
            </div>
          @endif
          
          <div class="sidebar-search">
            <i class="bx bx-search sidebar-search-icon"></i>
            <input type="text" class="form-control form-control-sm" placeholder="{{ __('Search conversations...') }}" id="searchChats">
            <button type="button" class="btn btn-sm position-absolute" style="right: 5px; top: 50%; transform: translateY(-50%); padding: 2px 6px; display: none;" id="clearSearch">
              <i class="bx bx-x" style="font-size: 16px;"></i>
            </button>
          </div>
        </div>
        
        <div class="chat-list" id="chatList" data-archived="{{ $showArchived ?? false ? 'true' : 'false' }}">
          @forelse($chats as $chat)
          <div class="chat-item {{ $currentChat && $currentChat->id == $chat->id ? 'active' : '' }}" 
               data-chat-id="{{ $chat->id }}" 
               onclick="loadChat({{ $chat->id }})">
            <div class="chat-item-title">
              {{ $chat->title ?: __('New Chat') }}
            </div>
            @if($chat->latest_message_preview)
            <div class="chat-item-preview">
              {{ \Illuminate\Support\Str::limit($chat->latest_message_preview, 80) }}
            </div>
            @endif
            <div class="chat-item-meta">
              <div class="chat-item-time">
                <i class="bx bx-time-five" style="font-size: 10px;"></i>
                {{ $chat->formatted_last_message }}
              </div>
              <div class="chat-item-badges">
                @if($chat->message_count > 0)
                  <span class="chat-item-badge" style="background: #e2e8f0; color: #4a5568;">
                    {{ $chat->message_count }} msgs
                  </span>
                @endif
              </div>
            </div>
          </div>
          @empty
          <div class="p-4 text-center">
            @if($showArchived ?? false)
              <i class="bx bx-archive bx-lg text-muted mb-2" style="font-size: 48px;"></i>
              <p class="text-muted mb-0">{{ __('No archived chats') }}</p>
              <p class="text-muted small">{{ __('Archived chats will appear here') }}</p>
            @else
              <i class="bx bx-message-square-dots bx-lg text-muted mb-2" style="font-size: 48px;"></i>
              <p class="text-muted mb-0">{{ __('No conversations yet') }}</p>
              <p class="text-muted small">{{ __('Start a new chat to begin') }}</p>
            @endif
          </div>
          @endforelse
        </div>
        
        <div class="sidebar-footer">
          <div class="text-center">
            @if($showArchived ?? false)
              <a href="#" onclick="showActiveChats()" class="text-decoration-none small">
                <i class="bx bx-chat"></i> {{ __('Back to Active Chats') }}
              </a>
            @else
              <a href="#" onclick="showArchivedChats()" class="text-decoration-none small">
                <i class="bx bx-archive"></i> {{ __('View Archived') }}
              </a>
            @endif
          </div>
        </div>
      </div>
      
      {{-- Chat Main Area --}}
      <div class="chat-main">
        @if($currentChat)
        {{-- Chat Header --}}
        <div class="chat-header">
          <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-icon d-md-none me-2" onclick="toggleSidebar()">
              <i class="bx bx-menu"></i>
            </button>
            <h5 class="mb-0 me-3">{{ $currentChat->title ?: __('Chat') }}</h5>
          </div>
          
          <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
              <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                <i class="bx bx-dots-vertical-rounded"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="archiveChat({{ $currentChat->id }})">
                  <i class="bx bx-archive me-1"></i> {{ __('Archive Chat') }}
                </a></li>
              </ul>
            </div>
          </div>
        </div>
        
        {{-- Chat Messages --}}
        <div class="chat-messages" id="chatMessages">
          @foreach($currentChat->messages as $message)
          <div class="message {{ $message->role }}" data-message-id="{{ $message->id }}">
            <div class="message-avatar">
              @if($message->role == 'user')
                {{ substr($message->user->name ?? 'U', 0, 1) }}
              @else
                AI
              @endif
            </div>
            <div class="message-content">
              <div class="message-text">{!! $message->role == 'assistant' ? \Illuminate\Support\Str::markdown($message->content) : e($message->content) !!}</div>
              <div class="message-time">
                {{ $message->formatted_time }}
              </div>
              
              <div class="message-actions">
                <button class="message-action-btn" onclick="copyMessage('{{ $message->id }}')" title="{{ __('Copy') }}">
                  <i class="bx bx-copy"></i>
                </button>
                @if($message->role == 'assistant')
                <button class="message-action-btn" onclick="regenerateMessage('{{ $message->id }}')" title="{{ __('Regenerate') }}">
                  <i class="bx bx-refresh"></i>
                </button>
                @endif
                <button class="message-action-btn" onclick="togglePin('{{ $message->id }}')" title="{{ __('Pin') }}">
                  <i class="bx {{ $message->is_pinned ? 'bxs-pin' : 'bx-pin' }}"></i>
                </button>
              </div>
            </div>
          </div>
          @endforeach
          
          <div class="typing-indicator" id="typingIndicator" style="display: none;">
            <div class="message-avatar">AI</div>
            <div class="message-content">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>
        
        {{-- Chat Input --}}
        <div class="chat-input-container">
          <form id="chatForm" onsubmit="sendMessage(event)">
            <div class="chat-input-wrapper">
              <textarea 
                class="chat-input" 
                id="messageInput" 
                placeholder="{{ __('Type your message...') }}"
                rows="1"
                required></textarea>
              <button type="submit" class="btn btn-primary btn-icon" id="sendButton">
                <i class="bx bx-send"></i>
              </button>
            </div>
            
          </form>
        </div>
        
        @else
        {{-- Empty State --}}
        <div class="chat-empty">
          <i class="bx bx-message-square-dots bx-lg mb-3"></i>
          <h4>{{ __('Welcome to AI Chat Assistant') }}</h4>
          <p class="text-muted">{{ __('Select a chat from the sidebar or start a new conversation') }}</p>
          
          <div class="chat-suggestions">
            <div class="suggestion-chip" onclick="startWithPrompt('{{ __('Help me write a professional email') }}')">
              {{ __('Write an email') }}
            </div>
            <div class="suggestion-chip" onclick="startWithPrompt('{{ __('Explain quantum computing in simple terms') }}')">
              {{ __('Learn something new') }}
            </div>
            <div class="suggestion-chip" onclick="startWithPrompt('{{ __('Generate ideas for a startup') }}')">
              {{ __('Brainstorm ideas') }}
            </div>
            <div class="suggestion-chip" onclick="startWithPrompt('{{ __('Help me debug this code') }}')">
              {{ __('Code assistance') }}
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Page Data --}}
<script>
window.chatData = {
  currentChatId: {{ $currentChat ? $currentChat->id : 'null' }},
  routes: {
    createChat: '{{ route("aichat.create") }}',
    sendMessage: '{{ route("aichat.send") }}',
    loadChat: '{{ route("aichat.history", ":id") }}',
    archiveChat: '{{ route("aichat.archive", ":id") }}',
    searchChats: '{{ route("aichat.search") }}',
    togglePin: '{{ route("aichat.message.pin", ":id") }}',
    statistics: '{{ route("aichat.statistics") }}'
  },
  translations: {
    confirmArchive: '{{ __("Are you sure you want to archive this chat?") }}',
    messageCopied: '{{ __("Message copied to clipboard") }}',
    error: '{{ __("An error occurred") }}',
    sending: '{{ __("Sending...") }}',
    newChat: '{{ __("New Chat") }}'
  }
};
</script>
@endsection