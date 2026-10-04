<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\SchedulingStepRequest;
use App\Http\Requests\Healthcare\StoreConsultationAppointmentRequest;
use App\Services\Healthcare\ConsultationSchedulingService;
use App\Services\Telemedicine\TelemedicineApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationSchedulingController extends Controller
{
    public function specialties(Request $request, ConsultationSchedulingService $service): JsonResponse
    {
        return $this->respond(fn () => $service->specialties($request->user()));
    }

    public function days(SchedulingStepRequest $request, ConsultationSchedulingService $service): JsonResponse
    {
        return $this->respond(fn () => $service->days(
            $request->user(),
            $request->integer('specialty_id'),
        ));
    }

    public function times(SchedulingStepRequest $request, ConsultationSchedulingService $service): JsonResponse
    {
        return $this->respond(fn () => $service->times(
            $request->user(),
            $request->integer('specialty_id'),
            $request->string('date')->toString(),
        ));
    }

    public function doctors(SchedulingStepRequest $request, ConsultationSchedulingService $service): JsonResponse
    {
        return $this->respond(fn () => $service->doctors(
            $request->user(),
            $request->integer('specialty_id'),
            $request->string('date')->toString(),
            $request->string('time')->toString(),
        ));
    }

    public function store(
        StoreConsultationAppointmentRequest $request,
        ConsultationSchedulingService $service,
    ): JsonResponse {
        return $this->respond(fn () => $service->create($request->user(), $request->validated()), 201);
    }

    private function respond(callable $callback, int $successStatus = 200): JsonResponse
    {
        try {
            return response()->json($callback(), $successStatus);
        } catch (TelemedicineApiException $exception) {
            $status = match ($exception->reason) {
                'invalid', 'not_found' => 422,
                'business_rejection' => 409,
                default => 503,
            };

            return response()->json(['message' => $exception->getMessage()], $status);
        }
    }
}
