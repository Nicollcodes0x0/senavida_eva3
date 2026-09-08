<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class PictogramCategoryPolicy
{
    /**
     * Igual que PictogramPolicy: sin type-hint porque tanto staff como
     * paciente necesitan ver las categorias para navegar el catalogo.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    public function create(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede crear categorias.');
    }

    public function update(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede editar categorias.');
    }

    /**
     * "Eliminar" una categoria tambien es desactivar, no borrar: puede
     * tener pictogramas asociados que a su vez estan referenciados en
     * mensajes de chat historicos.
     */
    public function delete(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede desactivar categorias.');
    }
}