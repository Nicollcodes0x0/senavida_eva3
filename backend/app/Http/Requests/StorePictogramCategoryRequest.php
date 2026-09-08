<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePictogramCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\PictogramCategory::class);
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100', Rule::unique('pictogram_categories', 'name')],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function validatedForModel(): array
    {
        $data = $this->validated();

        return [
            'name'       => $data['name'],
            'sort_order' => $data['sortOrder'] ?? 0,
            'is_active'  => true,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoria es obligatorio.',
            'name.unique'   => 'Ya existe una categoria con ese nombre.',
        ];
    }
}