@extends('layouts.master')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Player Chat</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Player Chat</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid" id="chat-page" data-chat-base-url="{{ url('admin/chat') }}">
            <div class="row">
                <div class="col-12">
                    @if ($players->isEmpty())
                        <div class="card">
                            <div class="card-body text-center text-muted">
                                You don't have any players yet.
                            </div>
                        </div>
                    @else
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Your Players</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="player-list">
                                    @foreach ($players as $player)
                                        <button type="button"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center player-chat-trigger"
                                            data-player-id="{{ $player->id }}"
                                            data-player-name="{{ $player->user_name }}"
                                            data-unread-count="{{ $player->unread_chat_count ?? 0 }}">
                                            <div>
                                                <div class="font-weight-bold">{{ $player->user_name }}</div>
                                                <small class="text-muted">{{ $player->name ?? $player->user_name }}</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="text-primary">
                                                    <i class="fas fa-comments mr-1"></i>
                                                    Chat
                                                </span>
                                                <span class="badge badge-danger ml-2 player-unread-badge {{ ($player->unread_chat_count ?? 0) > 0 ? '' : 'd-none' }}"
                                                    data-player-id="{{ $player->id }}"
                                                    data-count="{{ $player->unread_chat_count ?? 0 }}">
                                                    {{ $player->unread_chat_count ?? '' }}
                                                </span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chat with <span id="chatModalPlayerName">—</span></h5>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm mr-2" id="chatModalRefreshBtn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="chatModalMessages" class="chat-body border rounded mb-3">
                        <p class="text-center text-muted my-3">Select a player to load messages.</p>
                    </div>
                    <div class="text-danger small mb-2 d-none" id="chatModalError"></div>
                    <form id="chatModalForm">
                        <div class="form-group">
                            <label for="chatModalInput" class="sr-only">Message</label>
                            <textarea class="form-control" id="chatModalInput" rows="3" placeholder="Type your message..." disabled></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Messages are private between you and this player.</small>
                            <button type="submit" class="btn btn-primary" id="chatModalSendBtn" disabled>
                                <i class="fas fa-paper-plane mr-1"></i>Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .chat-body {
            min-height: 320px;
            max-height: 60vh;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 1.25rem;
        }

        .chat-message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 12px;
            max-width: 80%;
            position: relative;
            word-break: break-word;
        }

        .chat-message-agent {
            background: #007bff;
            color: #fff;
            margin-left: auto;
        }

        .chat-message-player {
            background: #ffffff;
            border: 1px solid #dee2e6;
            margin-right: auto;
        }

        .chat-meta {
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
            display: flex;
            justify-content: space-between;
            opacity: 0.8;
        }
    </style>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.__agentChatScriptInitialized) {
                return;
            }
            window.__agentChatScriptInitialized = true;

            const chatPage = document.getElementById('chat-page');
            if (!chatPage) {
                return;
            }

            const baseUrl = chatPage.dataset.chatBaseUrl;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const modalElement = document.getElementById('chatModal');
            const modalPlayerName = document.getElementById('chatModalPlayerName');
            const messagesContainer = document.getElementById('chatModalMessages');
            const errorBox = document.getElementById('chatModalError');
            const input = document.getElementById('chatModalInput');
            const sendBtn = document.getElementById('chatModalSendBtn');
            const refreshBtn = document.getElementById('chatModalRefreshBtn');
            const form = document.getElementById('chatModalForm');

            const playerTriggers = new Map();
            document.querySelectorAll('.player-chat-trigger').forEach((button) => {
                playerTriggers.set(String(button.dataset.playerId), button);
            });

            const playerBadgeMap = new Map();
            document.querySelectorAll('.player-unread-badge').forEach((badge) => {
                playerBadgeMap.set(String(badge.dataset.playerId), badge);
            });

            let currentPlayerId = null;
            let isModalVisible = false;

            const syncNavBadge = () => {
                const total = Array.from(playerTriggers.values()).reduce((sum, button) => {
                    return sum + Number(button.dataset.unreadCount ?? 0);
                }, 0);

                window.dispatchEvent(new CustomEvent('chat-sync-badge', {
                    detail: { count: total },
                }));
            };

            const jqueryModal = window.$ && window.$.fn && typeof window.$('#chatModal').modal === 'function'
                ? window.$('#chatModal')
                : null;
            let bootstrapModal = null;

            const ensureModalInstance = () => {
                if (bootstrapModal) {
                    return bootstrapModal;
                }

                if (window.bootstrap?.Modal) {
                    bootstrapModal = new window.bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: true,
                    });
                }

                return bootstrapModal;
            };

            const showModal = () => {
                if (jqueryModal) {
                    jqueryModal.modal('show');
                    return;
                }

                const instance = ensureModalInstance();
                if (instance) {
                    instance.show();
                    return;
                }

                modalElement.classList.add('show');
                modalElement.style.display = 'block';
            };

            const escapeHtml = (value = '') => `${value}`.replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const formatMessage = (value) => escapeHtml(value).replace(/\n/g, '<br>');

            const formatDate = (value) => {
                if (!value) {
                    return '';
                }

                const date = new Date(value);

                return date.toLocaleString();
            };

            const renderMessageBubble = (message) => {
                const isAgent = message.sender_type === 'agent';
                const bubbleClass = isAgent ? 'chat-message chat-message-agent' : 'chat-message chat-message-player';
                const senderLabel = escapeHtml(message.sender?.user_name ?? (isAgent ? 'You' : 'Player'));

                return `
                    <div class="${bubbleClass}">
                        <div class="chat-meta">
                            <strong>${senderLabel}</strong>
                            <small>${formatDate(message.created_at)}</small>
                        </div>
                        <div class="chat-text">${formatMessage(message.message)}</div>
                    </div>
                `;
            };

            const appendMessageBubble = (message) => {
                messagesContainer.insertAdjacentHTML('beforeend', renderMessageBubble(message));
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            };

            const setError = (message = '') => {
                if (!message) {
                    errorBox.classList.add('d-none');
                    errorBox.textContent = '';

                    return;
                }

                errorBox.classList.remove('d-none');
                errorBox.textContent = message;
            };

            const toggleComposer = (enabled) => {
                if (input) {
                    input.disabled = !enabled;
                    if (!enabled) {
                        input.value = '';
                    }
                }

                if (sendBtn) {
                    sendBtn.disabled = !enabled;
                }
            };

            const notifyChatRead = (playerId) => {
                if (!playerId) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('chat-read', {
                    detail: { player_id: playerId },
                }));
            };

            const updatePlayerBadge = (playerId, count) => {
                const key = String(playerId);
                const badge = playerBadgeMap.get(key);
                const trigger = playerTriggers.get(key);
                const sanitized = Math.max(0, Number(count) || 0);

                if (trigger) {
                    trigger.dataset.unreadCount = sanitized;
                }

                if (!badge) {
                    return;
                }

                if (sanitized > 0) {
                    badge.textContent = sanitized;
                    badge.dataset.count = sanitized;
                    badge.classList.remove('d-none');
                } else {
                    badge.textContent = '';
                    badge.dataset.count = '0';
                    badge.classList.add('d-none');
                }

                syncNavBadge();
            };

            const incrementPlayerBadge = (playerId, delta = 1) => {
                const trigger = playerTriggers.get(String(playerId));
                const current = Number(trigger?.dataset.unreadCount ?? 0);
                updatePlayerBadge(playerId, current + delta);
            };

            const renderMessages = (payload) => {
                const items = payload?.data ?? [];

                if (!items.length) {
                    messagesContainer.innerHTML = '<p class="text-center text-muted my-3">No messages yet. Start the conversation!</p>';
                    updatePlayerBadge(currentPlayerId, 0);
                    notifyChatRead(currentPlayerId);

                    return;
                }

                items.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                messagesContainer.innerHTML = items.map(renderMessageBubble).join('');
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                updatePlayerBadge(currentPlayerId, 0);
                notifyChatRead(currentPlayerId);
            };

            const loadMessages = () => {
                if (!currentPlayerId) {
                    return;
                }

                setError();
                messagesContainer.innerHTML = '<p class="text-center text-muted my-3">Loading messages...</p>';

                fetch(`${baseUrl}/${currentPlayerId}/messages?per_page=50`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Unable to load messages.');
                        }

                        return response.json();
                    })
                    .then(renderMessages)
                    .catch((error) => {
                        setError(error.message ?? 'Failed to load messages.');
                        messagesContainer.innerHTML = '<p class="text-center text-danger my-3">Failed to load messages.</p>';
                    });
            };

            const openChatModal = (playerId, playerName) => {
                currentPlayerId = playerId;
                modalPlayerName.textContent = playerName;
                toggleComposer(true);
                setError();
                messagesContainer.innerHTML = '<p class="text-center text-muted my-3">Loading messages...</p>';
                showModal();
                loadMessages();
            };

            playerTriggers.forEach((button, playerId) => {
                button.addEventListener('click', () => {
                    openChatModal(playerId, button.dataset.playerName);
                });
            });

            syncNavBadge();

            refreshBtn?.addEventListener('click', () => loadMessages());

            form?.addEventListener('submit', (event) => {
                event.preventDefault();

                if (!currentPlayerId) {
                    setError('Please select a player first.');
                    return;
                }

                const message = input.value.trim();

                if (!message) {
                    setError('Message cannot be empty.');
                    return;
                }

                setError();
                sendBtn.disabled = true;
                input.disabled = true;

                fetch(`${baseUrl}/${currentPlayerId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ message }),
                })
                    .then(async (response) => {
                        if (!response.ok) {
                            const data = await response.json().catch(() => ({}));
                            const errorMessage = data?.message ?? 'Failed to send message.';

                            throw new Error(errorMessage);
                        }

                        return response.json();
                    })
                    .then((payload) => {
                        input.value = '';
                        appendMessageBubble(payload.data);
                        updatePlayerBadge(currentPlayerId, 0);
                        notifyChatRead(currentPlayerId);
                    })
                    .catch((error) => {
                        setError(error.message ?? 'Failed to send message.');
                    })
                    .finally(() => {
                        sendBtn.disabled = false;
                        input.disabled = false;
                        input.focus();
                    });
            });

            const handleModalVisibility = (visible) => {
                isModalVisible = visible;

                if (!visible) {
                    currentPlayerId = null;
                    toggleComposer(false);
                    messagesContainer.innerHTML = '<p class="text-center text-muted my-3">Select a player to load messages.</p>';
                    setError();
                } else {
                    input?.focus();
                }
            };

            modalElement.addEventListener('shown.bs.modal', () => handleModalVisibility(true));
            modalElement.addEventListener('hidden.bs.modal', () => handleModalVisibility(false));

            window.addEventListener('chat-notification', (event) => {
                const payload = event.detail ?? {};
                const data = payload.notification_data ?? {};
                const playerId = data.player_id;

                if (!playerId) {
                    return;
                }

                if (isModalVisible && Number(playerId) === Number(currentPlayerId)) {
                    const incomingMessage = {
                        sender_type: data.sender_type ?? 'player',
                        sender: {
                            user_name: data.player_user_name ?? payload.title ?? 'Player',
                        },
                        message: data.message ?? payload.body ?? '',
                        created_at: data.created_at ?? new Date().toISOString(),
                    };

                    appendMessageBubble(incomingMessage);
                    updatePlayerBadge(playerId, 0);
                    notifyChatRead(playerId);
                    setError();
                } else {
                    incrementPlayerBadge(playerId, 1);
                }
            });

            window.addEventListener('chat-read', (event) => {
                const playerId = event.detail?.player_id;
                if (!playerId) {
                    return;
                }

                updatePlayerBadge(playerId, 0);
            });
        });
    </script>
@endsection
