<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\MaxMessageRequest;
use App\Max\Services\MaxAssistantService;
use Illuminate\Http\JsonResponse;

class MaxMessageController extends Controller
{
    public function __invoke(MaxMessageRequest $request, MaxAssistantService $max): JsonResponse
    {
        return response()->json($max->respond(
            $request->user(),
            trim($request->validated('message')),
            $request->validated('page'),
        ));
    }
}
