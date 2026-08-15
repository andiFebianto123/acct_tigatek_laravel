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
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $currencyCode = $this->input('currency_code', 'IDR');

        $cleanPercent = function ($val) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;
            $str = str_replace(',', '.', trim((string) $val));
            return (float) $str;
        };

        $cleanNominal = function ($val) use ($currencyCode) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;

            $str = trim((string) $val);
            if ($currencyCode === 'USD') {
                if (strpos($str, ',') !== false && strpos($str, '.') === false) {
                    $str = str_replace(',', '.', $str);
                } else {
                    $str = str_replace(',', '', $str);
                }
                return (float) $str;
            }

            // IDR
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
            return (float) $str;
        };

        $nominal_exclude = $cleanNominal($this->input('nominal_exclude_ppn'));
        $tax_ppn = $cleanPercent($this->input('tax_ppn'));
        $nominal_include = $cleanNominal($this->input('nominal_include_ppn'));

        if ($nominal_include <= 0 && $nominal_exclude > 0) {
            $nominal_include = $nominal_exclude + ($nominal_exclude * $tax_ppn / 100);
            if ($currencyCode === 'IDR') {
                $nominal_include = round($nominal_include);
            }
        }

        $this->merge([
            'nominal_include_ppn' => $nominal_include,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->get('id') ?? $this->route('id');

        $currencyCode = request()->input('currency_code', 'IDR');

        $cleanPercent = function ($val) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;
            $str = str_replace(',', '.', trim((string) $val));
            return (float) $str;
        };

        $cleanNominal = function ($val) use ($currencyCode) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;

            $str = trim((string) $val);
            if ($currencyCode === 'USD') {
                if (strpos($str, ',') !== false && strpos($str, '.') === false) {
                    $str = str_replace(',', '.', $str);
                } else {
                    $str = str_replace(',', '', $str);
                }
                return (float) $str;
            }

            // IDR
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
            return (float) $str;
        };
        $nominal_exclude_ppn = $cleanNominal(request()->nominal_exclude_ppn);

        $rule = [
            // 'name' => 'required|min:5|max:255'
            // 'job_value' => 'required|numeric|min:1000',
            'currency_code' => 'required|in:IDR,USD',
            'invoice_number' => 'required|min:3|max:50|unique:invoice_clients,invoice_number,' . $id,
            'invoice_date' => 'required',
            'category' => 'required|in:rutin,non_rutin',
            'client_id' => 'required|exists:clients,id',
            'client_po_id' => 'nullable|exists:client_po,id',
            'status' => 'nullable|in:Paid,Unpaid',
            'invoice_document' => 'nullable|file|mimes:pdf|max:30720', // 30MB = 30720 KB
            'document_imei_iccid' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'withholding_agent' => 'required|in:WAPU,NON WAPU',
            'account_source_id' => 'required|exists:cast_accounts,id',
            'type_device' => 'nullable|in:App\Models\BillingDevice,App\Models\BillingSimcard,App\Models\DeviceStock',
            'term' => 'nullable|string',
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'pic' => 'required|string|max:150',
        ];

        if ($id) {
            $items = json_decode(request()->invoice_client_details_edit, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as $item) {
                    $price = $cleanNominal($item['price'] ?? 0);
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
                    function ($attribute, $value, $fail) use ($nominal_exclude_ppn, $items, $cleanNominal) {
                        $items_total_price = 0;
                        foreach ($items as $item) {
                            $price = $cleanNominal($item['price'] ?? 0);
                            $qty = (int) ($item['qty'] ?? 1);
                            $items_total_price += ($price * $qty);
                        }
                        if (abs($nominal_exclude_ppn - $items_total_price) > 0.01) {
                            $fail(trans('backpack::crud.invoice_client.field.item.errors.total_price'));
                        }
                    }
                ];
                $rule['invoice_client_details_edit.*.name'] = 'required|max:120';
                $rule['invoice_client_details_edit.*.price'] = 'required';
            }
        } else {
            $items = json_decode(request()->invoice_client_details, true);
            $status_empty = true;
            $items_total_price = 0;
            if ($items != null) {
                foreach ($items as $item) {
                    $price = $cleanNominal($item['price'] ?? 0);
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
                    function ($attribute, $value, $fail) use ($nominal_exclude_ppn, $items, $cleanNominal) {
                        $items_total_price = 0;
                        foreach ($items as $item) {
                            $price = $cleanNominal($item['price'] ?? 0);
                            $qty = (int) ($item['qty'] ?? 1);
                            $items_total_price += ($price * $qty);
                        }

                        if (abs($nominal_exclude_ppn - $items_total_price) > 0.01) {
                            $fail(trans('backpack::crud.invoice_client.field.item.errors.total_price'));
                        }
                    }
                ];
                $rule['invoice_client_details.*.name'] = 'required|max:120';
                $rule['invoice_client_details.*.price'] = 'required';
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
