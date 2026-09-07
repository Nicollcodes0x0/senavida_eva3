/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * UsersCrudTable.tsx
 *
 * UserRegistrationForm.tsx ya cubre Crear (POST /users). Este componente
 * completa Leer, Actualizar y Desactivar/Restaurar, revisando el patrón
 * real de UserController.php:
 *
 * - GET /users devuelve una lista paginada, filtrada del lado del backend
 *   según quién pregunta (admin_institucional solo ve su propio centro;
 *   super_admin ve a todos). No repetimos ese filtro acá.
 * - PUT /users/{id} solo permite cambiar name, email y role — el propio
 *   backend rechaza que alguien cambie su propio rol, y rechaza asignar
 *   un rol que quien edita no tenga permitido otorgar.
 * - DELETE /users/{id} es un soft delete (is_active=false). El backend
 *   impide que alguien se desactive a sí mismo.
 * - PATCH /users/{id}/restore reactiva a alguien previamente desactivado.
 */

import React, { useState, useEffect, useCallback } from 'react';
import { Pencil, Check, X, RotateCcw, Ban, UserCog } from 'lucide-react';
import {
  listUsers,
  updateUser,
  deactivateUser,
  restoreUser,
  UserListItem,
  UserRole,
  ApiError,
} from '../lib/apiClient';

const ROLE_OPTIONS: { value: UserRole; label: string }[] = [
  { value: 'admision', label: 'Admisión' },
  { value: 'categorizacion', label: 'Categorización' },
  { value: 'medico', label: 'Médico' },
  { value: 'admin_institucional', label: 'Admin. Institucional' },
  { value: 'super_admin', label: 'Super Admin' },
];

const ROLE_LABELS: Record<string, string> = Object.fromEntries(
  ROLE_OPTIONS.map((r) => [r.value, r.label])
);

interface UsersCrudTableProps {
  highContrast?: boolean;
}

// Estado editable de una fila mientras está en modo edición.
interface EditDraft {
  name: string;
  email: string;
  role: UserRole;
}

export const UsersCrudTable: React.FC<UsersCrudTableProps> = ({ highContrast = false }) => {
  const [users, setUsers] = useState<UserListItem[]>([]);
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  const [editingId, setEditingId] = useState<string | null>(null);
  const [draft, setDraft] = useState<EditDraft>({ name: '', email: '', role: 'admision' });
  const [busyId, setBusyId] = useState<string | null>(null);

  const loadUsers = useCallback(async () => {
    setError('');
    try {
      const result = await listUsers();
      setUsers(result.users);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No pudimos cargar los funcionarios.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadUsers();
  }, [loadUsers]);

  const startEditing = (user: UserListItem) => {
    setEditingId(user.id);
    setDraft({ name: user.name, email: user.email, role: user.role });
  };

  const cancelEditing = () => {
    setEditingId(null);
  };

  const saveEditing = async (id: string) => {
    setBusyId(id);
    try {
      await updateUser(id, draft);
      setEditingId(null);
      await loadUsers();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No pudimos actualizar el funcionario.');
    } finally {
      setBusyId(null);
    }
  };

  const handleDeactivate = async (id: string) => {
    setBusyId(id);
    try {
      await deactivateUser(id);
      await loadUsers();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No pudimos desactivar al funcionario.');
    } finally {
      setBusyId(null);
    }
  };

  const handleRestore = async (id: string) => {
    setBusyId(id);
    try {
      await restoreUser(id);
      await loadUsers();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No pudimos restaurar al funcionario.');
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div
      className={`bg-white p-5 rounded-xl border space-y-4 ${
        highContrast ? 'bg-black border-yellow-400 text-yellow-400' : 'border-brand-border'
      }`}
    >
      <div className="border-b pb-3">
        <h3 className="text-sm font-extrabold text-brand-dark flex items-center gap-1.5">
          <UserCog className="w-4 h-4 text-brand-primary" /> Funcionarios registrados
        </h3>
        <p className="text-[11px] text-brand-text-secondary mt-0.5">
          Editar, desactivar y restaurar. Nadie puede desactivarse ni cambiar su propio rol — eso lo impide el backend.
        </p>
      </div>

      {error && (
        <div className="p-3 rounded-lg border border-brand-coral/20 bg-brand-coral-light/25 text-brand-coral-dark text-xs font-semibold flex items-center justify-between gap-3">
          <span>{error}</span>
          <button
            onClick={() => setError('')}
            title="Cerrar este aviso"
            className="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center hover:bg-brand-coral/10"
          >
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      {isLoading ? (
        <p className="text-xs text-brand-text-secondary">Cargando funcionarios…</p>
      ) : users.length === 0 ? (
        <p className="text-xs text-brand-text-secondary italic">Sin funcionarios registrados todavía.</p>
      ) : (
        <div className="space-y-1.5">
          {users.map((user) => {
            const isEditing = editingId === user.id;
            const isBusy = busyId === user.id;

            return (
              <div
                key={user.id}
                className={`p-2.5 rounded-lg border text-xs ${
                  highContrast ? 'border-yellow-400' : 'border-brand-border bg-brand-bg'
                } ${!user.isActive ? 'opacity-60' : ''}`}
              >
                {isEditing ? (
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center">
                    <input
                      type="text"
                      value={draft.name}
                      onChange={(e) => setDraft({ ...draft, name: e.target.value })}
                      disabled={isBusy}
                      className="h-8 px-2 bg-white rounded border border-brand-primary font-medium outline-none disabled:opacity-60"
                      placeholder="Nombre"
                    />
                    <input
                      type="email"
                      value={draft.email}
                      onChange={(e) => setDraft({ ...draft, email: e.target.value })}
                      disabled={isBusy}
                      className="h-8 px-2 bg-white rounded border border-brand-primary font-medium outline-none disabled:opacity-60"
                      placeholder="Correo"
                    />
                    <div className="flex items-center gap-1.5">
                      <select
                        value={draft.role}
                        onChange={(e) => setDraft({ ...draft, role: e.target.value as UserRole })}
                        disabled={isBusy}
                        className="flex-1 h-8 px-2 bg-white rounded border border-brand-primary font-medium outline-none disabled:opacity-60"
                      >
                        {ROLE_OPTIONS.map((opt) => (
                          <option key={opt.value} value={opt.value}>
                            {opt.label}
                          </option>
                        ))}
                      </select>
                      <button
                        onClick={() => saveEditing(user.id)}
                        disabled={isBusy}
                        title="Guardar"
                        className="w-7 h-7 flex-shrink-0 rounded flex items-center justify-center text-brand-success hover:bg-brand-success-light disabled:opacity-50"
                      >
                        <Check className="w-4 h-4" />
                      </button>
                      <button
                        onClick={cancelEditing}
                        disabled={isBusy}
                        title="Cancelar"
                        className="w-7 h-7 flex-shrink-0 rounded flex items-center justify-center text-brand-text-secondary hover:bg-brand-border disabled:opacity-50"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ) : (
                  <div className="flex items-center gap-2">
                    <div className="flex-1 min-w-0">
                      <span className="font-semibold text-brand-dark block truncate">
                        {user.name}
                        {!user.isActive && (
                          <span className="ml-2 text-[10px] font-bold text-brand-coral-dark uppercase">
                            Inactivo
                          </span>
                        )}
                      </span>
                      <span className="text-[10px] text-brand-text-secondary block truncate">
                        {user.email} · {ROLE_LABELS[user.role] ?? user.role}
                      </span>
                    </div>
                    <button
                      onClick={() => startEditing(user)}
                      disabled={isBusy}
                      title="Editar"
                      className="w-7 h-7 flex-shrink-0 rounded flex items-center justify-center text-brand-primary hover:bg-brand-light disabled:opacity-50"
                    >
                      <Pencil className="w-3.5 h-3.5" />
                    </button>
                    {user.isActive ? (
                      <button
                        onClick={() => handleDeactivate(user.id)}
                        disabled={isBusy}
                        title="Desactivar"
                        className="w-7 h-7 flex-shrink-0 rounded flex items-center justify-center text-brand-coral-dark hover:bg-brand-coral-light disabled:opacity-50"
                      >
                        <Ban className="w-3.5 h-3.5" />
                      </button>
                    ) : (
                      <button
                        onClick={() => handleRestore(user.id)}
                        disabled={isBusy}
                        title="Restaurar"
                        className="w-7 h-7 flex-shrink-0 rounded flex items-center justify-center text-brand-turquoise-dark hover:bg-brand-success-light disabled:opacity-50"
                      >
                        <RotateCcw className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};