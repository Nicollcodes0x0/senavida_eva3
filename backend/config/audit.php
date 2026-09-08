<?php

return [
    /*
     * Clave secreta usada para firmar (HMAC-SHA256) las exportaciones
     * de la bitacora de auditoria. Separada de APP_KEY a proposito:
     * si APP_KEY se rota alguna vez, no debe invalidar silenciosamente
     * las firmas de exportaciones ya generadas.
     */
    'export_signing_key' => env('AUDIT_EXPORT_SIGNING_KEY'),
];