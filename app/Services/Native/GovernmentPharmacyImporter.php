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
    public function importTaubate(Tenant $tenant, array $records): array
    {
        return DB::transaction(function () use ($tenant, $records): array {
            $municipality = $this->taubate($tenant);
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

                $addressChanged = $this->storeAddress($pharmacy, $tenant, $record);
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
                        'user_agent' => 'artisan:healthcare:import-taubate-pharmacies',
                    ]);
                }
            }

            return $summary;
        });
    }

    private function taubate(Tenant $tenant): Municipality
    {
        $municipality = Municipality::withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where('ibge_code', '3554102')
            ->lockForUpdate()
            ->first();

        if (! $municipality) {
            $municipality = Municipality::withTrashed()
                ->where('tenant_id', $tenant->id)
                ->where('state', 'SP')
                ->whereIn('name', ['Taubaté', 'Taubate'])
                ->lockForUpdate()
                ->first();
        }

        if (! $municipality) {
            $municipality = new Municipality([
                'name' => 'Taubaté',
                'state' => 'SP',
                'ibge_code' => '3554102',
                'status' => RecordStatus::Active,
            ]);
            $municipality->tenant_id = $tenant->id;
            $municipality->save();

            return $municipality;
        }

        if ($municipality->trashed()) {
            $municipality->restore();
        }

        $this->setIfChanged($municipality, 'ibge_code', '3554102');
        $municipality->save();

        return $municipality;
    }

    /**
     * @param  array{street: string, district: string}  $record
     */
    private function storeAddress(Pharmacy $pharmacy, Tenant $tenant, array $record): bool
    {
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
        $this->setIfChanged($address, 'city', 'Taubaté');
        $this->setIfChanged($address, 'state', 'SP');
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
