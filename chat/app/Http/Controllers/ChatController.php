<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
{
    public function index(Request $request)
    {
        $chats = Chat::query()
            ->when($request->filled('q'), fn ($q) =>
                $q->where('name', 'ilike', '%'.$request->q.'%'))
            ->withCount('messages')
            ->orderBy('name')
            ->get();
        return view('chat.index', compact('chats'));
    }

    public function create()
    {
        return view('chat.create');
    }

    // Отправка нового сообщения.
    public function storeChat(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:chats,name',
            'description' => 'nullable|string|max:500',
        ]);
        $data['slug'] = Str::slug($data['name']) ?: 'chat';
        $base = $data['slug'];
        $i = 1;
        while (Chat::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $base.'-'.$i++;
        }
        $data['creator_id'] = $request->user()->id;
        $chat = Chat::create($data);
        return redirect()->route('chat.show', $chat)
            ->with('success', 'Чат создан');
    }

    public function show(Chat $chat)
    {
        $messages = $chat->messages()
            ->with('user')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();
        $chats = Chat::withCount('messages')->orderBy('name')->get();
        return view('chat.show', compact('chat', 'chats', 'messages'));
    }

    public function storeMessage(Request $request, Chat $chat)
    {
        $data = $request->validate([
            'body'  => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);
        if (empty($data['body']) && ! $request->hasFile('image')) {
            return back()->withErrors(['body' => 'Введите сообщение или прикрепите картинку']);
        }
        $path = $request->hasFile('image')
            ? $request->file('image')->store('chat', 'public')
            : null;
        $chat->messages()->create([
            'user_id'    => $request->user()->id,
            'body'       => $data['body'] ?? null,
            'image_path' => $path,
        ]);
        return back();
    }

    // Получение новых сообщений.
    public function fetch(Request $request, Chat $chat)
    {
        $afterId = (int) $request->query('after', 0);
        $messages = $chat->messages()
            ->with('user')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(50)
            ->get();
        return response()->json($this->mapMessages($messages));
    }

    // Подгрузка более старых сообщений.
    public function older(Request $request, Chat $chat)
    {
        $beforeId = (int) $request->query('before', 0);
        $messages = $chat->messages()
            ->with('user')
            ->when($beforeId > 0, fn ($q) => $q->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();
        return response()->json($this->mapMessages($messages));
    }

    // Модерация: удалить сообщение.
    public function destroyMessage(Message $message)
    {
        Gate::authorize('delete', $message);
        ModerationAction::create([
            'actor_id'          => auth()->id(),
            'action'            => 'delete_message',
            'target_message_id' => $message->id,
            'target_user_id'    => $message->user_id,
        ]);
        $message->delete(); // soft delete — отмена удалённого сообщения
        return back()->with('success', 'Сообщение удалено');
    }

    // Модерация: удалить пользователя.
    public function destroyUser(User $user)
    {
        Gate::authorize('delete', $user);
        $messageIds = $user->messages()->pluck('id')->all();
        ModerationAction::create([
            'actor_id'       => auth()->id(),
            'action'         => 'delete_user',
            'target_user_id' => $user->id,
            'payload'        => ['message_ids' => $messageIds],
        ]);
        $user->messages()->delete();
        $user->delete();
        return redirect()->route('chat.index')->with('success', 'Пользователь удалён');
    }

    // Сериализация сообщений для JSON-ответов.
    private function mapMessages($messages): array
    {
        return $messages->map(fn (Message $m) => [
            'id'           => $m->id,
            'chat_id'      => $m->chat_id,
            'user_id'      => $m->user_id,
            'user_name'    => $m->user?->name ?? 'Удалённый',
            'is_moderator' => $m->user?->isModerator() ?? false,
            'body'         => $m->body,
            'image_url'    => $m->image_path ? Storage::url($m->image_path) : null,
            'created_at'   => $m->created_at->format('d.m.Y H:i'),
        ])->all();
    }
}