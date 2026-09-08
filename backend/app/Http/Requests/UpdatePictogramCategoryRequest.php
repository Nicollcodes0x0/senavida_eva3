<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePictogramCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('pictogram_category'));
    }

    public function rules(): array
    {
        $categoryId = $this->route('pictogram_category')?->id;

        return [
            'name'      => ['sometimes', 'string', 'max:100', Rule::unique('pictogram_categories', 'name')->ignore($categoryId)],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
            'isActive'  => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Igual que UpdatePictogramRequest: solo se incluye en el resultado
     * lo que el cliente realmente envio, para no pisar valores existentes
     * con defaults que nadie pidio.
     */
    public function validatedForModel(): array
    {
        $data = $this->validated();

        $map = [
            'name'      => 'name',
            'sortOrder' => 'sort_order',
            'isActive'  => 'is_active',
        ];

        $result = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $result[$column] = $data[$input];
            }
        }

        return $result;
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una categoria con ese nombre.',
        ];
    }
}