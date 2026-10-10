@extends('layouts.chat')

@section('title', 'Создать чат')

@section('content')
    <form method="POST" action="{{ route('chat.storeChat') }}" class="card">
        @csrf
        <div style="margin-bottom:12px;">
            <label>Название</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
        </div>
        <div style="margin-bottom:12px;">
            <label>Описание (необязательно)</label>
            <textarea name="description" maxlength="500" rows="3"
                style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">{{ old('description') }}</textarea>
        </div>
        <button class="btn btn-primary" type="submit">Создать</button>
    </form>
@endsection