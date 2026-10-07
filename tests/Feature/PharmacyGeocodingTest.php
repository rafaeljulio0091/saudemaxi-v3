<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Pharmacy;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PharmacyGeocodingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'geocoding.enabled' => true,
            'geocoding.provider' => 'nominatim',
            'geocoding.base_url' => 'https://geocoder.test',
            'geocoding.user_agent' => 'SaudeMaxiTest/1.0',
            'geocoding.contact_email' => 'operacao@example.com',
            'geocoding.request_interval_ms' => 0,
        ]);
    }

    public function test_command_geocodes_only_the_selected_tenants_pharmacies_and_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'tenant-selecionado']);
        $address = $this->pharmacyAddress($tenant, 'Farmácia Local');
        $otherTenant = Tenant::factory()->create(['slug' => 'outro-tenant']);
        $otherAddress = $this->pharmacyAddress($otherTenant, 'Farmácia Externa');

        Http::fake([
            'https://geocoder.test/search*' => Http::response([[
                'lat' => '-23.0264000',
                'lon' => '-45.5553000',
            ]]),
        ]);

        $this->artisan('healthcare:geocode-pharmacies', ['tenant' => $tenant->slug])
            ->assertSuccessful();
        $this->artisan('healthcare:geocode-pharmacies', ['tenant' => $tenant->slug])
            ->assertSuccessful();

        $address->refresh();
        $this->assertSame('-23.0264000', $address->latitude);
        $this->assertSame('-45.5553000', $address->longitude);
        $this->assertSame('nominatim', $address->geocoding_provider);
        $this->assertNotNull($address->geocoding_attempted_at);
        $this->assertNotNull($address->geocoded_at);
        $this->assertNull($otherAddress->fresh()->latitude);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'pharmacy.coordinates_geocoded',
            'resource_id' => $address->addressable_id,
        ]);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return $request->url() !== ''
                && $query['city'] === 'Taubaté'
                && $query['state'] === 'SP'
                && $query['countrycodes'] === 'br'
                && ! array_key_exists('latitude', $query)
                && ! array_key_exists('longitude', $query)
                && $request->hasHeader('User-Agent', 'SaudeMaxiTest/1.0');
        });
    }

    public function test_empty_result_is_recorded_and_not_repeated_without_retry_option(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'sem-resultado']);
        $address = $this->pharmacyAddress($tenant, 'Farmácia sem coordenada');
        Http::fake(['https://geocoder.test/search*' => Http::response([])]);

        $this->artisan('healthcare:geocode-pharmacies', ['tenant' => $tenant->slug])
            ->assertSuccessful();
        $this->artisan('healthcare:geocode-pharmacies', ['tenant' => $tenant->slug])
            ->assertSuccessful();

        $address->refresh();
        $this->assertNull($address->latitude);
        $this->assertNull($address->longitude);
        $this->assertNotNull($address->geocoding_attempted_at);
        $this->assertNull($address->geocoded_at);
        Http::assertSentCount(1);
    }

    public function test_provider_failure_does_not_persist_false_coordinates(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'provedor-indisponivel']);
        $address = $this->pharmacyAddress($tenant, 'Farmácia pendente');
        Http::fake(['https://geocoder.test/search*' => Http::response([], 503)]);

        $this->artisan('healthcare:geocode-pharmacies', ['tenant' => $tenant->slug])
            ->assertFailed();

        $address->refresh();
        $this->assertNull($address->latitude);
        $this->assertNull($address->longitude);
        $this->assertNull($address->geocoding_attempted_at);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function pharmacyAddress(Tenant $tenant, string $name): Address
    {
        $pharmacy = new Pharmacy([
            'name' => $name,
            'is_public' => false,
            'is_active' => true,
            'data_source' => 'test',
        ]);
        $pharmacy->tenant_id = $tenant->id;
        $pharmacy->save();

        $address = $pharmacy->addresses()->make([
            'street' => 'Rua das Flores',
            'district' => 'Centro',
            'city' => 'Taubaté',
            'state' => 'SP',
            'country' => 'BR',
        ]);
        $address->tenant_id = $tenant->id;
        $address->save();

        return $address;
    }
}
