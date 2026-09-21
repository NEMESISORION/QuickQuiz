<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\AdvanceLiveSession;
use App\Domain\LiveSessions\StartLiveSession;
use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LiveSessionControlController extends Controller
{
    public function start(LiveSession $liveSession, StartLiveSession $startLiveSession): RedirectResponse
    {
        Gate::authorize('control', $liveSession);
        $startLiveSession->handle($liveSession);

        return back()->with('status', 'The first question is live.');
    }

    public function advance(LiveSession $liveSession, AdvanceLiveSession $advanceLiveSession): RedirectResponse
    {
        Gate::authorize('control', $liveSession);
        $updatedSession = $advanceLiveSession->handle($liveSession);
        $message = $updatedSession->status === LiveSessionStatus::Completed
            ? 'Live session completed. The final leaderboard is ready.'
            : 'The next question is live.';

        return back()->with('status', $message);
    }
}
