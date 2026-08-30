<?php

namespace App\Repositories\CompanyManagement;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class CompanyRepository
{
    /**
     * Get base query for Company.
     */
    public function getQuery(): Builder
    {
        return Company::query();
    }

    /**
     * Get filtered data for export.
     */
    public function getExportData($request)
    {
        return $this->getQuery();
    }
}
