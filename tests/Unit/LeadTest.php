<?php

namespace Tests\Unit;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_can_be_created()
    {
        $lead = Lead::factory()->create(['name' => 'Maria Teste']);

        $this->assertNotNull($lead->id);
        $this->assertDatabaseHas('leads', ['name' => 'Maria Teste']);
    }

    public function test_phone_is_normalized()
    {
        $lead = new Lead();
        $lead->phone = '(11) 98765-4321';

        $this->assertEquals('11987654321', $lead->phone);
    }

    public function test_won_lead_is_closed()
    {
        $lead = new Lead(['status' => 'WON']);

        $this->assertTrue($lead->isClosed());
    }

    public function test_lost_lead_is_closed()
    {
        $lead = new Lead(['status' => 'LOST']);

        $this->assertTrue($lead->isClosed());
    }

    public function test_new_lead_is_not_closed()
    {
        $lead = new Lead(['status' => 'NEW']);

        $this->assertFalse($lead->isClosed());
    }

    public function test_statuses()
    {
        $this->assertCount(6, Lead::STATUSES);
    }
}
