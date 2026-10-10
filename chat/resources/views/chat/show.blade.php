@extends('layouts.chat')

@section('title', '# '.$chat->name)

@section('header-actions')
    @if($chat->description)
        <span style="color:#6b7280; font-size:13px;">{{ $chat->description }}</span>
    @endif
@endsection

@section('content')
    <div id="messages">
        <button id="load-older" class="btn btn-ghost" style="margin-bottom:8px;">↑ Загрузить старые</button>

        @foreach($messages as $message)
            <div class="message" id="msg-{{ $message->id }}" data-user-id="{{ $message->user_id }}">
                <div class="meta">
                    <strong>{{ $message->user?->name ?? 'Удалённый' }}</strong>
                    @if($message->user?->isAdmin())
                        <span class="role-badge admin">admin</span>
                    @elseif($message->user?->isPureModerator())
                        <span class="role-badge mod">мод</span>
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
                            <button class="btn btn-danger" type="submit">✕</button>
                        </form>
                        @if($message->user && $message->user_id !== auth()->id()
                            && auth()->user()->can('delete', $message->user))
                            <form method="POST" action="{{ route('users.destroy', $message->user) }}"
                                    onsubmit="return confirm('Удалить пользователя и все его сообщения?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger" type="submit">бан</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection

@section('composer')
    <form class="composer" method="POST" action="{{ route('chat.message.store', $chat) }}"
            enctype="multipart/form-data">
        @csrf
        <textarea name="body" placeholder="Сообщение в #{{ $chat->name }}…">{{ old('body') }}</textarea>
        <div class="row">
            <input type="file" name="image" accept="image/*">
            <button class="btn btn-primary" type="submit">Отправить</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    const box       = document.getElementById('messages');
    const olderBtn  = document.getElementById('load-older');
    const csrf      = document.querySelector('meta[name="csrf-token"]').content;
    const isMod     = @json(auth()->user()->isModerator());
    const myId      = {{ auth()->id() }};
    const chatSlug  = @json($chat->slug);

    let lastId  = {{ $messages->last()?->id ?? 0 }};
    let firstId = {{ $messages->first()?->id ?? 0 }};

    const esc = s => String(s ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    function scrollBottom() { box.scrollTop = box.scrollHeight; }
    function scrollBottomAfterImages() {
        scrollBottom();
        const imgs = box.querySelectorAll('img');
        let pending = 0;
        imgs.forEach(img => {
            if (!img.complete) {
                pending++;
                const done = () => { if (--pending === 0) scrollBottom(); };
                img.addEventListener('load', done);
                img.addEventListener('error', done);
            }
        });
    }
    window.addEventListener('load', scrollBottomAfterImages);
    scrollBottomAfterImages();

    function buildMessage(m) {
        const div = document.createElement('div');
        div.className = 'message';
        div.id = 'msg-' + m.id;
        div.dataset.userId = m.user_id;

        let html = '<div class="meta"><strong>' + esc(m.user_name) + '</strong>';
        if (m.is_moderator) html += ' <span class="role-badge mod">мод</span>';
        html += ' · ' + esc(m.created_at) + '</div>';

        if (m.body)      html += '<div class="body">' + esc(m.body) + '</div>';
        if (m.image_url) html += '<img src="' + esc(m.image_url) + '" alt="img">';

        if (isMod) {
            html += '<div class="actions">';
            html += '<form method="POST" action="/messages/' + m.id + '" onsubmit="return confirm(\'Удалить сообщение?\')">';
            html += '<input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="_method" value="DELETE">';
            html += '<button class="btn btn-danger" type="submit">✕</button></form>';
            html += '</div>';
        }
        div.innerHTML = html;
        return div;
    }

    async function poll() {
        try {
            const res = await fetch('/chats/' + chatSlug + '/messages?after=' + lastId, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const items = await res.json();
            if (!items.length) return;
            const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 150;
            for (const m of items) {
                if (!document.getElementById('msg-' + m.id)) {
                    box.appendChild(buildMessage(m));
                    lastId = Math.max(lastId, m.id);
                    if (!firstId) firstId = m.id;
                }
            }
            if (nearBottom) scrollBottom();
        } catch (e) {}
    }
    setInterval(poll, 3000);

    olderBtn?.addEventListener('click', async () => {
        olderBtn.disabled = true;
        const prevH = box.scrollHeight;
        try {
            const res = await fetch('/chats/' + chatSlug + '/older?before=' + firstId, {
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
            box.insertBefore(frag, olderBtn.nextSibling);
            box.scrollTop = box.scrollHeight - prevH;
            if (items.length < 30) olderBtn.style.display = 'none';
        } finally {
            olderBtn.disabled = false;
        }
    });
})();
</script>
@endpush