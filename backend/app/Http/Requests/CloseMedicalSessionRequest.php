<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseMedicalSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Igual que en S4: la Policy necesita la $session, que se
        // valida en el controlador con $this->authorize().
        return true;
    }

    public function rules(): array
    {
        return [
            'closure_reason' => ['required', 'string', 'in:completed,referred,abandoned'],
            'summary' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'closure_reason.in' => 'El motivo de cierre debe ser completed, referred o abandoned.',
            'summary.min' => 'El resumen de egreso debe tener al menos 10 caracteres.',
        ];
    }
}