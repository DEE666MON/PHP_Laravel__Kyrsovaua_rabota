@extends('layouts.chat')

@section('title', 'Заявки в модераторы')

@section('content')
    @forelse($applications as $app)
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>{{ $app->user->name }}</strong>
                    <span style="color:#6b7280;">· {{ $app->created_at->format('d.m.Y H:i') }}</span>
                    <span style="margin-left:8px;">
                        @if($app->status === 'pending')
                            <span style="color:#f59e0b;">на рассмотрении</span>
                        @elseif($app->status === 'approved')
                            <span style="color:#10b981;">одобрена</span>
                        @else
                            <span style="color:#ef4444;">отклонена</span>
                        @endif
                    </span>
                </div>
                <div style="font-size:13px; color:#6b7280;">
                    ✅ {{ $app->approveCount() }} / 3 &nbsp; ❌ {{ $app->rejectCount() }} / 3
                </div>
            </div>

            <p style="margin:8px 0; white-space:pre-wrap;">{{ $app->reason }}</p>

            @if($app->status === 'pending' && auth()->id() !== $app->user_id)
                <form method="POST" action="{{ route('moderator.vote', $app) }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="approve" value="1">
                    <button class="btn btn-success" type="submit">За</button>
                </form>
                <form method="POST" action="{{ route('moderator.vote', $app) }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="approve" value="0">
                    <button class="btn btn-danger" type="submit">Против</button>
                </form>
            @endif

            @if($app->votes->count())
                <div style="margin-top:8px; font-size:12px; color:#6b7280;">
                    Голоса:
                    @foreach($app->votes as $v)
                        {{ $v->voter->name }} {{ $v->approve ? '✅' : '❌' }}@if(!$loop->last), @endif
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p>Заявок пока нет.</p>
    @endforelse

    {{ $applications->links() }}
@endsection