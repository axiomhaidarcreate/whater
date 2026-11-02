<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ];
    }

    /**
     * Custom Arabic attribute names.
     */
    public function attributes(): array
    {
        return [
            'phone' => 'رقم الجوال',
            'password' => 'كلمة المرور',
            'device_name' => 'اسم الجهاز',
            'platform' => 'نظام التشغيل',
        ];
    }
}
