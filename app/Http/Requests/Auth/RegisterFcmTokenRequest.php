<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string'],
            'device_uuid' => ['required', 'string', 'max:100'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
        ];
    }

    public function attributes(): array
    {
        return [
            'fcm_token' => 'رمز الإشعار (FCM Token)',
            'device_uuid' => 'معرف الجهاز الفريد',
            'platform' => 'منصة التشغيل',
        ];
    }
}
