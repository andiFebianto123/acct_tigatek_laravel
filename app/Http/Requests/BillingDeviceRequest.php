<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillingDeviceRequest extends FormRequest
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
            'device_id' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'vehicle_uid' => 'nullable|string|max:100',
            'vehicle_name' => 'nullable|string|max:150',
            'imei' => 'nullable|string|max:100',
            'speed_limit' => 'nullable|integer',
            'sim_network' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'subscription_expiry_date' => 'nullable|date',
            'installation_date' => 'nullable|date',
            'expired_date' => 'nullable|date',
            'reminder_date' => 'nullable|date|after:expired_date',
        ];
    }
}
