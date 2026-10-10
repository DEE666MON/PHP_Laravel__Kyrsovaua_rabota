<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Чат')</title>
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; font-family: system-ui, sans-serif; background: #f3f4f6; }
            .app { display: flex; height: 100vh; }
            .sidebar {
                width: 280px; background: #111827; color: #e5e7eb;
                display: flex; flex-direction: column; padding: 12px; gap: 8px;
            }
            .sidebar a { color: #e5e7eb; text-decoration: none; }
            .sidebar a:hover { color: #fff; }
            .chat-list { list-style: none; padding: 0; margin: 0; overflow-y: auto; flex: 1; }
            .chat-list li { margin-bottom: 2px; }
            .chat-list a {
                display: block; padding: 6px 8px; border-radius: 6px; font-size: 14px;
            }
            .chat-list a.active, .chat-list a:hover { background: #1f2937; }
            .sidebar input[type=search] {
                width: 100%; padding: 6px 8px; border-radius: 6px;
                border: 1px solid #374151; background: #1f2937; color: #e5e7eb;
            }
            .user-block { border-top: 1px solid #374151; padding-top: 8px; font-size: 13px; }
            .role-badge {
                background: #10b981; color: #fff; padding: 1px 6px; border-radius: 4px;
                font-size: 11px; margin-left: 4px;
            }
            .role-badge.admin { background: #8b5cf6; }
            .role-badge.mod { background: #10b981; }
            .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
            .content { flex: 1; overflow-y: auto; padding: 16px; }
            .content-header {
                background: #fff; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;
                display: flex; justify-content: space-between; align-items: center;
            }
            .btn {
                border: none; padding: 6px 10px; border-radius: 6px;
                font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block;
            }
            .btn-primary { background: #3b82f6; color: #fff; }
            .btn-danger  { background: #ef4444; color: #fff; }
            .btn-ghost   { background: #e5e7eb; color: #333; }
            .btn-success { background: #10b981; color: #fff; }
            .errors { color: #ef4444; font-size: 13px; margin: 8px 0; }
            .success { color: #10b981; font-size: 13px; margin: 8px 0; }
            .message {
                background: #fff; border-radius: 8px; padding: 10px 12px;
                margin-bottom: 8px; position: relative;
            }
            .message .meta { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
            .message .body { white-space: pre-wrap; word-break: break-word; }
            .message img { max-width: 280px; border-radius: 8px; margin-top: 6px; display: block; }
            .message .actions {
                position: absolute; top: 8px; right: 8px; display: flex; gap: 4px;
            }
            .message .actions button { font-size: 11px; padding: 3px 6px; }
            form.composer {
                background: #fff; border-top: 1px solid #e5e7eb; padding: 12px 16px;
            }
            form.composer textarea {
                width: 100%; padding: 8px; border: 1px solid #d1d5db;
                border-radius: 8px; resize: vertical; min-height: 50px;
            }
            form.composer .row { display: flex; gap: 8px; align-items: center; margin-top: 6px; }
            .card { background: #fff; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
            .attach-btn {
                display: inline-flex; align-items: center; justify-content: center;
                width: 36px; height: 36px; border-radius: 8px;
                background: #e5e7eb; cursor: pointer; font-size: 18px;
                transition: background .15s; user-select: none;
            }
            .attach-btn:hover { background: #d1d5db; }
        </style>
    </head>
    <body>
        <div class="app">
            <aside class="sidebar">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong>Чаты</strong>
                    <a href="{{ route('chat.create') }}" class="btn btn-primary" style="font-size:12px;">+</a>
                </div>

                <input type="search" id="chat-search" placeholder="Поиск чата…">

                <ul class="chat-list" id="chat-list">
                    @foreach($chats ?? [] as $c)
                        <li style="display:flex; align-items:center; gap:4px;">
                            <a href="{{ route('chat.show', $c) }}"
                            class="{{ isset($chat) && $chat->id === $c->id ? 'active' : '' }}"
                            style="flex:1;">
                                # {{ $c->name }}
                                <span style="color:#9ca3af;font-size:12px;">({{ $c->messages_count ?? '' }})</span>
                            </a>

                            @if(auth()->user()->isModerator())
                                <form method="POST" action="{{ route('chats.destroy', $c) }}"
                                    style="margin:0;"
                                    onsubmit="return confirm('Удалить чат «{{ $c->name }}»?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            title="Удалить чат"
                                            style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:14px;">
                                        ×
                                    </button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="user-block">
                    @auth
                        <div style="display:flex; align-items:center; gap:8px;">
                            @if(auth()->user()->avatar_path)
                                <img src="{{ Storage::disk('public')->url(auth()->user()->avatar_path) }}"
                                    style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                            @endif
                            <div>
                                {{ auth()->user()->name }}
                                @if(auth()->user()->isAdmin())
                                    <span class="role-badge admin">admin</span>
                                @elseif(auth()->user()->isPureModerator())
                                    <span class="role-badge mod">мод</span>
                                @endif
                            </div>
                        </div>
                        <div style="margin-top:6px; display:flex; flex-direction:column; gap:4px;">
                            <a href="{{ route('profile.edit') }}">⚙ Профиль</a>
                            @if(auth()->user()->isModerator())
                                <a href="{{ route('moderator.applications') }}">📋 Заявки</a>
                            @endif
                            @unless(auth()->user()->isModerator())
                                <a href="{{ route('moderator.apply') }}">✍ Стать модератором</a>
                            @endunless
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.actions') }}">🛡 Действия</a>
                                <a href="{{ route('admin.users') }}">👥 Пользователи</a>
                                <a href="{{ route('admin.chats') }}">💬 Чаты</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn btn-ghost" style="width:100%; margin-top:4px;">Выйти</button>
                            </form>
                        </div>
                    @endauth
                </div>
            </aside>

            <div class="main">
                <div class="content-header">
                    <strong>@yield('title', 'Чат')</strong>
                    @yield('header-actions')
                </div>

                <div class="content">
                    @if(session('success')) <div class="success">{{ session('success') }}</div> @endif
                    @if($errors->any()) <div class="errors">{{ $errors->first() }}</div> @endif

                    @yield('content')
                </div>

                @yield('composer')
            </div>
        </div>

        <script>
        document.getElementById('chat-search')?.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#chat-list li').forEach(li => {
                li.style.display = li.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
        </script>
        @stack('scripts')
    </body>
</html>