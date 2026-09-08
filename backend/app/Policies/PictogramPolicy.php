<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class PictogramPolicy
{
    /**
     * Cualquier identidad autenticada puede ver el catalogo: tanto el
     * staff como el paciente lo necesitan para construir mensajes en el chat.
     *
     * Sin type-hint a proposito: si aqui pusieramos "User $user", un Patient
     * autenticado provocaria un TypeError (error fatal), no un 403 controlado,
     * porque Patient no es una instancia de User.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    public function create(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede crear pictogramas.');
    }

    public function update(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede editar pictogramas.');
    }

    /**
     * "Eliminar" un pictograma en realidad lo desactiva (is_active = false).
     * No se borra de la base de datos porque puede estar referenciado por
     * mensajes de chat ya existentes: borrarlo de verdad rompería ese historial.
     * Misma regla de autorizacion que update.
     */
    public function delete(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede desactivar pictogramas.');
    }
}