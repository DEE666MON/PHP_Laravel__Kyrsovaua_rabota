<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function actions()
    {
        $actions = ModerationAction::with(['actor', 'undoneBy'])
            ->latest()
            ->paginate(50);
        return view('admin.actions', compact('actions'));
    }

    public function undo(ModerationAction $action)
    {
        abort_if($action->undone_at, 400, 'Это действие уже отменено');
        if ($action->action === 'delete_message') {
            Message::withTrashed()
                ->find($action->target_message_id)
                ?->restore();
        } elseif ($action->action === 'delete_user') {
            $user = User::withTrashed()->find($action->target_user_id);
            if ($user) {
                $user->restore();
                $ids = $action->payload['message_ids'] ?? [];
                if ($ids) {
                    Message::withTrashed()->whereIn('id', $ids)->restore();
                }
            }
        }
        $action->update([
            'undone_at' => now(),
            'undone_by' => auth()->id(),
        ]);
        return back()->with('success', 'Действие отменено');
    }

    public function users(Request $request)
    {
        $users = User::query()
            ->when($request->filled('q'), fn ($q) =>
                $q->where(function ($w) use ($request) {
                    $w->where('name', 'ilike', '%'.$request->q.'%')
                        ->orWhere('email', 'ilike', '%'.$request->q.'%');
                }))
            ->withTrashed()
            ->orderBy('id')
            ->paginate(50);
        return view('admin.users', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|in:user,moderator,admin',
        ]);
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Нельзя снять с себя роль админа']);
        }
        $user->update(['role' => $data['role']]);
        return back()->with('success', "Роль обновлена: {$data['role']}");
    }
}