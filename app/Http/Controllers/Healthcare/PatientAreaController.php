<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Services\Healthcare\HealthcareContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientAreaController extends Controller
{
    public function page(Request $request, HealthcareContext $context): Response
    {
        $page = $request->route('page');
        $title = $request->route('title');
        $module = $request->route('module');

        $healthcare = $context->forPatient($request->user());
        $blocked = $module && ! ($healthcare['modules'][$module] ?? false);

        return Inertia::render('Healthcare/'.($blocked ? 'Unavailable' : $page), [
            'healthcare' => $healthcare,
            'title' => $title,
            'reason' => $blocked ? 'Este serviço não está incluído no seu plano.' : null,
        ]);
    }
}
