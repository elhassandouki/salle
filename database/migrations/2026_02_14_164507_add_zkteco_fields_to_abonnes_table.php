<?php
// database/migrations/xxxx_xx_xx_add_fields_to_abonnes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('abonnes', function (Blueprint $table) {
            $table->string('sexe')->nullable()->after('email');
            $table->string('lieu_naissance')->nullable()->after('date_naissance');
            $table->string('nationalite')->nullable()->after('lieu_naissance');
            $table->string('situation_familiale')->nullable()->after('nationalite');
            $table->string('profession')->nullable()->after('situation_familiale');
            $table->text('notes')->nullable()->after('adresse');
        });
    }

    public function down()
    {
        Schema::table('abonnes', function (Blueprint $table) {
            $table->dropColumn([
                'sexe',
                'lieu_naissance',
                'nationalite',
                'situation_familiale',
                'profession',
                'notes'
            ]);
        });
    }
};