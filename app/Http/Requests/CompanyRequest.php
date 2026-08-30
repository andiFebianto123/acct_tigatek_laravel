<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
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
        $id = $this->get('id') ?? $this->route('id'); // untuk update

        return [
            'name'        => 'required|string|max:255|unique:companies,name,' . $id,
            'address'     => 'required|string',
            'city'        => 'required|string|max:255',
            'province'    => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:255',
            'website'     => 'nullable|string|max:255',
            'logo'        => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
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
            'name'        => trans('backpack::crud.company.column.name'),
            'address'     => trans('backpack::crud.company.column.address'),
            'city'        => trans('backpack::crud.company.column.city'),
            'province'    => trans('backpack::crud.company.column.province'),
            'postal_code' => trans('backpack::crud.company.column.postal_code'),
            'phone'       => trans('backpack::crud.company.column.phone'),
            'email'       => trans('backpack::crud.company.column.email'),
            'website'     => trans('backpack::crud.company.column.website'),
            'logo'        => trans('backpack::crud.company.column.logo'),
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
