<?php

namespace App\Http\Controllers;

use App\Models\ModeratorApplication;
use App\Models\ModeratorApplicationVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModeratorApplicationController extends Controller
{
    public function create()
    {
        $myApplications = auth()->user()
            ->applications()
            ->latest()
            ->get();
        return view('moderator.apply', compact('myApplications'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->isModerator()) {
            return back()->withErrors(['reason' => 'Вы уже модератор или админ']);
        }
        $hasPending = auth()->user()
            ->applications()
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return back()->withErrors(['reason' => 'У вас уже есть активная заявка']);
        }
        $data = $request->validate([
            'reason' => 'required|string|min:20|max:2000',
        ]);
        ModeratorApplication::create([
            'user_id' => auth()->id(),
            'reason'  => $data['reason'],
        ]);
        return back()->with('success', 'Заявка отправлена. Ожидайте голосов модераторов.');
    }

    public function index()
    {
        abort_unless(auth()->user()->isModerator(), 403);
        $applications = ModeratorApplication::with(['user', 'votes.voter'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20);
        return view('moderator.index', compact('applications'));
    }

    public function vote(Request $request, ModeratorApplication $application)
    {
        Gate::authorize('vote', $application);
        $data = $request->validate([
            'approve' => 'required|boolean',
        ]);
        ModeratorApplicationVote::updateOrCreate(
            ['application_id' => $application->id, 'voter_id' => auth()->id()],
            ['approve' => $data['approve']]
        );
        $this->evaluate($application->fresh());
        return back()->with('success', 'Голос учтён');
    }

    private function evaluate(ModeratorApplication $app): void
    {
        if ($app->status !== 'pending') {
            return;
        }
        // Голос админа — решает мгновенно
        $adminVote = $app->votes()
            ->whereHas('voter', fn ($q) => $q->where('role', 'admin'))
            ->latest()
            ->first();
        if ($adminVote) {
            $status = $adminVote->approve ? 'approved' : 'rejected';
            $app->update([
                'status'     => $status,
                'decided_by' => $adminVote->voter_id,
                'decided_at' => now(),
            ]);
            if ($status === 'approved') {
                $app->user->update(['role' => 'moderator']);
            }
            return;
        }
        // Три голоса модераторов "за" / "против"
        if ($app->approveCount() >= 3) {
            $app->update(['status' => 'approved', 'decided_at' => now()]);
            $app->user->update(['role' => 'moderator']);
        } elseif ($app->rejectCount() >= 3) {
            $app->update(['status' => 'rejected', 'decided_at' => now()]);
        }
    }
}