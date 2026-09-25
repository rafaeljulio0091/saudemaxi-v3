<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\ConsultationHistoryRequest;
use App\Models\User;
use App\Services\Healthcare\HealthcareDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HealthcareDataController extends Controller
{
    public function read(Request $request, HealthcareDataService $service, string $resource, ?string $id = null): JsonResponse
    {
        return response()->json($service->read($request->user(), $resource, $id));
    }

    public function execute(Request $request, HealthcareDataService $service, string $operation): JsonResponse
    {
        $input = $request->all();

        if ($operation === 'photo') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            ]);
            $input['photo_path'] = $request->file('file')->store(
                'prescriptions/'.$request->user()->id,
                'local',
            );
        }

        if ($operation === 'consultations-search') {
            // The CPF is never taken from the browser: HealthcareDataService
            // always uses the authenticated patient's own CPF.
            $input = $request->validate([
                'status' => ['nullable', Rule::in(ConsultationHistoryRequest::STATUSES)],
                'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            ]);
        }

        if ($operation === 'patient') {
            // Always the authenticated patient's own record: any "id" sent by
            // the browser is ignored. Same rules as ProfileUpdateRequest.
            $input = $request->validate([
                'nome' => ['required', 'string', 'max:255'],
                'email' => [
                    'required', 'string', 'lowercase', 'email', 'max:255',
                    Rule::unique(User::class)->ignore($request->user()->id),
                ],
                'telefone' => ['nullable', 'string', 'max:30'],
            ]);
        }

        return response()->json($service->execute($request->user(), $operation, $input));
    }
}
