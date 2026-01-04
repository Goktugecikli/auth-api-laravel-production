<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'integer', 'min:1'],
            'starts_at'   => ['required', 'date', 'after:now'],
            'ends_at'     => ['required', 'date', 'after:starts_at'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }
}
