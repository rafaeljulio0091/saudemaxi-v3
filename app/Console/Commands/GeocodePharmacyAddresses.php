<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\AuditLog;
use App\Models\Pharmacy;
use App\Models\Tenant;
use App\Services\Geocoding\GeocodingException;
use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GeocodePharmacyAddresses extends Command
{
    protected $signature = 'healthcare:geocode-pharmacies
                            {tenant : Slug do tenant proprietário das farmácias}
                            {--limit=50 : Quantidade máxima de endereços por execução}
                            {--retry-failed : Tenta novamente endereços sem resultado anterior}';

    protected $description = 'Preenche coordenadas de endereços públicos de farmácias para o cálculo local de distância';

    public function handle(NominatimGeocoder $geocoder): int
    {
        $tenant = Tenant::query()->where('slug', $this->argument('tenant'))->first();

        if (! $tenant) {
            $this->error('Tenant não encontrado.');

            return self::FAILURE;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 500],
        ]);

        if ($limit === false) {
            $this->error('O limite deve estar entre 1 e 500.');

            return self::FAILURE;
        }

        try {
            $geocoder->ensureConfigured();
        } catch (GeocodingException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $addresses = Address::query()
            ->where('tenant_id', $tenant->id)
            ->where('addressable_type', (new Pharmacy)->getMorphClass())
            ->whereIn('addressable_id', Pharmacy::query()
                ->select('id')
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true))
            ->where(fn (Builder $query) => $query
                ->whereNull('latitude')
                ->orWhereNull('longitude'))
            ->when(! $this->option('retry-failed'), fn (Builder $query) => $query
                ->whereNull('geocoding_attempted_at'))
            ->with('addressable')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $updated = 0;
        $notFound = 0;

        foreach ($addresses as $index => $address) {
            try {
                $result = $geocoder->geocode($address);
            } catch (GeocodingException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $attemptedAt = now();

            if ($result === null) {
                $address->forceFill([
                    'geocoding_provider' => config('geocoding.provider'),
                    'geocoding_attempted_at' => $attemptedAt,
                    'geocoded_at' => null,
                ])->save();
                $notFound++;
            } else {
                DB::transaction(function () use ($address, $result, $attemptedAt): void {
                    $address->forceFill([
                        'latitude' => $result['latitude'],
                        'longitude' => $result['longitude'],
                        'geocoding_provider' => $result['provider'],
                        'geocoding_attempted_at' => $attemptedAt,
                        'geocoded_at' => $attemptedAt,
                    ])->save();

                    AuditLog::create([
                        'tenant_id' => $address->tenant_id,
                        'user_id' => null,
                        'action' => 'pharmacy.coordinates_geocoded',
                        'resource_type' => class_basename($address->addressable),
                        'resource_id' => $address->addressable_id,
                        'resource_uuid' => $address->addressable?->uuid,
                        'ip_address' => null,
                        'user_agent' => 'artisan:healthcare:geocode-pharmacies',
                    ]);
                });
                $updated++;
            }

            if ($index < $addresses->count() - 1) {
                usleep($this->requestIntervalMs() * 1000);
            }
        }

        $this->table(['Atualizadas', 'Sem resultado', 'Processadas'], [[
            $updated,
            $notFound,
            $addresses->count(),
        ]]);

        return self::SUCCESS;
    }

    private function requestIntervalMs(): int
    {
        $configured = max(0, (int) config('geocoding.request_interval_ms'));
        $usesPublicNominatim = config('geocoding.provider') === 'nominatim'
            && rtrim((string) config('geocoding.base_url'), '/') === 'https://nominatim.openstreetmap.org';

        return $usesPublicNominatim ? max(1000, $configured) : $configured;
    }
}
