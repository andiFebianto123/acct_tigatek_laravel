<?php

namespace App\Http\Requests;

use App\Models\ClientPo;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
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
            // 'name' => 'required|min:5|max:255'
            // 'job_value' => 'required|numeric|min:1000',
            'invoice_number' => 'required|min:3|max:50|unique:invoice_clients,invoice_number,' . $id,
            'invoice_date' => 'required',
            'client_po_id' => 'required|exists:client_po,id',
            'status' => 'nullable|in:Paid,Unpaid',
            'invoice_document' => 'nullable|file|mimes:pdf|max:30720', // 30MB = 30720 KB
            'withholding_agent' => 'required|in:WAPU,NON WAPU',
            'account_source_id' => 'required|exists:cast_accounts,id',
            'type_device' => 'nullable|in:App\Models\BillingDevice,App\Models\BillingSimcard',
        ];

        if ($id) {
            $items = json_decode(request()->invoice_client_details_edit, true);
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
                'invoice_client_details_edit' => $items,
            ]);
            if (!$status_empty) {
                $rule['invoice_client_details_edit'] = [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($client_po, $items) {
                        $client = ClientPo::find($client_po);
                        if (!$client) {
                            $fail('PO Client tidak ditemukan.');
                            return;
                        }
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
                ];
                $rule['invoice_client_details_edit.*.name'] = 'required|max:120';
                $rule['invoice_client_details_edit.*.price'] = 'required|numeric|min:1000';
            }
        } else {
            $items = json_decode(request()->invoice_client_details, true);
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
                'invoice_client_details' => $items,
            ]);
            if (!$status_empty) {
                $rule['invoice_client_details'] = [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($client_po, $items) {
                        $client = ClientPo::find($client_po);
                        if (!$client) {
                            $fail('PO Client tidak ditemukan.');
                            return;
                        }
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
                ];
                $rule['invoice_client_details.*.name'] = 'required|max:120';
                $rule['invoice_client_details.*.price'] = 'required|numeric|min:1000';
            }
        }

        return $rule;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
