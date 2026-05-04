<?php

namespace App\Repositories\ClientManagement;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class ClientRepository
{
    /**
     * Get filtered data for the client list.
     */
    public function getFilteredData($filters = [])
    {
        $query = Client::query();

        // Add filter by year if needed, similar to SpkRepository
        if (isset($filters['filter_year']) && $filters['filter_year'] !== 'all') {
            $query->whereHas('client_po', function($q) use($filters){
                $q->whereYear('end_date', $filters['filter_year']);
            });
        }

        return $query;
    }
}
