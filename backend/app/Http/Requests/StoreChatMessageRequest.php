<?php

namespace App\Http\Requests;

use App\Enums\MessageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'        => ['required', 'string', 'max:2000'],
            'messageType' => ['required', new Enum(MessageType::class), Rule::notIn(['system'])],
            'pictogramId' => [
                Rule::requiredIf(fn () => $this->input('messageType') === 'pictogram'),
                'nullable',
                'uuid',
                Rule::exists('pictograms', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required'        => 'El mensaje no puede estar vacio.',
            'body.max'              => 'El mensaje no puede superar los 2000 caracteres.',
            'messageType.required' => 'Debes indicar el tipo de mensaje.',
            'pictogramId.required' => 'Debes seleccionar un pictograma.',
            'pictogramId.exists'   => 'El pictograma seleccionado no existe.',
        ];
    }
}