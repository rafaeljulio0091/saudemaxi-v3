<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\DemoOperationRequest;
use App\Http\Requests\Healthcare\DemoScenarioRequest;
use App\Services\Healthcare\DemoContext;
use App\Services\Healthcare\DemoHealthcareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DemoController extends Controller
{
    public function entry(DemoContext $context): Response
    {
        $data = $context->data();

        return Inertia::render('Healthcare/Demo', ['scenarios' => array_values($data['CLIENTES']), 'plans' => $data['PLANOS']]);
    }

    public function select(DemoScenarioRequest $request, DemoContext $context): RedirectResponse
    {
        $context->select($request->validated());

        return redirect('/demonstracao/'.($request->validated('profile') === 'manager' ? 'gestor/painel' : 'inicio'));
    }

    public function page(Request $request, DemoContext $context): Response|RedirectResponse
    {
        if (! $request->session()->has('healthcare_demo.context')) {
            return redirect('/demonstracao');
        }
        $context->authorize($request->route('profile'));
        $props = $context->props();
        $module = $request->route('module');
        $blocked = $module && ! ($props['modules'][$module] ?? false);
        $page = $blocked ? 'Unavailable' : $request->route('page');

        return Inertia::render('Healthcare/'.$page, [
            'healthcare' => $props, 'recordId' => $request->route('id'),
            'title' => $request->route('title'), 'reason' => $blocked ? 'Este serviço não está incluído no seu plano.' : null,
        ]);
    }

    public function read(DemoContext $context, DemoHealthcareService $service, string $resource, ?string $id = null): JsonResponse
    {
        $result = $service->read($resource, $id);
        abort_if($context->current()['network'] === 'error', 503, 'Não foi possível carregar os dados de demonstração.');

        return response()->json($context->current()['network'] === 'empty' && is_array($result) && array_is_list($result) ? [] : $result);
    }

    public function execute(DemoOperationRequest $request, DemoContext $context, DemoHealthcareService $service, string $operation): JsonResponse
    {
        abort_if($context->current()['network'] === 'error', 503, 'O serviço de demonstração está indisponível.');
        $result = $service->execute($operation, $request->validated());

        return response()->json($context->current()['network'] === 'empty' && in_array($operation, ['days', 'times', 'doctors'], true) ? [] : $result);
    }
}
