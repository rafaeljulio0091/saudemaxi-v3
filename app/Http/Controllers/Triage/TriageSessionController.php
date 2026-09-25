<?php

namespace App\Http\Controllers\Triage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Triage\StartTriageSessionRequest;
use App\Models\TriageSession;
use App\Triage\Actions\StartTriageSession;
use App\Triage\Services\TriageSessionPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TriageSessionController extends Controller
{
    public function store(
        StartTriageSessionRequest $request,
        StartTriageSession $action,
        TriageSessionPresenter $presenter,
    ): JsonResponse {
        $session = $action->execute($request->user());

        return response()->json($presenter->present($session), 201);
    }

    public function show(
        Request $request,
        TriageSession $triageSession,
        TriageSessionPresenter $presenter,
    ): JsonResponse {
        Gate::authorize('view', $triageSession);

        return response()->json($presenter->present($triageSession));
    }
}
