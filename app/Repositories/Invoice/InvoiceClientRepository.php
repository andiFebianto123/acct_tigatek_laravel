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
        })->leftJoin('client_po', 'client_po.id', '=', 'invoice_clients.client_po_id')
            ->leftJoin('companies', 'companies.id', '=', 'invoice_clients.company_id');

        $this->applyFilters($query, $dto);
    }

    public function applyFilters($query, InvoiceClientFilterData $dto): void
    {
        $query->where('invoice_clients.invoice_number', 'not like', 'INV-UMUM%');

        $this->applyColumnFilters($query, $dto->columns);

        if ($dto->invoice_date) {
            $query->where('invoice_clients.invoice_date', $dto->invoice_date);
        }

        if ($dto->po_date) {
            $query->where(function ($q) use ($dto) {
                $q->where('client_po.date_po', $dto->po_date)
                  ->orWhereHas('delivery_note', function ($dn) use ($dto) {
                      $dn->where(function ($sub) use ($dto) {
                          $sub->where('delivery_notes.reference_type', 'client_po')
                              ->whereHas('client_po', fn($p) => $p->where('date_po', $dto->po_date));
                      })->orWhere(function ($sub) use ($dto) {
                          $sub->whereNotNull('delivery_notes.client_po_id')
                              ->whereHas('client_po', fn($p) => $p->where('date_po', $dto->po_date));
                      });
                  });
            });
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

        if ($dto->company_id && $dto->company_id != 'all') {
            $query->where('invoice_clients.company_id', $dto->company_id);
        }

        if ($dto->category && $dto->category != 'all') {
            $query->where('invoice_clients.category', $dto->category);
        }
    }

    private function applyColumnFilters($query, mixed $columns): void
    {
        if (empty($columns) || !is_array($columns)) return;

        $isDatatable = isset($columns[0]['search']);
        $offset = (backpack_user() && backpack_user()->canAccessAllCompanies()) ? 1 : 0;

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
                    'invoice_number' => $query->where('invoice_clients.invoice_number', 'like', "%{$value}%"),
                    'category' => $query->where('invoice_clients.category', 'like', "%{$value}%"),
                    'kdp' => $query->where('invoice_clients.kdp', 'like', "%{$value}%"),
                    'name' => $query->whereHas('client_po', fn($q) => $q->where('job_name', 'like', "%{$value}%")),
                    'description' => $query->where('invoice_clients.description', 'like', "%{$value}%"),
                    'client_po_id' => $query->where(function ($q) use ($value) {
                        $q->whereHas('client_po', fn($sub) => $sub->where('po_number', 'like', "%{$value}%"))
                          ->orWhereHas('delivery_note', function ($dn) use ($value) {
                              $dn->where(function ($sub) use ($value) {
                                  $sub->where('delivery_notes.reference_type', 'client_po')
                                      ->whereHas('client_po', fn($p) => $p->where('po_number', 'like', "%{$value}%"));
                              })->orWhere(function ($sub) use ($value) {
                                  $sub->whereNotNull('delivery_notes.client_po_id')
                                      ->whereHas('client_po', fn($p) => $p->where('po_number', 'like', "%{$value}%"));
                              });
                          });
                    }),
                    'po_date_from_po' => $query->where(function ($q) use ($value) {
                        $q->where('client_po.date_po', 'like', "%{$value}%")
                          ->orWhereHas('delivery_note', function ($dn) use ($value) {
                              $dn->where(function ($sub) use ($value) {
                                  $sub->where('delivery_notes.reference_type', 'client_po')
                                      ->whereHas('client_po', fn($p) => $p->where('date_po', 'like', "%{$value}%"));
                              })->orWhere(function ($sub) use ($value) {
                                  $sub->whereNotNull('delivery_notes.client_po_id')
                                      ->whereHas('client_po', fn($p) => $p->where('date_po', 'like', "%{$value}%"));
                              });
                          });
                    }),
                    'client_name' => $query->where(function ($q) use ($value) {
                        $q->whereHas('client', fn($sub) => $sub->where('name', 'like', "%{$value}%"))
                          ->orWhereHas('client_po.client', fn($sub) => $sub->where('name', 'like', "%{$value}%"))
                          ->orWhereHas('delivery_note.client', fn($sub) => $sub->where('name', 'like', "%{$value}%"));
                    }),
                    'price_total_exclude_ppn' => $query->where('invoice_clients.price_total_exclude_ppn', 'like', "%{$value}%"),
                    'price_total_include_ppn' => $query->where('invoice_clients.price_total_include_ppn', 'like', "%{$value}%"),
                    'discount_pph' => $query->where('invoice_clients.discount_pph', 'like', "%{$value}%"),
                    default => null
                };
            } else {
                // By index with offset
                if ($offset === 1 && $index === 1) {
                    $query->where('companies.name', 'like', "%{$value}%");
                }

                match ($index - $offset) {
                    1 => $query->where('invoice_clients.invoice_number', 'like', "%{$value}%"),
                    2 => $query->where('invoice_clients.kdp', 'like', "%{$value}%"),
                    3 => $query->whereHas('client_po', fn($q) => $q->where('job_name', 'like', "%{$value}%")),
                    4 => $query->where('invoice_clients.description', 'like', "%{$value}%"),
                    5 => $query->where('invoice_clients.invoice_date', 'like', "%{$value}%"),
                    6 => $query->where('invoice_clients.category', 'like', "%{$value}%"),
                    7 => $query->where(function ($q) use ($value) {
                        $q->whereHas('client_po', fn($sub) => $sub->where('po_number', 'like', "%{$value}%"))
                          ->orWhereHas('delivery_note', function ($dn) use ($value) {
                              $dn->where(function ($sub) use ($value) {
                                  $sub->where('delivery_notes.reference_type', 'client_po')
                                      ->whereHas('client_po', fn($p) => $p->where('po_number', 'like', "%{$value}%"));
                              })->orWhere(function ($sub) use ($value) {
                                  $sub->whereNotNull('delivery_notes.client_po_id')
                                      ->whereHas('client_po', fn($p) => $p->where('po_number', 'like', "%{$value}%"));
                              });
                          });
                    }),
                    8 => $query->where(function ($q) use ($value) {
                        $q->where('client_po.date_po', 'like', "%{$value}%")
                          ->orWhereHas('delivery_note', function ($dn) use ($value) {
                              $dn->where(function ($sub) use ($value) {
                                  $sub->where('delivery_notes.reference_type', 'client_po')
                                      ->whereHas('client_po', fn($p) => $p->where('date_po', 'like', "%{$value}%"));
                              })->orWhere(function ($sub) use ($value) {
                                  $sub->whereNotNull('delivery_notes.client_po_id')
                                      ->whereHas('client_po', fn($p) => $p->where('date_po', 'like', "%{$value}%"));
                              });
                          });
                    }),
                    9 => $query->where(function ($q) use ($value) {
                        $q->whereHas('client', fn($sub) => $sub->where('name', 'like', "%{$value}%"))
                          ->orWhereHas('client_po.client', fn($sub) => $sub->where('name', 'like', "%{$value}%"))
                          ->orWhereHas('delivery_note.client', fn($sub) => $sub->where('name', 'like', "%{$value}%"));
                    }),
                    10 => $query->where('invoice_clients.currency_code', 'like', "%{$value}%"),
                    11 => $query->where('invoice_clients.price_total_exclude_ppn', 'like', "%{$value}%"),
                    12 => $query->where('invoice_clients.price_total_exclude_ppn_base', 'like', "%{$value}%"),
                    13 => $query->where('invoice_clients.price_total_include_ppn', 'like', "%{$value}%"),
                    14 => $query->where('invoice_clients.discount_pph', 'like', "%{$value}%"),
                    15 => $query->where('invoice_clients.send_invoice_normal_date', 'like', "%{$value}%"),
                    16 => $query->where('invoice_clients.send_invoice_revision_date', 'like', "%{$value}%"),
                    17 => $query->where('invoice_clients.status', 'like', "%{$value}%"),
                    default => null
                };
            }
        }
    }

    public function getTotals(InvoiceClientFilterData $dto): array
    {
        $query = InvoiceClient::select(
            DB::raw("SUM(price_total_exclude_ppn_base) as total_price_exclude_ppn"),
            DB::raw("SUM(price_total_include_ppn_base) as total_price_include_ppn"),
            DB::raw("SUM(discount_pph_base) as total_discount_pph")
        )->leftJoin('client_po', 'client_po.id', '=', 'invoice_clients.client_po_id')
            ->leftJoin('companies', 'companies.id', '=', 'invoice_clients.company_id');

        $this->applyFilters($query, $dto);

        return $query->first()?->toArray() ?? [];
    }

    /**
     * Check if an invoice exists for a specific device on a given date.
     */
    public function hasInvoiceForDevice(string $typeDevice, string $identifier, string $date): bool
    {
        return InvoiceClient::where('invoice_date', $date)
            ->where('type_device', $typeDevice)
            ->whereHas('invoice_client_details', function ($query) use ($identifier) {
                $query->where('name', $identifier);
            })
            ->exists();
    }
}
