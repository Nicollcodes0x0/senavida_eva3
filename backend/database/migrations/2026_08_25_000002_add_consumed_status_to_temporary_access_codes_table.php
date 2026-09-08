<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL no permite modificar un CHECK constraint directamente:
        // hay que quitarlo y volver a crearlo con el valor nuevo incluido.
        DB::statement("ALTER TABLE temporary_access_codes DROP CONSTRAINT temporary_access_codes_status_check");
        DB::statement("ALTER TABLE temporary_access_codes ADD CONSTRAINT temporary_access_codes_status_check CHECK (status IN ('active', 'expired', 'consumed'))");

        DB::statement("ALTER TABLE temporary_access_codes ADD COLUMN consumed_at TIMESTAMP NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE temporary_access_codes DROP COLUMN consumed_at");
        DB::statement("ALTER TABLE temporary_access_codes DROP CONSTRAINT temporary_access_codes_status_check");
        DB::statement("ALTER TABLE temporary_access_codes ADD CONSTRAINT temporary_access_codes_status_check CHECK (status IN ('active', 'expired'))");
    }
};