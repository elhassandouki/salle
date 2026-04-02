<?php

namespace Database\Seeders;

use App\Models\Abonne;
use App\Models\ActivityLog;
use App\Models\Coach;
use App\Models\Paiement;
use App\Models\Pointage;
use App\Models\ReclamationAssurance;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@gym.ma'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        $coach1 = Coach::updateOrCreate(
            ['email' => 'youssef@gym.ma'],
            [
                'nom' => 'Benali',
                'prenom' => 'Youssef',
                'telephone' => '0661234567',
                'specialite' => 'Musculation',
                'salaire' => 5000.00,
                'date_embauche' => '2024-01-15',
                'statut' => 'actif',
            ]
        );

        $coach2 = Coach::updateOrCreate(
            ['email' => 'amina@gym.ma'],
            [
                'nom' => 'Fadili',
                'prenom' => 'Amina',
                'telephone' => '0662345678',
                'specialite' => 'Yoga',
                'salaire' => 4500.00,
                'date_embauche' => '2024-03-01',
                'statut' => 'actif',
            ]
        );

        $coach3 = Coach::updateOrCreate(
            ['email' => 'karim@gym.ma'],
            [
                'nom' => 'Elmrani',
                'prenom' => 'Karim',
                'telephone' => '0663456789',
                'specialite' => 'CrossFit',
                'salaire' => 5500.00,
                'date_embauche' => '2023-06-10',
                'statut' => 'actif',
            ]
        );

        $service1 = Service::updateOrCreate(
            ['nom' => 'Musculation'],
            [
                'type' => 'activite',
                'description' => 'Entrainement en salle de musculation',
                'prix_mensuel' => 300.00,
                'prix_trimestriel' => 800.00,
                'prix_annuel' => 2800.00,
                'capacite_max' => 50,
                'coach_id' => $coach1->id,
                'couleur' => '#dc3545',
                'statut' => 'actif',
            ]
        );

        $service2 = Service::updateOrCreate(
            ['nom' => 'Yoga'],
            [
                'type' => 'activite',
                'description' => 'Cours de yoga et meditation',
                'prix_mensuel' => 250.00,
                'prix_trimestriel' => 650.00,
                'prix_annuel' => 2200.00,
                'capacite_max' => 30,
                'coach_id' => $coach2->id,
                'couleur' => '#28a745',
                'statut' => 'actif',
            ]
        );

        $service3 = Service::updateOrCreate(
            ['nom' => 'CrossFit'],
            [
                'type' => 'activite',
                'description' => 'Entrainement CrossFit intensif',
                'prix_mensuel' => 350.00,
                'prix_trimestriel' => 900.00,
                'prix_annuel' => 3200.00,
                'capacite_max' => 40,
                'coach_id' => $coach3->id,
                'couleur' => '#fd7e14',
                'statut' => 'actif',
            ]
        );

        $service4 = Service::updateOrCreate(
            ['nom' => 'Zumba'],
            [
                'type' => 'activite',
                'description' => 'Cours de zumba et danse fitness',
                'prix_mensuel' => 200.00,
                'prix_trimestriel' => 550.00,
                'prix_annuel' => 1900.00,
                'capacite_max' => 60,
                'coach_id' => null,
                'couleur' => '#e83e8c',
                'statut' => 'actif',
            ]
        );

        $assurance1 = Service::updateOrCreate(
            ['nom' => 'CNSS Plus'],
            [
                'type' => 'assurance',
                'description' => 'Couverture sante standard',
                'prix_mensuel' => 180.00,
                'prix_trimestriel' => 520.00,
                'prix_annuel' => 1800.00,
                'capacite_max' => null,
                'coach_id' => null,
                'couleur' => '#17a2b8',
                'statut' => 'actif',
            ]
        );

        $assurance2 = Service::updateOrCreate(
            ['nom' => 'Saham Premium'],
            [
                'type' => 'assurance',
                'description' => 'Couverture premium pour les soins',
                'prix_mensuel' => 250.00,
                'prix_trimestriel' => 720.00,
                'prix_annuel' => 2400.00,
                'capacite_max' => null,
                'coach_id' => null,
                'couleur' => '#6f42c1',
                'statut' => 'actif',
            ]
        );

        $abonne1 = Abonne::updateOrCreate(
            ['cin' => 'AB123456'],
            [
                'uid' => '12345678',
                'card_id' => 'AB001',
                'nom' => 'Alami',
                'prenom' => 'Sara',
                'telephone' => '0701010101',
                'email' => 'sara.alami@email.com',
                'sexe' => 'femme',
                'date_naissance' => '1995-05-15',
                'lieu_naissance' => 'Casablanca',
                'nationalite' => 'Marocaine',
                'situation_familiale' => 'celibataire',
                'profession' => 'Ingenieur',
                'adresse' => '123 Bd Mohammed V, Casablanca',
                'notes' => 'Cliente fidele depuis 2024',
            ]
        );

        $abonne2 = Abonne::updateOrCreate(
            ['cin' => 'CD234567'],
            [
                'uid' => '23456789',
                'card_id' => 'AB002',
                'nom' => 'Berrada',
                'prenom' => 'Mohammed',
                'telephone' => '0711223344',
                'email' => 'mohammed.berrada@email.com',
                'sexe' => 'homme',
                'date_naissance' => '1990-08-22',
                'lieu_naissance' => 'Rabat',
                'nationalite' => 'Marocain',
                'situation_familiale' => 'marie',
                'profession' => 'Medecin',
                'adresse' => '45 Rue Fes, Rabat',
            ]
        );

        $abonne3 = Abonne::updateOrCreate(
            ['cin' => 'EF345678'],
            [
                'uid' => '34567890',
                'card_id' => 'AB003',
                'nom' => 'Chakir',
                'prenom' => 'Fatima',
                'telephone' => '0722334455',
                'email' => 'fatima.chakir@email.com',
                'sexe' => 'femme',
                'date_naissance' => '1988-03-10',
                'lieu_naissance' => 'Marrakech',
                'nationalite' => 'Marocaine',
                'situation_familiale' => 'mariee',
                'profession' => 'Avocat',
                'adresse' => '78 Ave Houphouet Boigny, Marrakech',
                'notes' => 'Abonnement premium',
            ]
        );

        $abonne4 = Abonne::updateOrCreate(
            ['cin' => 'GH456789'],
            [
                'uid' => '45678901',
                'card_id' => 'AB004',
                'nom' => 'Idrissi',
                'prenom' => 'Omar',
                'telephone' => '0733445566',
                'email' => 'omar.idrissi@email.com',
                'sexe' => 'homme',
                'date_naissance' => '1992-11-30',
                'lieu_naissance' => 'Fes',
                'nationalite' => 'Marocain',
                'situation_familiale' => 'celibataire',
                'profession' => 'Architecte',
                'adresse' => '15 Rue Tanger, Fes',
            ]
        );

        $abonne5 = Abonne::updateOrCreate(
            ['cin' => 'IJ567890'],
            [
                'uid' => '56789012',
                'card_id' => 'AB005',
                'nom' => 'Tazi',
                'prenom' => 'Nadia',
                'telephone' => '0744556677',
                'email' => 'nadia.tazi@email.com',
                'sexe' => 'femme',
                'date_naissance' => '1997-07-08',
                'lieu_naissance' => 'Agadir',
                'nationalite' => 'Marocaine',
                'situation_familiale' => 'celibataire',
                'profession' => 'Etudiante',
                'adresse' => '90 Blvd Hassan II, Agadir',
                'notes' => 'Etudiante en medecine',
            ]
        );

        $sub1 = Subscription::updateOrCreate(
            ['abonne_id' => $abonne1->id, 'service_id' => $service1->id, 'date_debut' => '2026-01-01'],
            [
                'type_abonnement' => 'annuel',
                'date_fin' => '2027-01-01',
                'montant' => 2800.00,
                'remise' => 0.00,
                'montant_total' => 2800.00,
                'montant_paye' => 2800.00,
                'reste' => 0.00,
                'statut' => 'actif',
                'auto_renew' => true,
            ]
        );

        $sub2 = Subscription::updateOrCreate(
            ['abonne_id' => $abonne2->id, 'service_id' => $service2->id, 'date_debut' => '2026-03-01'],
            [
                'type_abonnement' => 'mensuel',
                'date_fin' => '2026-04-01',
                'montant' => 250.00,
                'remise' => 0.00,
                'montant_total' => 250.00,
                'montant_paye' => 250.00,
                'reste' => 0.00,
                'statut' => 'actif',
                'auto_renew' => false,
            ]
        );

        $sub3 = Subscription::updateOrCreate(
            ['abonne_id' => $abonne3->id, 'service_id' => $service3->id, 'date_debut' => '2026-02-15'],
            [
                'type_abonnement' => 'trimestriel',
                'date_fin' => '2026-05-15',
                'montant' => 950.00,
                'remise' => 50.00,
                'montant_total' => 900.00,
                'montant_paye' => 900.00,
                'reste' => 0.00,
                'statut' => 'actif',
                'auto_renew' => false,
                'notes' => 'Paiement en deux fois',
            ]
        );

        $sub4 = Subscription::updateOrCreate(
            ['abonne_id' => $abonne4->id, 'service_id' => $service4->id, 'date_debut' => '2026-02-01'],
            [
                'type_abonnement' => 'mensuel',
                'date_fin' => '2026-03-01',
                'montant' => 200.00,
                'remise' => 0.00,
                'montant_total' => 200.00,
                'montant_paye' => 200.00,
                'reste' => 0.00,
                'statut' => 'expire',
                'auto_renew' => false,
            ]
        );

        $sub5 = Subscription::updateOrCreate(
            ['abonne_id' => $abonne5->id, 'service_id' => $service1->id, 'date_debut' => '2026-03-15'],
            [
                'type_abonnement' => 'annuel',
                'date_fin' => '2027-03-15',
                'montant' => 3100.00,
                'remise' => 300.00,
                'montant_total' => 2800.00,
                'montant_paye' => 1000.00,
                'reste' => 1800.00,
                'statut' => 'actif',
                'auto_renew' => true,
                'notes' => 'Etudiante - remise speciale',
            ]
        );

        $insuranceSub1 = Subscription::updateOrCreate(
            ['abonne_id' => $abonne1->id, 'service_id' => $assurance1->id, 'date_debut' => '2026-01-10'],
            [
                'type_abonnement' => 'annuel',
                'date_fin' => '2027-01-10',
                'montant' => 1800.00,
                'remise' => 0.00,
                'montant_total' => 1800.00,
                'montant_paye' => 1800.00,
                'reste' => 0.00,
                'statut' => 'actif',
                'auto_renew' => false,
                'notes' => 'Assurance familiale',
            ]
        );

        $insuranceSub2 = Subscription::updateOrCreate(
            ['abonne_id' => $abonne3->id, 'service_id' => $assurance2->id, 'date_debut' => '2026-02-01'],
            [
                'type_abonnement' => 'annuel',
                'date_fin' => '2027-02-01',
                'montant' => 2400.00,
                'remise' => 100.00,
                'montant_total' => 2300.00,
                'montant_paye' => 1500.00,
                'reste' => 800.00,
                'statut' => 'actif',
                'auto_renew' => false,
                'notes' => 'Couverture premium',
            ]
        );

        $payments = [
            ['subscription_id' => $sub1->id, 'montant' => 2800.00, 'mode_paiement' => 'especes', 'reference' => 'PAY-001', 'date_paiement' => '2026-01-01 10:00:00', 'user_id' => $admin->id, 'notes' => 'Paiement complet annuel'],
            ['subscription_id' => $sub2->id, 'montant' => 250.00, 'mode_paiement' => 'carte', 'reference' => 'PAY-002', 'date_paiement' => '2026-03-01 14:30:00', 'user_id' => $admin->id, 'notes' => null],
            ['subscription_id' => $sub3->id, 'montant' => 500.00, 'mode_paiement' => 'virement', 'reference' => 'PAY-003', 'date_paiement' => '2026-02-15 09:15:00', 'user_id' => $admin->id, 'notes' => 'Premiere tranche'],
            ['subscription_id' => $sub3->id, 'montant' => 400.00, 'mode_paiement' => 'virement', 'reference' => 'PAY-004', 'date_paiement' => '2026-02-20 11:00:00', 'user_id' => $admin->id, 'notes' => 'Deuxieme tranche'],
            ['subscription_id' => $sub4->id, 'montant' => 200.00, 'mode_paiement' => 'especes', 'reference' => 'PAY-005', 'date_paiement' => '2026-02-01 08:30:00', 'user_id' => $admin->id, 'notes' => null],
            ['subscription_id' => $sub5->id, 'montant' => 1000.00, 'mode_paiement' => 'cheque', 'reference' => 'PAY-006', 'date_paiement' => '2026-03-15 16:45:00', 'user_id' => $admin->id, 'notes' => 'Acompte'],
            ['subscription_id' => $insuranceSub1->id, 'montant' => 1800.00, 'mode_paiement' => 'carte', 'reference' => 'PAY-007', 'date_paiement' => '2026-01-10 10:30:00', 'user_id' => $admin->id, 'notes' => 'Assurance reglee'],
            ['subscription_id' => $insuranceSub2->id, 'montant' => 1500.00, 'mode_paiement' => 'virement', 'reference' => 'PAY-008', 'date_paiement' => '2026-02-01 15:00:00', 'user_id' => $admin->id, 'notes' => 'Premier versement assurance'],
        ];

        foreach ($payments as $payment) {
            Paiement::updateOrCreate(
                ['reference' => $payment['reference']],
                $payment
            );
        }

        $pointages = [
            ['abonne_id' => $abonne1->id, 'uid' => '12345678', 'date_pointage' => '2026-03-29 08:30:00', 'type' => 'entree', 'synced' => false],
            ['abonne_id' => $abonne1->id, 'uid' => '12345678', 'date_pointage' => '2026-03-29 10:30:00', 'type' => 'sortie', 'synced' => false],
            ['abonne_id' => $abonne2->id, 'uid' => '23456789', 'date_pointage' => '2026-03-29 09:00:00', 'type' => 'entree', 'synced' => false],
            ['abonne_id' => $abonne5->id, 'uid' => '56789012', 'date_pointage' => '2026-03-29 07:45:00', 'type' => 'entree', 'synced' => false],
            ['abonne_id' => $abonne3->id, 'uid' => '34567890', 'date_pointage' => '2026-03-30 18:00:00', 'type' => 'entree', 'synced' => true],
            ['abonne_id' => $abonne3->id, 'uid' => '34567890', 'date_pointage' => '2026-03-30 19:20:00', 'type' => 'sortie', 'synced' => true],
        ];

        foreach ($pointages as $pointage) {
            Pointage::updateOrCreate(
                [
                    'abonne_id' => $pointage['abonne_id'],
                    'date_pointage' => $pointage['date_pointage'],
                    'type' => $pointage['type'],
                ],
                $pointage
            );
        }

        $claims = [
            [
                'abonne_id' => $abonne1->id,
                'service_id' => $assurance1->id,
                'type' => 'consultation',
                'montant_total' => 450.00,
                'montant_rembourse' => 320.00,
                'statut' => 'en_attente',
                'date_reclamation' => '2026-03-10',
                'date_traitement' => null,
                'notes' => 'Consultation generaliste',
            ],
            [
                'abonne_id' => $abonne3->id,
                'service_id' => $assurance2->id,
                'type' => 'examen',
                'montant_total' => 1200.00,
                'montant_rembourse' => 900.00,
                'statut' => 'approuve',
                'date_reclamation' => '2026-03-05',
                'date_traitement' => '2026-03-12',
                'notes' => 'Examen scanner',
            ],
            [
                'abonne_id' => $abonne3->id,
                'service_id' => $assurance2->id,
                'type' => 'medicament',
                'montant_total' => 300.00,
                'montant_rembourse' => 210.00,
                'statut' => 'rembourse',
                'date_reclamation' => '2026-03-18',
                'date_traitement' => '2026-03-25',
                'notes' => 'Pharmacie',
            ],
        ];

        foreach ($claims as $index => $claim) {
            ReclamationAssurance::updateOrCreate(
                [
                    'abonne_id' => $claim['abonne_id'],
                    'service_id' => $claim['service_id'],
                    'date_reclamation' => $claim['date_reclamation'],
                    'type' => $claim['type'],
                ],
                $claim + ['justificatif_path' => null]
            );
        }

        $depenses = [
            ['nom' => 'Electricite', 'montant' => 1800.00, 'categorie' => 'Charges', 'date_depense' => '2026-03-05', 'notes' => 'Facture mensuelle'],
            ['nom' => 'Produits entretien', 'montant' => 650.00, 'categorie' => 'Maintenance', 'date_depense' => '2026-03-08', 'notes' => 'Nettoyage salle'],
            ['nom' => 'Reparation tapis', 'montant' => 1200.00, 'categorie' => 'Equipement', 'date_depense' => '2026-03-16', 'notes' => 'Maintenance machine'],
            ['nom' => 'Campagne Facebook', 'montant' => 900.00, 'categorie' => 'Marketing', 'date_depense' => '2026-03-20', 'notes' => 'Pub locale'],
        ];

        foreach ($depenses as $depense) {
            DB::table('depenses')->updateOrInsert(
                ['nom' => $depense['nom'], 'date_depense' => $depense['date_depense']],
                $depense + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        Setting::set('adminlte_menu', '');
        Setting::set('company_name', 'Gym Demo Center');
        Setting::set('currency', 'MAD');

        $logs = [
            ['action' => 'seeded_demo_data', 'model' => 'Subscription', 'model_id' => $sub1->id],
            ['action' => 'created_claim', 'model' => 'ReclamationAssurance', 'model_id' => ReclamationAssurance::first()?->id],
            ['action' => 'recorded_payment', 'model' => 'Paiement', 'model_id' => Paiement::where('reference', 'PAY-008')->value('id')],
        ];

        foreach ($logs as $log) {
            DB::table('activity_logs')->updateOrInsert(
                ['action' => $log['action'], 'model' => $log['model'], 'model_id' => $log['model_id'] ?? 0],
                [
                    'user_id' => $admin->id,
                    'old_data' => null,
                    'new_data' => json_encode(['source' => 'DatabaseSeeder']),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'artisan db:seed',
                ]
            );
        }

        $this->command?->info('Demo data seeded successfully.');
        $this->command?->line('Users: ' . User::count());
        $this->command?->line('Coaches: ' . Coach::count());
        $this->command?->line('Abonnes: ' . Abonne::count());
        $this->command?->line('Services: ' . Service::count());
        $this->command?->line('Subscriptions: ' . Subscription::count());
        $this->command?->line('Paiements: ' . Paiement::count());
        $this->command?->line('Pointages: ' . Pointage::count());
        $this->command?->line('Reclamations: ' . ReclamationAssurance::count());
        $this->command?->line('Depenses: ' . DB::table('depenses')->count());
        $this->command?->line('Settings: ' . Setting::count());
        $this->command?->line('Activity logs: ' . DB::table('activity_logs')->count());
    }
}
