<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\CreateLiveSession;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EducatorLiveSessionController extends Controller
{
    public function store(Request $request, Quiz $quiz, CreateLiveSession $createLiveSession): RedirectResponse
    {
        Gate::authorize('view', $quiz);
        $host = $request->user();
        assert($host instanceof User);

        $liveSession = $createLiveSession->handle($quiz, $host);

        return redirect()
            ->route('educator.live-sessions.show', $liveSession)
            ->with('status', 'Live lobby is ready. Share the join code with your learners.');
    }

    public function show(LiveSession $liveSession): View
    {
        Gate::authorize('viewHostLobby', $liveSession);
        $liveSession->load(['quiz', 'participants.learner']);

        return view('educator.live-sessions.show', ['liveSession' => $liveSession]);
    }
}
