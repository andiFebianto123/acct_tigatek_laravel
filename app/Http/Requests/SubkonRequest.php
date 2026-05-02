<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubkonRequest extends FormRequest
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
            // 'name' => 'required|min:5|max:255'
            'name' => 'required|string|max:255|unique:subkons,name,'.$id,
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'npwp' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_account' => 'required|numeric|digits_between:5,255',
            'account_holder_name' => 'required|max:60',
            'company_id' => backpack_user()->hasRole('Super Admin') ? 'required|exists:companies,id' : 'nullable',
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
