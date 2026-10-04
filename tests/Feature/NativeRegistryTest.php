<?php

namespace Tests\Feature;

use App\Models\HealthProfessional;
use App\Models\HealthUnit;
use App\Models\Municipality;
use App\Models\Organization;
use App\Models\Pharmacy;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NativeRegistryTest extends TestCase
{
    use RefreshDatabase;

    private function manager(?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::factory()->create();

        return User::factory()->manager()->create(['tenant_id' => $tenant->id]);
    }

    public function test_manager_can_create_native_directory_with_tenant_scoped_relationships(): void
    {
        $manager = $this->manager();

        $municipalityUuid = $this->actingAs($manager)->postJson('/gestor/cadastros/municipios', [
            'name' => 'São Paulo',
            'state' => 'sp',
            'ibge_code' => '3550308',
        ])->assertCreated()->json('data.uuid');

        $organizationUuid = $this->actingAs($manager)->postJson('/gestor/cadastros/organizacoes', [
            'type' => 'clinic',
            'legal_name' => 'Clínica Saúde Ltda.',
            'trade_name' => 'Clínica Saúde',
            'cnpj' => '11.222.333/0001-81',
            'municipality_uuid' => $municipalityUuid,
            'address' => [
                'street' => 'Rua Um',
                'number' => '10',
                'district' => 'Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'zip_code' => '01001-000',
            ],
        ])->assertCreated()->json('data.uuid');

        $unitUuid = $this->actingAs($manager)->postJson('/gestor/cadastros/unidades-saude', [
            'organization_uuid' => $organizationUuid,
            'municipality_uuid' => $municipalityUuid,
            'name' => 'Unidade Centro',
            'code' => 'UC-01',
            'type' => 'clinic',
            'phone' => '1130000000',
            'email' => 'unidade@example.com',
        ])->assertCreated()->json('data.uuid');

        $this->actingAs($manager)->postJson('/gestor/cadastros/farmacias', [
            'municipality_uuid' => $municipalityUuid,
            'name' => 'Farmácia Municipal',
            'corporate_name' => 'Farmácia Municipal de São Paulo',
            'cnpj' => '11.222.333/0001-81',
            'is_public' => true,
        ])->assertCreated();

        $this->actingAs($manager)->postJson('/gestor/cadastros/profissionais-saude', [
            'name' => 'Dra. Ana Silva',
            'professional_type' => 'physician',
            'registration_number' => '123456',
            'registration_state' => 'sp',
            'registration_authority' => 'crm',
            'specialty' => 'Clínica médica',
            'health_unit_uuids' => [$unitUuid],
        ])->assertCreated();

        $this->assertSame($manager->tenant_id, Municipality::sole()->tenant_id);
        $this->assertSame($manager->tenant_id, Organization::sole()->tenant_id);
        $this->assertSame($manager->tenant_id, HealthUnit::sole()->tenant_id);
        $this->assertSame($manager->tenant_id, Pharmacy::sole()->tenant_id);
        $this->assertSame($manager->tenant_id, HealthProfessional::sole()->tenant_id);
        $this->assertTrue(HealthProfessional::sole()->healthUnits->contains('uuid', $unitUuid));
        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('audit_logs', 5);
    }

    public function test_non_manager_cannot_create_native_directory_records(): void
    {
        $patient = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);

        $this->actingAs($patient)->postJson('/gestor/cadastros/municipios', [
            'name' => 'São Paulo',
            'state' => 'SP',
        ])->assertForbidden();
    }

    public function test_relationship_ids_from_another_tenant_are_not_accepted(): void
    {
        $manager = $this->manager();
        $otherManager = $this->manager();
        $foreignMunicipality = $this->actingAs($otherManager)->postJson('/gestor/cadastros/municipios', [
            'name' => 'Curitiba',
            'state' => 'PR',
        ])->json('data.uuid');

        $this->actingAs($manager)->postJson('/gestor/cadastros/organizacoes', [
            'type' => 'clinic',
            'legal_name' => 'Tentativa cruzada',
            'municipality_uuid' => $foreignMunicipality,
        ])->assertNotFound();

        $this->assertDatabaseMissing('organizations', ['tenant_id' => $manager->tenant_id]);
    }

    public function test_tenant_and_external_source_fields_are_prohibited(): void
    {
        $manager = $this->manager();
        $otherTenant = Tenant::factory()->create();

        $this->actingAs($manager)->postJson('/gestor/cadastros/farmacias', [
            'name' => 'Invasora',
            'is_public' => false,
            'tenant_id' => $otherTenant->id,
            'data_source' => 'lsx',
            'external_provider' => 'lsx',
            'external_id' => '123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id', 'data_source', 'external_provider', 'external_id']);

        $this->assertDatabaseCount('pharmacies', 0);
    }

    public function test_cnpj_and_address_are_not_stored_in_plain_text(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->postJson('/gestor/cadastros/organizacoes', [
            'type' => 'clinic',
            'legal_name' => 'Clínica Protegida',
            'cnpj' => '11.222.333/0001-81',
            'address' => [
                'street' => 'Rua Sensível',
                'city' => 'São Paulo',
                'state' => 'SP',
            ],
        ])->assertCreated();

        $rawOrganization = DB::table('organizations')->first();
        $rawAddress = DB::table('addresses')->first();
        $this->assertNotSame('11222333000181', $rawOrganization->cnpj);
        $this->assertNotSame('Rua Sensível', $rawAddress->street);
    }
}
