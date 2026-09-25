<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Services\Healthcare\PatientContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientAreaController extends Controller
{
    public function __construct(private PatientContext $context) {}

    public function page(Request $request): Response
    {
        $page = $request->route('page');
        $title = $request->route('title');
        $module = $request->route('module');

        if (! $this->context->hasPatient()) {
            return Inertia::render('Healthcare/PatientNotConfigured', [
                'reason' => 'Sua conta ainda não está vinculada a um perfil de paciente. Fale com o administrador da sua unidade.',
            ]);
        }

        $blocked = $module && ! $this->context->moduleEnabled($module);

        return Inertia::render('Healthcare/'.($blocked ? 'Unavailable' : $page), [
            'healthcare' => $this->context->props(),
            'title' => $title,
            'reason' => $blocked ? 'Este serviço não está incluído no seu plano.' : null,
        ]);
    }
}
