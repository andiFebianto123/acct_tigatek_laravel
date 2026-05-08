<?php

namespace App\Repositories\Invoice;

use App\Models\InvoiceClient;
use App\DTOs\Invoice\InvoiceClientFilterData;
use Illuminate\Support\Facades\DB;

class InvoiceClientRepository
{
    public function applyListQuery($query, InvoiceClientFilterData $dto): void
    {
        $query->leftJoin('log_payments as log_void', function ($join) {
            $join->on('log_void.reference_id', '=', 'invoice_clients.id')
                ->where('log_void.reference_type', '=', 'App\Models\InvoiceClient')
                ->where('log_void.name', '=', 'CREATE_PAYMENT_INVOICE');
        })->leftJoin('client_po', 'client_po.id', '=', 'invoice_clients.client_po_id');

        $this->applyFilters($query, $dto);
    }

    public function applyFilters($query, InvoiceClientFilterData $dto): void
    {
        $this->applyColumnFilters($query, $dto->columns);

        if ($dto->invoice_date) {
            $query->where('invoice_clients.invoice_date', $dto->invoice_date);
        }

        if ($dto->po_date) {
            $query->where('client_po.date_po', $dto->po_date);
        }

        if ($dto->send_invoice_normal) {
            $query->where('invoice_clients.send_invoice_normal_date', $dto->send_invoice_normal);
        }

        if ($dto->send_invoice_revision) {
            $query->where('invoice_clients.send_invoice_revision_date', $dto->send_invoice_revision);
        }

        if ($dto->filter_paid_status && $dto->filter_paid_status != 'all') {
            $query->where('invoice_clients.status', $dto->filter_paid_status);
        }

        if ($dto->filter_year && $dto->filter_year != 'all') {
            $query->whereYear('invoice_clients.invoice_date', $dto->filter_year);
        }
    }

    private function applyColumnFilters($query, mixed $columns): void
    {
        if (empty($columns) || !is_array($columns)) return;

        $isDatatable = isset($columns[0]['search']);
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
                    'invoice_number' => $query->where('invoice_clients.invoice_number', 'like', "%{$value}%"),
                    'kdp' => $query->where('invoice_clients.kdp', 'like', "%{$value}%"),
                    'name' => $query->whereHas('client_po', fn($q) => $q->where('job_name', 'like', "%{$value}%")),
                    'description' => $query->where('invoice_clients.description', 'like', "%{$value}%"),
                    'client_po_id' => $query->whereHas('client_po', fn($q) => $q->where('po_number', 'like', "%{$value}%")),
                    'client_name' => $query->whereHas('client_po.client', fn($q) => $q->where('name', 'like', "%{$value}%")),
                    'price_total_exclude_ppn' => $query->where('invoice_clients.price_total_exclude_ppn', 'like', "%{$value}%"),
                    'price_total_include_ppn' => $query->where('invoice_clients.price_total_include_ppn', 'like', "%{$value}%"),
                    'discount_pph' => $query->where('invoice_clients.discount_pph', 'like', "%{$value}%"),
                    default => null
                };
            } else {
                match ($index) {
                    1 => $query->where('invoice_clients.invoice_number', 'like', "%{$value}%"),
                    2 => $query->where('invoice_clients.kdp', 'like', "%{$value}%"),
                    3 => $query->whereHas('client_po', fn($q) => $q->where('job_name', 'like', "%{$value}%")),
                    4 => $query->where('invoice_clients.description', 'like', "%{$value}%"),
                    6 => $query->whereHas('client_po', fn($q) => $q->where('po_number', 'like', "%{$value}%")),
                    8 => $query->whereHas('client_po.client', fn($q) => $q->where('name', 'like', "%{$value}%")),
                    9 => $query->where('invoice_clients.price_total_exclude_ppn', 'like', "%{$value}%"),
                    10 => $query->where('invoice_clients.price_total_include_ppn', 'like', "%{$value}%"),
                    11 => $query->where('invoice_clients.discount_pph', 'like', "%{$value}%"),
                    default => null
                };
            }
        }
    }

    public function getTotals(InvoiceClientFilterData $dto): array
    {
        $query = InvoiceClient::select(
            DB::raw("SUM(price_total_exclude_ppn) as total_price_exclude_ppn"),
            DB::raw("SUM(price_total_include_ppn) as total_price_include_ppn"),
            DB::raw("SUM(discount_pph) as total_discount_pph")
        )->leftJoin('client_po', 'client_po.id', '=', 'invoice_clients.client_po_id');

        $this->applyFilters($query, $dto);

        return $query->first()?->toArray() ?? [];
    }
}
