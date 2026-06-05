<?php

namespace App\Repositories\Invoice;

use App\Models\ProformaInvoice;
use App\DTOs\Invoice\ProformaInvoiceFilterData;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceRepository
{
    public function applyListQuery($query, ProformaInvoiceFilterData $dto): void
    {
        $query->leftJoin('client_po', 'client_po.id', '=', 'proforma_invoices.client_po_id')
            ->leftJoin('companies', 'companies.id', '=', 'proforma_invoices.company_id')
            ->leftJoin('subkons', 'subkons.id', '=', 'proforma_invoices.subkon_id');

        $this->applyFilters($query, $dto);
    }

    public function applyFilters($query, ProformaInvoiceFilterData $dto): void
    {
        $this->applyColumnFilters($query, $dto->columns);

        if ($dto->invoice_date) {
            $query->where('proforma_invoices.invoice_date', $dto->invoice_date);
        }

        if ($dto->po_date) {
            $query->where('client_po.date_po', $dto->po_date);
        }

        if ($dto->send_invoice_normal) {
            $query->where('proforma_invoices.send_invoice_normal_date', $dto->send_invoice_normal);
        }

        if ($dto->send_invoice_revision) {
            $query->where('proforma_invoices.send_invoice_revision_date', $dto->send_invoice_revision);
        }

        if ($dto->filter_paid_status && $dto->filter_paid_status != 'all') {
            $query->where('proforma_invoices.status', $dto->filter_paid_status);
        }

        if ($dto->filter_year && $dto->filter_year != 'all') {
            $query->whereYear('proforma_invoices.invoice_date', $dto->filter_year);
        }

        if ($dto->company_id && $dto->company_id != 'all') {
            $query->where('proforma_invoices.company_id', $dto->company_id);
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
                    'invoice_number' => $query->where('proforma_invoices.invoice_number', 'like', "%{$value}%"),
                    'invoice_date' => $query->where('proforma_invoices.invoice_date', 'like', "%{$value}%"),
                    'description' => $query->where('proforma_invoices.description', 'like', "%{$value}%"),
                    'subkon_name' => $query->where('subkons.name', 'like', "%{$value}%"),
                    'price_total_exclude_ppn' => $query->where('proforma_invoices.price_total_exclude_ppn', 'like', "%{$value}%"),
                    'tax_ppn' => $query->where('proforma_invoices.tax_ppn', 'like', "%{$value}%"),
                    'price_total_include_ppn' => $query->where('proforma_invoices.price_total_include_ppn', 'like', "%{$value}%"),
                    'note' => $query->where('proforma_invoices.note', 'like', "%{$value}%"),
                    default => null
                };
            } else {
                if ($offset === 1 && $index === 1) {
                    $query->where('companies.name', 'like', "%{$value}%");
                }
 
                match ($index - $offset) {
                    1 => $query->where('proforma_invoices.invoice_number', 'like', "%{$value}%"),
                    2 => $query->where('proforma_invoices.invoice_date', 'like', "%{$value}%"),
                    3 => $query->where('subkons.name', 'like', "%{$value}%"),
                    4 => $query->where('proforma_invoices.description', 'like', "%{$value}%"),
                    5 => $query->where('proforma_invoices.price_total_exclude_ppn', 'like', "%{$value}%"),
                    6 => $query->where('proforma_invoices.tax_ppn', 'like', "%{$value}%"),
                    7 => $query->where('proforma_invoices.price_total_include_ppn', 'like', "%{$value}%"),
                    8 => $query->where('proforma_invoices.note', 'like', "%{$value}%"),
                    default => null
                };
            }
        }
    }

    public function getTotals(ProformaInvoiceFilterData $dto): array
    {
        $query = ProformaInvoice::select(
            DB::raw("SUM(price_total_exclude_ppn) as total_price_exclude_ppn"),
            DB::raw("SUM(price_total_include_ppn) as total_price_include_ppn"),
            DB::raw("SUM(discount_pph) as total_discount_pph")
        )->leftJoin('client_po', 'client_po.id', '=', 'proforma_invoices.client_po_id')
            ->leftJoin('companies', 'companies.id', '=', 'proforma_invoices.company_id')
            ->leftJoin('subkons', 'subkons.id', '=', 'proforma_invoices.subkon_id');

        $this->applyFilters($query, $dto);

        return $query->first()?->toArray() ?? [];
    }
}
