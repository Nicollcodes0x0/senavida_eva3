<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingRequest extends FormRequest
{
    /**
     * No hay {id} en la URL: este endpoint siempre opera sobre el propio
     * centro del usuario autenticado, nunca sobre uno elegido por el cliente.
     * Por eso basta verificar el rol, sin consultar la Policy contra un
     * modelo que -al no venir por la ruta- Laravel no podria resolver.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'admin_institucional';
    }

    public function rules(): array
    {
        return [
            // between:1,10 -> un limite de 0 no tendria sentido (bloquearia
            // el primer intento fallido siempre) y un limite absurdamente
            // alto (ej. 1000) anularia la proteccion contra fuerza bruta.
            'ctaMaxAttempts' => ['required', 'integer', 'between:1,10'],
        ];
    }

    public function validatedForModel(): array
    {
        $data = $this->validated();

        return [
            'cta_max_attempts' => $data['ctaMaxAttempts'],
        ];
    }

    public function messages(): array
    {
        return [
            'ctaMaxAttempts.required' => 'Debes indicar el numero maximo de intentos.',
            'ctaMaxAttempts.between'  => 'El limite debe estar entre 1 y 10 intentos.',
        ];
    }
}