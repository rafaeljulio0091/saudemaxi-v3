<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Concerns\SharesDashboardContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\PatientIndexRequest;
use App\Http\Requests\Healthcare\StorePatientRequest;
use App\Services\Telemedicine\LsxMedicalPatientClient;
use App\Services\Telemedicine\TelemedicineApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    use SharesDashboardContext;

    public function __construct(private readonly LsxMedicalPatientClient $client) {}

    public function index(PatientIndexRequest $request): Response
    {
        $filters = $request->validated();

        // The "search", "status" and "is_dependent" query parameter names are a
        // best-effort guess (see LsxMedicalPatientClient docblock): the real
        // contract of GET /api/clinic/filter-patients/ could not be verified
        // against a homologation token from this environment.
        $query = array_filter([
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
            'is_dependent' => match ($filters['holder'] ?? null) {
                'titular' => false,
                'dependente' => true,
                default => null,
            },
            'page' => $filters['page'] ?? 1,
            'page_size' => 10,
        ], fn ($value) => $value !== null);

        $error = null;
        $patients = ['count' => 0, 'results' => []];

        try {
            $result = $this->client->search($query);
            $patients = [
                'count' => $result['count'] ?? 0,
                'results' => $result['results'] ?? [],
            ];
        } catch (TelemedicineApiException $e) {
            report($e);
            $error = $e->getMessage();
        }

        return Inertia::render('Manager/Patients', [
            ...$this->dashboardContext($request->user()),
            'patients' => $patients,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'holder' => $filters['holder'] ?? '',
                'page' => (int) ($filters['page'] ?? 1),
            ],
            'error' => $error,
            'status' => session('status'),
        ]);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        try {
            $this->client->create($request->validated());
        } catch (TelemedicineApiException $e) {
            report($e);

            return back()
                ->withErrors(! empty($e->errors) ? $e->errors : ['name' => $e->getMessage()])
                ->withInput();
        }

        return Redirect::route('healthcare.manager.patients')
            ->with('status', 'Paciente cadastrado com sucesso.');
    }
}
