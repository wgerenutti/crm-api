<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('X-Api-Key', 'test-key');
    }

    private function fakeSources()
    {
        Http::fake([
            '*/sources' => Http::response([
                'data' => [
                    ['code' => 'WEBSITE', 'label' => 'Site'],
                    ['code' => 'INSTAGRAM', 'label' => 'Instagram'],
                    ['code' => 'WHATSAPP', 'label' => 'WhatsApp'],
                ],
            ]),
        ]);
    }

    public function test_can_list_leads()
    {
        Lead::factory()->count(3)->create();

        $response = $this->getJson('/api/leads');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_filter_leads_by_status()
    {
        Lead::factory()->count(2)->create(['status' => 'QUALIFIED']);
        Lead::factory()->count(3)->create(['status' => 'NEW']);

        $response = $this->getJson('/api/leads?status=QUALIFIED');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_can_filter_leads_by_status_and_source()
    {
        Lead::factory()->create(['status' => 'QUALIFIED', 'source' => 'INSTAGRAM']);
        Lead::factory()->create(['status' => 'QUALIFIED', 'source' => 'WEBSITE']);
        Lead::factory()->create(['status' => 'NEW', 'source' => 'INSTAGRAM']);

        $response = $this->getJson('/api/leads?status=QUALIFIED&source=INSTAGRAM');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_search_leads()
    {
        Lead::factory()->create(['name' => 'João Pereira']);
        Lead::factory()->create(['name' => 'Maria Souza']);

        $response = $this->getJson('/api/leads?search=João');

        $response->assertStatus(200);
    }

    public function test_can_show_lead()
    {
        $lead = Lead::factory()->create();

        $response = $this->getJson('/api/leads/' . $lead->id);

        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => $lead->email]);
    }

    public function test_show_returns_404_when_lead_does_not_exist()
    {
        $response = $this->getJson('/api/leads/999');

        $response->assertStatus(404);
    }

    public function test_can_create_lead()
    {
        $this->fakeSources();

        $response = $this->postJson('/api/leads', [
            'name' => 'Carlos Lima',
            'email' => 'carlos.lima@example.com',
            'phone' => '(11) 91234-5678',
            'company' => 'Empresa Teste',
            'source' => 'WEBSITE',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('leads', [
            'email' => 'carlos.lima@example.com',
            'phone' => '11912345678',
        ]);
    }

    public function test_create_lead_requires_name()
    {
        $this->fakeSources();

        $response = $this->postJson('/api/leads', [
            'email' => 'sem.nome@example.com',
            'source' => 'WEBSITE',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_lead_requires_valid_source()
    {
        $this->fakeSources();

        $response = $this->postJson('/api/leads', [
            'name' => 'Ana Costa',
            'email' => 'ana.costa@example.com',
            'source' => 'TELEGRAM',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('source');
    }

    public function test_can_update_lead()
    {
        $lead = Lead::factory()->create(['status' => 'NEW']);

        $response = $this->putJson('/api/leads/' . $lead->id, [
            'status' => 'CONTACTED',
            'notes' => 'Primeiro contato realizado por telefone.',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'CONTACTED',
        ]);
    }

    public function test_can_delete_lead()
    {
        $lead = Lead::factory()->create();

        $response = $this->deleteJson('/api/leads/' . $lead->id);

        $response->assertStatus(200);
    }

    public function test_sync_command_imports_leads_from_crm()
    {
        Http::fake([
            '*/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 10,
                        'name' => 'Fernanda Alves',
                        'email' => 'fernanda.alves@example.com',
                        'phone' => '11988887777',
                        'company' => null,
                        'source' => 'INSTAGRAM',
                        'status' => 'NEW',
                        'notes' => null,
                        'created_at' => '2026-05-10T14:00:00Z',
                    ],
                    [
                        'id' => 11,
                        'name' => 'Rafael Gomes',
                        'email' => 'rafael.gomes@example.com',
                        'phone' => '(21) 3333-4444',
                        'company' => 'Gomes Engenharia',
                        'source' => 'REFERRAL',
                        'status' => 'QUALIFIED',
                        'notes' => 'Indicado pelo cliente Padaria Pão Dourado.',
                        'created_at' => '2026-05-12T09:30:00Z',
                    ],
                ],
            ]),
        ]);

        $this->artisan('leads:sync')->assertExitCode(0);

        $this->assertDatabaseCount('leads', 2);
        $this->assertDatabaseHas('leads', ['external_id' => 11, 'email' => 'rafael.gomes@example.com']);
    }

    public function test_sync_keeps_status_and_notes_maintained_locally()
    {
        $lead = Lead::factory()->create([
            'external_id' => 10,
            'status' => 'PROPOSAL',
            'notes' => 'Proposta enviada em reunião.',
        ]);

        Http::fake([
            '*/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 10,
                        'name' => 'Fernanda Alves',
                        'email' => 'fernanda.alves@example.com',
                        'phone' => '11988887777',
                        'company' => 'Alves Consultoria',
                        'source' => 'INSTAGRAM',
                        'status' => 'NEW',
                        'notes' => null,
                        'created_at' => '2026-05-10T14:00:00Z',
                    ],
                ],
            ]),
        ]);

        $this->artisan('leads:sync')->assertExitCode(0);

        $lead->refresh();
        $this->assertEquals('PROPOSAL', $lead->status);
        $this->assertEquals('Proposta enviada em reunião.', $lead->notes);
        $this->assertEquals('Alves Consultoria', $lead->company);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_sync_command_does_not_fail_when_crm_is_unavailable()
    {
        Http::fake([
            '*' => Http::response(['error' => 'internal_error'], 500),
        ]);

        $this->artisan('leads:sync')->assertExitCode(0);

        $this->assertDatabaseCount('leads', 0);
    }
}
