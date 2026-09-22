<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Http\Requests\Healthcare\SearchConsultationsRequest;
use App\Http\Requests\Healthcare\UpdateOwnPatientProfileRequest;
use App\Services\Healthcare\TenantHealthcareContext;
use App\Services\Telemedicine\TelemedicineClient;
use App\Services\Telemedicine\TelemedicineException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Real (non-demo) patient portal, backed by an authenticated tenant user
 * and the external telemedicine platform. See AGENTS.md sections 9 and 12.
 */
class PatientPortalController extends Controller
{
    public function home(Request $request): Response
    {
        $context = $this->context($request);
        $context->authorize('patient');

        return Inertia::render('Healthcare/Patient/Home', [
            'healthcare' => $context->props(),
            'title' => 'Início',
        ]);
    }

    public function immediate(Request $request): Response
    {
        $context = $this->context($request);
        $context->authorize('patient', 'atendimento');

        return Inertia::render('Healthcare/Patient/Immediate', [
            'healthcare' => $context->props(),
            'title' => 'Falar com um médico',
            'returned' => $request->query('status') ? [
                'status' => $request->query('status'),
                'consultationCode' => $request->query('consultation_code'),
            ] : null,
        ]);
    }

    public function consultations(Request $request): Response
    {
        $context = $this->context($request);
        $context->authorize('patient');

        return Inertia::render('Healthcare/Shared/Consultations', [
            'healthcare' => $context->props(),
            'title' => 'Minhas consultas',
        ]);
    }

    public function account(Request $request): Response
    {
        $context = $this->context($request);
        $context->authorize('patient');

        return Inertia::render('Healthcare/Patient/Account', [
            'healthcare' => $context->props(),
            'title' => 'Minha conta',
        ]);
    }

    public function notReady(): Response
    {
        return Inertia::render('Healthcare/NotReady');
    }

    public function apiAccount(Request $request): JsonResponse
    {
        $this->context($request)->authorize('patient');
        $user = $request->user();

        return response()->json([
            'nome' => $user->name,
            'email' => $user->email,
            'telefone' => $user->phone,
            'nascimento' => $user->birth_date?->toDateString(),
            'dependentes' => 0,
        ]);
    }

    public function apiUpdatePatient(UpdateOwnPatientProfileRequest $request, TelemedicineClient $client): JsonResponse
    {
        $this->context($request)->authorize('patient');
        $validated = $request->validated();
        $user = $request->user();
        $user->fill([
            'name' => $validated['nome'],
            'email' => $validated['email'] ?? $user->email,
            'phone' => $validated['telefone'] ?? null,
        ])->save();

        if ($user->cpf) {
            try {
                $client->updatePatient($user->cpf, [
                    'name' => $validated['nome'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['telefone'] ?? null,
                ]);
            } catch (TelemedicineException $e) {
                // The SaudeMaxi record is the source of truth for the account
                // page; a sync failure with the provider is not fatal here,
                // but must not be silently dropped (AGENTS.md section 17).
                Log::warning('healthcare.patient_sync_failed', ['status' => $e->status]);
            }
        }

        return response()->json([
            'nome' => $user->name,
            'email' => $user->email,
            'telefone' => $user->phone,
        ]);
    }

    public function apiConsultations(Request $request, TelemedicineClient $client): JsonResponse
    {
        $this->context($request)->authorize('patient');

        return response()->json($this->fetchConsultations($client));
    }

    public function apiConsultationsSearch(SearchConsultationsRequest $request, TelemedicineClient $client): JsonResponse
    {
        $this->context($request)->authorize('patient');
        $validated = $request->validated();

        $records = collect($this->fetchConsultations($client));
        $search = Str::lower(Str::ascii($validated['search'] ?? ''));
        $records = $records->filter(function ($record) use ($validated, $search) {
            $text = collect(['codigo', 'especialidade', 'medico'])->map(fn ($f) => $record[$f] ?? '')->implode(' ');

            return ($search === '' || Str::contains(Str::lower(Str::ascii($text)), $search))
                && (empty($validated['status']) || $record['status'] === $validated['status']);
        })->values();

        return response()->json([
            'count' => $records->count(),
            'results' => $records->slice(($validated['page'] - 1) * $validated['per_page'], $validated['per_page'])->values()->all(),
            'page' => $validated['page'],
            'per_page' => $validated['per_page'],
        ]);
    }

    public function apiEmergency(Request $request, TelemedicineClient $client): JsonResponse
    {
        $this->context($request)->authorize('patient', 'atendimento');
        $user = $request->user();
        abort_if(! $user->cpf, 422, 'Cadastre seu CPF em Minha conta antes de continuar.');

        try {
            $result = $client->createEmergencyConsultation($user->cpf, url('/atendimento'));
        } catch (TelemedicineException $e) {
            abort($e->status, $e->getMessage());
        }

        return response()->json([
            'message' => $result['message'] ?? 'Consulta de pronto atendimento criada.',
            'magic_link' => $result['magic_link'] ?? null,
            'consultation_code' => $result['consultation_code'] ?? null,
        ]);
    }

    private function context(Request $request): TenantHealthcareContext
    {
        return new TenantHealthcareContext($request->attributes->get('tenant'), $request->user());
    }

    private function fetchConsultations(TelemedicineClient $client): array
    {
        try {
            $raw = $client->consultationHistory();
        } catch (TelemedicineException $e) {
            abort($e->status, $e->getMessage());
        }
        $list = $raw['results'] ?? (array_is_list($raw) ? $raw : []);

        return array_map($this->normalizeConsultation(...), $list);
    }

    /**
     * Best-effort mapping onto the field names the Vue pages already use.
     * The exact shape of GET consultation-history/ is not confirmed against
     * the homologacao environment (see final report); both the documented
     * english field names and a defensive fallback are accepted.
     */
    private function normalizeConsultation(array $record): array
    {
        return [
            'codigo' => $record['consultation_code'] ?? $record['codigo'] ?? '',
            'status' => $record['status'] ?? 'PENDING',
            'especialidade' => $record['specialty'] ?? $record['especialidade'] ?? null,
            'medico' => $record['doctor'] ?? $record['medico'] ?? null,
            'agendadaPara' => $record['scheduled_for'] ?? $record['agendadaPara'] ?? null,
            'pago' => (bool) ($record['is_paid'] ?? $record['pago'] ?? false),
            'duracao' => $record['duration'] ?? null,
        ];
    }
}
