<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\CrmApiClient;
use Illuminate\Console\Command;

class SyncLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa e atualiza os leads a partir da API do CRM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = new CrmApiClient();
        $leads = $client->getLeads();

        $this->info('Leads recebidos: ' . count($leads));

        foreach ($leads as $item) {
            $lead = Lead::firstOrNew(['external_id' => $item['id']]);

            $lead->fill([
                'name' => $item['name'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'company' => $item['company'],
                'source' => $item['source'],
                'created_at' => $item['created_at'],
                'raw_payload' => json_encode($item),
                'synced_at' => now(),
            ]);

            // Depois da importação, status e observações ficam por conta do time comercial
            if (! $lead->exists) {
                $lead->status = $item['status'];
                $lead->notes = $item['notes'];
            }

            $lead->save();
        }

        $this->info('Sincronização concluída.');

        return Command::SUCCESS;
    }
}
