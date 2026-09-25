<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\ExpireLiveSession;
use App\Domain\LiveSessions\LiveSessionState;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LiveSessionStateController extends Controller
{
    public function __invoke(Request $request, LiveSession $liveSession, LiveSessionState $liveSessionState, ExpireLiveSession $expireLiveSession): JsonResponse
    {
        Gate::authorize('viewState', $liveSession);
        $liveSession = $expireLiveSession->handle($liveSession);

        if ($request->isMethod('POST')) {
            $user = $request->user();
            assert($user instanceof User);
            $participant = $liveSession->participants()
                ->whereBelongsTo($user, 'learner')
                ->first();

            if ($participant !== null && $participant->last_seen_at->lessThan(now()->subSeconds(5))) {
                $participant->update(['last_seen_at' => now()]);
            }
        }

        return response()->json(['version' => $liveSessionState->version($liveSession)]);
    }
}
