<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'hire_date'               => ['nullable', 'date'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'certifications'          => ['nullable', 'array'],
            'certifications.*'        => ['string', 'max:100'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'avatar'                  => ['nullable', 'image', 'max:2048'],
        ];
    }
}
