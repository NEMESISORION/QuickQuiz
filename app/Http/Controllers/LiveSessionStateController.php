<?php

namespace App\Http\Controllers;

use App\Domain\LiveSessions\LiveSessionState;
use App\Models\LiveSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class LiveSessionStateController extends Controller
{
    public function __invoke(LiveSession $liveSession, LiveSessionState $liveSessionState): JsonResponse
    {
        Gate::authorize('viewState', $liveSession);

        return response()->json(['version' => $liveSessionState->version($liveSession)]);
    }
}
