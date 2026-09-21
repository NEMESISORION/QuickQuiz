<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\JoinLiveSession;
use App\Domain\LiveSessions\LiveSessionState;
use App\Enums\LiveSessionStatus;
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

    public function show(Request $request, LiveSession $liveSession, LiveSessionState $liveSessionState): View
    {
        Gate::authorize('viewLearnerLobby', $liveSession);
        $learner = $request->user();
        assert($learner instanceof User);
        $participant = $liveSession->participants()
            ->whereBelongsTo($learner, 'learner')
            ->firstOrFail();
        $participant->update(['last_seen_at' => now()]);
        $liveSession->load(['quiz.questions', 'host', 'currentQuestion.answerOptions']);
        $currentResponse = $liveSession->current_question_id === null
            ? null
            : $participant->responses()->where('question_id', $liveSession->current_question_id)->first();
        $leaderboard = $liveSession->status === LiveSessionStatus::Completed
            ? $liveSession->participants()
                ->with('learner')
                ->withSum('responses', 'points_awarded')
                ->get()
                ->sortByDesc('responses_sum_points_awarded')
                ->values()
            : collect();
        $questionNumber = $liveSession->current_question_id === null
            ? null
            : $liveSession->quiz->questions->search(
                fn ($question): bool => $question->getKey() === $liveSession->current_question_id,
            );

        return view('learner.live-sessions.show', [
            'liveSession' => $liveSession,
            'participant' => $participant,
            'currentResponse' => $currentResponse,
            'leaderboard' => $leaderboard,
            'questionNumber' => $questionNumber === false || $questionNumber === null ? null : $questionNumber + 1,
            'maxScore' => (int) $liveSession->quiz->questions->sum('points'),
            'currentScore' => (int) $participant->responses()->sum('points_awarded'),
            'syncVersion' => $liveSessionState->version($liveSession),
        ]);
    }
}
