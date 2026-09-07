/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * CatalogCrudTable.tsx
 *
 * CatalogManagementForm.tsx ya cubre Crear (POST). Este componente cubre
 * los otros tres verbos que pide la rúbrica de la Unidad 3: Leer (GET, con
 * las inactivas incluidas), Actualizar (PUT) y Eliminar de forma segura
 * (DELETE, que en este backend es un soft delete: nunca borra la fila,
 * solo la marca is_active=false — y el propio servidor rechaza la
 * desactivación de una organización si todavía tiene centros de salud
 * activos, devolviendo 409).
 *
 * Mostramos las tres entidades (Organización, Centro de Salud, Unidad) en
 * listas separadas, cada una con:
 * - Un botón "Editar" que abre un campo de texto en línea para cambiar el
 *   nombre y guardarlo con PUT.
 * - Un botón "Desactivar" (si está activa) o "Restaurar" (si no lo está),
 *   que llama a DELETE o PATCH /restore según corresponda.
 *
 * Después de cualquier acción, volvemos a pedir la lista completa al
 * servidor en vez de "adivinar" el cambio en el estado local — así la
 * pantalla siempre refleja exactamente lo que hay en la base de datos.
 */

import React, { useState, useEffect, useCallback } from 'react';
import { Pencil, Check, X, RotateCcw, Ban, Building2, Hospital, LayoutGrid } from 'lucide-react';
import {
  listOrganizations,
  listHealthCenters,
  listUnits,
  updateOrganization,
  updateHealthCenter,
  updateUnit,
  deactivateOrganization,
  deactivateHealthCenter,
  deactivateUnit,
  restoreOrganization,
  restoreHealthCenter,
  restoreUnit,
  Organization,
  HealthCenter,
  Unit,
  ApiError,
} from '../lib/apiClient';

interface CatalogCrudTableProps {
  highContrast?: boolean;
}

// Un solo tipo interno para poder reutilizar la misma fila visual con las
// tres entidades, ya que las tres comparten id/name/isActive.
interface CatalogRow {
  id: string;
  name: string;
  isActive: boolean;
  subtitle?: string;
}

export const CatalogCrudTable: React.FC<CatalogCrudTableProps> = ({ highContrast = false }) => {
  const [organizations, setOrganizations] = useState<Organization[]>([]);
  const [healthCenters, setHealthCenters] = useState<HealthCenter[]>([]);
  const [units, setUnits] = useState<Unit[]>([]);
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  const loadAll = useCallback(async () => {
    setError('');
    try {
      const [orgs, centers, unitList] = await Promise.all([
        listOrganizations('all'),
        listHealthCenters('all'),
        listUnits(undefined, 'all'),
      ]);
      setOrganizations(orgs);
      setHealthCenters(centers);
      setUnits(unitList);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No pudimos cargar los catálogos.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadAll();
  }, [loadAll]);

  if (isLoading) {
    return <p className="text-xs text-brand-text-secondary">Cargando catálogos…</p>;
  }

  return (
    <div
      className={`bg-white p-5 rounded-xl border space-y-6 ${
        highContrast ? 'bg-black border-yellow-400 text-yellow-400' : 'border-brand-border'
      }`}
    >
      <div className="border-b pb-3">
        <h3 className="text-sm font-extrabold text-brand-dark">Catálogos existentes</h3>
        <p className="text-[11px] text-brand-text-secondary mt-0.5">
          Editar, desactivar y restaurar. Ninguna acción borra datos de la base — desactivar solo oculta el registro; restaurar lo vuelve a habilitar.
        </p>
      </div>

      {error && (
        <div className="p-3 rounded-lg border border-brand-coral/20 bg-brand-coral-light/25 text-brand-coral-dark text-xs font-semibold">
          {error}
        </div>
      )}

      <CatalogSection
        title="Organizaciones"
        icon={<Building2 className="w-4 h-4" />}
        rows={organizations.map((o) => ({ id: o.id, name: o.name, isActive: o.isActive }))}
        highContrast={highContrast}
        onUpdate={async (id, name) => {
          await updateOrganization(id, name);
          await loadAll();
        }}
        onDeactivate={async (id) => {
          await deactivateOrganization(id);
          await loadAll();
        }}
        onRestore={async (id) => {
          await restoreOrganization(id);
          await loadAll();
        }}
        onActionError={setError}
      />

      <CatalogSection
        title="Centros de Salud"
        icon={<Hospital className="w-4 h-4" />}
        rows={healthCenters.map((c) => ({ id: c.id, name: c.name, isActive: c.isActive }))}
        highContrast={highContrast}
        onUpdate={async (id, name) => {
          await updateHealthCenter(id, name);
          await loadAll();
        }}
        onDeactivate={async (id) => {
          await deactivateHealthCenter(id);
          await loadAll();
        }}
        onRestore={async (id) => {
          await restoreHealthCenter(id);
          await loadAll();
        }}
        onActionError={setError}
      />

      <CatalogSection
        title="Unidades"
        icon={<LayoutGrid className="w-4 h-4" />}
        rows={units.map((u) => ({ id: u.id, name: u.name, isActive: u.isActive }))}
        highContrast={highContrast}
        onUpdate={async (id, name) => {
          await updateUnit(id, name);
          await loadAll();
        }}
        onDeactivate={async (id) => {
          await deactivateUnit(id);
          await loadAll();
        }}
        onRestore={async (id) => {
          await restoreUnit(id);
          await loadAll();
        }}
        onActionError={setError}
      />
    </div>
  );
};

// ── Sección reutilizable: una lista con Editar/Desactivar/Restaurar ─────

interface CatalogSectionProps {
  title: string;
  icon: React.ReactNode;
  rows: CatalogRow[];
  highContrast: boolean;
  onUpdate: (id: string, name: string) => Promise<void>;
  onDeactivate: (id: string) => Promise<void>;
  onRestore: (id: string) => Promise<void>;
  onActionError: (message: string) => void;
}

const CatalogSection: React.FC<CatalogSectionProps> = ({
  title,
  icon,
  rows,
  highContrast,
  onUpdate,
  onDeactivate,
  onRestore,
  onActionError,
}) => {
  // Solo una fila puede estar en edición a la vez, identificada por su id.
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editValue, setEditValue] = useState('');
  const [busyId, setBusyId] = useState<string | null>(null);

  const startEditing = (row: CatalogRow) => {
    setEditingId(row.id);
    setEditValue(row.name);
  };

  const cancelEditing = () => {
    setEditingId(null);
    setEditValue('');
  };

  const saveEditing = async (id: string) => {
    setBusyId(id);
    try {
      await onUpdate(id, editValue);
      setEditingId(null);
    } catch (err) {
      onActionError(err instanceof ApiError ? err.message : 'No pudimos actualizar el registro.');
    } finally {
      setBusyId(null);
    }
  };

  const handleDeactivate = async (id: string) => {
    setBusyId(id);
    try {
      await onDeactivate(id);
    } catch (err) {
      onActionError(err instanceof ApiError ? err.message : 'No pudimos desactivar el registro.');
    } finally {
      setBusyId(null);
    }
  };

  const handleRestore = async (id: string) => {
    setBusyId(id);
    try {
      await onRestore(id);
    } catch (err) {
      onActionError(err instanceof ApiError ? err.message : 'No pudimos restaurar el registro.');
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div>
      <h4 className="text-xs font-extrabold text-brand-dark flex items-center gap-1.5 mb-2">
        {icon} {title}
      </h4>

      {rows.length === 0 ? (
        <p className="text-[11px] text-brand-text-secondary italic">Sin registros todavía.</p>
      ) : (
        <div className="space-y-1.5">
          {rows.map((row) => {
            const isEditing = editingId === row.id;
            const isBusy = busyId === row.id;

            return (
              <div
                key={row.id}
                className={`flex items-center gap-2 p-2.5 rounded-lg border text-xs ${
                  highContrast ? 'border-yellow-400' : 'border-brand-border bg-brand-bg'
                } ${!row.isActive ? 'opacity-60' : ''}`}
              >
                {isEditing ? (
                  <input
                    type="text"
                    value={editValue}
                    onChange={(e) => setEditValue(e.target.value)}
                    autoFocus
                    disabled={isBusy}
                    className="flex-1 h-8 px-2 bg-white rounded border border-brand-primary font-medium outline-none disabled:opacity-60"
                  />
                ) : (
                  <span className="flex-1 font-semibold text-brand-dark">
                    {row.name}
                    {!row.isActive && (
                      <span className="ml-2 text-[10px] font-bold text-brand-coral-dark uppercase">
                        Inactivo
                      </span>
                    )}
                  </span>
                )}

                {isEditing ? (
                  <>
                    <button
                      onClick={() => saveEditing(row.id)}
                      disabled={isBusy}
                      title="Guardar"
                      className="w-7 h-7 rounded flex items-center justify-center text-brand-success hover:bg-brand-success-light disabled:opacity-50"
                    >
                      <Check className="w-4 h-4" />
                    </button>
                    <button
                      onClick={cancelEditing}
                      disabled={isBusy}
                      title="Cancelar"
                      className="w-7 h-7 rounded flex items-center justify-center text-brand-text-secondary hover:bg-brand-border disabled:opacity-50"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  </>
                ) : (
                  <>
                    <button
                      onClick={() => startEditing(row)}
                      disabled={isBusy}
                      title="Editar"
                      className="w-7 h-7 rounded flex items-center justify-center text-brand-primary hover:bg-brand-light disabled:opacity-50"
                    >
                      <Pencil className="w-3.5 h-3.5" />
                    </button>
                    {row.isActive ? (
                      <button
                        onClick={() => handleDeactivate(row.id)}
                        disabled={isBusy}
                        title="Desactivar"
                        className="w-7 h-7 rounded flex items-center justify-center text-brand-coral-dark hover:bg-brand-coral-light disabled:opacity-50"
                      >
                        <Ban className="w-3.5 h-3.5" />
                      </button>
                    ) : (
                      <button
                        onClick={() => handleRestore(row.id)}
                        disabled={isBusy}
                        title="Restaurar"
                        className="w-7 h-7 rounded flex items-center justify-center text-brand-turquoise-dark hover:bg-brand-success-light disabled:opacity-50"
                      >
                        <RotateCcw className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};