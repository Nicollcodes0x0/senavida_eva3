<?php

namespace App\Http\Requests;

use App\Models\MedicalSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MedicalSession::class);
    }

    public function rules(): array
    {
        return [
            'access_code_id' => ['required', 'uuid', 'exists:temporary_access_codes,id'],
            'code' => ['required', 'string', 'regex:/^SV-\d{6}$/i'],
            'reason_of_visit' => ['required', 'string', 'min:10', 'max:2000'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'access_code_id.exists' => 'El código de atención indicado no existe.',
            'code.regex' => 'El formato del código debe ser SV- seguido de 6 dígitos.',
            'reason_of_visit.min' => 'El motivo de consulta debe tener al menos 10 caracteres.',
        ];
    }
}