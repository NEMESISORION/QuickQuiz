<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\RecordLiveAnswer;
use App\Http\Requests\StoreLiveAnswerRequest;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class LiveSessionAnswerController extends Controller
{
    public function __invoke(
        StoreLiveAnswerRequest $request,
        LiveSession $liveSession,
        RecordLiveAnswer $recordLiveAnswer,
    ): RedirectResponse {
        $learner = $request->user();
        assert($learner instanceof User);
        $participant = $liveSession->participants()->whereBelongsTo($learner, 'learner')->firstOrFail();
        assert($participant instanceof LiveSessionParticipant);

        $recordLiveAnswer->handle($liveSession, $participant, $request->integer('answer_option_id'));

        return back()->with('status', 'Answer locked. Waiting for the host.');
    }
}
