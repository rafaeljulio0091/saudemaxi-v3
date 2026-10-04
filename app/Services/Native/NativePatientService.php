<?php

namespace App\Services\Native;

use App\Enums\RecordStatus;
use App\Models\HealthUnit;
use App\Models\Municipality;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NativePatientService
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly SensitiveIdentifier $identifiers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count: int, results: array<int, array<string, mixed>>}
     */
    public function search(User $manager, array $filters): array
    {
        $tenant = $this->tenants->manager($manager);
        $query = Patient::query()
            ->where('tenant_id', $tenant->id)
            ->with('plan:id,name');

        $this->applySearch($query, $filters);

        $paginator = $query
            ->orderBy('name')
            ->paginate(perPage: min((int) ($filters['per_page'] ?? 10), 50), page: (int) ($filters['page'] ?? 1));

        return [
            'count' => $paginator->total(),
            'results' => collect($paginator->items())->map(fn (Patient $patient) => $this->summary($patient))->all(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(User $manager, array $data): Patient
    {
        $tenant = $this->tenants->manager($manager);
        $cpf = $this->identifiers->digits($data['cpf']);
        $cpfHash = $this->identifiers->hash($cpf);
        $cnsHash = $this->identifiers->hash($this->identifiers->digits($data['cns'] ?? null));

        if (Patient::withTrashed()->where('tenant_id', $tenant->id)->where('cpf_hash', $cpfHash)->exists()) {
            throw ValidationException::withMessages(['cpf' => 'Já existe um paciente com este CPF neste cliente.']);
        }

        if ($cnsHash && Patient::withTrashed()->where('tenant_id', $tenant->id)->where('cns_hash', $cnsHash)->exists()) {
            throw ValidationException::withMessages(['cns' => 'Já existe um paciente com este CNS neste cliente.']);
        }

        try {
            return DB::transaction(function () use ($manager, $tenant, $data, $cpf, $cpfHash, $cnsHash): Patient {
                $patient = new Patient($this->patientAttributes($data, $cpf));
                $patient->tenant_id = $tenant->id;
                $patient->cpf_hash = $cpfHash;
                $patient->cns_hash = $cnsHash;
                $patient->email_hash = $this->identifiers->hash($this->identifiers->email($data['email'] ?? null));
                $patient->municipality_id = $this->municipalityId($tenant->id, $data['municipality_uuid'] ?? null);
                $patient->holder_patient_id = $this->holderId($tenant->id, $data['holder_cpf'] ?? null);
                $patient->save();

                $this->storeAddress($patient, $tenant->id, $data['address'] ?? null);
                $this->syncHealthUnits($patient, $tenant->id, $data['health_unit_uuids'] ?? []);
                $this->audit->record($manager, 'patient.created', $patient);

                return $patient->load(['plan:id,name', 'addresses']);
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                $field = str_contains(strtolower($exception->getMessage()), 'cns_hash') ? 'cns' : 'cpf';

                throw ValidationException::withMessages([$field => "Já existe um paciente com este {$field} neste cliente."]);
            }

            throw $exception;
        }
    }

    public function findForManager(User $manager, string $uuid, bool $withTrashed = false): Patient
    {
        $tenant = $this->tenants->manager($manager);

        return Patient::query()
            ->when($withTrashed, fn (Builder $query) => $query->withTrashed())
            ->where('tenant_id', $tenant->id)
            ->where('uuid', $uuid)
            ->with(['plan:id,name', 'addresses'])
            ->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    public function update(User $manager, Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($manager, $patient, $data): Patient {
            foreach (['name', 'social_name', 'birth_date', 'sex'] as $field) {
                if (array_key_exists($field, $data)) {
                    $patient->{$field} = $data[$field];
                }
            }

            if (array_key_exists('email', $data)) {
                $patient->email = $this->identifiers->email($data['email']);
            }

            if (array_key_exists('phone', $data)) {
                $patient->phone = $this->identifiers->digits($data['phone']);
            }

            if (array_key_exists('status', $data)) {
                $patient->status = strtolower($data['status']);
            }

            if (array_key_exists('email', $data)) {
                $patient->email_hash = $this->identifiers->hash($this->identifiers->email($data['email']));
            }

            $patient->save();
            $this->audit->record($manager, 'patient.updated', $patient);

            return $patient->refresh()->load(['plan:id,name', 'addresses']);
        });
    }

    /** @return array<string, mixed> */
    public function detail(User $manager, Patient $patient): array
    {
        $this->audit->record($manager, 'patient.viewed_sensitive_data', $patient);

        return [
            ...$this->summary($patient),
            'social_name' => $patient->social_name,
            'birth_date' => $patient->birth_date?->format('Y-m-d'),
            'sex' => $patient->sex,
            'email' => $this->identifiers->maskEmail($patient->email),
            'phone' => $this->identifiers->maskPhone($patient->phone),
            'cns' => $patient->cns ? '***********'.substr((string) $patient->cns, -4) : null,
        ];
    }

    public function delete(User $manager, Patient $patient): void
    {
        DB::transaction(function () use ($manager, $patient): void {
            $patient->delete();
            $this->audit->record($manager, 'patient.deleted', $patient);
        });
    }

    public function restore(User $manager, Patient $patient): Patient
    {
        return DB::transaction(function () use ($manager, $patient): Patient {
            $patient->restore();
            $this->audit->record($manager, 'patient.restored', $patient);

            return $patient->refresh();
        });
    }

    /** @param array<string, mixed> $filters */
    private function applySearch(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', strtolower($filters['status']));
        }

        if (($filters['holder'] ?? null) === 'titular') {
            $query->whereNull('holder_patient_id');
        } elseif (($filters['holder'] ?? null) === 'dependente') {
            $query->whereNotNull('holder_patient_id');
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search === '') {
            return;
        }

        $digits = $this->identifiers->digits($search);
        if ($digits !== null && strlen($digits) === 11) {
            $query->where('cpf_hash', $this->identifiers->hash($digits));

            return;
        }

        if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
            $query->where('email_hash', $this->identifiers->hash($this->identifiers->email($search)));

            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('social_name', 'like', "%{$search}%");
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function patientAttributes(array $data, string $cpf): array
    {
        return [
            'name' => $data['name'],
            'social_name' => $data['social_name'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'sex' => $data['sex'] ?? null,
            'cpf' => $cpf,
            'cns' => $this->identifiers->digits($data['cns'] ?? null),
            'email' => $this->identifiers->email($data['email'] ?? null),
            'phone' => $this->identifiers->digits($data['phone'] ?? null),
            'status' => RecordStatus::Active,
        ];
    }

    private function municipalityId(int $tenantId, ?string $uuid): ?int
    {
        if (! $uuid) {
            return null;
        }

        return Municipality::where('tenant_id', $tenantId)->where('uuid', $uuid)->firstOrFail()->id;
    }

    private function holderId(int $tenantId, ?string $cpf): ?int
    {
        $digits = $this->identifiers->digits($cpf);
        if (! $digits) {
            return null;
        }

        return Patient::where('tenant_id', $tenantId)
            ->where('cpf_hash', $this->identifiers->hash($digits))
            ->firstOrFail()
            ->id;
    }

    /** @param array<string, mixed>|null $address */
    private function storeAddress(Patient $patient, int $tenantId, ?array $address): void
    {
        if (! $address || empty($address['street'])) {
            return;
        }

        $record = $patient->addresses()->make([
            'zip_code' => $this->identifiers->digits($address['zip_code'] ?? null),
            'street' => $address['street'],
            'number' => $address['number'] ?? null,
            'complement' => $address['complement'] ?? null,
            'district' => $address['district'] ?? $address['neighborhood'] ?? null,
            'city' => $address['city'],
            'state' => strtoupper($address['state']),
            'country' => 'BR',
        ]);
        $record->tenant_id = $tenantId;
        $record->save();
    }

    /** @param array<int, string> $uuids */
    private function syncHealthUnits(Patient $patient, int $tenantId, array $uuids): void
    {
        if ($uuids === []) {
            return;
        }

        $units = HealthUnit::where('tenant_id', $tenantId)->whereIn('uuid', $uuids)->get();
        abort_unless($units->count() === count(array_unique($uuids)), 404);

        $patient->healthUnits()->sync($units->mapWithKeys(fn (HealthUnit $unit) => [
            $unit->id => ['tenant_id' => $tenantId, 'status' => RecordStatus::Active->value],
        ])->all());
    }

    /** @return array<string, mixed> */
    private function summary(Patient $patient): array
    {
        return [
            'id' => $patient->uuid,
            'uuid' => $patient->uuid,
            'name' => $patient->name,
            'nome' => $patient->name,
            'cpf' => $this->identifiers->maskCpf($patient->cpf),
            'holder_cpf' => $patient->holder_patient_id ? 'masked' : null,
            'titular' => $patient->holder_patient_id === null,
            'status' => strtoupper($patient->status->value),
            'insurance_plan_code' => $patient->plan?->name,
            'plan_name' => $patient->plan?->name,
            'email' => $this->identifiers->maskEmail($patient->email),
            'phone' => $this->identifiers->maskPhone($patient->phone),
        ];
    }
}
