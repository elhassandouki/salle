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
        // جدول المدربين
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('specialite')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('salaire', 10, 2)->nullable();
            $table->date('date_embauche')->nullable();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->timestamps();
        });

        // جدول الأنشطة
        Schema::create('activites', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->onDelete('set null');
            $table->decimal('prix_mensuel', 10, 2)->default(0);
            $table->decimal('prix_trimestriel', 10, 2)->default(0);
            $table->decimal('prix_annuel', 10, 2)->default(0);
            $table->integer('capacite_max')->default(20);
            $table->string('couleur')->nullable();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->timestamps();
        });

        // جدول المشتركين
        Schema::create('abonnes', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique()->nullable()->comment('ID في ZKTeco');
            $table->string('nom');
            $table->string('prenom');
            $table->string('cin')->unique()->nullable();
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->date('date_naissance')->nullable();
            $table->text('adresse')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        // جدول شركات التأمين
        Schema::create('assurance_companies', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('taux_couverture', 5, 2)->default(80.00)->comment('نسبة التغطية %');
            $table->integer('delai_remboursement')->default(30)->comment('الأيام اللازمة للاسترجاع');
            $table->timestamps();
        });

        // جدول الاشتراكات
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonne_id')->constrained('abonnes')->onDelete('cascade');
            $table->foreignId('activite_id')->constrained('activites')->onDelete('cascade');
            $table->enum('type_abonnement', ['mensuel', 'trimestriel', 'annuel']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('montant', 10, 2);
            $table->enum('statut', ['actif', 'expiré', 'suspendu'])->default('actif');
            $table->boolean('zk_sync')->default(false)->comment('تمت مزامنته مع ZK');
            $table->timestamps();
            
            $table->index(['date_fin', 'statut'], 'abonnement_date_statut_index');
        });

        // جدول تأمينات المشتركين
        Schema::create('abonne_assurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonne_id')->constrained('abonnes')->onDelete('cascade');
            $table->foreignId('assurance_company_id')->constrained('assurance_companies');
            $table->string('numero_contrat');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('plafond_annuel', 10, 2)->default(5000.00);
            $table->decimal('montant_utilise', 10, 2)->default(0.00);
            $table->enum('statut', ['actif', 'expiré', 'resilie'])->default('actif');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // اسم قصير للمفتاح الفريد
            $table->unique(['abonne_id', 'assurance_company_id', 'numero_contrat'], 'ab_ass_co_num_unique');
        });

        // جدول المطالبات
        Schema::create('reclamation_assurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonne_assurance_id')->constrained('abonne_assurances')->onDelete('cascade');
            $table->enum('type', ['consultation', 'examen', 'medicament', 'rehabilitation']);
            $table->decimal('montant_total', 10, 2);
            $table->decimal('montant_remboursable', 10, 2)->nullable();
            $table->date('date_reclamation');
            $table->date('date_traitement')->nullable();
            $table->enum('statut', ['en_attente', 'approuve', 'refuse', 'rembourse'])->default('en_attente');
            $table->string('justificatif_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // جدول سجلات الدخول (من ZKTeco)
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonne_id')->constrained('abonnes')->onDelete('cascade');
            $table->string('uid')->nullable()->comment('UID من ZKTeco');
            $table->dateTime('date_pointage');
            $table->enum('type', ['entree', 'sortie'])->default('entree');
            $table->boolean('synced')->default(false)->comment('تم استيراده من ZK');
            $table->timestamps();
            
            $table->index('date_pointage', 'pointage_date_index');
            $table->index(['abonne_id', 'date_pointage'], 'abonne_pointage_index');
        });

        // جدول المدفوعات
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonnement_id')->constrained('abonnements')->onDelete('cascade');
            $table->decimal('montant', 10, 2);
            $table->enum('mode_paiement', ['especes', 'carte', 'cheque', 'virement'])->default('especes');
            $table->dateTime('date_paiement')->useCurrent();
            $table->string('reference')->nullable()->comment('رقم المرجع أو الشيك');
            $table->string('imprimante_id')->nullable()->comment('رقم الطابعة');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // جدول الإعدادات
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // جدول سجل الأنشطة
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['model_type', 'model_id'], 'model_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('pointages');
        Schema::dropIfExists('reclamation_assurances');
        Schema::dropIfExists('abonne_assurances');
        Schema::dropIfExists('abonnements');
        Schema::dropIfExists('assurance_companies');
        Schema::dropIfExists('abonnes');
        Schema::dropIfExists('activites');
        Schema::dropIfExists('coaches');
    }
};