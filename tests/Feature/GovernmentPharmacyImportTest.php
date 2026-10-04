<?php

namespace Tests\Feature;

use App\Models\Municipality;
use App\Models\Tenant;
use App\Services\Native\GovernmentPharmacySpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GovernmentPharmacyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_spreadsheet_contains_the_expected_taubate_records(): void
    {
        $records = $this->app->make(GovernmentPharmacySpreadsheetReader::class)
            ->read(base_path('docs/09c1abc7-f600-4a36-8a94-170efe48c578.xlsx'));

        $this->assertCount(22, $records);
        $this->assertSame('13584019000174', $records[0]['cnpj']);
        $this->assertSame('APRIGIO & PEREIRA DROGARIA E PERFUMARIA LTDA - ME', $records[0]['name']);
        $this->assertSame('JARDIM MARIA AUGUSTA', $records[21]['district']);
    }

    public function test_command_imports_the_spreadsheet_for_one_tenant_without_plaintext_sensitive_fields(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'clinica-taubate']);
        $municipality = new Municipality([
            'name' => 'Taubate',
            'state' => 'SP',
            'ibge_code' => '3554102',
            'status' => 'active',
        ]);
        $municipality->tenant_id = $tenant->id;
        $municipality->save();

        $this->artisan('healthcare:import-taubate-pharmacies', ['tenant' => $tenant->slug])
            ->assertSuccessful();

        $this->assertDatabaseCount('pharmacies', 22);
        $this->assertDatabaseCount('addresses', 22);
        $this->assertDatabaseCount('audit_logs', 22);
        $this->assertDatabaseMissing('pharmacies', ['tenant_id' => null]);
        $this->assertDatabaseMissing('addresses', ['tenant_id' => null]);
        $this->assertDatabaseHas('pharmacies', [
            'tenant_id' => $tenant->id,
            'municipality_id' => $municipality->id,
            'data_source' => 'gov_pfpb',
            'is_active' => true,
        ]);

        $rawPharmacy = DB::table('pharmacies')->first();
        $rawAddress = DB::table('addresses')->first();
        $this->assertNotSame('13584019000174', $rawPharmacy->cnpj);
        $this->assertNotSame('ENGENHEIRO MILTON DE ALVARENGA PEIXOTO', $rawAddress->street);

        $this->artisan('healthcare:import-taubate-pharmacies', ['tenant' => $tenant->slug])
            ->assertSuccessful();

        $this->assertDatabaseCount('pharmacies', 22);
        $this->assertDatabaseCount('addresses', 22);
        $this->assertDatabaseCount('audit_logs', 22);
    }

    public function test_command_rejects_an_unknown_tenant_without_importing_data(): void
    {
        $this->artisan('healthcare:import-taubate-pharmacies', ['tenant' => 'inexistente'])
            ->assertFailed();

        $this->assertDatabaseCount('pharmacies', 0);
    }
}
