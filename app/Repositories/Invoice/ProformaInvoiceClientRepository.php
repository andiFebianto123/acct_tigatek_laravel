<?php

namespace App\Repositories\Invoice;

use App\Models\ProformaInvoiceClient;
use App\DTOs\Invoice\ProformaInvoiceClientFilterData;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceClientRepository
{
    public function applyListQuery($query, ProformaInvoiceClientFilterData $dto): void
    {
        $query->leftJoin('companies', 'companies.id', '=', 'proforma_invoice_clients.company_id')
            ->leftJoin('clients', 'clients.id', '=', 'proforma_invoice_clients.client_id');

        $this->applyFilters($query, $dto);
    }

    public function applyFilters($query, ProformaInvoiceClientFilterData $dto): void
    {
        $this->applyColumnFilters($query, $dto->columns);

        if ($dto->invoice_date) {
            $query->where('proforma_invoice_clients.invoice_date', $dto->invoice_date);
        }



        if ($dto->send_invoice_normal) {
            $query->where('proforma_invoice_clients.send_invoice_normal_date', $dto->send_invoice_normal);
        }

        if ($dto->send_invoice_revision) {
            $query->where('proforma_invoice_clients.send_invoice_revision_date', $dto->send_invoice_revision);
        }

        if ($dto->filter_paid_status && $dto->filter_paid_status != 'all') {
            $query->where('proforma_invoice_clients.status', $dto->filter_paid_status);
        }

        if ($dto->filter_year && $dto->filter_year != 'all') {
            $query->whereYear('proforma_invoice_clients.invoice_date', $dto->filter_year);
        }

        if ($dto->company_id && $dto->company_id != 'all') {
            $query->where('proforma_invoice_clients.company_id', $dto->company_id);
        }
    }

    private function applyColumnFilters($query, mixed $columns): void
    {
        if (empty($columns) || !is_array($columns)) return;

        $isDatatable = isset($columns[0]['search']);
        $offset = (backpack_user()->hasRole('Super Admin')) ? 1 : 0;

        foreach ($columns as $index => $column) {
            $value = '';
            $name = null;

            if ($isDatatable) {
                $value = trim($column['search']['value'] ?? '');
                $name = $column['name'] ?? null;
            } else if (is_string($column)) {
                $value = trim($column ?? '');
            } else if (is_array($column)) {
                $value = trim($column['search']['value'] ?? '');
            }

            if ($value === '') continue;

            if ($name) {
                match ($name) {
                    'company' => $query->where('companies.name', 'like', "%{$value}%"),
                    'invoice_number' => $query->where('proforma_invoice_clients.invoice_number', 'like', "%{$value}%"),
                    'invoice_date' => $query->where('proforma_invoice_clients.invoice_date', 'like', "%{$value}%"),
                    'description' => $query->where('proforma_invoice_clients.description', 'like', "%{$value}%"),
                    'client_name' => $query->where('clients.name', 'like', "%{$value}%"),
                    'price_total_exclude_ppn' => $query->where('proforma_invoice_clients.price_total_exclude_ppn', 'like', "%{$value}%"),
                    'tax_ppn' => $query->where('proforma_invoice_clients.tax_ppn', 'like', "%{$value}%"),
                    'price_total_include_ppn' => $query->where('proforma_invoice_clients.price_total_include_ppn', 'like', "%{$value}%"),
                    'note' => $query->where('proforma_invoice_clients.note', 'like', "%{$value}%"),
                    default => null
                };
            } else {
                if ($offset === 1 && $index === 1) {
                    $query->where('companies.name', 'like', "%{$value}%");
                }
 
                match ($index - $offset) {
                    1 => $query->where('proforma_invoice_clients.invoice_number', 'like', "%{$value}%"),
                    2 => $query->where('proforma_invoice_clients.invoice_date', 'like', "%{$value}%"),
                    3 => $query->where('clients.name', 'like', "%{$value}%"),
                    4 => $query->where('proforma_invoice_clients.currency_code', 'like', "%{$value}%"),
                    5 => $query->where('proforma_invoice_clients.description', 'like', "%{$value}%"),
                    6 => $query->where('proforma_invoice_clients.price_total_exclude_ppn', 'like', "%{$value}%"),
                    7 => $query->where('proforma_invoice_clients.price_total_exclude_ppn_base', 'like', "%{$value}%"),
                    8 => $query->where('proforma_invoice_clients.tax_ppn', 'like', "%{$value}%"),
                    9 => $query->where('proforma_invoice_clients.price_total_include_ppn', 'like', "%{$value}%"),
                    10 => $query->where('proforma_invoice_clients.note', 'like', "%{$value}%"),
                    default => null
                };
            }
        }
    }

    public function getTotals(ProformaInvoiceClientFilterData $dto): array
    {
        $query = ProformaInvoiceClient::select(
            DB::raw("SUM(price_total_exclude_ppn_base) as total_price_exclude_ppn"),
            DB::raw("SUM(price_total_include_ppn_base) as total_price_include_ppn"),
            DB::raw("SUM(discount_pph_base) as total_discount_pph")
        )->leftJoin('companies', 'companies.id', '=', 'proforma_invoice_clients.company_id')
            ->leftJoin('clients', 'clients.id', '=', 'proforma_invoice_clients.client_id');

        $this->applyFilters($query, $dto);

        return $query->first()?->toArray() ?? [];
    }

    /**
     * Generate next Proforma Invoice Client number.
     */
    public function generateNextNumber()
    {
        $settings = \App\Models\Setting::first();
        $prefix = $settings?->pi_prefix ?? 'PI-CLIENT';
        $monthYear = now()->format('my');
        $pattern = $prefix . '/' . $monthYear . '/';
        $lastEntry = ProformaInvoiceClient::where('invoice_number', 'like', $pattern . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();
        if ($lastEntry) {
            $parts = explode('/', $lastEntry->invoice_number);
            $lastIndex = (int) end($parts);
            $nextIndex = $lastIndex + 1;
        } else {
            $nextIndex = 1;
        }
        return $pattern . sprintf('%02d', $nextIndex);
    }
}
