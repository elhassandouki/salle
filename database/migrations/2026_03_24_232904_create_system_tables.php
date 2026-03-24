<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        // Coaches
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('specialite')->nullable();
            $table->decimal('salaire', 10, 2)->nullable();
            $table->timestamps();
        });

        // Abonnes
        Schema::create('abonnes', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('card_id')->nullable()->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('cin')->nullable()->unique();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('sexe')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->nullable();
            $table->string('situation_familiale')->nullable();
            $table->string('profession')->nullable();
            $table->text('adresse')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        // Services (activite / assurance)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type'); // activite / assurance
            $table->text('description')->nullable();
            $table->decimal('prix_mensuel', 10, 2)->default(0);
            $table->decimal('prix_trimestriel', 10, 2)->default(0);
            $table->decimal('prix_annuel', 10, 2)->default(0);
            $table->integer('capacite_max')->nullable();
            $table->foreignId('coach_id')->nullable()->constrained()->nullOnDelete();
            $table->string('couleur')->nullable();
            $table->string('statut')->default('actif');
            $table->timestamps();
        });

        // Subscriptions (abonnement + assurance + facture)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonne_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('type_abonnement'); // mensuel / trimestriel / annuel
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('montant', 10, 2);
            $table->decimal('remise', 10, 2)->default(0);
            $table->decimal('montant_total', 10, 2);
            $table->decimal('montant_paye', 10, 2)->default(0);
            $table->decimal('reste', 10, 2)->default(0);
            $table->string('statut')->default('en_attente'); // paye / partiel / expire
            $table->boolean('auto_renew')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('mode_paiement')->nullable();
            $table->string('reference')->nullable();
            $table->dateTime('date_paiement')->useCurrent();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Pointages RFID / ZKTeco
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonne_id')->constrained()->cascadeOnDelete();
            $table->string('uid')->nullable();
            $table->dateTime('date_pointage');
            $table->string('type')->default('entree');
            $table->boolean('synced')->default(false);
            $table->timestamps();
        });

        // Reclamations Assurance
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonne_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('montant_total', 10, 2);
            $table->decimal('montant_rembourse', 10, 2)->nullable();
            $table->string('statut')->default('en_attente');
            $table->date('date_reclamation');
            $table->date('date_traitement')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Depenses
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->decimal('montant', 10, 2);
            $table->string('categorie')->nullable();
            $table->date('date_depense');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('depenses');
        Schema::dropIfExists('reclamations');
        Schema::dropIfExists('pointages');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('services');
        Schema::dropIfExists('abonnes');
        Schema::dropIfExists('coaches');
    }
};