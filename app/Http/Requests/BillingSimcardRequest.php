<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillingSimcardRequest extends FormRequest
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
        return [
            'company_id' => backpack_user()->hasRole('Super Admin') ? 'nullable|exists:companies,id' : 'nullable',
            'product' => 'nullable|string|max:100',
            'device_name' => 'nullable|string|max:150',
            'technology' => 'nullable|string|max:50',
            'device_profile_id' => 'nullable|string|max:100',
            'iccid' => 'nullable|string|max:100',
            'msisdn' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'rate_plan' => 'nullable|string|max:100',
            'subscription_expiry_date' => 'nullable|date',
            'installation_date' => 'nullable|date',
            'expired_date' => 'nullable|date',
            'reminder_date' => 'nullable|date|after:expired_date',
        ];
    }
}
