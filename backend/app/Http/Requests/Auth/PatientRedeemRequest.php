<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PatientRedeemRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ctaCode' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * Mensajes en español, coherentes con el resto del proyecto.
     */
    public function messages(): array
    {
        return [
            'ctaCode.required' => 'Debes ingresar tu código de atención.',
            'ctaCode.string'   => 'El código de atención no es válido.',
            'ctaCode.max'      => 'El código de atención no es válido.',
        ];
    }
}