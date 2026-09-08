<?php

namespace App\Http\Requests;

use App\Enums\PictogramSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdatePictogramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('pictogram'));
    }

    /**
     * Todas las reglas usan "sometimes": el campo solo se valida si el
     * cliente lo envio. Un PATCH que solo manda {"isActive": false} no
     * debe verse obligado a repetir title, phrase, emoji, etc.
     */
    public function rules(): array
    {
        return [
            'pictogramCategoryId' => ['sometimes', 'uuid', Rule::exists('pictogram_categories', 'id')],
            'title'               => ['sometimes', 'string', 'max:100'],
            'phrase'              => ['sometimes', 'string', 'max:255'],
            'speechText'          => ['sometimes', 'string', 'max:255'],
            'emoji'               => ['sometimes', 'string', 'max:8'],
            'severity'            => ['sometimes', new Enum(PictogramSeverity::class)],
            'isActive'            => ['sometimes', 'boolean'],
            'sortOrder'           => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * A diferencia de StorePictogramRequest, aqui NO se rellenan defaults
     * (is_active => true, sort_order => 0). Si el campo no vino, no debe
     * aparecer en el array final, o el update() del modelo lo sobrescribiria
     * con ese default aunque el cliente nunca haya querido tocarlo.
     */
    public function validatedForModel(): array
    {
        $data = $this->validated();

        $map = [
            'pictogramCategoryId' => 'pictogram_category_id',
            'title'               => 'title',
            'phrase'              => 'phrase',
            'speechText'          => 'speech_text',
            'emoji'               => 'emoji',
            'severity'            => 'severity',
            'isActive'            => 'is_active',
            'sortOrder'           => 'sort_order',
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
            'pictogramCategoryId.exists' => 'La categoria seleccionada no existe.',
            'severity.Illuminate\Validation\Rules\Enum' => 'La severidad indicada no es valida.',
        ];
    }
}