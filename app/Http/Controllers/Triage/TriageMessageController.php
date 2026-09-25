<?php

namespace App\Http\Controllers\Triage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Triage\StoreTriageMessageRequest;
use App\Models\TriageSession;
use App\Triage\Services\TriageConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TriageMessageController extends Controller
{
    public function store(
        StoreTriageMessageRequest $request,
        TriageSession $triageSession,
        TriageConversationService $service,
    ): JsonResponse {
        $reply = $service->respond(
            $triageSession,
            $request->validated('message'),
            $request->validated('request_id') ?? (string) Str::uuid(),
        );

        return response()->json($reply->toArray());
    }
}
