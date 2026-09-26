<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Services\Healthcare\HealthcareContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManagerAreaController extends Controller
{
    public function page(Request $request, HealthcareContext $context): Response
    {
        return Inertia::render('Healthcare/'.$request->route('page'), [
            'healthcare' => $context->forManager($request->user()),
            'title' => $request->route('title'),
        ]);
    }
}
