<?php

namespace App\Services\Native;

use App\Enums\RecordStatus;
use App\Models\AuditLog;
use App\Models\Municipality;
use App\Models\Pharmacy;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GovernmentPharmacyImporter
{
    public function __construct(private readonly SensitiveIdentifier $identifiers) {}

    /**
     * @param  list<array{cnpj: string, name: string, street: string, district: string}>  $records
     * @return array{created: int, updated: int, unchanged: int}
     */
    public function import(
        Tenant $tenant,
        string $municipalityName,
        string $state,
        string $ibgeCode,
        array $records,
        array $municipalityAliases = [],
    ): array {
        return DB::transaction(function () use (
            $tenant,
            $municipalityName,
            $state,
            $ibgeCode,
            $records,
            $municipalityAliases,
        ): array {
            $municipality = $this->municipality(
                $tenant,
                $municipalityName,
                $state,
                $ibgeCode,
                $municipalityAliases,
            );
            $summary = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

            foreach ($records as $record) {
                $cnpj = $this->identifiers->digits($record['cnpj']);
                $hash = $this->identifiers->hash($cnpj);
                $pharmacy = Pharmacy::withTrashed()
                    ->where('tenant_id', $tenant->id)
                    ->where('cnpj_hash', $hash)
                    ->lockForUpdate()
                    ->first();
                $created = $pharmacy === null;
                $restored = $pharmacy?->trashed() ?? false;

                if ($created) {
                    $pharmacy = new Pharmacy;
                    $pharmacy->tenant_id = $tenant->id;
                    $pharmacy->is_public = false;
                }

                $this->setIfChanged($pharmacy, 'municipality_id', $municipality->id);
                $this->setIfChanged($pharmacy, 'name', $record['name']);
                $this->setIfChanged($pharmacy, 'cnpj', $cnpj);
                $this->setIfChanged($pharmacy, 'cnpj_hash', $hash);
                $this->setIfChanged($pharmacy, 'is_active', true);
                $this->setIfChanged($pharmacy, 'data_source', 'gov_pfpb');
                $pharmacyChanged = $pharmacy->isDirty();
                $pharmacy->save();

                if ($restored) {
                    $pharmacy->restore();
                }

                $addressChanged = $this->storeAddress(
                    $pharmacy,
                    $tenant,
                    $record,
                    $municipalityName,
                    $state,
                );
                $changed = $created || $restored || $pharmacyChanged || $addressChanged;

                if ($created) {
                    $summary['created']++;
                } elseif ($changed) {
                    $summary['updated']++;
                } else {
                    $summary['unchanged']++;
                }

                if ($changed) {
                    AuditLog::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => null,
                        'action' => $created ? 'pharmacy.imported' : 'pharmacy.import_refreshed',
                        'resource_type' => class_basename($pharmacy),
                        'resource_id' => $pharmacy->getKey(),
                        'resource_uuid' => $pharmacy->uuid,
                        'ip_address' => null,
                        'user_agent' => 'artisan:healthcare:import-government-pharmacies',
                    ]);
                }
            }

            return $summary;
        });
    }

    private function municipality(
        Tenant $tenant,
        string $name,
        string $state,
        string $ibgeCode,
        array $aliases,
    ): Municipality {
        $municipality = Municipality::withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where('ibge_code', $ibgeCode)
            ->lockForUpdate()
            ->first();

        if (! $municipality) {
            $municipality = Municipality::withTrashed()
                ->where('tenant_id', $tenant->id)
                ->where('state', $state)
                ->whereIn('name', [$name, ...$aliases])
                ->lockForUpdate()
                ->first();
        }

        if (! $municipality) {
            $municipality = new Municipality([
                'name' => $name,
                'state' => $state,
                'ibge_code' => $ibgeCode,
                'status' => RecordStatus::Active,
            ]);
            $municipality->tenant_id = $tenant->id;
            $municipality->save();

            return $municipality;
        }

        if ($municipality->trashed()) {
            $municipality->restore();
        }

        $this->setIfChanged($municipality, 'name', $name);
        $this->setIfChanged($municipality, 'state', $state);
        $this->setIfChanged($municipality, 'ibge_code', $ibgeCode);
        $municipality->save();

        return $municipality;
    }

    /**
     * @param  array{street: string, district: string}  $record
     */
    private function storeAddress(
        Pharmacy $pharmacy,
        Tenant $tenant,
        array $record,
        string $municipalityName,
        string $state,
    ): bool {
        $address = $pharmacy->addresses()
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $address) {
            $address = $pharmacy->addresses()->make();
            $address->tenant_id = $tenant->id;
        }

        $created = ! $address->exists;
        $this->setIfChanged($address, 'street', $record['street']);
        $this->setIfChanged($address, 'district', $record['district'] ?: null);
        $this->setIfChanged($address, 'city', $municipalityName);
        $this->setIfChanged($address, 'state', $state);
        $this->setIfChanged($address, 'country', 'BR');
        $changed = $address->isDirty();

        if ($changed) {
            $address->save();
        }

        return $created || $changed;
    }

    private function setIfChanged(Model $model, string $attribute, mixed $value): void
    {
        if ($model->getAttribute($attribute) !== $value) {
            $model->setAttribute($attribute, $value);
        }
    }
}
