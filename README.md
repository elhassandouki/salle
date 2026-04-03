# Gestion Salle de Sport

Application web Laravel pour gerer une salle de sport: abonnés, abonnements, activites, paiements, pointages, assurances et reclamations.

## Vue d'ensemble

Le projet centralise les operations principales d'une salle de sport dans une seule interface d'administration:

- gestion des abonnes
- gestion des abonnements et subscriptions
- gestion des activites et des coaches
- suivi des paiements
- suivi des pointages et integration ZKTeco
- gestion des assurances
- gestion des reclamations d'assurance
- tableaux de bord et rapports

L'interface utilise `AdminLTE` pour le back-office et `DataTables` pour les listes dynamiques.

## Modules Fonctionnels

### 1. Abonnes

Permet de:

- ajouter, modifier et supprimer un abonne
- stocker les informations personnelles: nom, prenom, CIN, telephone, email, sexe, date de naissance, adresse, photo
- suivre le statut actif/inactif d'un abonne
- exporter la liste en CSV
- importer des abonnes depuis un fichier CSV
- synchroniser un ou plusieurs abonnes vers un appareil ZKTeco

### 2. Abonnements

Permet de:

- creer un abonnement pour un abonne
- lier un abonnement a une activite
- gerer les types d'abonnement: `mensuel`, `trimestriel`, `annuel`
- calculer les dates de debut et de fin
- suivre les statuts: `actif`, `expire`, `suspendu`
- renouveler un abonnement
- exporter la liste des abonnements

Le projet utilise le modele principal `Subscription`, avec un alias de compatibilite `Abonnement`.

### 3. Activites

Permet de:

- creer et modifier les activites de la salle
- definir les prix mensuels, trimestriels et annuels
- definir la capacite maximale
- associer un coach a une activite
- recuperer les prix selon le type d'abonnement

### 4. Coaches

Permet de:

- gerer les coaches
- enregistrer la specialite, le salaire, la date d'embauche et le statut

### 5. Paiements

Permet de:

- enregistrer les paiements lies a un abonnement
- suivre le montant paye et le reste
- definir le mode de paiement: `especes`, `carte`, `cheque`, `virement`
- consulter les statistiques financieres
- exporter les paiements

### 6. Pointages

Permet de:

- enregistrer les entrees/sorties des abonnes
- importer les pointages depuis ZKTeco
- consulter les statistiques de frequentation
- exporter les pointages

### 7. Assurances

Permet de:

- gerer les compagnies d'assurance
- associer une assurance a un abonne
- suivre le solde et le montant utilise

Les assurances sont stockees dans la table `subscriptions` avec un filtre de type `assurance`.

### 8. Reclamations Assurance

Permet de:

- creer une reclamation liee a une assurance active
- joindre un justificatif (`pdf`, `jpg`, `jpeg`, `png`)
- calculer le montant remboursable
- traiter la reclamation avec un statut: `en_attente`, `approuve`, `refuse`, `rembourse`
- exporter les reclamations

### 9. Dashboard et Rapports

Le dashboard fournit:

- nombre total de membres
- nombre de subscriptions actives
- entrees du jour
- revenu du mois
- membres ajoutes ce mois
- taux de renouvellement

Les rapports couvrent:

- financier
- frequentation
- assurances
- subscriptions

## Stack Technique

- PHP `^8.2`
- Laravel `^12.0`
- Laravel UI
- Laravel AdminLTE
- Yajra DataTables
- Rats ZKTeco
- Vite pour les assets frontend

## Structure Principale

### Controllers

- `AbonneController`
- `AbonnementController`
- `ActiviteController`
- `CoachController`
- `PaiementController`
- `PointageController`
- `AssuranceCompanyController`
- `AbonneAssuranceController`
- `ReclamationAssuranceController`
- `HomeController`
- `SettingController`

### Models

- `Abonne`
- `Subscription`
- `Abonnement` (compatibilite)
- `Service`
- `Coach`
- `Paiement`
- `Pointage`
- `AssuranceCompany`
- `AbonneAssurance`
- `ReclamationAssurance`
- `Setting`
- `ActivityLog`

## Base de Donnees

Les tables principales creees par les migrations sont:

- `users`
- `abonnes`
- `services`
- `subscriptions`
- `paiements`
- `pointages`
- `reclamations`
- `settings`
- `activity_logs`
- `coaches`

## Installation

### 1. Cloner le projet

```bash
git clone <repository-url>
cd gestion-salle-sport
```

### 2. Installer les dependances

```bash
composer install
npm install
```

### 3. Configurer l'environnement

```bash
copy .env.example .env
php artisan key:generate
```

Configurer ensuite la base de donnees dans `.env`.

### 4. Lancer les migrations

```bash
php artisan migrate
```

### 5. Lancer l'application

```bash
php artisan serve
npm run dev
```

Ou utiliser le script:

```bash
composer run dev
```

## Comptes et Securite

- l'authentification Laravel est active
- les routes de gestion sont protegees par le middleware `auth`
- les actions sensibles passent par le back-office authentifie

## Import CSV des Abonnes

Le module d'import accepte un fichier CSV ou TXT.

Colonnes supportees:

- `nom`
- `prenom`
- `nom_complet`
- `cin`
- `card_id`
- `telephone`
- `email`
- `sexe`
- `date_naissance`
- `lieu_naissance`
- `nationalite`
- `situation_familiale`
- `profession`
- `adresse`
- `notes`

Le systeme:

- cree un nouvel abonne si aucun identifiant connu n'est trouve
- met a jour un abonne existant si `cin`, `email`, `card_id` ou `telephone` existe deja
- ignore les lignes vides ou incompletes

## Integration ZKTeco

Le projet expose des fonctionnalites pour:

- verifier le statut de configuration ZKTeco
- synchroniser les abonnes vers l'appareil
- importer des pointages

Routes principales:

- `GET /zk-status`
- `POST /abonnes/sync-zk`
- `POST /abonnes/{abonne}/sync-zk`
- `POST /pointages/import-zk`
- `POST /api/zkteco/sync`
- `GET /api/zkteco/abonnes`

## Tests

Lancer les tests:

```bash
php artisan test
```

Les tests couvrent actuellement:

- acces protege aux routes de gestion
- import CSV des abonnes
- validation des reclamations assurance

## Notes

- le projet utilise a la fois les chemins `subscriptions` et `abonnements` pour les memes ecrans metier
- les assurances sont gerees comme un cas particulier de `subscriptions`
- les exports principaux sont disponibles en CSV
