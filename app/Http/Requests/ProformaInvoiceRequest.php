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

        $rule = [
            'invoice_number' => 'required|min:3|max:50|unique:proforma_invoices,invoice_number,' . $id,
            'invoice_date' => 'required',
            'client_po_id' => 'nullable|exists:client_po,id',
            'status' => 'nullable|in:Paid,Unpaid',
            'withholding_agent' => 'nullable|in:WAPU,NON WAPU',
            'account_source_id' => 'nullable|exists:cast_accounts,id',
            'note' => 'nullable|string|max:500',
            'subkon_id' => 'required|exists:subkons,id',
        ];

        if ($id) {
            $items = json_decode(request()->proforma_invoice_details_edit, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as $item) {
                    $price = (float) str_replace('.', '', (string) ($item['price'] ?? 0));
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
                    function ($attribute, $value, $fail) use ($client_po, $items) {
                        if ($client_po) {
                            $client = ClientPo::find($client_po);
                            if ($client) {
                                $price_total = $client->job_value;
                                $items_total_price = 0;
                                foreach ($items as $item) {
                                    $price = (float) str_replace('.', '', (string) ($item['price'] ?? 0));
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
                $rule['proforma_invoice_details_edit.*.name'] = 'required|max:120';
                $rule['proforma_invoice_details_edit.*.price'] = 'required|numeric|min:1000';
            }
        } else {
            $items = json_decode(request()->proforma_invoice_details, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as $item) {
                    $price = (float) str_replace('.', '', (string) ($item['price'] ?? 0));
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
                    function ($attribute, $value, $fail) use ($client_po, $items) {
                        if ($client_po) {
                            $client = ClientPo::find($client_po);
                            if ($client) {
                                $price_total = $client->job_value;

                                $items_total_price = 0;
                                foreach ($items as $item) {
                                    $price = (float) str_replace('.', '', (string) ($item['price'] ?? 0));
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
                $rule['proforma_invoice_details.*.name'] = 'required|max:120';
                $rule['proforma_invoice_details.*.price'] = 'required|numeric|min:1000';
            }
        }

        return $rule;
    }
}
