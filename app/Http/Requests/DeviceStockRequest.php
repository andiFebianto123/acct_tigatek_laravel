<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceStockRequest extends FormRequest
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

        return [
            'name' => 'required|string|max:255|unique:device_stocks,name,' . $id,
            'code' => 'required|string|max:255|unique:device_stocks,code,' . $id,
            'category_id' => 'required|exists:device_stock_categories,id',
            'qty' => 'required|integer|min:0',
            'sell_price' => 'required|string',
            'buy_price' => 'required|string',
            'currency_code' => 'nullable|string|in:IDR,USD',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'Nama Barang',
            'code' => 'Kode Barang',
            'category_id' => 'Kategori',
            'qty' => 'Quantity',
            'sell_price' => 'Harga Jual',
            'buy_price' => 'Harga Beli',
        ];
    }
}
