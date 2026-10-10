@extends('layouts.chat')

@section('title', 'Пользователи')

@section('content')
    <form method="GET" class="card" style="display:flex; gap:8px;">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Поиск..."
            style="flex:1; padding:6px; border:1px solid #d1d5db; border-radius:6px;">
        <button class="btn btn-primary">Найти</button>
    </form>

    <table style="width:100%; background:#fff; border-radius:8px; padding:8px;">
        <thead>
        <tr style="text-align:left;">
            <th style="padding:8px;">ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Статус</th>
        </tr>
        </thead>
        <tbody>
        @foreach($users as $u)
            <tr style="border-top:1px solid #eee;">
                <td style="padding:8px;">{{ $u->id }}</td>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.users.role', $u) }}" style="display:flex; gap:4px;">
                        @csrf
                        <select name="role">
                            <option value="user"      @selected($u->role==='user')>user</option>
                            <option value="moderator" @selected($u->role==='moderator')>moderator</option>
                            <option value="admin"     @selected($u->role==='admin')>admin</option>
                        </select>
                        <button class="btn btn-ghost" type="submit">ОК</button>
                    </form>
                </td>
                <td>
                    @if($u->trashed())
                        <span style="color:#ef4444;">удалён</span>
                    @else
                        <span style="color:#10b981;">активен</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top:12px;">{{ $users->links() }}</div>
@endsection