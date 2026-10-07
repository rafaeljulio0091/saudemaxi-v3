<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\ManagerConsultationSearchRequest;
use App\Http\Requests\Healthcare\SearchConsultationAppointmentsRequest;
use App\Services\Healthcare\ConsultationAppointmentSearchService;
use App\Services\Healthcare\HealthcareDataService;
use Illuminate\Http\JsonResponse;

class ConsultationController extends Controller
{
    public function localSearch(
        SearchConsultationAppointmentsRequest $request,
        ConsultationAppointmentSearchService $service,
    ): JsonResponse {
        return response()->json(
            $service->search($request->user(), $request->validated()),
        );
    }

    public function search(
        ManagerConsultationSearchRequest $request,
        HealthcareDataService $service,
    ): JsonResponse {
        return response()->json(
            $service->searchConsultationsForManager($request->user(), $request->validated()),
        );
    }
}
