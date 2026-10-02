<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CrmApiClient
{
    public function getLeads(): array
    {
        $response = Http::withToken(config('services.crm_api.token'))
            ->acceptJson()
            ->get(config('services.crm_api.url') . '/leads', [
                'per_page' => 100,
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json()['data'];
    }

    public function getSources(): array
    {
        $response = Http::withToken(config('services.crm_api.token'))
            ->acceptJson()
            ->get(config('services.crm_api.url') . '/sources');

        return collect($response->json('data'))->pluck('code')->all();
    }
}
