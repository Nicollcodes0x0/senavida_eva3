<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Mismo principio que en StoreUserRequest: la lista de roles otorgables
     * depende de quien hace la peticion, no del usuario que se esta editando.
     */
    private function allowedRoles(): array
    {
        $actor = $this->user();

        if ($actor->role === 'super_admin') {
            return ['super_admin', 'admin_institucional', 'admision', 'categorizacion', 'medico'];
        }

        return ['admision', 'categorizacion', 'medico'];
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->route('user')?->id)],
            'role'     => ['sometimes', Rule::in($this->allowedRoles())],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Una regla que no se puede expresar como "rules()" simple: nadie puede
     * cambiar su PROPIO rol desde este endpoint. Sin esto, un admin_institucional
     * podria editarse a si mismo y ponerse role=super_admin - la misma
     * escalacion de privilegios, pero via update() en vez de create().
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $targetUser = $this->route('user');
            $isEditingSelf = $targetUser && $targetUser->id === $this->user()->id;

            if ($isEditingSelf && $this->filled('role')) {
                $validator->errors()->add('role', 'No puedes cambiar tu propio rol.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'role.in'    => 'No tienes permiso para asignar ese rol.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
        ];
    }
}