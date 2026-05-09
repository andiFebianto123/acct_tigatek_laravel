<?php

namespace App\Http\Requests\Coa;

use App\Models\Account;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CoaRequest extends FormRequest
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
        $id = $this->id;
        
        $rules = [
            'code' => 'required|min:3|max:20|unique:accounts,code,' . $id,
            'name' => 'required|max:100|unique:accounts,name,' . $id,
            'balance' => 'required|numeric|min:0',
        ];

        if ($id) {
            // When updating, balance is usually not required or handled differently
            unset($rules['balance']);

            $rules['code'] = [
                'required',
                'min:3',
                'max:20',
                Rule::unique('accounts', 'code')->ignore($id),
                function ($attribute, $value, $fail) use ($id) {
                    $old_account = Account::find($id);
                    if ($old_account && $value != $old_account->code) {
                        $child = Account::where('code', 'LIKE', "{$old_account->code}%")
                            ->where('id', '!=', $id)
                            ->count();
                        if ($child > 0) {
                            $fail(trans('backpack::crud.expense_account.field.code.errors.depedency'));
                        }
                    }
                }
            ];
        }

        return $rules;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'code' => trans('backpack::crud.expense_account.column.code'),
            'name' => trans('backpack::crud.expense_account.column.name'),
            'balance' => trans('backpack::crud.expense_account.column.balance'),
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
