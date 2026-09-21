<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\JoinLiveSession;
use App\Http\Requests\JoinLiveSessionRequest;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearnerLiveSessionController extends Controller
{
    public function create(): View
    {
        return view('learner.live-sessions.join');
    }

    public function store(JoinLiveSessionRequest $request, JoinLiveSession $joinLiveSession): RedirectResponse
    {
        $learner = $request->user();
        assert($learner instanceof User);
        $participant = $joinLiveSession->handle($request->string('code')->toString(), $learner);

        return redirect()
            ->route('learner.live-sessions.show', $participant->live_session_id)
            ->with('status', 'You joined the lobby.');
    }

    public function show(Request $request, LiveSession $liveSession): View
    {
        Gate::authorize('viewLearnerLobby', $liveSession);
        $learner = $request->user();
        assert($learner instanceof User);
        $participant = $liveSession->participants()
            ->whereBelongsTo($learner, 'learner')
            ->firstOrFail();
        $participant->update(['last_seen_at' => now()]);
        $liveSession->load(['quiz', 'host']);

        return view('learner.live-sessions.show', ['liveSession' => $liveSession]);
    }
}
