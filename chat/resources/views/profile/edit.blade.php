@extends('layouts.chat')

@section('title', 'Профиль')

@section('content')
    {{-- Аватарка + основная инфа --}}
    <div class="card" style="display:flex; gap:20px; align-items:flex-start;">
        <div style="text-align:center;">
            @if($user->avatar_path)
                <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="avatar"
                    style="width:120px;height:120px;border-radius:50%;object-fit:cover;display:block;">
            @else
                <div style="width:120px;height:120px;border-radius:50%;background:#e5e7eb;
                            display:flex;align-items:center;justify-content:center;
                            font-size:42px;color:#6b7280;">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.avatar') }}"
                    enctype="multipart/form-data" style="margin-top:8px;">
                @csrf
                <label for="avatar-input" class="btn btn-primary" style="cursor:pointer;">
                    Сменить аватар
                </label>
                <input type="file" name="avatar" id="avatar-input" accept="image/*"
                    style="display:none" onchange="this.form.submit()">
            </form>
            @error('avatar') <div class="errors">{{ $message }}</div> @enderror
        </div>

        <div style="flex:1;">
            <h2 style="margin-top:0;">{{ $user->name }}</h2>
            <p style="color:#6b7280;">
                {{ $user->email }}
                @if($user->email_verified_at)
                    <span style="color:#10b981;">✓ подтверждена</span>
                @else
                    <span style="color:#ef4444;">не подтверждена</span>
                @endif
            </p>
            <p style="color:#374151; white-space:pre-wrap;">
                {{ $user->bio ?: 'Описание пока не заполнено.' }}
            </p>
        </div>
    </div>

    {{-- Основные данные --}}
    <div class="card">
        <h3>Основные данные</h3>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf @method('PATCH')

            <div style="margin-bottom:12px;">
                <label>Имя</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                @error('name') <div class="errors">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:12px;">
                <label>Почта</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                @if($user->email_verified_at)
                    <small style="color:#6b7280;">При смене почты потребуется повторное подтверждение.</small>
                @endif
                @error('email') <div class="errors">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:12px;">
                <label>О себе</label>
                <textarea name="bio" rows="4" maxlength="1000"
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">{{ old('bio', $user->bio) }}</textarea>
                @error('bio') <div class="errors">{{ $message }}</div> @enderror
            </div>

            <button class="btn btn-primary" type="submit">Сохранить</button>
        </form>
    </div>

    {{-- Смена пароля --}}
    <div class="card">
        <h3>Смена пароля</h3>
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf @method('PUT')

            <div style="margin-bottom:12px;">
                <label>Текущий пароль</label>
                <input type="password" name="current_password" required
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                @error('current_password', 'updatePassword') <div class="errors">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:12px;">
                <label>Новый пароль</label>
                <input type="password" name="password" required
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                @error('password', 'updatePassword') <div class="errors">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:12px;">
                <label>Повторите новый пароль</label>
                <input type="password" name="password_confirmation" required
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
            </div>

            <button class="btn btn-primary" type="submit">Обновить пароль</button>
        </form>
    </div>

    {{-- Удаление аккаунта --}}
    <div class="card" style="border:1px solid #fecaca;">
        <h3 style="color:#b91c1c;">Удаление аккаунта</h3>
        <p style="color:#6b7280; font-size:14px;">
            Аккаунт будет скрыт (soft-delete). Сообщения останутся в чатах.
        </p>
        <form method="POST" action="{{ route('profile.destroy') }}"
                onsubmit="return confirm('Точно удалить аккаунт?')">
            @csrf @method('DELETE')

            <div style="margin-bottom:12px;">
                <label>Пароль для подтверждения</label>
                <input type="password" name="password" required
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px;">
                @error('password', 'userDeletion') <div class="errors">{{ $message }}</div> @enderror
            </div>

            <button class="btn btn-danger" type="submit">Удалить аккаунт</button>
        </form>
    </div>
@endsection