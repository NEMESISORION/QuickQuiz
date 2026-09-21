<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\CreateLiveSession;
use App\Domain\LiveSessions\LiveSessionState;
use App\Models\LiveSession;
use App\Models\LiveSessionResponse;
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

    public function show(LiveSession $liveSession, LiveSessionState $liveSessionState): View
    {
        Gate::authorize('viewHostLobby', $liveSession);
        $liveSession->load(['quiz.questions', 'currentQuestion.answerOptions']);
        $participants = $liveSession->participants()
            ->with('learner')
            ->withSum('responses', 'points_awarded')
            ->get()
            ->sortByDesc('responses_sum_points_awarded')
            ->values();
        $distribution = $liveSession->current_question_id === null
            ? collect()
            : LiveSessionResponse::query()
                ->where('question_id', $liveSession->current_question_id)
                ->whereHas('participant', fn ($query) => $query->whereBelongsTo($liveSession))
                ->selectRaw('answer_option_id, count(*) as response_count')
                ->groupBy('answer_option_id')
                ->pluck('response_count', 'answer_option_id');
        $questionNumber = $liveSession->current_question_id === null
            ? null
            : $liveSession->quiz->questions->search(
                fn ($question): bool => $question->getKey() === $liveSession->current_question_id,
            );

        return view('educator.live-sessions.show', [
            'liveSession' => $liveSession,
            'participants' => $participants,
            'distribution' => $distribution,
            'questionNumber' => $questionNumber === false || $questionNumber === null ? null : $questionNumber + 1,
            'maxScore' => (int) $liveSession->quiz->questions->sum('points'),
            'syncVersion' => $liveSessionState->version($liveSession),
        ]);
    }
}
