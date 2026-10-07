<?php

namespace App\Services\Geocoding;

use App\Models\Address;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class NominatimGeocoder
{
    /**
     * @return array{latitude: float, longitude: float, provider: string}|null
     *
     * @throws GeocodingException
     */
    public function geocode(Address $address): ?array
    {
        $this->ensureConfigured();

        try {
            $response = Http::baseUrl((string) config('geocoding.base_url'))
                ->withUserAgent((string) config('geocoding.user_agent'))
                ->withHeaders(['Accept-Language' => 'pt-BR'])
                ->acceptJson()
                ->connectTimeout(config('geocoding.connect_timeout'))
                ->timeout(config('geocoding.timeout'))
                ->get('/search', [
                    'street' => trim(implode(' ', array_filter([
                        $address->number,
                        $address->street,
                    ]))),
                    'city' => $address->city,
                    'state' => $address->state,
                    'country' => 'Brasil',
                    'countrycodes' => 'br',
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'addressdetails' => 0,
                    'email' => config('geocoding.contact_email'),
                ]);
        } catch (ConnectionException) {
            throw new GeocodingException('Não foi possível conectar ao serviço de geocodificação.');
        } catch (Throwable) {
            throw new GeocodingException('Não foi possível consultar o serviço de geocodificação.');
        }

        if (! $response->successful()) {
            throw new GeocodingException('O serviço de geocodificação recusou a consulta.');
        }

        $result = $response->json('0');

        if ($result === null) {
            return null;
        }

        if (! is_array($result) || ! is_numeric($result['lat'] ?? null) || ! is_numeric($result['lon'] ?? null)) {
            throw new GeocodingException('O serviço de geocodificação retornou dados inválidos.');
        }

        $latitude = (float) $result['lat'];
        $longitude = (float) $result['lon'];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new GeocodingException('O serviço de geocodificação retornou coordenadas inválidas.');
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'provider' => (string) config('geocoding.provider'),
        ];
    }

    /** @throws GeocodingException */
    public function ensureConfigured(): void
    {
        if (! config('geocoding.enabled')) {
            throw new GeocodingException('A geocodificação está desabilitada.');
        }

        if (
            blank(config('geocoding.base_url'))
            || blank(config('geocoding.user_agent'))
            || blank(config('geocoding.contact_email'))
        ) {
            throw new GeocodingException('Configure a URL, a identificação e o contato do serviço de geocodificação.');
        }
    }
}
