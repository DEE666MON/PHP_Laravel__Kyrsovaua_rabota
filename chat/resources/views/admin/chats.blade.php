@extends('layouts.chat')

@section('title', 'Управление чатами')

@section('content')
    <table style="width:100%; background:#fff; border-radius:8px; padding:8px; border-collapse:collapse;">
        <thead>
        <tr style="text-align:left;">
            <th style="padding:8px;">ID</th>
            <th>Название</th>
            <th>Сообщений</th>
            <th>Создан</th>
            <th>Статус</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($chats as $c)
            <tr style="border-top:1px solid #eee;">
                <td style="padding:8px;">{{ $c->id }}</td>
                <td>
                    @if($c->trashed())
                        {{ $c->name }}
                    @else
                        <a href="{{ route('chat.show', $c) }}"># {{ $c->name }}</a>
                    @endif
                </td>
                <td>{{ $c->messages_count }}</td>
                <td>{{ $c->created_at->format('d.m.Y H:i') }}</td>
                <td>
                    @if($c->trashed())
                        <span style="color:#ef4444;">удалён {{ $c->deleted_at->format('d.m.Y H:i') }}</span>
                    @else
                        <span style="color:#10b981;">активен</span>
                    @endif
                </td>
                <td>
                    @if($c->trashed())
                        <form method="POST" action="{{ route('admin.chats.restore', $c->id) }}"
                                onsubmit="return confirm('Восстановить чат «{{ $c->name }}»?')">
                            @csrf
                            <button class="btn btn-success" type="submit">Восстановить</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection