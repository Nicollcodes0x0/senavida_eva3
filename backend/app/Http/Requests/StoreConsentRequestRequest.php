<?php

namespace App\Http\Requests;

use App\Enums\ConsentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consentType' => ['required', Rule::in(ConsentType::implementados())],
            'contactId'   => [
                Rule::requiredIf(fn () => $this->input('consentType') === ConsentType::ShareWithContacts->value),
                'nullable',
                'uuid',
                Rule::exists('patient_contacts', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'consentType.required' => 'Debes indicar el tipo de consentimiento.',
            'consentType.in'       => 'Ese tipo de consentimiento no está disponible.',
            'contactId.required'   => 'Debes seleccionar el contacto con quien se compartirá la información.',
            'contactId.exists'     => 'El contacto seleccionado no existe.',
        ];
    }
}