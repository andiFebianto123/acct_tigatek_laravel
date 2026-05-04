<?php

namespace App\Services\ClientManagement;

use App\Models\Client;
use App\DTOs\ClientManagement\ClientData;
use Illuminate\Support\Facades\DB;

class ClientService
{
    /**
     * Create a new Client.
     */
    public function createClient(ClientData $data): Client
    {
        return DB::transaction(function () use ($data) {
            return Client::create($data->toArray());
        });
    }

    /**
     * Update an existing Client.
     */
    public function updateClient(int $id, ClientData $data): Client
    {
        return DB::transaction(function () use ($id, $data) {
            $client = Client::findOrFail($id);
            $client->update($data->toArray());
            return $client;
        });
    }

    /**
     * Delete a Client.
     */
    public function deleteClient(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $client = Client::findOrFail($id);
            return (bool) $client->delete();
        });
    }

    /**
     * Get UI events for the JSON response.
     */
    public function getUIEvents(Client $item, string $actionType = 'create'): array
    {
        $suffix = $actionType === 'create' ? 'create_success' : 'updated_success';

        return [
            "crudTable-client-list_{$suffix}" => $item, // Adjusted to match potential table name
        ];
    }
}
