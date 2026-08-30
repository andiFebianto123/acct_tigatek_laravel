<?php

namespace App\Http\Requests;

use App\Models\ClientPo;
use Illuminate\Foundation\Http\FormRequest;

class ProformaInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->get('id') ?? $this->route('id');
        $client_po = request()->client_po_id;
        $currency_code = request()->currency_code ?? 'IDR';
        $isUsd = strtoupper($currency_code) === 'USD';
        $minPrice = $isUsd ? 0.01 : 1;

        $parsePrice = function ($val) use ($isUsd) {
            $str = (string) ($val ?? 0);
            if ($isUsd) {
                return (float) str_replace(',', '', $str);
            }
            return (float) str_replace('.', '', $str);
        };

        $rule = [
            'invoice_number' => 'required|min:3|max:50|unique:proforma_invoices,invoice_number,' . $id,
            'invoice_date' => 'required',
            'client_po_id' => 'nullable|exists:client_po,id',
            'currency_code' => 'nullable|in:IDR,USD',
            'status' => 'nullable|in:Paid,Unpaid',
            'category' => 'nullable|in:rutin,non_rutin',
            'withholding_agent' => 'nullable|in:WAPU,NON WAPU',
            'account_source_id' => 'nullable|exists:cast_accounts,id',
            'note' => 'nullable|string|max:500',
            'term' => 'nullable|string',
            'pic' => 'required|string|max:150',
            'subkon_id' => 'required|exists:subkons,id',
            'company_id' => 'required|exists:companies,id',
        ];

        if ($id) {
            $items = json_decode(request()->proforma_invoice_details_edit, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as $item) {
                    $price = $parsePrice($item['price'] ?? 0);
                    $qty = (int) ($item['qty'] ?? 1);
                    $items_total_price += ($price * $qty);
                }
                if ($items_total_price > 0) {
                    $status_empty = false;
                }
            }
            $this->merge([
                'proforma_invoice_details_edit' => $items,
            ]);
            if (!$status_empty) {
                $rule['proforma_invoice_details_edit'] = [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($client_po, $items, $parsePrice) {
                        if ($client_po) {
                            $client = ClientPo::find($client_po);
                            if ($client) {
                                $price_total = $client->job_value;
                                $items_total_price = 0;
                                foreach ($items as $item) {
                                    $price = $parsePrice($item['price'] ?? 0);
                                    $qty = (int) ($item['qty'] ?? 1);
                                    $items_total_price += ($price * $qty);
                                }
                                if ($price_total != $items_total_price) {
                                    $fail(trans('backpack::crud.invoice_client.field.item.errors.total_price'));
                                }
                            }
                        }
                    }
                ];
                $rule['proforma_invoice_details_edit.*.reference_id'] = 'required_without:proforma_invoice_details_edit.*.name';
                $rule['proforma_invoice_details_edit.*.name'] = 'nullable|max:120';
                $rule['proforma_invoice_details_edit.*.price'] = "required|numeric|min:{$minPrice}";
            }
        } else {
            $items = json_decode(request()->proforma_invoice_details, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as $item) {
                    $price = $parsePrice($item['price'] ?? 0);
                    $qty = (int) ($item['qty'] ?? 1);
                    $items_total_price += ($price * $qty);
                }
                if ($items_total_price > 0) {
                    $status_empty = false;
                }
            }
            $this->merge([
                'proforma_invoice_details' => $items,
            ]);
            if (!$status_empty) {
                $rule['proforma_invoice_details'] = [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($client_po, $items, $parsePrice) {
                        if ($client_po) {
                            $client = ClientPo::find($client_po);
                            if ($client) {
                                $price_total = $client->job_value;

                                $items_total_price = 0;
                                foreach ($items as $item) {
                                    $price = $parsePrice($item['price'] ?? 0);
                                    $qty = (int) ($item['qty'] ?? 1);
                                    $items_total_price += ($price * $qty);
                                }

                                if ($price_total != $items_total_price) {
                                    $fail(trans('backpack::crud.invoice_client.field.item.errors.total_price'));
                                }
                            }
                        }
                    }
                ];
                $rule['proforma_invoice_details.*.reference_id'] = 'required_without:proforma_invoice_details.*.name';
                $rule['proforma_invoice_details.*.name'] = 'nullable|max:120';
                $rule['proforma_invoice_details.*.price'] = "required|numeric|min:{$minPrice}";
            }
        }

        return $rule;
    }
}
