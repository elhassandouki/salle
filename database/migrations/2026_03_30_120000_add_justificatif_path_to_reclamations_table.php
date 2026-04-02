<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reclamations') && ! Schema::hasColumn('reclamations', 'justificatif_path')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->string('justificatif_path')->nullable()->after('date_traitement');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reclamations') && Schema::hasColumn('reclamations', 'justificatif_path')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->dropColumn('justificatif_path');
            });
        }
    }
};
