<?php

namespace App\Http\Requests;

use App\Models\ClientPo;
use Illuminate\Foundation\Http\FormRequest;

class ProformaInvoiceClientRequest extends FormRequest
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

        $rule = [
            'invoice_number' => 'required|min:3|max:50|unique:proforma_invoice_clients,invoice_number,' . $id,
            'invoice_date' => 'required',
            'status' => 'nullable|in:Paid,Unpaid',
            'withholding_agent' => 'nullable|in:WAPU,NON WAPU',
            'account_source_id' => 'nullable|exists:cast_accounts,id',
            'note' => 'nullable|string|max:500',
            'term' => 'nullable|string',
            'type_device' => 'nullable|in:App\Models\BillingDevice,App\Models\BillingSimcard,App\Models\DeviceStock',
            'currency_code' => 'nullable|string|in:IDR,USD',
        ];

        $currencyCode = request()->input('currency_code', 'IDR');
        $minPrice = ($currencyCode === 'USD') ? 0.01 : 1000;

        if ($id) {
            $items = json_decode(request()->proforma_invoice_client_details_edit, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as &$item) {
                    $rawPrice = (string) ($item['price'] ?? 0);
                    if ($currencyCode === 'USD') {
                        $price = (float) str_replace(',', '', $rawPrice);
                    } else {
                        $price = (float) str_replace('.', '', $rawPrice);
                    }
                    $item['price'] = $price;
                    $qty = (int) ($item['qty'] ?? 1);
                    $items_total_price += ($price * $qty);
                }
                unset($item);
                if ($items_total_price > 0) {
                    $status_empty = false;
                }
            }
            $this->merge([
                'proforma_invoice_client_details_edit' => $items,
            ]);
            if (!$status_empty) {
                $rule['proforma_invoice_client_details_edit'] = [
                    'required',
                    'array',
                    'min:1',
                ];
                $rule['proforma_invoice_client_details_edit.*.name'] = 'required|max:120';
                $rule['proforma_invoice_client_details_edit.*.price'] = 'required|numeric|min:' . $minPrice;
            }
        } else {
            $items = json_decode(request()->proforma_invoice_client_details, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as &$item) {
                    $rawPrice = (string) ($item['price'] ?? 0);
                    if ($currencyCode === 'USD') {
                        $price = (float) str_replace(',', '', $rawPrice);
                    } else {
                        $price = (float) str_replace('.', '', $rawPrice);
                    }
                    $item['price'] = $price;
                    $qty = (int) ($item['qty'] ?? 1);
                    $items_total_price += ($price * $qty);
                }
                unset($item);
                if ($items_total_price > 0) {
                    $status_empty = false;
                }
            }
            $this->merge([
                'proforma_invoice_client_details' => $items,
            ]);
            if (!$status_empty) {
                $rule['proforma_invoice_client_details'] = [
                    'required',
                    'array',
                    'min:1',
                ];
                $rule['proforma_invoice_client_details.*.name'] = 'required|max:120';
                $rule['proforma_invoice_client_details.*.price'] = 'required|numeric|min:' . $minPrice;
            }
        }

        return $rule;
    }
}
