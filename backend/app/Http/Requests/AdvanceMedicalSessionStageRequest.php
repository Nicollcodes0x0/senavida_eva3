<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvanceMedicalSessionStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emergency' => ['sometimes', 'boolean'],
            'reason' => ['required_if:emergency,true', 'string', 'min:30', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'Debes indicar el motivo del salto de emergencia.',
            'reason.min' => 'El motivo debe describirse con al menos 30 caracteres, para que sea util en una auditoria.',
        ];
    }
}