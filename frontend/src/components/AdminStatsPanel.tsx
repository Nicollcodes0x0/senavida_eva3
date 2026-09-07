/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * AdminStatsPanel.tsx
 *
 * Reemplaza las tres tarjetas de arriba de DashboardAdmin.tsx que antes
 * tenían números escritos a mano en el código ("42 Usuarios", "3.4K
 * Peticiones", "100% Repudiable"). Ahora los tres vienen de GET
 * /admin/stats, calculados en vivo en el servidor:
 *
 * - activeUsers: cuenta real de usuarios activos del propio centro.
 * - apiRequests: contador de peticiones a la API, guardado en caché.
 * - auditCoverage: porcentaje calculado por reflexión de PHP sobre qué
 *   modelos sensibles tienen realmente conectado el observador de
 *   auditoría — si alguien agrega un modelo nuevo y se olvida de
 *   conectarlo, este número baja solo, sin que nadie lo edite a mano.
 *
 * Es exclusivo de admin_institucional (así lo valida el propio backend),
 * así que si otro rol llegara a ver este panel, mostramos el mensaje de
 * error tal cual lo devuelve el servidor, sin inventar un número falso.
 */

import React, { useState, useEffect } from 'react';
import { Users, Database, ShieldCheck } from 'lucide-react';
import { fetchAdminStats, AdminStats, ApiError } from '../lib/apiClient';

interface AdminStatsPanelProps {
  highContrast?: boolean;
}

export const AdminStatsPanel: React.FC<AdminStatsPanelProps> = ({ highContrast = false }) => {
  const [stats, setStats] = useState<AdminStats | null>(null);
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const loadStats = async () => {
      try {
        const data = await fetchAdminStats();
        setStats(data);
      } catch (err) {
        setError(err instanceof ApiError ? err.message : 'No pudimos cargar las estadísticas.');
      } finally {
        setIsLoading(false);
      }
    };
    loadStats();
  }, []);

  const cardClass = `p-5 rounded-xl border flex items-center gap-4 ${
    highContrast ? 'bg-black border-yellow-400' : 'bg-white border-brand-border'
  }`;

  if (error) {
    return (
      <div className="p-4 rounded-xl border border-brand-coral/20 bg-brand-coral-light/25 text-brand-coral-dark text-xs font-semibold">
        {error}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div className={cardClass}>
        <div className="w-11 h-11 rounded-lg bg-brand-light text-brand-primary flex items-center justify-center flex-shrink-0">
          <Users className="w-5 h-5" />
        </div>
        <div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-brand-text-secondary block">
            Funcionarios Activos
          </span>
          <span className="text-xl font-black text-brand-dark">
            {isLoading ? '…' : `${stats?.activeUsers} Usuarios`}
          </span>
        </div>
      </div>

      <div className={cardClass}>
        <div className="w-11 h-11 rounded-lg bg-brand-success-light text-brand-turquoise-dark flex items-center justify-center flex-shrink-0">
          <Database className="w-5 h-5" />
        </div>
        <div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-brand-text-secondary block">
            Consumo de la API
          </span>
          <span className="text-xl font-black text-brand-dark">
            {isLoading ? '…' : `${stats?.apiRequests} Peticiones`}
          </span>
        </div>
      </div>

      <div className={cardClass}>
        <div className="w-11 h-11 rounded-lg bg-brand-yellow-light text-brand-yellow-dark flex items-center justify-center flex-shrink-0">
          <ShieldCheck className="w-5 h-5" />
        </div>
        <div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-brand-text-secondary block">
            Cobertura de Auditoría
          </span>
          <span className="text-xl font-black text-brand-dark">
            {isLoading ? '…' : `${stats?.auditCoverage}% Cubierto`}
          </span>
        </div>
      </div>
    </div>
  );
};