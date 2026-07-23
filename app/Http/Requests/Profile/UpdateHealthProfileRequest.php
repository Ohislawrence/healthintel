<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'sex' => ['nullable', 'string', 'in:male,female'],
            'is_pregnant' => ['nullable', 'boolean'],
            'height_cm' => ['nullable', 'numeric', 'min:50', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'blood_type' => ['nullable', 'string', 'max:15'],
            'medical_conditions' => ['nullable', 'string', 'max:5000'],
            'current_medications' => ['nullable', 'string', 'max:5000'],
            'profile_completed' => ['nullable', 'boolean'],
        ];
    }
}
