<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'business'     => ['required', 'string', 'exists:businesses,code'],
            'name'         => ['required', 'string', 'max:255'],
            'customer'     => ['required', 'string', 'max:255'],
            'address'      => ['nullable', 'string', 'max:500'],
            'status'       => ['required', 'in:planning,active,on_hold,complete'],
            'phase'        => ['required', 'in:planning,installation,inspection,complete'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget'       => ['nullable', 'numeric', 'min:0'],
            'budget_spent' => ['nullable', 'numeric', 'min:0'],
            'van_id'       => ['nullable', 'exists:vans,id'],
            'staff_ids'    => ['nullable', 'array'],
            'staff_ids.*'  => ['exists:users,id'],
            'staff_roles'  => ['nullable', 'array'],
            'staff_roles.*'=> ['in:lead,support'],
            'notes'        => ['nullable', 'string', 'max:5000'],
        ];
    }
}
