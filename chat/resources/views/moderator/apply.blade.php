@extends('layouts.chat')

@section('title', 'Заявка в модераторы')

@section('content')
    <div class="card">
        <h3>Подать заявку</h3>
        <p style="color:#6b7280; font-size:14px;">
            Для получения роли модератора нужно <strong>3 голоса "за"</strong> от модераторов
            или <strong>1 голос "за" от администратора</strong>.
        </p>
        <form method="POST" action="{{ route('moderator.apply.store') }}">
            @csrf
            <textarea name="reason" rows="5" required minlength="20" maxlength="2000"
                placeholder="Почему вы хотите стать модератором?"
                style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">{{ old('reason') }}</textarea>
            <button class="btn btn-primary" type="submit" style="margin-top:8px;">Отправить заявку</button>
        </form>
    </div>

    <div class="card">
        <h3>Мои заявки</h3>
        @forelse($myApplications as $app)
            <div style="border-bottom:1px solid #eee; padding:8px 0;">
                <strong>{{ $app->created_at->format('d.m.Y H:i') }}</strong>
                — статус:
                @if($app->status === 'pending') <span style="color:#f59e0b;">на рассмотрении</span>
                @elseif($app->status === 'approved') <span style="color:#10b981;">одобрена</span>
                @else <span style="color:#ef4444;">отклонена</span>
                @endif
                <div style="margin-top:4px; color:#4b5563;">{{ $app->reason }}</div>
            </div>
        @empty
            <p style="color:#6b7280;">Заявок пока нет.</p>
        @endforelse
    </div>
@endsection