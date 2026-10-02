<?php

namespace Database\Seeders;

use App\Models\Lead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Leads de exemplo para desenvolvimento local.
     * Os dados reais vêm do CRM via `php artisan leads:sync`.
     *
     * @return void
     */
    public function run()
    {
        Lead::factory()->count(25)->create();
    }
}
