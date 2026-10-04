<?php

namespace App\Services\Native;

use App\Enums\RecordStatus;
use App\Models\HealthProfessional;
use App\Models\HealthUnit;
use App\Models\Municipality;
use App\Models\Organization;
use App\Models\Pharmacy;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NativeDirectoryService
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly SensitiveIdentifier $identifiers,
        private readonly AuditLogger $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function createMunicipality(User $manager, array $data): Municipality
    {
        $tenant = $this->tenants->manager($manager);

        return $this->translateDuplicate('ibge_code', function () use ($manager, $tenant, $data): Municipality {
            return DB::transaction(function () use ($manager, $tenant, $data): Municipality {
                $municipality = new Municipality([
                    'name' => $data['name'],
                    'state' => strtoupper($data['state']),
                    'ibge_code' => $data['ibge_code'] ?? null,
                    'status' => RecordStatus::Active,
                ]);
                $municipality->tenant_id = $tenant->id;
                $municipality->save();
                $this->audit->record($manager, 'municipality.created', $municipality);

                return $municipality;
            });
        });
    }

    /** @param array<string, mixed> $data */
    public function createOrganization(User $manager, array $data): Organization
    {
        $tenant = $this->tenants->manager($manager);
        $cnpj = $this->identifiers->digits($data['cnpj'] ?? null);

        return $this->translateDuplicate('cnpj', function () use ($manager, $tenant, $data, $cnpj): Organization {
            return DB::transaction(function () use ($manager, $tenant, $data, $cnpj): Organization {
                $organization = new Organization([
                    'type' => $data['type'],
                    'legal_name' => $data['legal_name'],
                    'trade_name' => $data['trade_name'] ?? null,
                    'cnpj' => $cnpj,
                    'status' => RecordStatus::Active,
                ]);
                $organization->tenant_id = $tenant->id;
                $organization->municipality_id = $this->municipality($tenant, $data['municipality_uuid'] ?? null)?->id;
                $organization->cnpj_hash = $this->identifiers->hash($cnpj);
                $organization->save();
                $this->storeAddress($organization, $tenant->id, $data['address'] ?? null);
                $this->audit->record($manager, 'organization.created', $organization);

                return $organization->load('addresses');
            });
        });
    }

    /** @param array<string, mixed> $data */
    public function createHealthUnit(User $manager, array $data): HealthUnit
    {
        $tenant = $this->tenants->manager($manager);

        return $this->translateDuplicate('code', function () use ($manager, $tenant, $data): HealthUnit {
            return DB::transaction(function () use ($manager, $tenant, $data): HealthUnit {
                $organization = Organization::where('tenant_id', $tenant->id)
                    ->where('uuid', $data['organization_uuid'])
                    ->firstOrFail();
                $unit = new HealthUnit([
                    'name' => $data['name'],
                    'code' => $data['code'] ?? null,
                    'type' => $data['type'],
                    'phone' => $this->identifiers->digits($data['phone'] ?? null),
                    'email' => $this->identifiers->email($data['email'] ?? null),
                    'status' => RecordStatus::Active,
                ]);
                $unit->tenant_id = $tenant->id;
                $unit->organization_id = $organization->id;
                $unit->municipality_id = $this->municipality($tenant, $data['municipality_uuid'] ?? null)?->id;
                $unit->save();
                $this->storeAddress($unit, $tenant->id, $data['address'] ?? null);
                $this->audit->record($manager, 'health_unit.created', $unit);

                return $unit->load(['organization:id,uuid,legal_name', 'addresses']);
            });
        });
    }

    /** @param array<string, mixed> $data */
    public function createPharmacy(User $manager, array $data): Pharmacy
    {
        $tenant = $this->tenants->manager($manager);
        $cnpj = $this->identifiers->digits($data['cnpj'] ?? null);

        return $this->translateDuplicate('cnpj', function () use ($manager, $tenant, $data, $cnpj): Pharmacy {
            return DB::transaction(function () use ($manager, $tenant, $data, $cnpj): Pharmacy {
                $pharmacy = new Pharmacy([
                    'name' => $data['name'],
                    'corporate_name' => $data['corporate_name'] ?? null,
                    'cnpj' => $cnpj,
                    'phone' => $this->identifiers->digits($data['phone'] ?? null),
                    'email' => $this->identifiers->email($data['email'] ?? null),
                    'is_public' => (bool) $data['is_public'],
                    'is_active' => true,
                    'data_source' => 'manual',
                ]);
                $pharmacy->tenant_id = $tenant->id;
                $pharmacy->municipality_id = $this->municipality($tenant, $data['municipality_uuid'] ?? null)?->id;
                $pharmacy->cnpj_hash = $this->identifiers->hash($cnpj);
                $pharmacy->save();
                $this->storeAddress($pharmacy, $tenant->id, $data['address'] ?? null);
                $this->audit->record($manager, 'pharmacy.created', $pharmacy);

                return $pharmacy->load('addresses');
            });
        });
    }

    /** @param array<string, mixed> $data */
    public function createHealthProfessional(User $manager, array $data): HealthProfessional
    {
        $tenant = $this->tenants->manager($manager);

        return $this->translateDuplicate('registration_number', function () use ($manager, $tenant, $data): HealthProfessional {
            return DB::transaction(function () use ($manager, $tenant, $data): HealthProfessional {
                $professional = new HealthProfessional([
                    'name' => $data['name'],
                    'professional_type' => $data['professional_type'],
                    'registration_number' => $data['registration_number'],
                    'registration_state' => isset($data['registration_state']) ? strtoupper($data['registration_state']) : null,
                    'registration_authority' => strtoupper($data['registration_authority']),
                    'specialty' => $data['specialty'] ?? null,
                    'status' => RecordStatus::Active,
                ]);
                $professional->tenant_id = $tenant->id;
                $professional->user_id = $this->user($tenant, $data['user_uuid'] ?? null)?->id;
                $professional->save();
                $this->storeAddress($professional, $tenant->id, $data['address'] ?? null);
                $this->syncUnits($professional, $tenant, $data['health_unit_uuids'] ?? []);
                $this->audit->record($manager, 'health_professional.created', $professional);

                return $professional->load(['healthUnits:id,uuid,name', 'addresses']);
            });
        });
    }

    private function municipality(Tenant $tenant, ?string $uuid): ?Municipality
    {
        if (! $uuid) {
            return null;
        }

        return Municipality::where('tenant_id', $tenant->id)->where('uuid', $uuid)->firstOrFail();
    }

    private function user(Tenant $tenant, ?string $uuid): ?User
    {
        if (! $uuid) {
            return null;
        }

        return User::where('tenant_id', $tenant->id)->where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<int, string> $uuids */
    private function syncUnits(HealthProfessional $professional, Tenant $tenant, array $uuids): void
    {
        if ($uuids === []) {
            return;
        }

        $units = HealthUnit::where('tenant_id', $tenant->id)->whereIn('uuid', $uuids)->get();
        abort_unless($units->count() === count(array_unique($uuids)), 404);
        $professional->healthUnits()->sync($units->mapWithKeys(fn (HealthUnit $unit) => [
            $unit->id => ['tenant_id' => $tenant->id, 'status' => RecordStatus::Active->value],
        ])->all());
    }

    /** @param array<string, mixed>|null $address */
    private function storeAddress(Model $resource, int $tenantId, ?array $address): void
    {
        if (! $address || empty($address['street'])) {
            return;
        }

        $record = $resource->addresses()->make([
            'zip_code' => $this->identifiers->digits($address['zip_code'] ?? null),
            'street' => $address['street'],
            'number' => $address['number'] ?? null,
            'complement' => $address['complement'] ?? null,
            'district' => $address['district'] ?? null,
            'city' => $address['city'],
            'state' => strtoupper($address['state']),
            'country' => 'BR',
        ]);
        $record->tenant_id = $tenantId;
        $record->save();
    }

    private function translateDuplicate(string $field, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([$field => 'Já existe um cadastro com este identificador neste cliente.']);
            }

            throw $exception;
        }
    }
}
