<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role'                    => ['required', 'string', 'in:admin,manager,site_head,staff'],
            'is_active'               => ['boolean'],
            'must_change_password'    => ['boolean'],
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
