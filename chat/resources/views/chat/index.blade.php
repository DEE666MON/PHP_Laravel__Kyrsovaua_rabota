<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Чат</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #f3f4f6; }
        .container { max-width: 800px; margin: 20px auto; padding: 16px; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            background: #fff; padding: 12px 16px; border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08); margin-bottom: 12px;
        }
        .role-badge {
            background: #10b981; color: #fff; padding: 2px 8px;
            border-radius: 6px; font-size: 12px; margin-left: 6px;
        }
        .messages {
            background: #fff; border-radius: 10px; padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            max-height: 60vh; overflow-y: auto;
        }
        .message { padding: 10px; border-bottom: 1px solid #eee; position: relative; }
        .message:last-child { border-bottom: none; }
        .message .meta { font-size: 12px; color: #666; margin-bottom: 4px; }
        .message .body { white-space: pre-wrap; word-break: break-word; }
        .message img { max-width: 300px; border-radius: 8px; margin-top: 6px; display: block; }
        .message .actions {
            position: absolute; top: 8px; right: 8px; display: flex; gap: 6px;
        }
        .btn {
            border: none; padding: 4px 8px; border-radius: 6px;
            font-size: 12px; cursor: pointer;
        }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-ghost { background: #e5e7eb; color: #333; display: block; width: 100%; padding: 6px; }
        form.composer {
            background: #fff; border-radius: 10px; padding: 12px;
            margin-top: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }
        form.composer textarea {
            width: 100%; padding: 8px; border: 1px solid #ddd;
            border-radius: 8px; resize: vertical; min-height: 60px;
        }
        form.composer .row { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
        .errors { color: #ef4444; font-size: 13px; margin-bottom: 6px; }
        .success { color: #10b981; font-size: 13px; margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <strong>{{ auth()->user()->name }}</strong>
            @if(auth()->user()->isModerator())
                <span class="role-badge">модератор</span>
            @endif
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Выйти</button>
        </form>
    </div>

    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif

    <div class="messages" id="messages">
        <button id="load-older" class="btn btn-ghost" style="margin-bottom:8px;">↑ Загрузить старые</button>

        @foreach($messages as $message)
            <div class="message" id="msg-{{ $message->id }}" data-user-id="{{ $message->user_id }}">
                <div class="meta">
                    <strong>{{ $message->user->name }}</strong>
                    @if($message->user->isModerator())
                        <span class="role-badge" style="font-size:10px">мод</span>
                    @endif
                    · {{ $message->created_at->format('d.m.Y H:i') }}
                </div>
                @if($message->body)
                    <div class="body">{{ $message->body }}</div>
                @endif
                @if($message->image_path)
                    <img src="{{ Storage::url($message->image_path) }}" alt="img">
                @endif

                @if(auth()->user()->isModerator())
                    <div class="actions">
                        <form method="POST" action="{{ route('messages.destroy', $message) }}"
                            onsubmit="return confirm('Удалить сообщение?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger" type="submit">✕ удалить</button>
                        </form>
                        @if($message->user_id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $message->user) }}"
                                onsubmit="return confirm('Удалить пользователя и все его сообщения?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger" type="submit">бан юзера</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <form class="composer" method="POST" action="{{ route('chat.store') }}" enctype="multipart/form-data">
        @csrf
        <textarea name="body" placeholder="Ваше сообщение...">{{ old('body') }}</textarea>
        <div class="row">
            <input type="file" name="image" accept="image/*">
            <button class="btn btn-primary" type="submit">Отправить</button>
        </div>
    </form>
</div>

<script>
(function () {
    const box          = document.getElementById('messages');
    const loadOlderBtn = document.getElementById('load-older');
    const csrfToken    = document.querySelector('meta[name="csrf-token"]').content;
    const isModerator  = @json(auth()->user()->isModerator());
    const currentUserId= {{ auth()->id() }};

    let lastId  = {{ $messages->last()?->id ?? 0 }};
    let firstId = {{ $messages->first()?->id ?? 0 }};

    // Автоскролл вниз при загрузке
    function scrollToBottom() {
        box.scrollTop = box.scrollHeight;
    }

    // Первичный скролл — после того как все картинки загрузятся
    function scrollToBottomAfterImages() {
        scrollToBottom();
        const imgs = box.querySelectorAll('img');
        let pending = 0;
        imgs.forEach(img => {
            if (!img.complete) {
                pending++;
                img.addEventListener('load',  () => { if (--pending === 0) scrollToBottom(); });
                img.addEventListener('error', () => { if (--pending === 0) scrollToBottom(); });
            }
        });
    }

    window.addEventListener('load', scrollToBottomAfterImages);
    scrollToBottomAfterImages();

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function buildMessage(m) {
        const div = document.createElement('div');
        div.className = 'message';
        div.id = 'msg-' + m.id;
        div.dataset.userId = m.user_id;

        let html = '<div class="meta"><strong>' + escapeHtml(m.user_name) + '</strong>';
        if (m.is_moderator) html += ' <span class="role-badge" style="font-size:10px">мод</span>';
        html += ' · ' + escapeHtml(m.created_at) + '</div>';

        if (m.body) html += '<div class="body">' + escapeHtml(m.body) + '</div>';
        if (m.image_url) html += '<img src="' + escapeHtml(m.image_url) + '" alt="img">';

        if (isModerator) {
            html += '<div class="actions">';
            html += '<form method="POST" action="/messages/' + m.id + '" onsubmit="return confirm(\'Удалить сообщение?\')">';
            html += '<input type="hidden" name="_token" value="' + csrfToken + '">';
            html += '<input type="hidden" name="_method" value="DELETE">';
            html += '<button class="btn btn-danger" type="submit">✕ удалить</button></form>';

            if (m.user_id !== currentUserId) {
                html += '<form method="POST" action="/users/' + m.user_id + '" onsubmit="return confirm(\'Удалить пользователя?\')">';
                html += '<input type="hidden" name="_token" value="' + csrfToken + '">';
                html += '<input type="hidden" name="_method" value="DELETE">';
                html += '<button class="btn btn-danger" type="submit">бан юзера</button></form>';
            }
            html += '</div>';
        }

        div.innerHTML = html;
        return div;
    }

    // Автообновление страницы
    async function poll() {
        try {
            const res = await fetch('/chat/messages?after=' + lastId, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const items = await res.json();
            if (!items.length) return;

            const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 150;

            for (const m of items) {
                if (!document.getElementById('msg-' + m.id)) {
                    box.appendChild(buildMessage(m));
                    scrollToBottom();
                    lastId = Math.max(lastId, m.id);
                    if (firstId === 0) firstId = m.id;
                }
            }

            if (nearBottom) box.scrollTop = box.scrollHeight;
        } catch (e) {}
    }
    setInterval(poll, 3000);

    // Подгрузка старых сообщений
    loadOlderBtn?.addEventListener('click', async () => {
        loadOlderBtn.disabled = true;
        const prevHeight = box.scrollHeight;
        try {
            const res = await fetch('/chat/older?before=' + firstId, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const items = await res.json();

            const frag = document.createDocumentFragment();
            for (const m of items) {
                if (!document.getElementById('msg-' + m.id)) {
                    frag.appendChild(buildMessage(m));
                    firstId = Math.min(firstId || m.id, m.id);
                }
            }
            box.insertBefore(frag, loadOlderBtn.nextSibling);

            // сохранить позицию скролла
            box.scrollTop = box.scrollHeight - prevHeight;

            if (items.length < 30) loadOlderBtn.style.display = 'none';
        } finally {
            loadOlderBtn.disabled = false;
        }
    });
})();
</script>
</body>
</html>
