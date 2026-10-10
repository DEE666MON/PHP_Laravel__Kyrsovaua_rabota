@extends('layouts.chat')

@section('title', 'Выберите чат')

@section('content')
    <div class="card">
        <h2>Добро пожаловать в чат</h2>
        <p>Слева выберите один из существующих чатов или создайте новый.</p>
        <a class="btn btn-primary" href="{{ route('chat.create') }}">Создать чат</a>
    </div>
@endsection