<?php

namespace App\Http\Requests;

use App\Enums\PictogramSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePictogramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Pictogram::class);
    }

    public function rules(): array
    {
        return [
            'pictogramCategoryId' => ['required', 'uuid', Rule::exists('pictogram_categories', 'id')],
            'title'               => ['required', 'string', 'max:100'],
            'phrase'              => ['required', 'string', 'max:255'],
            'speechText'          => ['required', 'string', 'max:255'],
            'emoji'               => ['required', 'string', 'max:8'],
            'severity'            => ['required', new Enum(PictogramSeverity::class)],
            'isActive'            => ['sometimes', 'boolean'],
            'sortOrder'           => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * El frontend manda camelCase; la base de datos usa snake_case.
     * Este mapeo centraliza esa traduccion en un solo lugar.
     */
    public function validatedForModel(): array
    {
        $data = $this->validated();

        return [
            'pictogram_category_id' => $data['pictogramCategoryId'],
            'title'                 => $data['title'],
            'phrase'                => $data['phrase'],
            'speech_text'           => $data['speechText'],
            'emoji'                 => $data['emoji'],
            'severity'              => $data['severity'],
            'is_active'             => $data['isActive'] ?? true,
            'sort_order'            => $data['sortOrder'] ?? 0,
        ];
    }

    public function messages(): array
    {
        return [
            'pictogramCategoryId.required' => 'Debes seleccionar una categoria.',
            'pictogramCategoryId.exists'   => 'La categoria seleccionada no existe.',
            'title.required'               => 'El titulo es obligatorio.',
            'phrase.required'              => 'La frase que se envia al chat es obligatoria.',
            'speechText.required'          => 'El texto para voz es obligatorio.',
            'emoji.required'               => 'Debes asignar un simbolo al pictograma.',
            'severity.required'            => 'Debes indicar la severidad del pictograma.',
        ];
    }
}