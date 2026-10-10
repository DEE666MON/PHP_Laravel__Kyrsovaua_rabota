<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'bio'   => ['nullable', 'string', 'max:1000'],
        ]);
        $emailChanged = $data['email'] !== $user->email;
        $user->fill($data);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();
        $msg = $emailChanged
            ? 'Профиль обновлён. Подтвердите новую почту.'
            : 'Профиль обновлён.';
        return back()->with('success', $msg);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);
        $user = auth()->user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);
        return back()->with('success', 'Аватарка обновлена.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);
        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);
        return back()->with('success', 'Пароль обновлён.');
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);
        $user = auth()->user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}