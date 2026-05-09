<?php

namespace App\Http\Requests\Coa;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BalanceSheetRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $id = $this->id ?? $this->route('id');
        $type = $this->type;

        $rules = [
            'type' => 'required',
            'name' => 'required|max:100|unique:accounts,name,' . $id,
            'date' => 'required',
        ];

        if (!$id) {
            $rules['balance'] = 'required|numeric|min:0';
        }

        $rules['code'] = [
            'required',
            'min:3',
            'max:20',
            Rule::unique('accounts', 'code')->ignore($id),
            function ($attribute, $value, $fail) use ($id, $type) {
                $old_code = null;
                if ($id) {
                    $old_account = Account::find($id);
                    $old_code = $old_account?->code;
                }

                if ($value != $old_code) {
                    // Check if any parent has different type
                    for ($i = 1; $i < strlen($value); $i++) {
                        $prefix = substr($value, 0, $i);
                        $account = Account::where('code', $prefix)
                            ->where('level', '>=', 2)->first();
                        if ($account && $account->type != $type) {
                            $fail(trans('backpack::crud.expense_account.field.code.errors.depedency'));
                            return;
                        }
                    }
                }

                if ($id && $value != $old_code) {
                    // Check if it has children when changing code
                    $childCount = Account::where('code', 'LIKE', "$old_code%")
                        ->where('id', '!=', $id)
                        ->count();
                    if ($childCount > 0) {
                        $fail(trans('backpack::crud.expense_account.field.code.errors.depedency'));
                    }
                }
            }
        ];

        return $rules;
    }

    protected function prepareForValidation()
    {
        if ($this->has('balance')) {
            $this->merge([
                'balance' => (float) str_replace('.', '', $this->balance ?? '0'),
            ]);
        }
    }
}
