<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Services\Healthcare\HealthcareDataService;
use App\Services\Healthcare\PatientContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthcareDataController extends Controller
{
    public function __construct(private PatientContext $context) {}

    public function read(Request $request, HealthcareDataService $service, string $resource, ?string $id = null): JsonResponse
    {
        return response()->json($service->read($resource, $id));
    }

    public function execute(Request $request, HealthcareDataService $service, string $operation): JsonResponse
    {
        $input = $request->all();

        if ($operation === 'photo') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            ]);
            $input['photo_path'] = $request->file('file')->store(
                'prescriptions/'.$this->context->current()->id,
                'local',
            );
        }

        return response()->json($service->execute($operation, $input));
    }
}
