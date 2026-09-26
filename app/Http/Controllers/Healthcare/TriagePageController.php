<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Services\Healthcare\HealthcareContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TriagePageController extends Controller
{
    public function __invoke(Request $request, HealthcareContext $context): Response
    {
        abort_unless($request->user()->isPatient(), 403, 'Esta área é exclusiva para pacientes.');

        $healthcare = $context->forPatient($request->user());
        $blocked = ! ($healthcare['modules']['orientacao'] ?? false);

        return Inertia::render($blocked ? 'Healthcare/Unavailable' : 'Healthcare/Patient/Guidance', [
            'healthcare' => $healthcare,
            'title' => 'Orientação em saúde',
            'reason' => $blocked ? 'Este serviço não está incluído no seu plano.' : null,
        ]);
    }
}
