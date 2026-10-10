@extends('layouts.chat')

@section('title', 'Действия модераторов')

@section('content')
    <table style="width:100%; background:#fff; border-radius:8px; padding:8px; border-collapse:collapse;">
        <thead>
            <tr style="text-align:left;">
                <th style="padding:8px;">Когда</th>
                <th>Модератор</th>
                <th>Действие</th>
                <th>Цель</th>
                <th>Статус</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($actions as $a)
            <tr style="border-top:1px solid #eee;">
                <td style="padding:8px;">{{ $a->created_at->format('d.m.Y H:i') }}</td>
                <td>{{ $a->actor->name ?? '—' }}</td>
                <td>
                    @if($a->action === 'delete_message') удаление сообщения
                    @elseif($a->action === 'delete_user') удаление пользователя
                    @else {{ $a->action }}
                    @endif
                </td>
                <td>
                    @if($a->action === 'delete_message') msg #{{ $a->target_message_id }}
                    @elseif($a->action === 'delete_user') user #{{ $a->target_user_id }}
                    @endif
                </td>
                <td>
                    @if($a->undone_at)
                        <span style="color:#6b7280;">отменено {{ $a->undoneBy->name ?? '' }}</span>
                    @else
                        <span style="color:#10b981;">активно</span>
                    @endif
                </td>
                <td>
                    @unless($a->undone_at)
                        <form method="POST" action="{{ route('admin.undo', $a) }}">
                            @csrf
                            <button class="btn btn-ghost" type="submit">Отменить</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top:12px;">{{ $actions->links() }}</div>
@endsection