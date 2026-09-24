<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Concerns\SharesDashboardContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\ConsultationHistoryRequest;
use App\Services\Telemedicine\LsxMedicalConsultationClient;
use App\Services\Telemedicine\TelemedicineApiException;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationController extends Controller
{
    use SharesDashboardContext;

    public function __construct(private readonly LsxMedicalConsultationClient $client) {}

    public function index(ConsultationHistoryRequest $request): Response
    {
        $filters = $request->validated();
        $error = null;
        $consultations = ['count' => 0, 'results' => []];

        try {
            $consultations = $this->client->search($filters);
        } catch (TelemedicineApiException $e) {
            report($e);
            $error = $e->getMessage();
        }

        return Inertia::render('Manager/Consultations', [
            ...$this->dashboardContext($request->user()),
            'consultations' => $consultations,
            'filters' => [
                'cpf' => $filters['cpf'] ?? '',
                'status' => $filters['status'] ?? '',
                'doctor_cpf' => $filters['doctor_cpf'] ?? '',
                'start_date_min' => $filters['start_date_min'] ?? '',
                'start_date_max' => $filters['start_date_max'] ?? '',
                'page' => (int) ($filters['page'] ?? 1),
            ],
            'error' => $error,
        ]);
    }
}
