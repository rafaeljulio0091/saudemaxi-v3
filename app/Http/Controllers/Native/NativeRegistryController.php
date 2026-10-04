<?php

namespace App\Http\Controllers\Native;

use App\Http\Controllers\Controller;
use App\Http\Requests\Native\StoreHealthProfessionalRequest;
use App\Http\Requests\Native\StoreHealthUnitRequest;
use App\Http\Requests\Native\StoreMunicipalityRequest;
use App\Http\Requests\Native\StoreOrganizationRequest;
use App\Http\Requests\Native\StorePharmacyRequest;
use App\Services\Native\NativeDirectoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class NativeRegistryController extends Controller
{
    public function __construct(private readonly NativeDirectoryService $registry) {}

    public function municipality(StoreMunicipalityRequest $request): JsonResponse
    {
        return $this->created($this->registry->createMunicipality($request->user(), $request->validated()));
    }

    public function organization(StoreOrganizationRequest $request): JsonResponse
    {
        return $this->created($this->registry->createOrganization($request->user(), $request->validated()));
    }

    public function healthUnit(StoreHealthUnitRequest $request): JsonResponse
    {
        return $this->created($this->registry->createHealthUnit($request->user(), $request->validated()));
    }

    public function pharmacy(StorePharmacyRequest $request): JsonResponse
    {
        return $this->created($this->registry->createPharmacy($request->user(), $request->validated()));
    }

    public function healthProfessional(StoreHealthProfessionalRequest $request): JsonResponse
    {
        return $this->created($this->registry->createHealthProfessional($request->user(), $request->validated()));
    }

    private function created(Model $model): JsonResponse
    {
        return response()->json([
            'data' => [
                'uuid' => $model->getAttribute('uuid'),
                'name' => $model->getAttribute('name') ?? $model->getAttribute('legal_name'),
            ],
        ], 201);
    }
}
