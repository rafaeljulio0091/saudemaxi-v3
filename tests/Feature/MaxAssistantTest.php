<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MaxAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function patient(array $attributes = []): User
    {
        return User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, ...$attributes]);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create(['tenant_id' => Tenant::factory()->create()->id]);
    }

    private function enableAi(): void
    {
        config([
            'ai.max.enabled' => true,
            'ai.max.openai_model' => 'gpt-test',
            'ai.openai.api_key' => 'openai-key',
            'ai.openai.base_url' => 'https://openai.test/v1',
            'ai.jev.enabled' => true,
            'ai.jev.api_key' => 'jev-key',
            'ai.jev.api_url' => 'https://jev.test/v1/systemone',
            'ai.jev.model' => 'jev-test',
        ]);
    }

    private function fakeProviders(string $intent = 'navigation', float $emergency = 0.01, array $reply = []): void
    {
        Http::fake([
            'jev.test/*' => Http::response(['answers' => [
                'max_intent' => ['choice' => $intent, 'confidence' => 0.95],
                'possible_emergency' => ['noul' => $emergency],
            ]]),
            'openai.test/*' => Http::response(['status' => 'completed', 'output' => [[
                'type' => 'message',
                'content' => [['type' => 'output_text', 'text' => json_encode($reply + [
                    'message_to_user' => 'Você encontra suas consultas no menu Minhas consultas.',
                    'actions' => ['consultas'],
                ])]],
            ]]]),
        ]);
    }

    public function test_requires_authentication_role_and_tenant(): void
    {
        $this->postJson('/triagem/max', ['message' => 'Oi'])->assertUnauthorized();
        $this->postJson('/gestor/dados/max', ['message' => 'Oi'])->assertUnauthorized();

        $this->actingAs($this->manager())->postJson('/triagem/max', ['message' => 'Oi'])->assertForbidden();
        $this->actingAs($this->patient())->postJson('/gestor/dados/max', ['message' => 'Oi'])->assertForbidden();

        $orphan = User::factory()->create(['tenant_id' => null]);
        $this->actingAs($orphan)->postJson('/triagem/max', ['message' => 'Oi'])->assertForbidden();
    }

    public function test_validates_the_message(): void
    {
        $patient = $this->patient();

        $this->actingAs($patient)->postJson('/triagem/max', ['message' => ''])->assertUnprocessable()->assertJsonValidationErrors('message');
        $this->actingAs($patient)->postJson('/triagem/max', ['message' => str_repeat('a', 2001)])->assertUnprocessable();
    }

    public function test_works_with_deterministic_rules_when_ai_is_disabled(): void
    {
        Http::fake();

        $this->actingAs($this->patient())->postJson('/triagem/max', ['message' => 'Minhas consultas', 'page' => '/consultas'])
            ->assertOk()
            ->assertJsonPath('tone', 'info')
            ->assertJsonPath('actions', [
                ['label' => 'Minhas consultas', 'path' => '/consultas'],
                ['label' => 'Minha conta', 'path' => '/conta'],
            ]);

        $this->actingAs($this->manager())->postJson('/gestor/dados/max', ['message' => 'Minhas pendências'])
            ->assertOk()
            ->assertJsonPath('actions.2', ['label' => 'Planos e módulos', 'path' => '/gestor/planos']);

        Http::assertNothingSent();
    }

    public function test_emergency_privacy_and_medication_never_call_ai_providers(): void
    {
        $this->enableAi();
        $this->fakeProviders();
        $patient = $this->patient();

        $this->actingAs($patient)->postJson('/triagem/max', ['message' => 'Minha receita, mas estou com dor no peito'])
            ->assertOk()->assertJsonPath('tone', 'danger')
            ->assertJsonPath('actions', [['label' => 'Ligar 192, SAMU', 'path' => 'tel:192']]);
        $this->actingAs($patient)->postJson('/triagem/max', ['message' => 'Quero trocar um medicamento'])
            ->assertOk()->assertJsonPath('actions', [['label' => 'Minhas receitas', 'path' => '/farmacia']]);
        $this->actingAs($this->manager())->postJson('/gestor/dados/max', ['message' => 'Qual paciente tem ansiedade?'])
            ->assertOk()->assertJsonPath('actions', [])
            ->assertJson(fn ($json) => $json->where('text', fn ($text) => str_contains($text, 'nunca dados individuais'))->etc());

        Http::assertNothingSent();
    }

    public function test_navigation_uses_jev_then_openai_with_minimised_data(): void
    {
        $this->enableAi();
        $this->fakeProviders();
        $patient = $this->patient(['name' => 'Maria Secreta', 'cpf' => '98765432100']);

        $this->actingAs($patient)->postJson('/triagem/max', [
            'message' => 'Onde vejo meu histórico? meu cpf é 123.456.789-01 e email maria@x.com',
            'page' => '/receita/ABC123',
        ])->assertOk()
            ->assertJsonPath('text', 'Você encontra suas consultas no menu Minhas consultas.')
            ->assertJsonPath('actions', [['label' => 'Minhas consultas', 'path' => '/consultas']]);

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) {
            $body = $request->body();

            return ! str_contains($body, '123.456.789-01') && ! str_contains($body, 'maria@x.com')
                && ! str_contains($body, 'Maria Secreta') && ! str_contains($body, '98765432100')
                && ! str_contains($body, 'ABC123') && str_contains($body, '[dado removido]');
        });
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'jev.test')
            && $request->hasHeader('Authorization', 'Bearer jev-key')
            && isset($request['questions']['max_intent']));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'openai.test/v1/responses')
            && $request->hasHeader('Authorization', 'Bearer openai-key')
            && $request['text']['format']['name'] === 'max_assistant_reply');
    }

    public function test_jev_can_escalate_to_emergency_without_calling_openai(): void
    {
        $this->enableAi();
        $this->fakeProviders(emergency: 0.9);

        $this->actingAs($this->patient())->postJson('/triagem/max', ['message' => 'meu coração está disparado'])
            ->assertOk()->assertJsonPath('tone', 'danger');

        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'openai.test'));
    }

    public function test_links_are_restricted_to_the_catalog_and_plan_modules(): void
    {
        $this->enableAi();
        $patient = $this->patient();
        $patient->tenant->plans()->create(['name' => 'Básico', 'modules' => ['farmacia' => false], 'is_default' => true]);
        $this->fakeProviders(reply: ['actions' => ['consultas', 'conta']]);

        $this->actingAs($patient)->postJson('/triagem/max', ['message' => 'Como uso a plataforma?'])
            ->assertOk()->assertJsonPath('actions', [
                ['label' => 'Minhas consultas', 'path' => '/consultas'],
                ['label' => 'Minha conta', 'path' => '/conta'],
            ]);

        Http::assertSent(fn (Request $request) => ! str_contains(json_encode($request['text'] ?? []), '"farmacia"'));
    }

    public function test_actions_outside_the_catalog_make_the_reply_fall_back_to_rules(): void
    {
        $this->enableAi();
        $patient = $this->patient();

        $this->fakeProviders(reply: ['actions' => ['https://evil.test']]);
        $this->actingAs($patient)->postJson('/triagem/max', ['message' => 'Como uso a plataforma?'])
            ->assertOk()->assertJsonPath('actions.0.path', '/consultas')
            ->assertJsonPath('text', 'Posso ajudar com suas consultas, seu plano e seu cadastro. A marcação com especialista segue as regras do seu contrato.');
    }

    public function test_unsafe_ai_reply_is_discarded_by_the_existing_message_guard(): void
    {
        $this->enableAi();
        $this->fakeProviders(reply: ['message_to_user' => 'Pare de tomar o remédio.', 'actions' => ['consultas']]);
        Log::spy();

        $this->actingAs($this->patient())->postJson('/triagem/max', ['message' => 'Como uso a plataforma?'])
            ->assertOk()->assertJsonPath('text', 'Posso ajudar com suas consultas, seu plano e seu cadastro. A marcação com especialista segue as regras do seu contrato.');

        Http::assertSentCount(2);
        Log::shouldHaveReceived('warning')->with('max.reply_rejected', ['reason' => 'unsafe_content'])->once();
    }

    public function test_provider_failure_falls_back_and_logs_no_message_content(): void
    {
        $this->enableAi();
        Http::fake(['*' => Http::response(null, 500)]);
        Log::spy();

        $this->actingAs($this->patient())->postJson('/triagem/max', ['message' => 'Como vejo minhas coisas sigilosas?'])
            ->assertOk()->assertJsonPath('tone', 'info');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context = []) => $message === 'max.ai_fallback'
                && ! str_contains(json_encode($context), 'sigilosas'))
            ->atLeast()->once();
    }

    public function test_messages_are_rate_limited_per_user(): void
    {
        $patient = $this->patient();

        for ($i = 0; $i < 12; $i++) {
            $this->actingAs($patient)->postJson('/triagem/max', ['message' => 'Oi'])->assertOk();
        }

        $this->actingAs($patient)->postJson('/triagem/max', ['message' => 'Oi'])->assertTooManyRequests();
    }

    public function test_demo_assistant_is_unchanged(): void
    {
        config(['healthcare.demo_enabled' => true]);
        Http::fake();

        $this->post('/demonstracao/cenario', [
            'scenario' => 'queimados', 'profile' => 'patient', 'network' => 'normal',
        ]);
        $this->postJson('/demonstracao/dados/max', ['message' => 'Quero trocar um medicamento', 'page' => '/farmacia'])
            ->assertOk()->assertJsonPath('trace.rule', 'medication')->assertJsonPath('trace.version', 'demo-1');

        Http::assertNothingSent();
    }
}
