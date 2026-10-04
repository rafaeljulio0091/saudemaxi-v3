<?php

namespace App\Services\Healthcare;

use App\Models\Address;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Support\Collection;

class NearbyPharmacyService
{
    private const MAX_RESULTS = 50;

    private const MAX_GEO_CANDIDATES = 500;

    public function __construct(private readonly TenantPlanService $plans) {}

    /**
     * The browser location is used only during this request. It is not
     * persisted, cached or included in application logs.
     *
     * @param  array{latitude: float|int|string, longitude: float|int|string, accuracy?: float|int|string|null}  $location
     * @return list<array<string, mixed>>
     */
    public function search(User $user, array $location): array
    {
        $user->loadMissing('tenant');
        abort_unless($user->isPatient() && $user->tenant, 403, 'Paciente sem vínculo com um cliente.');
        abort_unless(
            $this->plans->effectivePlan($user->tenant)['modules']['farmacia'] ?? false,
            403,
            'Este serviço não está incluído no seu plano.',
        );

        $latitude = (float) $location['latitude'];
        $longitude = (float) $location['longitude'];
        $pharmacyIds = $this->coordinateCandidates($user->tenant_id, $latitude, $longitude);

        $query = Pharmacy::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->with(['addresses' => fn ($query) => $query
                ->where('tenant_id', $user->tenant_id)
                ->orderBy('id')]);

        if ($pharmacyIds->isNotEmpty()) {
            $query->whereIn('id', $pharmacyIds);
        } else {
            $query->orderBy('name')->limit(self::MAX_RESULTS);
        }

        return $query->get()
            ->map(fn (Pharmacy $pharmacy): array => $this->present(
                $pharmacy,
                $latitude,
                $longitude,
            ))
            ->sort(function (array $left, array $right): int {
                if ($left['distance_km'] === null && $right['distance_km'] === null) {
                    return strcasecmp($left['name'], $right['name']);
                }

                if ($left['distance_km'] === null) {
                    return 1;
                }

                if ($right['distance_km'] === null) {
                    return -1;
                }

                return $left['distance_km'] <=> $right['distance_km'];
            })
            ->take(self::MAX_RESULTS)
            ->values()
            ->all();
    }

    /** @return Collection<int, int> */
    private function coordinateCandidates(int $tenantId, float $latitude, float $longitude): Collection
    {
        $latitudeDelta = 1.5;
        $longitudeDelta = 1.5 / max(abs(cos(deg2rad($latitude))), 0.2);

        return Address::query()
            ->where('tenant_id', $tenantId)
            ->where('addressable_type', (new Pharmacy)->getMorphClass())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
            ->limit(self::MAX_GEO_CANDIDATES)
            ->pluck('addressable_id')
            ->unique()
            ->values();
    }

    /** @return array<string, mixed> */
    private function present(Pharmacy $pharmacy, float $latitude, float $longitude): array
    {
        $address = $pharmacy->addresses->first();
        $distance = null;

        if ($address?->latitude !== null && $address->longitude !== null) {
            $distance = round($this->distance(
                $latitude,
                $longitude,
                (float) $address->latitude,
                (float) $address->longitude,
            ), 2);
        }

        $addressParts = array_values(array_filter([
            $address?->street,
            $address?->number,
            $address?->district,
            $address?->city,
            $address?->state,
        ], static fn (?string $part): bool => $part !== null && $part !== ''));

        return [
            'id' => $pharmacy->uuid,
            'name' => $pharmacy->name,
            'address' => implode(', ', $addressParts),
            'city' => $address?->city,
            'state' => $address?->state,
            'distance_km' => $distance,
            'source' => $pharmacy->data_source === 'gov_pfpb'
                ? 'Programa Farmácia Popular'
                : null,
        ];
    }

    private function distance(float $fromLat, float $fromLon, float $toLat, float $toLon): float
    {
        $earthRadiusKm = 6371.0088;
        $latDistance = deg2rad($toLat - $fromLat);
        $lonDistance = deg2rad($toLon - $fromLon);
        $a = sin($latDistance / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lonDistance / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
