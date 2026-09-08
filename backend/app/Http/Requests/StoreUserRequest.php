<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * La lista de roles que se puede asignar depende de quien hace la
     * peticion, no es fija. super_admin gestiona la ESTRUCTURA del sistema
     * (puede otorgar cualquier rol, incluido otro super_admin o un
     * admin_institucional). admin_institucional gestiona la OPERACION
     * clinica de su centro: solo puede otorgar roles operativos.
     *
     * Sin esta distincion, un admin_institucional podria crear una cuenta
     * super_admin llenando el campo "role" del formulario - una escalacion
     * de privilegios real, señalada como riesgo critico en el contrato.
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
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'role'                  => ['required', Rule::in($this->allowedRoles())],
            'organizationId'        => ['required', 'uuid', 'exists:organizations,id'],
            'healthCenterId'        => ['required', 'uuid', 'exists:health_centers,id'],
            'unitId'                => ['required', 'uuid', 'exists:units,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'No tienes permiso para asignar ese rol.',
        ];
    }
}