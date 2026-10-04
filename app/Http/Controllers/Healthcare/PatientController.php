<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\PatientIndexRequest;
use App\Http\Requests\Healthcare\StorePatientRequest;
use App\Http\Requests\Healthcare\UpdatePatientFromPayloadRequest;
use App\Http\Requests\Healthcare\UpdatePatientRequest;
use App\Services\Native\NativePatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class PatientController extends Controller
{
    public function __construct(private readonly NativePatientService $patients) {}

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $this->patients->create($request->user(), $request->validated());

        return Redirect::route('healthcare.manager.patients')
            ->with('status', 'Paciente cadastrado com sucesso.');
    }

    public function storeJson(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patients->create($request->user(), $request->validated());

        return response()->json(['data' => ['uuid' => $patient->uuid]], 201);
    }

    public function search(PatientIndexRequest $request): JsonResponse
    {
        return response()->json($this->patients->search($request->user(), $request->validated()));
    }

    public function show(Request $request, string $nativePatient): JsonResponse
    {
        $patient = $this->patients->findForManager($request->user(), $nativePatient);
        Gate::authorize('view', $patient);

        return response()->json(['data' => $this->patients->detail($request->user(), $patient)]);
    }

    public function update(UpdatePatientRequest $request, string $nativePatient): JsonResponse
    {
        $patient = $this->patients->findForManager($request->user(), $nativePatient);
        Gate::authorize('update', $patient);
        $updated = $this->patients->update($request->user(), $patient, $request->validated());

        return response()->json(['data' => $this->patients->detail($request->user(), $updated)]);
    }

    public function updateFromPayload(UpdatePatientFromPayloadRequest $request): JsonResponse
    {
        $patient = $this->patients->findForManager($request->user(), $request->validated('id'));
        Gate::authorize('update', $patient);
        $updated = $this->patients->update($request->user(), $patient, $request->safe()->except('id'));

        return response()->json(['data' => $this->patients->detail($request->user(), $updated)]);
    }

    public function destroy(Request $request, string $nativePatient): JsonResponse
    {
        $patient = $this->patients->findForManager($request->user(), $nativePatient);
        Gate::authorize('delete', $patient);
        $this->patients->delete($request->user(), $patient);

        return response()->json(status: 204);
    }

    public function restore(Request $request, string $nativePatient): JsonResponse
    {
        $patient = $this->patients->findForManager($request->user(), $nativePatient, withTrashed: true);
        Gate::authorize('restore', $patient);

        return response()->json(['data' => ['uuid' => $this->patients->restore($request->user(), $patient)->uuid]]);
    }
}
