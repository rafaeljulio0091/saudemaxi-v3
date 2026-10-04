<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\NearbyPharmaciesRequest;
use App\Services\Healthcare\NearbyPharmacyService;
use Illuminate\Http\JsonResponse;

class NearbyPharmacyController extends Controller
{
    public function __invoke(
        NearbyPharmaciesRequest $request,
        NearbyPharmacyService $service,
    ): JsonResponse {
        return response()->json($service->search($request->user(), $request->validated()));
    }
}
