<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // Первая загрузка страницы чата.
    public function index()
    {
        $messages = Message::with('user')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return view('chat.index', compact('messages'));
    }

    // Отправка нового сообщения.
    public function store(Request $request)
    {
        $data = $request->validate([
            'body'  => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        if (empty($data['body']) && ! $request->hasFile('image')) {
            return back()->withErrors(['body' => 'Введите сообщение или прикрепите картинку']);
        }

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat', 'public');
        }

        Message::create([
            'user_id'    => $request->user()->id,
            'body'       => $data['body'] ?? null,
            'image_path' => $path,
        ]);

        return back();
    }

    // Получение новых сообщений.
    public function fetch(Request $request)
    {
        $afterId = (int) $request->query('after', 0);

        $messages = Message::with('user')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(50)
            ->get();

        return response()->json($this->mapMessages($messages));
    }

    // Подгрузка более старых сообщений.
    public function older(Request $request)
    {
        $beforeId = (int) $request->query('before', 0);

        $messages = Message::with('user')
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

        if ($message->image_path) {
            Storage::disk('public')->delete($message->image_path);
        }

        $message->delete();

        return back()->with('success', 'Сообщение удалено');
    }

    // Модерация: удалить пользователя.
    public function destroyUser(User $user)
    {
        Gate::authorize('delete', $user);

        foreach ($user->messages as $m) {
            if ($m->image_path) {
                Storage::disk('public')->delete($m->image_path);
            }
        }

        $user->delete();

        return redirect()->route('chat.index')->with('success', 'Пользователь удалён');
    }

    // Сериализация сообщений для JSON-ответов.
    private function mapMessages($messages): array
    {
        return $messages->map(fn (Message $m) => [
            'id'           => $m->id,
            'user_id'      => $m->user_id,
            'user_name'    => $m->user?->name ?? 'Удалён',
            'is_moderator' => $m->user?->isModerator() ?? false,
            'body'         => $m->body,
            'image_url'    => $m->image_path ? Storage::url($m->image_path) : null,
            'created_at'   => $m->created_at->format('d.m.Y H:i'),
        ])->all();
    }
}