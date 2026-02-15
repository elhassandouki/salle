<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('abonnes', function (Blueprint $table) {
            // card_id bach n-stocker code la carte RFID (Unique o nullable)
            $table->string('card_id')->nullable()->unique()->after('cin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonnes', function (Blueprint $table) {
            $table->dropColumn('card_id');
        });
    }
};